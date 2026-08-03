<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        $hasVideo = fake()->boolean(80); // 80% have a video
        $hasPdf = fake()->boolean(50);   // 50% have a PDF

        // Ensure at least one file exists
        if (!$hasVideo && !$hasPdf) {
            $hasVideo = true;
        }

        return [
            'course_id' => Course::factory(),

            'title' => Str::ucfirst(fake()->words(4, true)),

            'video_path' => $hasVideo
                ? 'lessons/videos/' . fake()->uuid() . '.mp4'
                : null,

            'pdf_path' => $hasPdf
                ? 'lessons/pdfs/' . fake()->uuid() . '.pdf'
                : null,

            'attachments' => fake()->boolean(30)
                ? [
                    'lessons/attachments/' . fake()->uuid() . '.zip',
                    'lessons/attachments/' . fake()->uuid() . '.docx',
                ]
                : null,

            'storage_disk' => 'private',

            'duration_seconds' => $hasVideo
                ? fake()->numberBetween(180, 2400)
                : null,

            'order_index' => 0,

            'allow_attachments' => fake()->boolean(30),

            'allow_download' => fake()->boolean(20),
        ];
    }

    public function video(): static
    {
        return $this->state(fn () => [
            'video_path' => 'lessons/videos/' . fake()->uuid() . '.mp4',
            'pdf_path' => null,
            'duration_seconds' => fake()->numberBetween(180, 2400),
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn () => [
            'video_path' => null,
            'pdf_path' => 'lessons/pdfs/' . fake()->uuid() . '.pdf',
            'duration_seconds' => null,
        ]);
    }

    public function videoAndPdf(): static
    {
        return $this->state(fn () => [
            'video_path' => 'lessons/videos/' . fake()->uuid() . '.mp4',
            'pdf_path' => 'lessons/pdfs/' . fake()->uuid() . '.pdf',
            'duration_seconds' => fake()->numberBetween(180, 2400),
        ]);
    }
}
