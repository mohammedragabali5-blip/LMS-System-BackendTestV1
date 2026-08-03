<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->student(),
            'type' => 'course.assigned',
            'title' => 'New course assigned',
            'body' => 'You have been assigned to a new course.',
            'data' => [],
            'read_at' => null,
            'created_at' => fake()->dateTimeBetween('-14 days', 'now'),
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => fake()->dateTimeBetween('-13 days', 'now')]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn () => [
            'type' => 'course.expiring',
            'title' => 'Course expires soon',
            'body' => 'One of your courses expires in 7 days.',
        ]);
    }

    public function passwordChanged(): static
    {
        return $this->state(fn () => [
            'type' => 'account.password_changed',
            'title' => 'Password changed',
            'body' => 'Your password was recently changed.',
        ]);
    }
}
