<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get booking details from localStorage (via URL params or session)
$selectedSeats = isset($_GET['seats']) ? $_GET['seats'] : '';
$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$hall_id = isset($_GET['hall_id']) ? (int)$_GET['hall_id'] : 0;
$show_time = isset($_GET['show_time']) ? urldecode($_GET['show_time']) : '';
$total_amount = isset($_GET['total']) ? (float)$_GET['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - Core Cinema World</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .navbar {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .nav-brand i {
            font-size: 28px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-brand h2 {
            font-size: 22px;
            color: #1f2937;
        }
        
        .nav-user {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logout-btn {
            color: #ef4444;
            text-decoration: none;
            font-size: 18px;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .terms-card {
            background: white;
            border-radius: 28px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .terms-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .terms-header i {
            font-size: 60px;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .terms-header h1 {
            font-size: 32px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .terms-header p {
            color: #6b7280;
        }
        
        .booking-summary {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #e5e7eb;
        }
        
        .summary-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .summary-label {
            font-weight: 600;
            color: #4b5563;
        }
        
        .summary-value {
            color: #667eea;
            font-weight: 600;
        }
        
        .terms-content {
            margin: 30px 0;
        }
        
        .terms-section {
            margin-bottom: 25px;
        }
        
        .terms-section h3 {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .terms-section h3 i {
            color: #667eea;
        }
        
        .terms-section p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .terms-section ul {
            padding-left: 20px;
            margin: 10px 0;
        }
        
        .terms-section li {
            color: #6b7280;
            margin: 8px 0;
            font-size: 14px;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 25px 0;
            padding: 15px;
            background: #f0fdf4;
            border-radius: 12px;
            border: 1px solid #bbf7d0;
        }
        
        .checkbox-container input {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #28a745;
        }
        
        .checkbox-container label {
            color: #1f2937;
            font-weight: 500;
            cursor: pointer;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn-back, .btn-proceed {
            padding: 14px 32px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
        }
        
        .btn-back:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-proceed {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-proceed:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(40, 167, 69, 0.4);
        }
        
        .btn-proceed:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .warning-text {
            color: #dc2626;
            font-size: 13px;
            margin-top: 10px;
            display: none;
        }
        
        @media (max-width: 768px) {
            .terms-card {
                padding: 25px;
            }
            
            .terms-header h1 {
                font-size: 24px;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn-back, .btn-proceed {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <i class="fas fa-film"></i>
            <h2>Core Cinema World</h2>
        </div>
        <div class="nav-user">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>
    
    <div class="container">
        <div class="terms-card">
            <div class="terms-header">
                <i class="fas fa-file-contract"></i>
                <h1>Terms & Conditions</h1>
                <p>Please read and accept our terms before proceeding with your booking</p>
            </div>
            
            <!-- Booking Summary -->
            <div class="booking-summary" id="bookingSummary">
                <div class="summary-title">
                    <i class="fas fa-ticket-alt"></i> Booking Summary
                </div>
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="summary-label">Movie:</span>
                        <span class="summary-value" id="summaryMovie">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Cinema Hall:</span>
                        <span class="summary-value" id="summaryHall">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Show Time:</span>
                        <span class="summary-value" id="summaryShowTime">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Selected Seats:</span>
                        <span class="summary-value" id="summarySeats">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Number of Seats:</span>
                        <span class="summary-value" id="summarySeatCount">-</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total Amount:</span>
                        <span class="summary-value" id="summaryTotal">-</span>
                    </div>
                </div>
            </div>
            
            <!-- Terms Content -->
            <div class="terms-content">
                <div class="terms-section">
                    <h3><i class="fas fa-ticket-alt"></i> 1. Booking Policy</h3>
                    <p>By booking tickets through Core Cinema World, you agree to the following terms:</p>
                    <ul>
                        <li>Tickets are non-refundable once purchased.</li>
                        <li>Ticket exchanges are allowed up to 2 hours before show time (subject to availability).</li>
                        <li>Each ticket is valid for one person only.</li>
                        <li>Children above 3 years require a separate ticket.</li>
                    </ul>
                </div>
                
                <div class="terms-section">
                    <h3><i class="fas fa-credit-card"></i> 2. Payment Terms</h3>
                    <ul>
                        <li>Full payment is required at the time of booking.</li>
                        <li>All prices are in Indian Rupees (INR).</li>
                        <li>Taxes and convenience fees are included in the ticket price.</li>
                        <li>We accept all major credit/debit cards, UPI, and digital wallets.</li>
                    </ul>
                </div>
                
                <div class="terms-section">
                    <h3><i class="fas fa-clock"></i> 3. Cancellation & Refund Policy</h3>
                    <ul>
                        <li>Cancellations made 24 hours before show time: 50% refund.</li>
                        <li>Cancellations made 12 hours before show time: 25% refund.</li>
                        <li>Cancellations made less than 12 hours before show time: No refund.</li>
                        <li>Refunds will be processed within 7-10 business days.</li>
                    </ul>
                </div>
                
                <div class="terms-section">
                    <h3><i class="fas fa-building"></i> 4. Cinema Hall Rules</h3>
                    <ul>
                        <li>Please arrive at least 15 minutes before the show time.</li>
                        <li>Outside food and drinks are not allowed inside the cinema hall.</li>
                        <li>Mobile phones must be switched off or set to silent mode.</li>
                        <li>Recording of any kind inside the auditorium is strictly prohibited.</li>
                        <li>Management reserves the right to refuse entry.</li>
                    </ul>
                </div>
                
                <div class="terms-section">
                    <h3><i class="fas fa-user-shield"></i> 5. Privacy & Data Security</h3>
                    <ul>
                        <li>Your personal information is protected and not shared with third parties.</li>
                        <li>Payment information is encrypted using SSL technology.</li>
                        <li>We use cookies to enhance your browsing experience.</li>
                    </ul>
                </div>
                
                <div class="terms-section">
                    <h3><i class="fas fa-gavel"></i> 6. General Terms</h3>
                    <ul>
                        <li>Core Cinema World reserves the right to modify these terms at any time.</li>
                        <li>In case of any dispute, the decision of management shall be final.</li>
                        <li>These terms are governed by the laws of India.</li>
                    </ul>
                </div>
            </div>
            
            <!-- Acceptance Checkbox -->
            <div class="checkbox-container">
                <input type="checkbox" id="acceptTerms" onchange="toggleProceedButton()">
                <label for="acceptTerms">
                    <i class="fas fa-check-circle" style="color: #28a745;"></i> 
                    I have read and agree to the Terms & Conditions
                </label>
            </div>
            <div id="warningText" class="warning-text">
                <i class="fas fa-exclamation-triangle"></i> Please accept the Terms & Conditions to proceed.
            </div>
            
            <!-- Buttons -->
            <div class="button-group">
                <a href="seat-selection.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Seat Selection
                </a>
                <button id="proceedBtn" class="btn-proceed" disabled onclick="proceedToPayment()">
                    Proceed to Payment <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
    
    <script>
        let selectedSeats = [];
        let movieId = null;
        let hallId = null;
        let movieTitle = '';
        let hallName = '';
        let showTime = '';
        let priceMultiplier = 1;
        
        // Load data from localStorage
        document.addEventListener('DOMContentLoaded', function() {
            try {
                selectedSeats = JSON.parse(localStorage.getItem('selectedSeats') || '[]');
                movieId = localStorage.getItem('movieId');
                hallId = localStorage.getItem('hallId');
                movieTitle = localStorage.getItem('movieTitle') || 'Movie';
                hallName = localStorage.getItem('hallName') || 'Cinema';
                showTime = localStorage.getItem('showTime') || 'Not Selected';
                priceMultiplier = parseFloat(localStorage.getItem('priceMultiplier') || '1');
                
                if(selectedSeats.length === 0) {
                    alert('No seats selected! Please select seats first.');
                    window.location.href = 'home.php';
                    return;
                }
                
                const total = selectedSeats.reduce((sum, s) => sum + s.price, 0);
                
                document.getElementById('summaryMovie').textContent = movieTitle;
                document.getElementById('summaryHall').textContent = hallName;
                document.getElementById('summaryShowTime').textContent = showTime;
                document.getElementById('summarySeats').textContent = selectedSeats.map(s => s.seat).join(', ');
                document.getElementById('summarySeatCount').textContent = selectedSeats.length;
                document.getElementById('summaryTotal').textContent = '₹' + total;
                
            } catch(e) {
                console.error('Error loading data:', e);
                alert('Error loading booking data. Please go back and select seats again.');
                window.location.href = 'home.php';
            }
        });
        
        function toggleProceedButton() {
            const acceptCheckbox = document.getElementById('acceptTerms');
            const proceedBtn = document.getElementById('proceedBtn');
            const warningText = document.getElementById('warningText');
            
            if(acceptCheckbox.checked) {
                proceedBtn.disabled = false;
                warningText.style.display = 'none';
            } else {
                proceedBtn.disabled = true;
                warningText.style.display = 'block';
            }
        }
        
        function proceedToPayment() {
            const acceptCheckbox = document.getElementById('acceptTerms');
            
            if(!acceptCheckbox.checked) {
                document.getElementById('warningText').style.display = 'block';
                return;
            }
            
            // Store that terms are accepted
            localStorage.setItem('termsAccepted', 'true');
            
            // Redirect to payment page
            window.location.href = 'payment.php';
        }
    </script>
</body>
</html>