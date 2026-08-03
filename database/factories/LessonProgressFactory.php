<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonProgress>
 */
class LessonProgressFactory extends Factory
{
    protected $model = LessonProgress::class;

    public function definition(): array
    {
        $percentage = fake()->randomFloat(2, 0, 100);

        return [
            'user_id' => User::factory()->student(),
            'lesson_id' => Lesson::factory(),
            'last_position_seconds' => fake()->numberBetween(0, 1800),
            'watched_percentage' => $percentage,
            'completed_at' => $percentage >= 95 ? fake()->dateTimeBetween('-30 days', 'now') : null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'watched_percentage' => 100,
            'completed_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function notStarted(): static
    {
        return $this->state(fn () => ['last_position_seconds' => 0, 'watched_percentage' => 0, 'completed_at' => null]);
    }
}
