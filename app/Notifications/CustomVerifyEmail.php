<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;

use Illuminate\Notifications\Messages\MailMessage;
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

        // Laravel signed verification URL
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1(
                    $notifiable->getEmailForVerification()
                ),
            ]
        );


        // Get signature parameters
        $parts = parse_url($verificationUrl);


        // Frontend URL from environment
        $frontendUrl = env('FRONTEND_URL') . '/verify-email'
            . '?id=' . $notifiable->getKey()
            . '&hash=' . sha1(
                $notifiable->getEmailForVerification()
            )
            . '&' . $parts['query'];



        return (new MailMessage)
            ->subject('تأكيد البريد الإلكتروني')
            ->view('auth.email-verified', [
                'user' => $notifiable,
                'url' => $frontendUrl,
            ]);
    }


    public function toArray(object $notifiable): array
    {
        return [];
    }
}
