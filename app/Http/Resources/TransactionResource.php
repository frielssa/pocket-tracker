<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'amount'     => $this->amount,
            'type'       => $this->type->value,
            'category'   => $this->category,
            'date'       => $this->date->format('Y-m-d'),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}