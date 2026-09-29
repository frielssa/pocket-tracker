<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

final class TransactionController extends Controller
{
    // GET /api/transactions
    public function index(): JsonResponse
    {
        $transactions = Transaction::query()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $income = (float) Transaction::query()->where('type', 'income')->sum('amount');
        $expense = (float) Transaction::query()->where('type', 'expense')->sum('amount');

        return response()->json([
            'data' => $transactions,
            'summary' => [
                'balance'       => $income - $expense,
                'total_income'  => $income,
                'total_expense' => $expense,
            ],
        ]);
    }

    public function update(StoreTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $transaction->update($request->validated());

        return response()->json([
        'message' => 'Transaksi berhasil diperbarui',
        'data'    => $transaction,
        ]   , 200);
    }

    // POST /api/transactions
    public function store(StoreTransactionRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        // Pasang default user_id jika tabel transactions membutuhkan user_id
        if (!isset($data['user_id'])) {
            $data['user_id'] = 1;
        }

        $transaction = Transaction::create($data);

        return response()->json([
            'message' => 'Transaksi berhasil ditambahkan',
            'data'    => $transaction,
        ], 201);
    }

    // DELETE /api/transactions/{transaction}
    public function destroy(Transaction $transaction): JsonResponse
    {
        $transaction->delete();

        return response()->json([
            'message' => 'Transaksi berhasil dihapus'
        ], 200);
    }
}