<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Reuse one hashed password for all factory users.
     */
    protected static ?string $hashedPassword = null;

    public function definition(): array
    {
        return [
            'fname' => fake()->firstName(),
            'lname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional(0.8)->numerify('010########'),
            'password' => static::$hashedPassword ??= Hash::make('password'),
            'role' => 'student',
            'status' => 'active',
            'profile_picture' => null,
            'last_login_at' => fake()->optional(0.6)->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function student(): static
    {
        return $this->state(fn () => [
            'role' => 'student',
        ]);
    }

    public function assistant(): static
    {
        return $this->state(fn () => [
            'role' => 'assistant',
            'status' => 'active',
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => 'inactive',
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn () => [
            'status' => 'disabled',
        ]);
    }

    public function withPassword(string $plain): static
    {
        return $this->state(fn () => [
            'password' => Hash::make($plain),
        ]);
    }
}