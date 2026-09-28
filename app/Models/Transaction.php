<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'amount',
        'type',
        'category',
        'date',
    ];

    protected $casts = [
        'amount' => 'float',
        'type'   => TransactionType::class,
        'date'   => 'immutable_date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}