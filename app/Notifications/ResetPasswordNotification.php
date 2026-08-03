<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    protected $token;


    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }


    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }


    /**
     * Generate password reset URL
     */
    protected function resetUrl($notifiable)
    {
        return config('app.frontend_url')
            . "/reset-password?token={$this->token}&email={$notifiable->email}";
    }


    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $url = $this->resetUrl($notifiable);

        return (new MailMessage)
            ->subject('إعادة تعيين كلمة المرور')
            ->view('auth.password-reset', [
                'user' => $notifiable,
                'url'  => $url,
            ]);
    }


    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}