<!DOCTYPE html>
<html>
<head>
    <title>Account Created</title>
</head>
<body>
    <h2>Hello {{ $user->first_name }},</h2>
    <p>Your account has been successfully created on UniCou.</p>
    
    <p><strong>Login Details:</strong></p>
    <ul>
        <li><strong>Email:</strong> {{ $user->email }}</li>
        <li><strong>Password:</strong> {{ $password }}</li>
    </ul>

    <p>Please click the link below to complete your profile details:</p>
    
    @php
        $link = route('dashboard');
        if($user->account_type === 'student') {
            $link = route('auth.form.student');
        } elseif($user->account_type === 'reseller_agent') {
            $link = route('auth.forms.B2BResellerAgent');
        }
    @endphp

    <p>
        <a href="{{ $link }}" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 5px;">
            Complete Your Profile
        </a>
    </p>

    <p>If you wish to reset your password, you can do so from your profile settings after logging in.</p>

    <p>Thank you,<br>UniCou Team</p>
</body>
</html>
