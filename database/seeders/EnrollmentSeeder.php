<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{

    public function run(): void
    {
        // Get the only admin in the system
        $admin = User::where('role', 'admin')->firstOrFail();

        // Demo student
        $sam = User::where('email', 'student@example.com')->firstOrFail();

        // Demo courses
        $algorithms = Course::where('slug', 'intro-algorithms')->firstOrFail();
        $calculus = Course::where('slug', 'calculus-i')->firstOrFail();

        // Student enrolled in Algorithms
        $enrollment = Enrollment::updateOrCreate(
            [
                'user_id' => $sam->id,
                'course_id' => $algorithms->id,
            ],
            [
                'assigned_by' => $admin->id,
                'start_date' => now()->subDays(10)->toDateString(),
                'end_date' => now()->addDays(355)->toDateString(),
                'status' => 'active',
            ]
        );

        // Student enrolled in Calculus
        Enrollment::updateOrCreate(
            [
                'user_id' => $sam->id,
                'course_id' => $calculus->id,
            ],
            [
                'assigned_by' => $admin->id,
                'start_date' => now()->subDays(200)->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
                'status' => 'active',
            ]
        );

        // Lesson progress
        $firstLesson = Lesson::where('course_id', $algorithms->id)
            ->orderBy('order_index')
            ->first();

        if ($firstLesson) {
            LessonProgress::updateOrCreate(
                [
                    'user_id' => $sam->id,
                    'lesson_id' => $firstLesson->id,
                ],
                [
                    'last_position_seconds' => 245,
                    'watched_percentage' => 62.5,
                    'completed_at' => null,
                ]
            );
        }

        // Random enrollments
        $students = User::where('role', 'student')
            ->where('id', '!=', $sam->id)
            ->inRandomOrder()
            ->limit(20)
            ->get();

        $courses = Course::where('status', 'active')->get();

        foreach ($students as $student) {

            $course = $courses->random();

            Enrollment::factory()
                ->state(function () {
                    if (rand(0, 4) === 0) {
                        return [
                            'status' => 'expired',
                            'end_date' => now()->subDays(rand(1, 60))->toDateString(),
                        ];
                    }

                    return [];
                })
                ->create([
                    'user_id' => $student->id,
                    'course_id' => $course->id,
                    'assigned_by' => $admin->id,
                ]);

            foreach (
                Lesson::where('course_id', $course->id)
                    ->inRandomOrder()
                    ->limit(rand(1, 3))
                    ->get() as $lesson
            ) {
                LessonProgress::factory()->create([
                    'user_id' => $student->id,
                    'lesson_id' => $lesson->id,
                ]);
            }
        }
    }
}