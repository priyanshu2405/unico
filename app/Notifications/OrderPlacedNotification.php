<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification
{
    use Queueable;

    public $order;
    public $message;
    public $type;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, $message, $type = 'order_placed')
    {
        $this->order = $order;
        $this->message = $message;
        $this->type = $type;
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
            'order_id' => $this->order->order_id,
            'amount' => $this->order->amount,
            'voucher_type' => $this->order->voucher_type,
            'quantity' => $this->order->quantity,
            'message' => $this->message,
            'type' => $this->type
        ];
    }
}
