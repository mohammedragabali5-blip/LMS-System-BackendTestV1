<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $active = $user->enrollments()->active()->with('course.category')->get();
        $expired = $user->enrollments()->expired()->with('course.category')->get();

        return response()->json([
            'active_courses' => $active->map(fn ($e) => [
                'course' => $e->course,
                'end_date' => $e->end_date->toDateString(),
                'remaining_days' => $e->remainingDays(),
            ]),
            'expired_courses' => $expired->map(fn ($e) => [
                'course' => $e->course,
                'end_date' => $e->end_date->toDateString(),
            ]),
            'unread_notifications_count' => $user->notifications()->whereNull('read_at')->count(),
        ]);
    }
}
