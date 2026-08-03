<?php

namespace App\Http\Middleware;

use App\Models\Enrollment;
use Closure;
use Illuminate\Http\Request;


class CheckEnrollmentActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $courseId = $request->route('course') ?? $request->route('courseId');

        if ($courseId instanceof Course) {
            $courseId = $courseId->id;
        }

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if (! $enrollment) {
            return response()->json(['message' => 'You are not enrolled in this course.'], 403);
        }

        if (! $enrollment->isActive()) {
            if ($enrollment->status === 'active') {
                $enrollment->update(['status' => 'expired']);
            }

            return response()->json([
                'message' => 'Your access has expired. Please contact administration.',
            ], 403);
        }

        $request->attributes->set('enrollment', $enrollment);

        return $next($request);
    }
}
