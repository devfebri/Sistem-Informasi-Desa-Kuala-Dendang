<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->regexify('[a-z][a-z0-9_]{7}'),
            'email' => null,
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'warga',
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => 'admin']);
    }

    public function kades(): static
    {
        return $this->state(['role' => 'kades']);
    }

    public function warga(): static
    {
        return $this->state(['role' => 'warga']);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
