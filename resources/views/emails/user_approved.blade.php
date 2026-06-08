<!DOCTYPE html>
<html>
<head>
    <title>Account Approved - UniCou</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="{{ asset('images/company_logo.png') }}" alt="UniCou Logo" style="max-height: 50px;">
        </div>
        <h2 style="color: #23AAE2; text-align: center;">Congratulations!</h2>
        <p>Dear {{ $user->first_name }},</p>
        <p>We are pleased to inform you that your <strong>UniCou</strong> account has been officially approved by our administration team.</p>
        <p>You can now log in to your dashboard and access all features including voucher purchases and management.</p>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ route('login') }}" style="background-color: #23AAE2; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;">Login to Your Dashboard</a>
        </div>

        <p>If you have any questions, feel free to reply to this email or contact us at <strong>connect@unicou.uk</strong>.</p>
        
        <p>Best Regards,<br>The UniCou Team</p>
        
        <hr style="border: 0; border-top: 1px solid #eee; margin-top: 30px;">
        <p style="font-size: 0.8em; color: #888; text-align: center;">
            &copy; {{ date('Y') }} UniCou Global. All rights reserved.
        </p>
    </div>
</body>
</html>
