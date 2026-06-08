<!DOCTYPE html>
<html>
<head>
    <title>Verify Your Email Address</title>
</head>
<body>
    <h2>Hello {{ $user->first_name }},</h2>
    <p>Thank you for registering with UniCou. Please click the button below to verify your email address and activate your account.</p>
    
    <p>
        <a href="{{ $url }}" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px;">
            Verify Email Address
        </a>
    </p>

    <p>If you did not create an account, no further action is required.</p>

    <p>Thank you,<br>UniCou Team</p>

    <hr>
    <p style="font-size: 12px; color: #777;">
        If you're having trouble clicking the "Verify Email Address" button, copy and paste the URL below into your web browser:
        <br>
        {{ $url }}
    </p>
</body>
</html>
