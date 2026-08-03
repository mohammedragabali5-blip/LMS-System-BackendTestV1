<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController extends Controller
{

  public function index()
{
    return response()->json(
        Course::with([
            'category',
            'creator:id,fname,lname'
        ])->paginate(20)
    );
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'default_access_days' => 'nullable|integer|min:1',
        ]);

        $course = Course::create([
            ...$validated,
            'slug' => Str::slug($validated['title']) . '-' . Str::random(6),
            'created_by' => $request->user()->id,
            'status' => 'draft',
            'default_access_days' => $validated['default_access_days'] ?? 365,
        ]);

        AuditLogService::log('course.created', $course, "Created course '{$course->title}'");
        return response()->json($course, 201);
    }


public function show(Course $course)
{
    $course->load([
        'category',
        'lessons',
        'enrollments.user',
    ]);

    return response()->json($course);
}

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|nullable|exists:categories,id',
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:draft,active,archived',
            'default_access_days' => 'sometimes|integer|min:1',
        ]);

        $course->update($validated);
        AuditLogService::log('course.updated', $course, null, $validated);

        return response()->json($course);
    }
    public function archive(Course $course)
    {
        $course->update(['status' => 'archived']);
        AuditLogService::log('course.archived', $course, "Archived course '{$course->title}'");

        return response()->json($course);
    }

    public function destroy(Course $course)
    {
        AuditLogService::log('course.deleted', $course, "Deleted course '{$course->title}'");
        $course->delete();
        return response()->json(['message' => 'Course deleted.']);
    }
}
