<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $sam = User::where('email', 'student@example.com')->first();

        Notification::updateOrCreate(
            ['user_id' => $sam->id, 'type' => 'course.assigned', 'title' => 'New course assigned'],
            ['body' => 'You have been assigned to "Introduction to Algorithms".', 'data' => [], 'read_at' => null, 'created_at' => now()->subDays(9)]
        );

        Notification::updateOrCreate(
            ['user_id' => $sam->id, 'type' => 'course.expiring', 'title' => 'Course expires soon'],
            ['body' => '"Calculus I" expires in 5 day(s).', 'data' => [], 'read_at' => null, 'created_at' => now()->subHours(3)]
        );

        // A handful of read/unread notifications for other students.
        Notification::factory()->count(15)->create();
        Notification::factory()->read()->count(10)->create();
    }
}
