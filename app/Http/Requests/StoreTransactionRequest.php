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
            'user_id'  => ['nullable', 'exists:users,id'],
            'title'    => ['required', 'string', 'max:255'],
            'amount'   => ['required', 'numeric', 'min:0'],
            'type'     => ['required', new Enum(TransactionType::class)],
            'category' => ['required', 'string', 'max:255'],
            'date'     => ['required', 'date'],
        ];
    }
}