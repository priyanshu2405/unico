<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PointsExpiryReminderNotification extends Notification
{
    use Queueable;

    public int $points;
    public int $daysLeft;
    public ?Carbon $expiryDate;

    public function __construct(int $points, int $daysLeft, ?Carbon $expiryDate)
    {
        $this->points = $points;
        $this->daysLeft = $daysLeft;
        $this->expiryDate = $expiryDate;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $formattedDate = $this->expiryDate ? $this->expiryDate->format('d M Y') : 'soon';
        return [
            'type' => 'points_expiry',
            'points' => $this->points,
            'days_left' => $this->daysLeft,
            'expiry_date' => $this->expiryDate ? $this->expiryDate->toIso8601String() : null,
            'message' => "Reminder: {$this->points} of your reward points will expire in {$this->daysLeft} day(s) on {$formattedDate}. Redeem them at checkout before they expire!",
        ];
    }
}
