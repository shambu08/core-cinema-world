<?php
// This is the intro/splash page that appears before login
// No session check needed as this is the first page users see
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Core Cinema World - Welcome</title>
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
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            overflow: hidden;
            position: relative;
        }
        
        /* Animated Background */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }
        
        .bg-animation .circle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.03);
            animation: float 20s infinite ease-in-out;
        }
        
        .bg-animation .circle:nth-child(1) {
            width: 300px;
            height: 300px;
            top: -100px;
            left: -100px;
            animation-duration: 25s;
        }
        
        .bg-animation .circle:nth-child(2) {
            width: 500px;
            height: 500px;
            bottom: -200px;
            right: -200px;
            animation-duration: 30s;
        }
        
        .bg-animation .circle:nth-child(3) {
            width: 200px;
            height: 200px;
            top: 50%;
            left: 50%;
            animation-duration: 20s;
        }
        
        .bg-animation .circle:nth-child(4) {
            width: 150px;
            height: 150px;
            top: 20%;
            right: 10%;
            animation-duration: 15s;
        }
        
        .bg-animation .circle:nth-child(5) {
            width: 250px;
            height: 250px;
            bottom: 10%;
            left: 20%;
            animation-duration: 35s;
        }
        
        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(180deg); }
            100% { transform: translateY(0) rotate(360deg); }
        }
        
        /* Main Container */
        .splash-container {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        /* Logo Card */
        .logo-card {
            text-align: center;
            animation: fadeInScale 1s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.8) translateY(30px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        /* Animated Logo with 🎬 Emoji */
        .logo-wrapper {
            margin-bottom: 30px;
            position: relative;
        }
        
        /* Outer Glow Ring */
        .glow-ring {
            position: absolute;
            top: -15px;
            left: -15px;
            right: -15px;
            bottom: -15px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(102,126,234,0.2), transparent);
            animation: glowPulse 3s infinite;
        }
        
        @keyframes glowPulse {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.05); }
        }
        
        /* Main Logo Circle */
        .logo-icon {
            width: 160px;
            height: 160px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            animation: gentlePulse 3s infinite ease-in-out;
            box-shadow: 0 0 50px rgba(102, 126, 234, 0.4);
            position: relative;
            z-index: 2;
        }
        
        @keyframes gentlePulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 40px rgba(102,126,234,0.4); }
            50% { transform: scale(1.03); box-shadow: 0 0 60px rgba(102,126,234,0.6); }
        }
        
        /* Decorative Rings */
        .ring {
            position: absolute;
            border-radius: 50%;
            border: 2px solid rgba(102, 126, 234, 0.3);
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            animation: ringExpand 4s infinite;
        }
        
        .ring-1 {
            width: 180px;
            height: 180px;
            animation-delay: 0s;
        }
        
        .ring-2 {
            width: 200px;
            height: 200px;
            animation-delay: 0.5s;
        }
        
        .ring-3 {
            width: 220px;
            height: 220px;
            animation-delay: 1s;
        }
        
        .ring-4 {
            width: 240px;
            height: 240px;
            animation-delay: 1.5s;
        }
        
        @keyframes ringExpand {
            0% {
                opacity: 0.5;
                transform: translate(-50%, -50%) scale(1);
            }
            100% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(1.2);
            }
        }
        
        /* Logo Inner Content with 🎬 Emoji */
        .logo-inner {
            text-align: center;
        }
        
        .logo-emoji {
            font-size: 70px;
            filter: drop-shadow(0 5px 15px rgba(0,0,0,0.3));
            animation: emojiBounce 2s infinite ease-in-out;
            display: inline-block;
        }
        
        @keyframes emojiBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        
        /* Title Section */
        .logo-title {
            margin-top: 30px;
        }
        
        .logo-title h1 {
            font-size: 44px;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, #a78bfa 50%, #c4b5fd 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
            margin-bottom: 10px;
        }
        
        .logo-title .subtitle {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 4px;
            text-transform: uppercase;
        }
        
        /* Divider */
        .divider {
            width: 60px;
            height: 2px;
            background: linear-gradient(90deg, transparent, #667eea, #764ba2, transparent);
            margin: 20px auto;
        }
        
        /* Tagline */
        .tagline {
            margin: 20px 0;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.4);
            letter-spacing: 1px;
        }
        
        /* GO Button */
        .go-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 14px 40px;
            font-size: 18px;
            font-weight: 600;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 25px;
            text-decoration: none;
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .go-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.6);
        }
        
        .go-button i {
            transition: transform 0.3s;
        }
        
        .go-button:hover i {
            transform: translateX(5px);
        }
        
        /* Movie Quotes Slider */
        .quotes-slider {
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
            overflow: hidden;
            height: 50px;
        }
        
        .quote-slide {
            position: absolute;
            width: 100%;
            text-align: center;
            opacity: 0;
            transition: opacity 1s ease;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
            font-style: italic;
        }
        
        .quote-slide.active {
            opacity: 1;
        }
        
        .quote-slide i {
            color: #f59e0b;
            margin-right: 8px;
        }
        
        /* Sparkle Effects around Logo */
        .sparkle {
            position: absolute;
            width: 6px;
            height: 6px;
            background: #fff;
            border-radius: 50%;
            opacity: 0;
            animation: sparkle 2s infinite;
        }
        
        .sparkle-1 { top: 10px; left: 20px; animation-delay: 0s; }
        .sparkle-2 { top: 30px; right: 15px; animation-delay: 0.5s; }
        .sparkle-3 { bottom: 20px; left: 30px; animation-delay: 1s; }
        .sparkle-4 { bottom: 40px; right: 25px; animation-delay: 1.5s; }
        
        @keyframes sparkle {
            0%, 100% { opacity: 0; transform: scale(0.5); }
            50% { opacity: 1; transform: scale(1.2); background: #f59e0b; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .logo-icon {
                width: 120px;
                height: 120px;
            }
            
            .logo-emoji {
                font-size: 50px;
            }
            
            .logo-title h1 {
                font-size: 32px;
            }
            
            .go-button {
                padding: 10px 30px;
                font-size: 16px;
            }
            
            .ring-1 { width: 140px; height: 140px; }
            .ring-2 { width: 160px; height: 160px; }
            .ring-3 { width: 180px; height: 180px; }
            .ring-4 { width: 200px; height: 200px; }
        }
    </style>
</head>
<body>
    <div class="bg-animation">
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
    </div>
    
    <div class="splash-container">
        <div class="logo-card">
            <div class="logo-wrapper">
                <!-- Sparkle Effects -->
                <div class="sparkle sparkle-1"></div>
                <div class="sparkle sparkle-2"></div>
                <div class="sparkle sparkle-3"></div>
                <div class="sparkle sparkle-4"></div>
                
                <!-- Expanding Rings -->
                <div class="ring ring-1"></div>
                <div class="ring ring-2"></div>
                <div class="ring ring-3"></div>
                <div class="ring ring-4"></div>
                
                <!-- Glow Ring -->
                <div class="glow-ring"></div>
                
                <!-- Main Logo with 🎬 Emoji -->
                <div class="logo-icon">
                    <div class="logo-inner">
                        <div class="logo-emoji">🎬</div>
                    </div>
                </div>
            </div>
            
            <div class="logo-title">
                <h1>Core Cinema World</h1>
                <div class="subtitle">CINEMATIC EXPERIENCE</div>
            </div>
            
            <div class="divider"></div>
            
            <div class="tagline">
                <i class="fas fa-ticket-alt"></i> Book Tickets | Choose Seats | Enjoy Movies <i class="fas fa-popcorn"></i>
            </div>
            
            <!-- GO Button -->
            <button class="go-button" onclick="goToLogin()">
                Begin Journey <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>
    
    <div class="quotes-slider" id="quotesSlider">
        <div class="quote-slide active">
            <i class="fas fa-quote-left"></i> "Movies are a passport to worlds unknown, where dreams take flight and magic becomes real."
        </div>
        <div class="quote-slide">
            <i class="fas fa-quote-left"></i> "Cinema is the most beautiful fraud in the world."
        </div>
        <div class="quote-slide">
            <i class="fas fa-quote-left"></i> "A good film is when the price of the dinner, the theatre admission and the babysitter were worth it."
        </div>
        <div class="quote-slide">
            <i class="fas fa-quote-left"></i> "Movies touch our hearts and awaken our dreams..."
        </div>
        <div class="quote-slide">
            <i class="fas fa-quote-left"></i> "This is where cinema comes alive."
        </div>
    </div>
    
    <script>
        // Go to login page when button is clicked
        function goToLogin() {
            window.location.href = 'login.php';
        }
        
        // Quote slider
        const quotes = document.querySelectorAll('.quote-slide');
        let currentQuote = 0;
        
        setInterval(function() {
            quotes[currentQuote].classList.remove('active');
            currentQuote = (currentQuote + 1) % quotes.length;
            quotes[currentQuote].classList.add('active');
        }, 3000);
        
        // Add click effect to button
        const goBtn = document.querySelector('.go-button');
        if(goBtn) {
            goBtn.addEventListener('click', function(e) {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        }
        
        // Hover effect on logo
        const logo = document.querySelector('.logo-icon');
        if(logo) {
            logo.addEventListener('mouseenter', function() {
                const emoji = document.querySelector('.logo-emoji');
                if(emoji) {
                    emoji.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        emoji.style.transform = '';
                    }, 300);
                }
            });
        }
    </script>
</body>
</html>