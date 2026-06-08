<?php

namespace App\Notifications;

use App\Models\InventoryVoucher;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InventoryOutOfStockNotification extends Notification
{
    use Queueable;

    public $inventory;
    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(InventoryVoucher $inventory)
    {
        $this->inventory = $inventory;
        $this->message = "Voucher SKU: {$inventory->sku_id} ({$inventory->brand_name}) is now OUT OF STOCK.";
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        
        // Send email only to admins
        if ($notifiable->account_type === 'admin') {
            $channels[] = 'mail';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Inventory Alert: Out of Stock - ' . $this->inventory->brand_name)
            ->view('emails.inventory-out-of-stock', [
                'inventory' => $this->inventory,
                'user' => $notifiable
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
            'sku_id' => $this->inventory->sku_id,
            'brand_name' => $this->inventory->brand_name,
            'voucher_type' => $this->inventory->voucher_type,
            'message' => $this->message,
            'type' => 'inventory_out_of_stock'
        ];
    }
}
