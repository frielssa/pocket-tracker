<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use App\Enums\TransactionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TransactionController extends Controller
{
    // GET /api/transactions
    public function index(Request $request): JsonResponse
    {
        // Utamakan user_id dari Sanctum/Auth
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $query = Transaction::query()->where('user_id', $user->id);

        // 1. Filter Kata Kunci (Pencarian Berdasarkan Judul atau Kategori)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // 2. Filter Periode Waktu
        if ($request->filled('period')) {
            $period = $request->input('period');
            $today = Carbon::today();

            match ($period) {
                'daily'   => $query->whereDate('date', $today),
                'weekly'  => $query->whereBetween('date', [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()]),
                'monthly' => $query->whereMonth('date', $today->month)->whereYear('date', $today->year),
                'yearly'  => $query->whereYear('date', $today->year),
                default   => null,
            };
        }

        // 3. Filter Bulan & Tahun Spesifik
        if ($request->filled('month')) {
            $query->whereMonth('date', $request->input('month'));
        }
        if ($request->filled('year')) {
            $query->whereYear('date', $request->input('year'));
        }

        // 4. Filter Rentang Tanggal Custom
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [
                $request->input('start_date'),
                $request->input('end_date')
            ]);
        }

        $transactions = (clone $query)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $income = (float) (clone $query)->where('type', TransactionType::INCOME->value)->sum('amount');
        $expense = (float) (clone $query)->where('type', TransactionType::EXPENSE->value)->sum('amount');

        return response()->json([
            'data' => $transactions,
            'summary' => [
                'balance'       => $income - $expense,
                'total_income'  => $income,
                'total_expense' => $expense,
            ],
        ]);
    }

    // POST /api/transactions
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validated();
        $data['user_id'] = $user->id;

        if (!empty($data['date'])) {
            $data['date'] = Carbon::parse($data['date'])->format('Y-m-d');
        }

        $transaction = Transaction::create($data);

        return response()->json([
            'message' => 'Transaksi berhasil ditambahkan',
            'data'    => $transaction,
        ], 201);
    }

    // PUT/PATCH /api/transactions/{id}
    public function update(StoreTransactionRequest $request, int|string $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Cari data transaksi. Jika ID tidak ada, kembalikan HTTP 404 Not Found (TC-10)
        $transaction = Transaction::find($id);
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found.'], 404);
        }

        // Cek Hak Akses / Kepemilikan Data (TC-09)
        if ($transaction->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();
        if (!empty($data['date'])) {
            $data['date'] = Carbon::parse($data['date'])->format('Y-m-d');
        }

        $transaction->update($data);

        return response()->json([
            'message' => 'Transaksi berhasil diperbarui',
            'data'    => $transaction,
        ], 200);
    }

    // DELETE /api/transactions/{id}
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Cari data transaksi. Jika ID tidak ada, kembalikan HTTP 404 Not Found (TC-10)
        $transaction = Transaction::find($id);
        if (!$transaction) {
            return response()->json(['message' => 'Transaction not found.'], 404);
        }

        // Cek Hak Akses / Kepemilikan Data (TC-09)
        if ($transaction->user_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $transaction->delete();

        return response()->json([
            'message' => 'Transaksi berhasil dihapus',
        ], 200);
    }

    // GET /api/transactions/export
    public function export(Request $request): StreamedResponse|JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $fileName = 'transactions_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($user) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Judul', 'Jumlah (Rp)', 'Tipe', 'Kategori', 'Tanggal']);

            Transaction::query()
                ->where('user_id', $user->id)
                ->orderByDesc('date')
                ->chunk(100, function ($transactions) use ($file) {
                    foreach ($transactions as $t) {
                        fputcsv($file, [
                            $t->id,
                            $t->title,
                            $t->amount,
                            $t->type instanceof TransactionType ? $t->type->value : $t->type,
                            $t->category,
                            $t->date,
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // GET /api/transactions/chart
    public function chart(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $monthlyData = Transaction::query()
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month, type, SUM(amount) as total")
            ->where('user_id', $user->id)
            ->groupBy('month', 'type')
            ->orderBy('month', 'asc')
            ->get();

        $chart = [];
        foreach ($monthlyData as $row) {
            $month = $row->month;
            $typeKey = $row->type instanceof TransactionType ? $row->type->value : (string) $row->type;

            if (!isset($chart[$month])) {
                $chart[$month] = [
                    'month'   => $month,
                    'income'  => 0,
                    'expense' => 0,
                ];
            }
            $chart[$month][$typeKey] = (float) $row->total;
        }

        return response()->json([
            'data' => array_values($chart)
        ]);
    }
}