<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'    => ['required', 'string', 'max:255'],
            'amount'   => ['required', 'numeric', 'gte:1000'], // Minimal Rp 1.000
            'type'     => ['required', new Enum(TransactionType::class)],
            'category' => ['required', 'string', 'max:100'],
            'date'     => ['required', 'date'],
        ];
    }
}