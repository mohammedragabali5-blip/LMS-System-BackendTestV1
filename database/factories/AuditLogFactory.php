<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $action = fake()->randomElement([
            'student.created', 'student.updated', 'student.disabled',
            'course.created', 'course.updated', 'course.archived',
            'lesson.uploaded', 'lesson.deleted',
            'course.assigned', 'enrollment.extended', 'enrollment.revoked',
            'assistant.created', 'assistant.permissions_updated',
        ]);

        return [
            'user_id' => User::factory()->admin(),
            'action' => $action,
            'auditable_type' => null,
            'auditable_id' => null,
            'description' => fake()->sentence(8),
            'meta' => [],
            'ip_address' => fake()->ipv4(),
            'created_at' => fake()->dateTimeBetween('-60 days', 'now'),
        ];
    }
}
