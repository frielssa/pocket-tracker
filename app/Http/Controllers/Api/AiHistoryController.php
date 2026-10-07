<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Riwayat chat AI per pengguna, dengan pagination berbasis kursor (before_id).
 * Kursor dipakai (bukan nomor halaman) supaya pesan baru yang masuk tidak menggeser/menduplikasi halaman.
 */
final class AiHistoryController extends Controller
{
    // GET /api/ai/history?limit=20&before_id=123
    public function index(Request $request): JsonResponse
    {
        $limit = max(1, min(50, (int) $request->input('limit', 20)));

        $query = AiMessage::query()->where('user_id', (int) $request->user()->id);

        if ($request->filled('before_id')) {
            $query->where('id', '<', (int) $request->input('before_id'));
        }

        // Ambil satu lebih banyak untuk mengetahui apakah masih ada pesan yang lebih lama
        $rows = $query->orderByDesc('id')->limit($limit + 1)->get();
        $hasMore = $rows->count() > $limit;

        $data = $rows->take($limit)
            ->reverse()
            ->values()
            ->map(fn (AiMessage $m) => [
                'id'         => $m->id,
                'role'       => $m->role,
                'content'    => $m->content,
                'created_at' => $m->created_at?->toIso8601String(),
            ])
            ->all();

        return response()->json([
            'data'     => $data,
            'has_more' => $hasMore,
        ]);
    }

    // POST /api/ai/history  (menyimpan catatan hasil aksi: "Aksi dijalankan / dibatalkan")
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
        ]);

        $message = AiMessage::create([
            'user_id' => (int) $request->user()->id,
            'role'    => 'assistant',
            'content' => $data['content'],
        ]);

        return response()->json(['data' => ['id' => $message->id]], 201);
    }

    // DELETE /api/ai/history
    public function destroy(Request $request): JsonResponse
    {
        $deleted = AiMessage::query()
            ->where('user_id', (int) $request->user()->id)
            ->delete();

        return response()->json([
            'message' => 'Riwayat chat AI dihapus',
            'deleted' => $deleted,
        ]);
    }
}