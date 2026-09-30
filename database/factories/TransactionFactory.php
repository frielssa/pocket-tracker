<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(2),
            'amount' => $this->faker->numberBetween(10000, 500000),
            'type' => $this->faker->randomElement(['income', 'expense']),
            'category' => $this->faker->word(),
            'date' => now()->format('Y-m-d'),
        ];
    }
}