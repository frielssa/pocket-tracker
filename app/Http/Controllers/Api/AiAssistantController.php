<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class AiAssistantController extends Controller
{
    public function ask(Request $request): JsonResponse
    {
        $request->validate([
            'prompt' => 'required|string|max:500',
        ]);

        $user = $request->user();

        // 1. Ambil konfigurasi dari config/services.php (bukan env() langsung)
        $baseUrl = rtrim((string) config('services.atmorouter.base_url'), '/');
        $apiKey  = (string) config('services.atmorouter.key');
        $model   = (string) config('services.atmorouter.model');

        if ($baseUrl === '' || $apiKey === '' || $model === '') {
            Log::error('AtmoRouter belum dikonfigurasi (ATMO_BASE_URL / ATMO_API_KEY / ATMO_MODEL).');

            return response()->json([
                'status'  => 'error',
                'message' => 'Layanan AI belum dikonfigurasi.',
            ], 503);
        }

        // Hindari ganda /v1
        $endpoint = str_contains($baseUrl, '/v1')
            ? "{$baseUrl}/chat/completions"
            : "{$baseUrl}/v1/chat/completions";

        try {
            // 2. Ambil 50 transaksi terbaru milik user yang login
            $transactions = $user->transactions()
                ->latest('date')
                ->take(50)
                ->get();

            // 3. Format riwayat transaksi
            $context = $transactions->map(function ($t) {
                $typeStr = is_object($t->type) && isset($t->type->value) ? $t->type->value : (string) $t->type;
                $formattedAmount = number_format((float) $t->amount, 0, ',', '.');

                return "- [{$t->date}] ({$typeStr}) Rp{$formattedAmount} | Kategori: {$t->category} | Ket: {$t->title}";
            })->implode("\n");

            $systemPrompt = <<<PROMPT
Anda adalah "PocketTracker AI", seorang Asisten & Analis Keuangan Pribadi yang ramah, objektif, dan profesional.

TUGAS DAN BATASAN SCOPE ANDA:
1. Ruang lingkup Anda HANYA seputar pencatatan kas, analisis arus kas (cash flow), saran penghematan, perencanaan anggaran, dan tren transaksi keuangan.
2. JIKA pengguna mengajukan pertanyaan DI LUAR TOPIK KEUANGAN, TOLAK DENGAN SOPAN. Katakan bahwa Anda adalah khusus asisten analisis keuangan PocketTracker.
3. Gunakan data transaksi pengguna di bawah ini untuk memberikan jawaban yang berbasis data aktual.
4. Berikan jawaban yang ringkas, praktis, dan mudah dipahami dalam bahasa Indonesia.

DATA TRANSAKSI TERAKHIR PENGGUNA:
{$context}
PROMPT;
            $response = Http::acceptJson()
                ->connectTimeout(15)
                ->timeout(45)
                ->retry(
                    2,
                    1000,
                    fn ($exception) => $exception instanceof ConnectionException
                        || ($exception instanceof RequestException && $exception->response->serverError()),
                    throw: false
                )
                ->withToken($apiKey)
                ->post($endpoint, [
                    'model'       => $model,
                    'messages'    => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $request->input('prompt')],
                    ],
                    'temperature' => 0.5,
                ]);

            if ($response->failed()) {
                // Detail lengkap hanya dicatat di log server, tidak dikirim ke browser
                Log::error('AtmoRouter error', [
                    'status' => $response->status(),
                    'body'   => mb_substr($response->body(), 0, 1000),
                ]);

                $payload = [
                    'status'  => 'error',
                    'message' => 'Layanan AI sedang tidak dapat dijangkau. Silakan coba lagi nanti.',
                ];

                // Detail teknis hanya muncul saat APP_DEBUG=true (development)
                if (config('app.debug')) {
                    $payload['debug'] = [
                        'upstream_status' => $response->status(),
                        'upstream_body'   => $response->json() ?? $response->body(),
                    ];
                }

                // 502 = gagal di layanan upstream (bukan 401 agar frontend tidak mengira sesi login habis)
                return response()->json($payload, 502);
            }

            $reply = $response->json('choices.0.message.content')
                ?? 'Maaf, AI tidak memberikan respons.';

            return response()->json([
                'status' => 'success',
                'reply'  => $reply,
            ]);
        } catch (\Throwable $e) {
            Log::error('AI Assistant Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            $payload = [
                'status'  => 'error',
                'message' => 'Terjadi kesalahan sistem saat menghubungi layanan AI.',
            ];

            if (config('app.debug')) {
                $payload['debug'] = $e->getMessage();
            }

            return response()->json($payload, 500);
        }
    }
}