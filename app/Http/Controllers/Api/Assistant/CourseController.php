<?php

namespace App\Http\Controllers\Api\Assistant;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index()
    {
        return response()->json(Course::with('category')->paginate(20));
    }


   public function show(Course $course)
{
    $course->load([
        'category',
        'lessons' => fn ($q) => $q->orderBy('order_index'),
        'enrollments.user',
    ]);

    return response()->json($course);
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'default_access_days' => 'required|integer|min:1',
        ]);

        $course = Course::create([
                    ...$validated,
            'slug' => Str::slug($validated['title']) . '-' . Str::random(6),
            'created_by' => $request->user()->id,
            'status' => 'draft',
            'default_access_days' => $validated['default_access_days'] ?? 365,
        ]);

        AuditLogService::log(
            'course.created',
            $course,
            "Created course '{$course->title}' (assistant)"
        );

        return response()->json($course->load('category'), 201);
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'category_id' => 'sometimes|nullable|exists:categories,id',
        ]);

        $course->update($validated);
        AuditLogService::log('course.updated', $course, "Updated course '{$course->title}'", $validated);

        return response()->json($course);
    }

    // NEW: deletes the course along with every lesson's stored files
    // (video/pdf/attachments) and the course thumbnail, so nothing gets
    // orphaned on disk.
    public function destroy(Course $course)
    {
        DB::transaction(function () use ($course) {
            $this->deleteCourseFilesAndLessons($course);

            AuditLogService::log('course.deleted', $course, "Deleted course '{$course->title}'");
            $course->delete();
        });

        return response()->json(['message' => 'Course deleted.']);
    }

    // Shared by destroy() here and CategoryController::destroy() (cascade
    // delete). Deletes every lesson's files + row for a course, and the
    // course's thumbnail file. Does NOT delete the course row itself —
    // callers do that after calling this.
    public function deleteCourseFilesAndLessons(Course $course): void
    {
        foreach ($course->lessons as $lesson) {
            $disk = Storage::disk($lesson->storage_disk);

            if ($lesson->video_path) {
                $disk->delete($lesson->video_path);
            }
            if ($lesson->pdf_path) {
                $disk->delete($lesson->pdf_path);
            }
            if (! empty($lesson->attachments)) {
                foreach ($lesson->attachments as $attachment) {
                    $disk->delete($attachment['path'] ?? $attachment);
                }
            }

            $lesson->delete();
        }

        if ($course->thumbnail_path) {
            Storage::disk('public')->delete($course->thumbnail_path);
        }
    }

    public function uploadThumbnail(Request $request, Course $course)
    {
        $request->validate(['thumbnail' => 'required|image|max:4096']);

        $path = $request->file('thumbnail')->store('course-thumbnails', 'public');
        $course->update(['thumbnail_path' => $path]);

        return response()->json($course);
    }

    public function storeLesson(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'order_index' => 'sometimes|nullable|integer|min:1',
            'allow_attachments' => 'sometimes|boolean',
            'allow_download' => 'sometimes|boolean',
            'video' => 'required_without:pdf|nullable|file|mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm|max:512000',
            'pdf' => 'required_without:video|nullable|file|mimes:pdf|max:512000',
            'attachments' => 'sometimes|array',
            'attachments.*' => 'file|max:512000',
        ]);

        $videoPath = null;
        $pdfPath = null;
        $attachmentPaths = [];

        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('lessons/'.$course->id.'/video', 'private');
        }

        if ($request->hasFile('pdf')) {
            $pdfPath = $request->file('pdf')->store('lessons/'.$course->id.'/pdf', 'private');
        }

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $attachmentPaths[] = [
                    'path' => $file->store('lessons/'.$course->id.'/attachments', 'private'),
                    'original_name' => $file->getClientOriginalName(),
                ];
            }
        }

        $orderIndex = $validated['order_index'] ?? (($course->lessons()->max('order_index') ?? 0) + 1);

        $lesson = Lesson::create([
            'course_id' => $course->id,
            'title' => $validated['title'],
            'video_path' => $videoPath,
            'pdf_path' => $pdfPath,
            'attachments' => $attachmentPaths,
            'storage_disk' => 'private',
            'order_index' => $orderIndex,
            'allow_attachments' => $request->boolean('allow_attachments'),
            'allow_download' => $request->boolean('allow_download'),
        ]);

        AuditLogService::log('lesson.uploaded', $lesson, "Uploaded lesson '{$lesson->title}' to course '{$course->title}'");

        return response()->json($lesson, 201);
    }

    public function destroyLesson(Course $course, Lesson $lesson)
    {
        abort_unless($lesson->course_id === $course->id, 404);

        AuditLogService::log('lesson.deleted', $lesson, "Deleted lesson '{$lesson->title}' from course '{$course->title}'");

        $disk = Storage::disk($lesson->storage_disk);

        if ($lesson->video_path) {
            $disk->delete($lesson->video_path);
        }
        if ($lesson->pdf_path) {
            $disk->delete($lesson->pdf_path);
        }
        if (!empty($lesson->attachments)) {
            foreach ($lesson->attachments as $attachment) {
                $disk->delete($attachment['path'] ?? $attachment);
            }
        }

        $lesson->delete();

        return response()->json(['message' => 'Lesson deleted.']);
    }

    public function reorderLessons(Request $request, Course $course)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:lessons,id',
        ]);

        foreach ($validated['order'] as $index => $lessonId) {
            Lesson::where('id', $lessonId)->where('course_id', $course->id)->update(['order_index' => $index + 1]);
        }

        return response()->json($course->lessons()->orderBy('order_index')->get());
    }
}
