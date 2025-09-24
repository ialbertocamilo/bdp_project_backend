<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TwoFactorEnabled extends Notification
{
    use Queueable;

    private $backupCodes;

    public function __construct($backupCodes)
    {
        $this->backupCodes = $backupCodes;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('2FA Enabled - BDP Project')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Two-factor authentication has been enabled for your account.')
            ->line('Please save these backup codes in a secure location:')
            ->line(implode(', ', $this->backupCodes))
            ->line('These codes can be used to access your account if you lose access to your authenticator app.')
            ->action('Visit Dashboard', url('/dashboard'))
            ->line('If you did not enable 2FA, please contact support immediately.');
    }
}