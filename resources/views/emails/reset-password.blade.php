<!DOCTYPE html>
<html>
<head>
    <title>Reset Your Password</title>
</head>
<body>
    <h2>Hello {{ $user->first_name }},</h2>
    <p>You are receiving this email because we received a password reset request for your account.</p>
    
    <p>
        <a href="{{ $url }}" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px;">
            Reset Password
        </a>
    </p>

    <p>This password reset link will expire in {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutes.</p>
    <p>If you did not request a password reset, no further action is required.</p>

    <p>Thank you,<br>UniCou Team</p>

    <hr>
    <p style="font-size: 12px; color: #777;">
        If you're having trouble clicking the "Reset Password" button, copy and paste the URL below into your web browser:
        <br>
        {{ $url }}
    </p>
</body>
</html>
