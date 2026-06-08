<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
        .header { background-color: #ef4444; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 20px; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; }
        .order-details { background-color: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .reason-box { background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; }
        .button { display: inline-block; padding: 12px 25px; background-color: #ef4444; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Cancelled</h1>
        </div>
        <div class="content">
            <p>Hello {{ $user->name }},</p>
            <p>We regret to inform you that your order has been cancelled.</p>
            
            <div class="reason-box">
                <p><strong>Reason for cancellation:</strong></p>
                <p>{{ $reason }}</p>
            </div>

            <div class="order-details">
                <p><strong>Order ID:</strong> {{ $order->order_id }}</p>
                <p><strong>Voucher:</strong> {{ $order->voucher_type }}</p>
                <p><strong>Amount:</strong> RS {{ number_format($order->amount) }}</p>
            </div>

            <p><strong>Refund Information:</strong> If you have already made the payment, your refund will be processed and credited back to your account within 7 working days.</p>

            <p>If you have any concerns regarding this cancellation, please contact our support team.</p>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="{{ route('orders.history') }}" class="button" style="color: white;">View My Orders</a>
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} UniCou Voucher Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>