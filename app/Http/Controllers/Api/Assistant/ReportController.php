<?php

namespace App\Http\Controllers\Api\Assistant;

use App\Http\Controllers\Controller;
use App\Models\Course;

// Requires 'permission:reports'
class ReportController extends Controller
{
    // UA-017
    public function studentProgress(Course $course)
    {
        $enrollments = $course->enrollments()->with('user')->get();

        $report = $enrollments->map(function ($enrollment) use ($course) {
            $progress = $course->lessons()
                ->with(['progress' => fn ($q) => $q->where('user_id', $enrollment->user_id)])
                ->get()
                ->pluck('progress')
                ->flatten();

            $lastLesson = $progress->sortByDesc('updated_at')->first();

            return [
                'student' => $enrollment->user->only('id', 'name', 'email'),
                'watched_percentage' => $progress->count() ? round($progress->avg('watched_percentage'), 2) : 0,
                'last_lesson_id' => $lastLesson?->lesson_id,
                'completed_lessons' => $progress->whereNotNull('completed_at')->count(),
                'total_lessons' => $course->lessons()->count(),
            ];
        });

        return response()->json($report);
    }
}
