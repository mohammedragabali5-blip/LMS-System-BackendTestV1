<?php
namespace App\Services;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Str;

class NotificationService
{
    public static  function send(User $user, string $type, string $title, ?string $body = null, array $data = [])
    {
        return Notification::create([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'created_at' => now(),
        ]);
    }
    public static function courseAssigned(User $user, string $courseTitle)
    {
        return self::send($user, 'course.assigned', 'New course assigned', "You have been assigned to \"{$courseTitle}\".");
    }
    public static function courseExpiringSoon(User $user, string $courseTitle, int $daysLeft): Notification
    {
        return self::send($user, 'course.expiring', 'Course expires soon', "\"{$courseTitle}\" expires in {$daysLeft} day(s).");
    }

    public static function passwordChanged(User $user): Notification
    {
        return self::send($user, 'account.password_changed', 'Password changed', 'Your password was recently changed.');
    }
}