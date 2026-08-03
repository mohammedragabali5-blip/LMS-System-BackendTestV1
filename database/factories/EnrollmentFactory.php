<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Enrollment>
 */
class EnrollmentFactory extends Factory
{
    protected $model = Enrollment::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'user_id' => User::factory()->student(),
            'course_id' => Course::factory(),
            'assigned_by' => User::factory()->admin(),
            'start_date' => $start->format('Y-m-d'),
            'end_date' => (clone $start)->modify('+365 days')->format('Y-m-d'),
            'status' => 'active',
            'revoked_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(function () {
            $start = fake()->dateTimeBetween('-2 years', '-1 year');

            return [
                'start_date' => $start->format('Y-m-d'),
                'end_date' => (clone $start)->modify('+180 days')->format('Y-m-d'),
                'status' => 'expired',
            ];
        });
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['status' => 'revoked', 'revoked_at' => now()]);
    }
}
