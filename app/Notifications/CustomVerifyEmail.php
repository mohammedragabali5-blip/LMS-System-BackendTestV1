<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailNotification;

class CustomVerifyEmail extends VerifyEmailNotification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */


    /**
     * Get the mail representation of the notification.
     */


public function toMail($notifiable)
{
    // إنشاء رابط التحقق الخاص بـ Laravel
    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]
    );

    // استخراج Query Parameters
    $parts = parse_url($verificationUrl);

    // إنشاء رابط React
    $frontendUrl =
        "http://localhost:5173/verify-email"
        . "?id=" . $notifiable->getKey()
        . "&hash=" . sha1($notifiable->getEmailForVerification())
        . "&" . $parts['query'];

    return (new MailMessage)
        ->subject('تأكيد البريد الإلكتروني')
        ->view('auth.email-verified', [
            'user' => $notifiable,
            'url'  => $frontendUrl,
        ]);
}
    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}