<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\PlaybackToken;
use App\Models\User;
use Illuminate\Support\Str;


class PlaybackTokenService
{
    protected const TTL_SECONDS = 60; 

    public static function issue(User $user, Lesson $lesson): PlaybackToken
    {
        // Invalidate previous unexpired tokens for this user+lesson to reduce reuse window.
        PlaybackToken::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->whereNull('used_at')
            ->update(['expires_at' => now()]);

        return PlaybackToken::create([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'token' => Str::random(64),
            'expires_at' => now()->addSeconds(self::TTL_SECONDS),
        ]);
    }

    public static function redeem(string $token): ?PlaybackToken
    {
        $record = PlaybackToken::where('token', $token)->first();

        if (! $record || ! $record->isValid()) {
            return null;
        }

        return $record;
    }
}