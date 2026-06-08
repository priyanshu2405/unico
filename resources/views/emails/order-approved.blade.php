<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
        .header { background-color: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 20px; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; }
        .order-details { background-color: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .button { display: inline-block; padding: 12px 25px; background-color: #2563eb; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Approved!</h1>
        </div>
        <div class="content">
            <p>Hello {{ $user->name }},</p>
            <p>Good news! Your order has been approved by our team. Your payment has been verified successfully.</p>
            
            <div class="order-details">
                <p><strong>Order ID:</strong> {{ $order->order_id }}</p>
                <p><strong>Voucher:</strong> {{ $order->voucher_id }}</p>
                <p><strong>Amount:</strong> RS {{ number_format($order->amount) }}</p>
                <p><strong>Date:</strong> {{ $order->created_at->format('d M Y') }}</p>
            </div>

            <p>You can now view your order status and any delivered codes in your dashboard.</p>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="{{ route('orders.history') }}" class="button" style="color: white;">View Order History</a>
            </div>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} UniCou Voucher Management System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
