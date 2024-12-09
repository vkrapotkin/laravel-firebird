<?php

namespace Vkrapotkin\Firebird\Tests\Support\Factories;

use Vkrapotkin\Firebird\Tests\Support\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public static int $id = 1;

    public function definition(): array
    {
        return [
            'id' => self::$id++,
            'name' => $this->faker->name,
            'email' => $this->faker->email,
            'city' => $this->faker->city,
            'state' => $this->faker->state,
            'post_code' => $this->faker->postcode,
            'country' => $this->faker->country,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
