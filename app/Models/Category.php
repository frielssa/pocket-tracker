<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    public const DEFAULTS = [
        'expense' => [
            ['Makanan', '#f97316'], ['Transport', '#3b82f6'], ['Belanja', '#ec4899'], ['Tagihan', '#eab308'],
            ['Hiburan', '#a855f7'], ['Kesehatan', '#ef4444'], ['Pendidikan', '#06b6d4'], ['Lainnya', '#6b7280'],
        ],
        'income' => [
            ['Gaji', '#10b981'], ['Bonus', '#22c55e'], ['Hadiah', '#f59e0b'], ['Lainnya', '#6b7280'],
        ],
    ];

    protected $fillable = ['user_id', 'name', 'type', 'color'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Isi kategori bawaan bila pengguna belum punya kategori sama sekali. */
    public static function ensureDefaultsFor(int $userId): void
    {
        if (static::query()->where('user_id', $userId)->exists()) {
            return;
        }

        $now = now();
        $rows = [];

        foreach (self::DEFAULTS as $type => $items) {
            foreach ($items as [$name, $color]) {
                $rows[] = [
                    'user_id'    => $userId,
                    'name'       => $name,
                    'type'       => $type,
                    'color'      => $color,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        static::query()->insertOrIgnore($rows);
    }
}