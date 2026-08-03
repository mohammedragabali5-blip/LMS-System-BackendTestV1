<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;


class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Enrollment::where('user_id', $request->user()->id)
            ->active()
            ->with('course.category');

        if ($search = $request->query('search')) {
            $query->whereHas('course', fn($q) => $q->where('title', 'like', "%{$search}%"));
        }

        return response()->json($query->get()->pluck('course'));
    }

    public function show(Request $request, int $course)
    {
        $enrollment = Enrollment::where('user_id', $request->user()->id)
            ->where('course_id', $course)
            ->firstOrFail();

        if (! $enrollment->isActive()) {
            return response()->json(['message' => 'Your access has expired. Please contact administration.'], 403);
        }

        $enrollment->load('course.lessons', 'course.category');

        $lessons = $enrollment->course->lessons->map(function ($lesson) use ($request) {
            $progress = $lesson->progress()->where('user_id', $request->user()->id)->first();

            return [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'has_video' => !empty($lesson->video_path),
                'has_pdf'   => !empty($lesson->pdf_path),

                'order_index' => $lesson->order_index,
                'allow_attachments' => $lesson->allow_attachments,
                'watched_percentage' => $progress->watched_percentage ?? 0,
                'completed' => (bool) ($progress->completed_at ?? false),
            ];
        });

        $overallProgress = $lessons->count()
            ? round($lessons->avg('watched_percentage'), 2)
            : 0;

        return response()->json([
            'course' => $enrollment->course,
            'lessons' => $lessons,
            'progress_percentage' => $overallProgress,
            'remaining_days' => $enrollment->remainingDays(),
        ]);
    }
}