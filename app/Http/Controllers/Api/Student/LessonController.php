<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\PlaybackTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LessonController extends Controller
{


    public function downloadPdf(Request $request, int $course, int $lesson)
    {
        $lessonModel = Lesson::where('course_id', $course)->findOrFail($lesson);

        if (!$lessonModel->pdf_path) {
            return response()->json([
                'message' => 'This lesson has no PDF.'
            ], 404);
        }

        $enrollment = Enrollment::where('user_id', $request->user()->id)
            ->where('course_id', $course)
            ->first();

        if (!$enrollment || !$enrollment->isActive()) {
            return response()->json([
                'message' => 'Your access has expired.'
            ], 403);
        }

        $disk = Storage::disk($lessonModel->storage_disk);

        if (!$disk->exists($lessonModel->pdf_path)) {
            return response()->json([
                'message' => 'PDF file not found.'
            ], 404);
        }

        return $disk->download(
            $lessonModel->pdf_path,
            basename($lessonModel->pdf_path),
            [
                'Content-Type' => 'application/pdf',
            ]
        );
    }
    public function requestPlayback(Request $request, int $course, int $lesson)
    {
        $lessonModel = Lesson::where('course_id', $course)->findOrFail($lesson);

        if (!$lessonModel->video_path) {
            return response()->json([
                'message' => 'This lesson has no video.'
            ], 422);
        }

        $token = PlaybackTokenService::issue($request->user(), $lessonModel);

        return response()->json([
            'playback_url' => route('stream.video', ['token' => $token->token]),
            'expires_at' => $token->expires_at->toIso8601String(),
        ]);
    }

    public function updateProgress(Request $request, int $course, int $lesson)
    {
        $request->validate([
            'position_seconds' => 'required|integer|min:0',
            'watched_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $lessonModel = Lesson::where('course_id', $course)->findOrFail($lesson);

        $progress = LessonProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'lesson_id' => $lessonModel->id],
            [
                'last_position_seconds' => $request->position_seconds,
                'watched_percentage' => $request->watched_percentage,
                'completed_at' => $request->watched_percentage >= 95 ? now() : null,
            ]
        );

        return response()->json($progress);
    }
}
