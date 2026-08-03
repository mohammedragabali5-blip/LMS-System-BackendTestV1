<?php

namespace Database\Factories;

use App\Models\DeviceSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeviceSession>
 */
class DeviceSessionFactory extends Factory
{
    protected $model = DeviceSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'personal_access_token_id' => null,
            'device_label' => fake()->randomElement(['Chrome on Windows', 'Safari on iPhone', 'Firefox on macOS', 'Edge on Windows']),
            'ip_address' => fake()->ipv4(),
            'last_used_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
