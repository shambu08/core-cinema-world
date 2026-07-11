<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'php/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Core Cinema World</title>
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
        
        /* Animated Circles Background */
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
        
        .circle:nth-child(1) {
            width: 300px;
            height: 300px;
            top: -100px;
            left: -100px;
            animation-duration: 25s;
        }
        
        .circle:nth-child(2) {
            width: 500px;
            height: 500px;
            bottom: -200px;
            right: -200px;
            animation-duration: 30s;
        }
        
        .circle:nth-child(3) {
            width: 200px;
            height: 200px;
            top: 50%;
            left: 50%;
            animation-duration: 20s;
        }
        
        .circle:nth-child(4) {
            width: 150px;
            height: 150px;
            top: 20%;
            right: 10%;
            animation-duration: 15s;
        }
        
        .circle:nth-child(5) {
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
        
        /* Floating Icons */
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
        
        /* Main Container */
        .welcome-container {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
        }
        
        /* White Card */
        .welcome-card {
            background: white;
            border-radius: 28px;
            padding: 50px;
            max-width: 900px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: fadeInScale 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(30px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
        
        /* Animated Logo */
        .logo {
            margin-bottom: 30px;
            position: relative;
        }
        
        .logo-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            animation: pulse 2s infinite;
            box-shadow: 0 0 30px rgba(102, 126, 234, 0.3);
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 30px rgba(102, 126, 234, 0.3); }
            50% { transform: scale(1.05); box-shadow: 0 0 50px rgba(102, 126, 234, 0.5); }
        }
        
        .logo-icon i {
            font-size: 50px;
            color: white;
        }
        
        /* Welcome Text */
        .welcome-text h1 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }
        
        /* Quote Section */
        .quote-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 24px;
            padding: 35px;
            margin: 30px 0;
            position: relative;
            transition: all 0.3s;
        }
        
        .quote-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .quote-icon {
            font-size: 40px;
            color: #667eea;
            opacity: 0.2;
            position: absolute;
            top: 20px;
            left: 25px;
        }
        
        .quote-icon.right {
            left: auto;
            right: 25px;
            top: auto;
            bottom: 20px;
            transform: rotate(180deg);
        }
        
        .quote-text {
            font-size: 28px;
            font-weight: 700;
            color: #1f2937;
            line-height: 1.4;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .quote-author {
            font-size: 16px;
            color: #667eea;
            font-weight: 600;
            position: relative;
            z-index: 1;
        }
        
        .quote-author i {
            color: #f59e0b;
            margin-right: 8px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 35px 0;
        }
        
        .stat-card {
            background: #f9fafb;
            padding: 25px 15px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid #e5e7eb;
            cursor: default;
        }
        
        .stat-card:hover {
            transform: translateY(-8px);
            border-color: #667eea;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            font-size: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 12px;
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
        }
        
        /* CTA Button */
        .cta-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 16px 45px;
            font-size: 18px;
            font-weight: 700;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
            text-decoration: none;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.4);
        }
        
        .cta-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .cta-button:hover::before {
            left: 100%;
        }
        
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px -5px rgba(102, 126, 234, 0.5);
        }
        
        .cta-button i {
            font-size: 20px;
            transition: transform 0.3s;
        }
        
        .cta-button:hover i {
            transform: translateX(8px);
        }
        
        /* Logout Link */
        .logout-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 25px;
            color: #9ca3af;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
            padding: 8px 16px;
            border-radius: 30px;
            background: #f3f4f6;
        }
        
        .logout-link:hover {
            color: #ef4444;
            background: #fee2e2;
        }
        
        /* Footer */
        .footer {
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #9ca3af;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .welcome-card {
                padding: 30px 20px;
            }
            
            .welcome-text h1 {
                font-size: 32px;
            }
            
            .quote-text {
                font-size: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .stat-number {
                font-size: 28px;
            }
            
            .cta-button {
                padding: 12px 30px;
                font-size: 16px;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quote-icon {
                font-size: 30px;
            }
            
            .quote-text {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Circles Background -->
    <div class="bg-circles">
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
        <div class="circle"></div>
    </div>
    
    <!-- Floating Icons -->
    <div class="floating-icon"><i class="fas fa-film"></i></div>
    <div class="floating-icon"><i class="fas fa-ticket-alt"></i></div>
    <div class="floating-icon"><i class="fas fa-clapperboard"></i></div>
    <div class="floating-icon"><i class="fas fa-video"></i></div>
    
    <div class="welcome-container">
        <div class="welcome-card">
            <div class="logo">
                <div class="logo-icon">
                    <i class="fas fa-film"></i>
                </div>
            </div>
            
            <div class="welcome-text">
                <h1>Welcome to Core Cinema World</h1>
            </div>
            
            <!-- Fixed Quote Section -->
            <div class="quote-section">
                <i class="fas fa-quote-left quote-icon"></i>
                <div class="quote-text">
                    "This is where cinema comes alive."
                </div>
                <div class="quote-author">
                    <i class="fas fa-star"></i> Core Cinema World
                </div>
                <i class="fas fa-quote-right quote-icon right"></i>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-film"></i></div>
                    <div class="stat-number" id="movieCount">49+</div>
                    <div class="stat-label">Movies Available</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-building"></i></div>
                    <div class="stat-number" id="hallCount">16+</div>
                    <div class="stat-label">Cinema Halls</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-city"></i></div>
                    <div class="stat-number" id="cityCount">10</div>
                    <div class="stat-label">Cities</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-language"></i></div>
                    <div class="stat-number" id="langCount">6</div>
                    <div class="stat-label">Languages</div>
                </div>
            </div>
            
            <!-- CTA Button -->
            <a href="home.php" class="cta-button">
                Start Booking <i class="fas fa-arrow-right"></i>
            </a>
            
            <div>
                <a href="logout.php" class="logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
            
            <div class="footer">
                <p>Experience the magic of cinema like never before</p>
                <p style="margin-top: 5px; font-size: 11px;">✨ Book tickets, choose seats, and enjoy the show ✨</p>
            </div>
        </div>
    </div>
    
    <script>
        // Animate numbers counting up
        function animateNumber(element, target, suffix = '+') {
            let current = 0;
            const increment = Math.ceil(target / 50);
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    element.textContent = target + suffix;
                    clearInterval(timer);
                } else {
                    element.textContent = current + suffix;
                }
            }, 30);
        }
        
        // Fetch real stats from database
        function fetchStats() {
            fetch('php/get_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.movieCount) {
                        animateNumber(document.getElementById('movieCount'), data.movieCount, '+');
                    }
                    if (data.hallCount) {
                        animateNumber(document.getElementById('hallCount'), data.hallCount, '+');
                    }
                    if (data.cityCount) {
                        animateNumber(document.getElementById('cityCount'), data.cityCount, '');
                    }
                    if (data.langCount) {
                        animateNumber(document.getElementById('langCount'), data.langCount, '');
                    }
                })
                .catch(error => {
                    console.log('Using default stats');
                    animateNumber(document.getElementById('movieCount'), 49, '+');
                    animateNumber(document.getElementById('hallCount'), 16, '+');
                });
        }
        
        // Fetch stats on load
        fetchStats();
        
        // Add hover effect to stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>