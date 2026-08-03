<?php

namespace App\Http\Controllers\Api\Assistant;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class CourseAssignmentController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:users,id',
            'course_id' => 'required|exists:courses,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $course = Course::findOrFail($validated['course_id']);
        $start = $validated['start_date'] ?? now()->toDateString();
        $end = $validated['end_date'] ?? now()->addDays($course->default_access_days)->toDateString();

        $enrollment = Enrollment::updateOrCreate(
            ['user_id' => $validated['student_id'], 'course_id' => $course->id],
            [
                'assigned_by' => $request->user()->id,
                'start_date' => $start,
                'end_date' => $end,
                'status' => 'active',
                'revoked_at' => null,
            ]
        );

        $student = User::find($validated['student_id']);
        if ($student->status === 'inactive') {
            $student->update(['status' => 'active']); // US-001: activated once a course is assigned
        }

        NotificationService::courseAssigned($student, $course->title);
        AuditLogService::log('course.assigned', $enrollment, "Assigned course '{$course->title}' to {$student->email}");

        return response()->json($enrollment, 201);
    }


    public function extend(Request $request, Enrollment $enrollment)
    {
        $validated = $request->validate(['extra_days' => 'required|integer|min:1']);

        $newEnd = $enrollment->end_date->addDays($validated['extra_days']);
        $enrollment->update(['end_date' => $newEnd, 'status' => 'active']);

        AuditLogService::log('enrollment.extended', $enrollment, "Extended by {$validated['extra_days']} day(s)");

        return response()->json($enrollment);
    }

    public function destroy(Enrollment $enrollment)
    {
        AuditLogService::log('enrollment.removed', $enrollment, 'Removed course access');
        $enrollment->delete();

        return response()->json(['message' => 'Course access removed.']);
    }
}
