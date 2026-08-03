<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@example.com')->first();
        $programming = Category::where('slug', 'programming')->first();
        $mathematics = Category::where('slug', 'mathematics')->first();

        /*
        |--------------------------------------------------------------------------
        | Demo Course #1
        |--------------------------------------------------------------------------
        */

        $algorithms = Course::updateOrCreate(
            ['slug' => 'intro-algorithms'],
            [
                'category_id' => $programming?->id,
                'created_by' => $admin?->id,
                'title' => 'Introduction to Algorithms',
                'description' => 'Foundations of algorithmic thinking, complexity, and problem solving.',
                'status' => 'active',
                'default_access_days' => 365,
            ]
        );

        $this->seedLessons($algorithms, [
            [
                'title' => 'What is an Algorithm?',
                'video' => true,
                'duration_seconds' => 480,
            ],
            [
                'title' => 'Big-O Notation',
                'video' => true,
                'duration_seconds' => 620,
            ],
            [
                'title' => 'Sorting Algorithms',
                'video' => true,
                'duration_seconds' => 900,
            ],
            [
                'title' => 'Course Slides',
                'pdf' => true,
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Demo Course #2
        |--------------------------------------------------------------------------
        */

        $calculus = Course::updateOrCreate(
            ['slug' => 'calculus-i'],
            [
                'category_id' => $mathematics?->id,
                'created_by' => $admin?->id,
                'title' => 'Calculus I',
                'description' => 'Limits, derivatives, and the basics of integration.',
                'status' => 'active',
                'default_access_days' => 180,
            ]
        );

        $this->seedLessons($calculus, [
            [
                'title' => 'Understanding Limits',
                'video' => true,
                'duration_seconds' => 540,
            ],
            [
                'title' => 'Introduction to Derivatives',
                'video' => true,
                'duration_seconds' => 700,
            ],
            [
                'title' => 'Practice Problem Set',
                'attachment' => true,
            ],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Random Courses
        |--------------------------------------------------------------------------
        */

        Course::factory()
            ->count(6)
            ->create([
                'created_by' => $admin?->id,
            ])
            ->each(function (Course $course) {
                Lesson::factory()
                    ->count(rand(3, 6))
                    ->sequence(fn ($sequence) => [
                        'order_index' => $sequence->index + 1,
                    ])
                    ->create([
                        'course_id' => $course->id,
                    ]);
            });

        Course::factory()
            ->draft()
            ->count(2)
            ->create([
                'created_by' => $admin?->id,
            ]);

        Course::factory()
            ->archived()
            ->count(2)
            ->create([
                'created_by' => $admin?->id,
            ]);
    }

    private function seedLessons(Course $course, array $lessons): void
    {
        foreach ($lessons as $index => $data) {

            $slug = Str::slug($data['title']);

            $videoPath = !empty($data['video'])
                ? "lessons/{$course->id}/video/{$slug}.mp4"
                : null;

            $pdfPath = !empty($data['pdf'])
                ? "lessons/{$course->id}/pdf/{$slug}.pdf"
                : null;

            $attachments = null;

            if (!empty($data['attachment'])) {
                $attachments = [
                    [
                        'path' => "lessons/{$course->id}/attachments/{$slug}.zip",
                        'original_name' => "{$slug}.zip",
                    ],
                ];
            }

            Lesson::updateOrCreate(
                [
                    'course_id' => $course->id,
                    'title' => $data['title'],
                ],
                [
                    'video_path' => $videoPath,
                    'pdf_path' => $pdfPath,
                    'attachments' => $attachments,
                    'storage_disk' => 'private',
                    'duration_seconds' => $data['duration_seconds'] ?? null,
                    'order_index' => $index + 1,
                    'allow_attachments' => !empty($attachments),
                    'allow_download' => false,
                ]
            );
        }
    }
}
