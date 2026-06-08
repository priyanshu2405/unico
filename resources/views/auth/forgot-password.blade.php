@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
<div class="login-wrapper">
    <!-- LEFT IMAGE -->
    <div class="login-image">
        <img src="{{asset('images/login_image.jpg')}}">
    </div>

    <!-- RIGHT FORM -->
    <div class="login-form">
        <h2>Forgot Password</h2>
        <p>Enter your email to receive a password reset link</p>

        <div id="message-container" style="display: none; margin-bottom: 15px; font-size: 14px;"></div>

        <form id="forgot-password-form" action="{{ route('password.email') }}" method="POST">
            @csrf

            <div class="field">
                <label>Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="Enter email" required>
                <div class="error" id="email-error"></div>
            </div>

            <button type="submit" class="login_btn" id="submit-btn">Send Reset Link</button>

            <p class="bottom-text">
                Remember your password?
                <a href="{{ route('login') }}" class="link">Back to login</a>
            </p>
        </form>
    </div>
</div>

<script>
document.getElementById('forgot-password-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const submitBtn = document.getElementById('submit-btn');
    const messageContainer = document.getElementById('message-container');
    const emailError = document.getElementById('email-error');
    
    // Clear previous errors
    emailError.innerText = '';
    messageContainer.style.display = 'none';
    submitBtn.disabled = true;
    submitBtn.innerText = 'Sending...';

    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            messageContainer.style.color = 'green';
            messageContainer.innerText = data.message;
            messageContainer.style.display = 'block';
            form.reset();
        } else {
            messageContainer.style.color = 'red';
            messageContainer.innerText = data.message || 'Something went wrong.';
            messageContainer.style.display = 'block';
            if (data.errors && data.errors.email) {
                emailError.innerText = data.errors.email[0];
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        messageContainer.style.color = 'red';
        messageContainer.innerText = 'An error occurred. Please try again.';
        messageContainer.style.display = 'block';
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerText = 'Send Reset Link';
    });
});
</script>
@endsection
