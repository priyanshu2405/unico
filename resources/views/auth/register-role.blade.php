<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Registration Type</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            text-align: center;
            background: white;
            padding: 50px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 90%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
        }
        
        .roles-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .role-card {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            border: 2px solid transparent;
        }
        
        .role-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: #667eea;
        }
        
        .role-card .icon {
            font-size: 40px;
            margin-bottom: 15px;
        }
        
        .role-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 20px;
        }
        
        .role-card p {
            color: #666;
            font-size: 14px;
        }
        
        .login-link {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .login-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Select Registration Type</h1>
        
        @if ($errors->any())
            <div style="color: red; margin-bottom: 20px;">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif
        
        <div class="roles-grid">
            <a href="{{ route('register.form', 'manager') }}" class="role-card">
                <div class="icon">📊</div>
                <h3>Manager</h3>
                <p>Team Manager</p>
            </a>
            
            <a href="{{ route('register.form', 'support') }}" class="role-card">
                <div class="icon">🎧</div>
                <h3>Support Team</h3>
                <p>Support Staff</p>
            </a>

            <a href="{{ route('register.form', 'student') }}" class="role-card">
                <div class="icon">🎓</div>
                <h3>Student</h3>
                <p>Learn and Earn</p>
            </a>
            
            <a href="{{ route('register.form', 'reseller_agent') }}" class="role-card">
                <div class="icon">🤝</div>
                <h3>Seller Agent</h3>
                <p>Reseller Partner</p>
            </a>
        </div>
        
        <p>Already have an account? <a href="{{ route('login') }}" class="login-link">Login here</a></p>
    </div>
</body>
</html>
