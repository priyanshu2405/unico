<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedNotification extends Notification
{
    use Queueable;

    public $user;
    public $creator;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user, ?User $creator = null)
    {
        $this->user = $user;
        $this->creator = $creator;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'New user created: ' . $this->user->first_name . ' (' . $this->user->account_type . ')',
            'user_id' => $this->user->id,
            'creator_name' => $this->creator ? $this->creator->first_name : 'Self Registration',
            'type' => 'user_created'
        ];
    }
}
