<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Services\PlaybackTokenService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Handles VS-001 .. VS-008.
 *
 * The raw video file is NEVER exposed at a stable/guessable URL. Clients must first call
 * Student\LessonController::requestPlayback() to obtain a one-time, short-lived token,
 * then hit this endpoint with that token to actually stream bytes.
 */
class VideoStreamController extends Controller
{
    public function stream(Request $request, string $token)
    {
        $playbackToken = PlaybackTokenService::redeem($token);

        if (! $playbackToken) {
            return response()->json(['message' => 'This playback link is invalid or has expired.'], 403);
        }

        $lesson = $playbackToken->lesson;
        $user = $playbackToken->user;

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $lesson->course_id)
            ->first();

        if (! $enrollment || ! $enrollment->isActive()) {
            return response()->json(['message' => 'Your access has expired. Please contact administration.'], 403);
        }

        $disk = \Illuminate\Support\Facades\Storage::disk($lesson->storage_disk);



        if (! $playbackToken->used_at) {
            $playbackToken->update(['used_at' => now()]);
        }

if (!$lesson->video_path) {
    return response()->json([
        'message' => 'This lesson has no video.'
    ], 422);
}

if (!$disk->exists($lesson->video_path)) {
    return response()->json([
        'message' => 'Video file not found.'
    ], 404);
}

$path = $disk->path($lesson->video_path);
$mime = $disk->mimeType($lesson->video_path) ?? 'video/mp4';
        $size = filesize($path);

        $watermarkText = "{$user->name} · {$user->email}";

        $start = 0;
        $end = $size - 1;
        $statusCode = 200;
        $headers = [
            'Content-Type' => $mime,
            'Accept-Ranges' => 'bytes',
            'Content-Disposition' => 'inline; filename="stream"', // VS-001: never a downloadable filename
            'X-Watermark' => $watermarkText,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store',
        ];

        if ($range = $request->header('Range')) {
            [$unit, $range] = explode('=', $range, 2);
            [$start, $end] = array_pad(explode('-', $range), 2, null);
            $start = (int) $start;
            $end = $end !== null && $end !== '' ? (int) $end : $size - 1;
            $statusCode = 206;
            $headers['Content-Range'] = "bytes {$start}-{$end}/{$size}";
        }

        $length = $end - $start + 1;
        $headers['Content-Length'] = $length;

        return new StreamedResponse(function () use ($path, $start, $length) {
            $handle = fopen($path, 'rb');
            fseek($handle, $start);
            $bytesRemaining = $length;
            $chunkSize = 8192;

            while ($bytesRemaining > 0 && ! feof($handle)) {
                $read = min($chunkSize, $bytesRemaining);
                echo fread($handle, $read);
                $bytesRemaining -= $read;
                flush();
            }

            fclose($handle);
        }, $statusCode, $headers);
    }
}