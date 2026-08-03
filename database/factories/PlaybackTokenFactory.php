<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\PlaybackToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PlaybackToken>
 */
class PlaybackTokenFactory extends Factory
{
    protected $model = PlaybackToken::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'lesson_id' => Lesson::factory(),
            'token' => Str::random(64),
            'expires_at' => now()->addSeconds(60),
            'used_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subMinute()]);
    }

    public function used(): static
    {
        return $this->state(fn () => ['used_at' => now()]);
    }
}
