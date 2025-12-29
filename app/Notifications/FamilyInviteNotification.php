<?php

namespace App\Notifications;

use App\Models\Account;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FamilyInviteNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Account $account,
        private string $inviteLink,
        private User $invitedBy
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You have been invited to ' . $this->account->name)
            ->greeting('Hi ' . ($notifiable->name ?? 'there') . ',')
            ->line($this->invitedBy->name . ' invited you to join the ' . $this->account->name . ' account.')
            ->line('Use the secure link below to accept the invite and set your password.')
            ->action('Accept invite', $this->inviteLink)
            ->line('If you already have an account, you can sign in after accepting the invite.');
    }
}
