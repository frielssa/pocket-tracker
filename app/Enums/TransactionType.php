<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case INCOME = 'income';
    case EXPENSE = 'expense';

    /**
     * Helper untuk mendapatkan label yang readable jika dibutuhkan
     */
    public function label(): string
    {
        return match ($this) {
            self::INCOME  => 'Pemasukan',
            self::EXPENSE => 'Pengeluaran',
        };
    }
}