<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    // AD-012 / AD-014
    public function assign(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'access_days' => 'nullable|integer|min:1',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $days = $validated['access_days'] ?? $course->default_access_days;

        $enrollment = Enrollment::updateOrCreate(
            ['user_id' => $validated['student_id'], 'course_id' => $course->id],
            [
                'assigned_by' => $request->user()->id,
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays($days)->toDateString(),
                'status' => 'active',
                'revoked_at' => null,
            ]
        );

        $student = User::find($validated['student_id']);
        if ($student->status === 'inactive') {
            $student->update(['status' => 'active']);
        }

        NotificationService::courseAssigned($student, $course->title);
        AuditLogService::log('course.assigned', $enrollment, "Admin assigned '{$course->title}' to {$student->email}");

        return response()->json($enrollment, 201);
    }

    // AD-013
    public function bulkAssign(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'access_days' => 'nullable|integer|min:1',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $days = $validated['access_days'] ?? $course->default_access_days;
        $created = [];

        foreach ($validated['student_ids'] as $studentId) {
            $enrollment = Enrollment::updateOrCreate(
                ['user_id' => $studentId, 'course_id' => $course->id],
                [
                    'assigned_by' => $request->user()->id,
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->addDays($days)->toDateString(),
                    'status' => 'active',
                    'revoked_at' => null,
                ]
            );

            $student = User::find($studentId);
            if ($student->status === 'inactive') {
                $student->update(['status' => 'active']);
            }
            NotificationService::courseAssigned($student, $course->title);

            $created[] = $enrollment;
        }

        AuditLogService::log('course.bulk_assigned', $course, 'Bulk assigned course to '.count($created).' student(s)');

        return response()->json($created, 201);
    }

    // AD-015
    public function renew(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate(['access_days' => 'nullable|integer|min:1']);
        $days = $validated['access_days'] ?? $enrollment->course->default_access_days;

        $enrollment->update([
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays($days)->toDateString(),
            'status' => 'active',
            'revoked_at' => null,
        ]);

        AuditLogService::log('enrollment.renewed', $enrollment, "Renewed for {$days} day(s)");

        return response()->json($enrollment);
    }

    // AD-016
    public function revoke(Enrollment $enrollment)
    {
        $enrollment->update(['status' => 'revoked', 'revoked_at' => now()]);
        AuditLogService::log('enrollment.revoked', $enrollment, 'Access revoked immediately');

        return response()->json($enrollment);
    }
}
