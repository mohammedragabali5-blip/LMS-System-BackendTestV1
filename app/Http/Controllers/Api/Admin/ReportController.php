<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;

class ReportController extends Controller
{
    public function dashboardStatistics()
    {
        return response()->json([
            'students' => User::where('role', 'student')->count(),
            'active_subscriptions' => Enrollment::active()->count(),
            'expired_subscriptions' => Enrollment::expired()->count(),
            'courses' => Course::count(),
            'videos' => Lesson::whereNotNull('video_path')
                  ->where('video_path', '!=', '')
                  ->count(),
            'assistants' => User::where('role', 'assistant')->count(),
        ]);
    }

    // AD-018
    public function studentReports()
    {
        $mostActive = User::where('role', 'student')
            ->withCount(['lessonProgress as completed_lessons_count' => fn ($q) => $q->whereNotNull('completed_at')])
            ->orderByDesc('completed_lessons_count')
            ->take(10)
            ->get(['id', 'fname', 'email']);

        $leastActive = User::where('role', 'student')
            ->withCount(['lessonProgress as completed_lessons_count' => fn ($q) => $q->whereNotNull('completed_at')])
            ->orderBy('completed_lessons_count')
            ->take(10)
            ->get(['id', 'fname','lname' , 'email']);

        $expiredStudents = User::where('role', 'student')
            ->whereHas('enrollments', fn ($q) => $q->expired())
            ->get(['id', 'fname','lname' ,'email']);

        return response()->json([
            'most_active' => $mostActive,
            'least_active' => $leastActive,
            'expired_students' => $expiredStudents,
        ]);
    }

    // AD-019
    public function courseReports()
    {
        $courses = Course::withCount('enrollments')->with('lessons')->get()->map(function ($course) {
            $totalStudents = $course->enrollments_count;
            $lessonIds = $course->lessons->pluck('id');

            $completions = LessonProgress::whereIn('lesson_id', $lessonIds)
                ->whereNotNull('completed_at')
                ->count();

            $totalPossible = $totalStudents * max(1, $lessonIds->count());
            $completionRate = $totalPossible ? round(($completions / $totalPossible) * 100, 2) : 0;

            $watchTimeSeconds = (int) LessonProgress::whereIn('lesson_id', $lessonIds)->sum('last_position_seconds');

            return [
                'course' => $course->only('id', 'title'),
                'number_of_students' => $totalStudents,
                'completion_rate_percent' => $completionRate,
                'total_watch_time_seconds' => $watchTimeSeconds,
            ];
        });

        return response()->json($courses);
    }
}
