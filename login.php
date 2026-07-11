<?php
session_start();
if(isset($_SESSION['user_id'])) {
    header("Location: welcome.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Core Cinema World - Login & Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            overflow-x: hidden;
            position: relative;
        }
        
        .bg-circles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }
        
        .circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
            animation: float 20s infinite ease-in-out;
        }
        
        .circle:nth-child(1) { width: 300px; height: 300px; top: -100px; left: -100px; animation-duration: 25s; }
        .circle:nth-child(2) { width: 500px; height: 500px; bottom: -200px; right: -200px; animation-duration: 30s; }
        .circle:nth-child(3) { width: 200px; height: 200px; top: 50%; left: 50%; animation-duration: 20s; }
        .circle:nth-child(4) { width: 150px; height: 150px; top: 20%; right: 10%; animation-duration: 15s; }
        .circle:nth-child(5) { width: 250px; height: 250px; bottom: 10%; left: 20%; animation-duration: 35s; }
        
        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
            100% { transform: translateY(0) rotate(360deg); }
        }
        
        .floating-icon {
            position: fixed;
            opacity: 0.1;
            font-size: 100px;
            pointer-events: none;
            z-index: 0;
            animation: floatIcon 25s infinite linear;
        }
        
        .floating-icon:nth-child(1) { top: 10%; left: -50px; animation-duration: 30s; }
        .floating-icon:nth-child(2) { bottom: 10%; right: -50px; animation-duration: 35s; }
        .floating-icon:nth-child(3) { top: 40%; left: 15%; animation-duration: 28s; }
        .floating-icon:nth-child(4) { bottom: 20%; right: 10%; animation-duration: 32s; }
        
        @keyframes floatIcon {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-40px) rotate(180deg); }
            100% { transform: translateY(0) rotate(360deg); }
        }
        
        .login-container {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 28px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            animation: fadeInScale 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9) translateY(30px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        
        .auth-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            background: #f3f4f6;
            padding: 5px;
            border-radius: 60px;
        }
        
        .auth-tab {
            flex: 1;
            padding: 12px 20px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s;
            color: #6b7280;
        }
        
        .auth-tab.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 10px rgba(102,126,234,0.3);
        }
        
        .auth-form {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .auth-form.active {
            display: block;
        }
        
        .logo {
            margin-bottom: 20px;
            position: relative;
        }
        
        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            animation: pulse 2s infinite;
            box-shadow: 0 0 30px rgba(102,126,234,0.3);
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 30px rgba(102,126,234,0.3); }
            50% { transform: scale(1.05); box-shadow: 0 0 50px rgba(102,126,234,0.5); }
        }
        
        .logo-icon i {
            font-size: 40px;
            color: white;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 5px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .login-header p {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 25px;
        }
        
        /* All other input groups - KEPT SAME */
        .input-group {
            position: relative;
            margin-bottom: 20px;
            text-align: left;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }
        
        .input-group i {
            position: absolute;
            left: 15px;
            bottom: 15px;
            color: #9ca3af;
            font-size: 18px;
        }
        
        .input-group input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 14px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
            background: #f9fafb;
        }
        
        .input-group input:focus {
            border-color: #667eea;
            outline: none;
            background: white;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            bottom: 15px;
            cursor: pointer;
            color: #9ca3af;
            font-size: 18px;
        }
        
        .password-toggle:hover {
            color: #667eea;
        }
        
        /* ONLY REFERRAL CODE GROUP - FIXED ALIGNMENT */
        .referral-group {
            position: relative;
            margin-bottom: 20px;
            text-align: left;
        }
        
        .referral-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
        }
        
        .referral-group i {
            position: absolute;
            left: 15px;
            bottom: 15px;
            color: #9ca3af;
            font-size: 18px;
        }
        
        .referral-group input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 14px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: inherit;
            background: #f9fafb;
        }
        
        .referral-group input:focus {
            border-color: #667eea;
            outline: none;
            background: white;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        
        .referral-help-text {
            display: block;
            margin-top: 8px;
            font-size: 12px;
            color: #6b7280;
            padding-left: 5px;
        }
        
        .referral-help-text i {
            color: #f59e0b;
            margin-right: 5px;
        }
        
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(102,126,234,0.4);
        }
        
        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }
        
        .submit-btn:hover::before {
            left: 100%;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px -5px rgba(102,126,234,0.5);
        }
        
        .error-message, .success-message {
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .error-message {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .success-message {
            background: #d1fae5;
            color: #065f46;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #9ca3af;
        }
        
        @media (max-width: 768px) {
            .login-card {
                padding: 30px 20px;
            }
            
            .auth-tab {
                padding: 10px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-circles">
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
    </div>
    
    <div class="floating-icon"><i class="fas fa-film"></i></div>
    <div class="floating-icon"><i class="fas fa-ticket-alt"></i></div>
    <div class="floating-icon"><i class="fas fa-clapperboard"></i></div>
    <div class="floating-icon"><i class="fas fa-video"></i></div>
    
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-film"></i>
                </div>
            </div>
            
            <div class="login-header">
                <h1>Core Cinema World</h1>
                <p>Your Ultimate Movie Experience</p>
            </div>
            
            <div class="auth-tabs">
                <button class="auth-tab active" data-tab="login">Login</button>
                <button class="auth-tab" data-tab="signup">Create Account</button>
            </div>
            
            <!-- Login Form -->
            <div id="loginForm" class="auth-form active">
                <?php if(isset($_GET['error'])): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        Invalid username or password!
                    </div>
                <?php endif; ?>
                <?php if(isset($_GET['registered'])): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        Account created successfully! Please login.
                    </div>
                <?php endif; ?>
                
                <form action="php/process_login.php" method="POST">
                    <div class="input-group">
                        <label>Username</label>
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Enter your username" required>
                    </div>
                    
                    <div class="input-group">
                        <label>Password</label>
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Enter your password" required>
                        <i class="fas fa-eye-slash password-toggle" onclick="togglePassword('password')"></i>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        Login <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>
            
            <!-- Sign Up Form -->
            <div id="signupForm" class="auth-form">
                <form action="php/process_signup.php" method="POST">
                    <div class="input-group">
                        <label>Full Name</label>
                        <i class="fas fa-user"></i>
                        <input type="text" name="full_name" placeholder="Enter your full name" required>
                    </div>
                    
                    <div class="input-group">
                        <label>Username</label>
                        <i class="fas fa-id-card"></i>
                        <input type="text" name="username" placeholder="Choose a username" required>
                    </div>
                    
                    <div class="input-group">
                        <label>Email</label>
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="input-group">
                        <label>Phone Number</label>
                        <i class="fas fa-phone"></i>
                        <input type="tel" name="phone" placeholder="Enter your phone number" required>
                    </div>
                    
                    <!-- REFERRAL CODE FIELD - FIXED ALIGNMENT -->
                    <div class="referral-group">
                        <label><i class="fas fa-gift"></i> Referral Code (Optional)</label>
                        <i class="fas fa-tag"></i>
                        <input type="text" name="referral_code" placeholder="Enter referral code if you have one">
                        <small class="referral-help-text">
                            <i class="fas fa-info-circle"></i> Enter a friend's referral code to get 25 bonus points and a welcome coupon!
                        </small>
                    </div>
                    
                    <div class="input-group">
                        <label>Password</label>
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="signupPassword" placeholder="Create a password" required>
                        <i class="fas fa-eye-slash password-toggle" onclick="togglePassword('signupPassword')"></i>
                    </div>
                    
                    <div class="input-group">
                        <label>Confirm Password</label>
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm your password" required>
                        <i class="fas fa-eye-slash password-toggle" onclick="togglePassword('confirmPassword')"></i>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        Create Account <i class="fas fa-user-plus"></i>
                    </button>
                </form>
            </div>
            
            <div class="footer">
                <p>Experience the magic of cinema like never before</p>
                <p style="margin-top: 5px; font-size: 11px;">✨ Book tickets, choose seats, and enjoy the show ✨</p>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching
        const tabs = document.querySelectorAll('.auth-tab');
        const forms = document.querySelectorAll('.auth-form');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(t => t.classList.remove('active'));
                forms.forEach(f => f.classList.remove('active'));
                tab.classList.add('active');
                const tabId = tab.dataset.tab + 'Form';
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggleIcon = field.nextElementSibling;
            if (field.type === 'password') {
                field.type = 'text';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            } else {
                field.type = 'password';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            }
        }
        
        // Password confirmation validation on signup form
        const signupForm = document.querySelector('#signupForm form');
        if(signupForm) {
            signupForm.addEventListener('submit', function(e) {
                const password = document.getElementById('signupPassword').value;
                const confirm = document.getElementById('confirmPassword').value;
                if(password !== confirm) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                }
            });
        }
    </script>
</body>
</html>