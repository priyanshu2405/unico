<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
        .header { background-color: #10b981; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 20px; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; }
        .order-details { background-color: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .codes-box { background-color: #f1f5f9; padding: 20px; border: 1px dashed #cbd5e1; border-radius: 8px; margin: 20px 0; font-family: monospace; white-space: pre-line; }
        .button { display: inline-block; padding: 12px 25px; background-color: #10b981; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Voucher Delivered!</h1>
        </div>
        <div class="content">
            <p>Hello {{ $user->name }},</p>
            <p>Your order has been successfully delivered! Here are your voucher codes:</p>
            
            <div class="codes-box">
{{ $codes }}
            </div>

            <div class="order-details">
                <p><strong>Order ID:</strong> {{ $order->order_id }}</p>
                <p><strong>Voucher:</strong> {{ $order->voucher_type }}</p>
                <p><strong>Amount Paid:</strong> RS {{ number_format($order->amount) }}</p>
            </div>

            <p>Thank you for choosing UniCou! If you have any questions, feel free to contact our support team.</p>
            
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