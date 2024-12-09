<?php

namespace Vkrapotkin\Firebird\Tests\Support\Factories;

use Vkrapotkin\Firebird\Tests\Support\Models\Order;
use Vkrapotkin\Firebird\Tests\Support\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public static int $id = 1;

    public function definition(): array
    {
        return [
            'id' => self::$id++,
            'user_id' => User::factory(),
            'name' => $this->faker->word,
            'price' => $this->faker->numberBetween(1, 200),
            'quantity' => $this->faker->numberBetween(0, 8),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
