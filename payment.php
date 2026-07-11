<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'php/db_connect.php';

// Get user details for loyalty points
$userStmt = $pdo->prepare("SELECT loyalty_points, full_name FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch();
$userPoints = $user['loyalty_points'] ?? 0;

// Get selected seats from session
$selectedSeats = isset($_SESSION['selected_seats']) ? $_SESSION['selected_seats'] : [];
$totalAmount = isset($_SESSION['total_amount']) ? $_SESSION['total_amount'] : 0;

// Points discount logic
$pointsDiscount = 0;
$appliedPoints = 0;
if(isset($_POST['apply_points']) && isset($_POST['points_to_use'])) {
    $pointsToUse = (int)$_POST['points_to_use'];
    if($pointsToUse >= 100 && $pointsToUse <= $userPoints && $pointsToUse % 100 == 0) {
        $appliedPoints = $pointsToUse;
        $pointsDiscount = $pointsToUse / 10;
        $totalAmount = max(0, $totalAmount - $pointsDiscount);
        
        $_SESSION['points_discount'] = $pointsDiscount;
        $_SESSION['applied_points'] = $appliedPoints;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Core Cinema World</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .payment-container {
            max-width: 700px;
            margin: 30px auto;
            background: white;
            border-radius: 28px;
            padding: 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .payment-header i {
            font-size: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .payment-header h2 {
            color: #1f2937;
            margin: 10px 0 5px;
        }
        
        .booking-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .summary-row:last-child {
            border-bottom: none;
        }
        
        .summary-label {
            font-weight: 600;
            color: #495057;
        }
        
        .summary-value {
            color: #667eea;
            font-weight: 600;
        }
        
        .total-row {
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #28a745;
            font-size: 20px;
        }
        
        .total-row .summary-label,
        .total-row .summary-value {
            font-size: 22px;
            font-weight: 800;
            color: #28a745;
        }
        
        .discount-row {
            color: #28a745;
        }
        
        .payment-methods {
            margin: 25px 0;
        }
        
        .payment-methods h3 {
            margin-bottom: 15px;
            color: #1f2937;
            font-size: 18px;
        }
        
        .method-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }
        
        .method-tab {
            padding: 12px 24px;
            background: #f3f4f6;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .method-tab i {
            font-size: 18px;
        }
        
        .method-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .method-tab:hover:not(.active) {
            background: #e5e7eb;
            transform: translateY(-2px);
        }
        
        .payment-form {
            background: #f9fafb;
            border-radius: 20px;
            padding: 25px;
            margin-top: 15px;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .card-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .pay-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .pay-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(40, 167, 69, 0.4);
        }
        
        .pay-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .loyalty-section {
            background: #fef3c7;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .points-input {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .points-input input {
            flex: 1;
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
        }
        
        .apply-points-btn {
            background: #f59e0b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .friend-input {
            margin-bottom: 15px;
            padding: 15px;
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
        }
        
        .friend-input h4 {
            margin-bottom: 10px;
            color: #667eea;
        }
        
        .add-friend-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
            width: 100%;
        }
        
        .split-summary {
            background: #e8f0fe;
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .split-summary p {
            margin: 5px 0;
        }
        
        .timer-display {
            background: #fee2e2;
            border-radius: 12px;
            padding: 12px;
            margin: 15px 0;
            text-align: center;
            font-weight: bold;
            color: #dc2626;
            font-size: 18px;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            color: #667eea;
        }
        
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            border-radius: 24px;
            padding: 30px;
            max-width: 550px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
        }
        
        .payment-link-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .payment-link-item:last-child {
            border-bottom: none;
        }
        
        .mark-paid-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .mark-paid-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .status-paid {
            color: #28a745;
            font-weight: bold;
        }
        
        .status-pending {
            color: #f59e0b;
            font-weight: bold;
        }
        
        .progress-bar {
            background: #e8f0fe;
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
        }
        
        .warning-message {
            background: #fee2e2;
            border-radius: 8px;
            padding: 10px;
            margin: 10px 0;
            text-align: center;
            font-size: 13px;
            color: #dc2626;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-cancel {
            background: #dc2626;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            flex: 1;
        }
        
        .btn-close {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .payment-container {
                margin: 15px;
                padding: 20px;
            }
            
            .card-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .method-tab {
                padding: 8px 16px;
                font-size: 12px;
            }
            
            .points-input {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <i class="fas fa-credit-card"></i>
            <h2>Secure Payment</h2>
            <p>Choose your preferred payment method</p>
        </div>
        
        <div class="booking-summary" id="bookingSummary">
            <div class="summary-row">
                <span class="summary-label"><i class="fas fa-film"></i> Movie:</span>
                <span class="summary-value" id="movieTitle">-</span>
            </div>
            <div class="summary-row">
                <span class="summary-label"><i class="fas fa-building"></i> Cinema:</span>
                <span class="summary-value" id="hallName">-</span>
            </div>
            <div class="summary-row">
                <span class="summary-label"><i class="fas fa-ticket-alt"></i> Selected Seats:</span>
                <span class="summary-value" id="seatList">-</span>
            </div>
            <div class="summary-row">
                <span class="summary-label"><i class="fas fa-chair"></i> Number of Seats:</span>
                <span class="summary-value" id="seatCount">-</span>
            </div>
            <div id="discountSummary"></div>
            <div class="summary-row total-row">
                <span class="summary-label"><i class="fas fa-rupee-sign"></i> Total Amount:</span>
                <span class="summary-value">₹<span id="totalAmount">0</span></span>
            </div>
        </div>
        
        <!-- Loyalty Points Discount Section -->
        <div class="loyalty-section">
            <h4><i class="fas fa-coins"></i> Use Loyalty Points for Discount</h4>
            <p>You have <strong><?php echo number_format($userPoints); ?></strong> points (≈ ₹<?php echo floor($userPoints / 10); ?> value)</p>
            <form method="POST" action="">
                <div class="points-input">
                    <input type="number" name="points_to_use" placeholder="Enter points (100 points = ₹10)" min="100" max="<?php echo $userPoints; ?>" step="100">
                    <button type="submit" name="apply_points" class="apply-points-btn">Apply Points</button>
                </div>
                <small><i class="fas fa-info-circle"></i> Minimum 100 points, multiples of 100 only</small>
            </form>
        </div>
        
        <div class="payment-methods">
            <h3><i class="fas fa-wallet"></i> Select Payment Method</h3>
            <div class="method-tabs">
                <button class="method-tab active" data-method="card">
                    <i class="fab fa-cc-visa"></i> Card
                </button>
                <button class="method-tab" data-method="upi">
                    <i class="fas fa-mobile-alt"></i> UPI
                </button>
                <button class="method-tab" data-method="netbanking">
                    <i class="fas fa-university"></i> Net Banking
                </button>
                <button class="method-tab" data-method="wallet">
                    <i class="fas fa-wallet"></i> Wallet
                </button>
                <button class="method-tab" data-method="split">
                    <i class="fas fa-users"></i> Split Payment
                </button>
            </div>
            
            <!-- Credit/Debit Card Form -->
            <div id="cardForm" class="payment-form">
                <div class="form-group">
                    <label><i class="fas fa-credit-card"></i> Card Number</label>
                    <input type="text" id="cardNumber" placeholder="Enter your 16-digit card number" maxlength="19" required>
                </div>
                <div class="card-row">
                    <div class="form-group">
                        <label><i class="far fa-calendar-alt"></i> Expiry Date</label>
                        <input type="text" id="expiryDate" placeholder="MM/YY" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> CVV</label>
                        <input type="password" id="cvv" placeholder="3-digit code" maxlength="3" required>
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Cardholder Name</label>
                    <input type="text" id="cardName" placeholder="Name as on card" required>
                </div>
            </div>
            
            <!-- UPI Form -->
            <div id="upiForm" class="payment-form" style="display: none;">
                <div class="form-group">
                    <label><i class="fas fa-id-card"></i> UPI ID</label>
                    <input type="text" id="upiId" placeholder="Enter your UPI ID (username@bank)" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-mobile-alt"></i> UPI App</label>
                    <select id="upiApp">
                        <option value="">Select UPI App</option>
                        <option value="Google Pay">Google Pay</option>
                        <option value="PhonePe">PhonePe</option>
                        <option value="Paytm">Paytm</option>
                        <option value="Amazon Pay">Amazon Pay</option>
                        <option value="BHIM">BHIM</option>
                    </select>
                </div>
            </div>
            
            <!-- Net Banking Form -->
            <div id="netbankingForm" class="payment-form" style="display: none;">
                <div class="form-group">
                    <label><i class="fas fa-university"></i> Select Bank</label>
                    <select id="bankSelect">
                        <option value="">-- Select Your Bank --</option>
                        <option value="State Bank of India">State Bank of India (SBI)</option>
                        <option value="HDFC Bank">HDFC Bank</option>
                        <option value="ICICI Bank">ICICI Bank</option>
                        <option value="Axis Bank">Axis Bank</option>
                        <option value="Kotak Mahindra Bank">Kotak Mahindra Bank</option>
                        <option value="Yes Bank">Yes Bank</option>
                        <option value="Punjab National Bank">Punjab National Bank</option>
                        <option value="Canara Bank">Canara Bank</option>
                        <option value="Bank of Baroda">Bank of Baroda</option>
                        <option value="Union Bank">Union Bank</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Account Holder Name</label>
                    <input type="text" id="accountName" placeholder="Enter account holder name" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Internet Banking Password</label>
                    <input type="password" id="netPassword" placeholder="Enter your net banking password" required>
                </div>
            </div>
            
            <!-- Wallet Form -->
            <div id="walletForm" class="payment-form" style="display: none;">
                <div class="form-group">
                    <label><i class="fas fa-wallet"></i> Select Wallet</label>
                    <select id="walletSelect">
                        <option value="">-- Select Wallet --</option>
                        <option value="Paytm Wallet">Paytm Wallet</option>
                        <option value="Amazon Pay Wallet">Amazon Pay Wallet</option>
                        <option value="FreeCharge Wallet">FreeCharge Wallet</option>
                        <option value="MobiKwik Wallet">MobiKwik Wallet</option>
                        <option value="PhonePe Wallet">PhonePe Wallet</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Wallet Mobile/Email</label>
                    <input type="text" id="walletId" placeholder="Enter registered mobile number or email" required>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Wallet PIN</label>
                    <input type="password" id="walletPin" placeholder="Enter your wallet PIN" maxlength="6" required>
                </div>
            </div>
            
            <!-- Split Payment Form - DYNAMIC based on number of seats -->
            <div id="splitForm" class="payment-form" style="display: none;">
                <h4 style="margin-bottom: 15px;"><i class="fas fa-users"></i> Split Payment with Friends</h4>
                <p style="margin-bottom: 15px; color: #6b7280;">
                    You have booked <strong id="splitSeatCount">0</strong> seat(s). 
                    Each friend will pay for their assigned seat.
                </p>
                
                <div id="friendInputsContainer"></div>
                
                <div id="splitSummary" class="split-summary" style="display: none;">
                    <h4>Split Summary</h4>
                    <p>Total Amount: ₹<span id="summaryTotal">0</span></p>
                    <p>Number of Friends (Seats): <span id="summaryFriends">0</span></p>
                    <p>Each pays: ₹<span id="summaryEach">0</span></p>
                </div>
                
                <button type="button" class="add-friend-btn" onclick="initiateSplitPayment()">
                    <i class="fas fa-share-alt"></i> Send Payment Requests
                </button>
            </div>
        </div>
        
        <button class="pay-btn" id="mainPayBtn" onclick="processPayment()">
            <i class="fas fa-lock"></i> Pay Now
        </button>
        
        <a href="home.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Cancel & Go Back
        </a>
    </div>
    
    <script>
        let selectedSeats = [];
        let movieId = null;
        let hallId = null;
        let movieTitle = '';
        let hallName = '';
        let currentPaymentMethod = 'card';
        let totalAmount = 0;
        let originalTotal = 0;
        let pointsDiscount = <?php echo $pointsDiscount ?? 0; ?>;
        let appliedPoints = <?php echo $appliedPoints ?? 0; ?>;
        let splitFriends = [];
        let paymentTimer = null;
        let timeLeft = 600; // 10 minutes in seconds
        let modalOpen = false;
        
        // Load data from localStorage
        document.addEventListener('DOMContentLoaded', function() {
            try {
                selectedSeats = JSON.parse(localStorage.getItem('selectedSeats') || '[]');
                movieId = localStorage.getItem('movieId');
                hallId = localStorage.getItem('hallId');
                movieTitle = localStorage.getItem('movieTitle') || 'Movie';
                hallName = localStorage.getItem('hallName') || 'Selected Cinema';
                
                if(selectedSeats.length === 0) {
                    alert('No seats selected! Please select seats first.');
                    window.location.href = 'home.php';
                    return;
                }
                
                // Calculate total
                originalTotal = selectedSeats.reduce((sum, s) => sum + s.price, 0);
                totalAmount = originalTotal - pointsDiscount;
                if(totalAmount < 0) totalAmount = 0;
                
                // Update summary
                document.getElementById('movieTitle').textContent = movieTitle;
                document.getElementById('hallName').textContent = hallName;
                document.getElementById('seatList').textContent = selectedSeats.map(s => s.seat).join(', ');
                document.getElementById('seatCount').textContent = selectedSeats.length;
                document.getElementById('splitSeatCount').textContent = selectedSeats.length;
                
                // Show discounts
                let discountHtml = '';
                if(pointsDiscount > 0) {
                    discountHtml += `<div class="summary-row discount-row">
                        <span><i class="fas fa-coins"></i> Points Discount (${appliedPoints} pts):</span>
                        <span>- ₹${pointsDiscount}</span>
                    </div>`;
                }
                document.getElementById('discountSummary').innerHTML = discountHtml;
                document.getElementById('totalAmount').textContent = totalAmount;
                
                // Generate split friend inputs based on number of seats
                generateSplitFriendInputs();
                
            } catch(e) {
                console.error('Error loading data:', e);
                alert('Error loading booking data. Please go back and select seats again.');
                window.location.href = 'home.php';
            }
        });
        
        // Generate friend inputs based on number of seats (1 seat = 1 friend)
        function generateSplitFriendInputs() {
            const numSeats = selectedSeats.length;
            const perPerson = totalAmount / numSeats;
            const container = document.getElementById('friendInputsContainer');
            
            let html = '<h4 style="margin: 15px 0 10px 0;">Friend Details (One friend per seat)</h4>';
            for(let i = 0; i < numSeats; i++) {
                const seatNumber = selectedSeats[i]?.seat || `Seat ${i+1}`;
                html += `
                    <div class="friend-input" data-friend-index="${i+1}">
                        <h4>Friend ${i+1} - <span style="color: #28a745;">${seatNumber}</span></h4>
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" id="friend_name_${i+1}" placeholder="Enter friend's full name" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" id="friend_email_${i+1}" placeholder="friend@example.com" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number (Optional)</label>
                            <input type="tel" id="friend_phone_${i+1}" placeholder="Enter phone number">
                        </div>
                        <div class="form-group">
                            <label>Assigned Seat</label>
                            <input type="text" value="${seatNumber}" readonly disabled style="background: #e8f0fe; font-weight: bold;">
                        </div>
                        <div class="form-group">
                            <label>Amount to Pay</label>
                            <input type="text" value="₹${perPerson.toFixed(2)}" readonly disabled style="background: #e8f0fe; font-weight: bold; color: #28a745;">
                        </div>
                    </div>
                `;
            }
            container.innerHTML = html;
            
            // Update split summary
            document.getElementById('splitSummary').style.display = 'block';
            document.getElementById('summaryTotal').textContent = totalAmount;
            document.getElementById('summaryFriends').textContent = numSeats;
            document.getElementById('summaryEach').textContent = perPerson.toFixed(2);
        }
        
        // Initiate split payment - collect friend details and show modal
        function initiateSplitPayment() {
            const numSeats = selectedSeats.length;
            const perPerson = totalAmount / numSeats;
            const friends = [];
            
            for(let i = 1; i <= numSeats; i++) {
                const name = document.getElementById(`friend_name_${i}`).value.trim();
                const email = document.getElementById(`friend_email_${i}`).value.trim();
                const phone = document.getElementById(`friend_phone_${i}`)?.value.trim() || '';
                const seat = selectedSeats[i-1]?.seat || `Seat ${i}`;
                
                if(!name || !email) {
                    alert(`Please enter name and email for Friend ${i} (${seat})`);
                    return;
                }
                
                // Basic email validation
                if(!email.includes('@') || !email.includes('.')) {
                    alert(`Please enter a valid email address for Friend ${i}`);
                    return;
                }
                
                friends.push({
                    id: i,
                    name: name,
                    email: email,
                    phone: phone,
                    seat: seat,
                    amount: perPerson,
                    paid: false
                });
            }
            
            splitFriends = friends;
            showSplitPaymentModal();
        }
        
        // Show modal with payment requests and timer
        function showSplitPaymentModal() {
            modalOpen = true;
            let paymentLinksHtml = '';
            
            splitFriends.forEach((friend, index) => {
                paymentLinksHtml += `
                    <div class="payment-link-item">
                        <div>
                            <strong>${escapeHtml(friend.name)}</strong>
                            <div style="font-size: 12px; color: #6b7280;">Seat: ${friend.seat}</div>
                            <div style="font-size: 12px; color: #6b7280;">${escapeHtml(friend.email)}</div>
                            ${friend.phone ? `<div style="font-size: 12px; color: #6b7280;">Phone: ${escapeHtml(friend.phone)}</div>` : ''}
                            <div style="font-weight: bold; color: #28a745; margin-top: 5px;">₹${friend.amount.toFixed(2)}</div>
                        </div>
                        <div style="text-align: right;">
                            <div id="status_${index}" class="status-pending">⏳ Pending</div>
                            <button onclick="markFriendPaid(${index})" id="pay_btn_${index}" class="mark-paid-btn">
                                Mark as Paid
                            </button>
                        </div>
                    </div>
                `;
            });
            
            const modalHtml = `
                <div id="splitPaymentModal" class="modal-overlay">
                    <div class="modal-content">
                        <h3 style="margin-bottom: 10px;"><i class="fas fa-share-alt"></i> Split Payment Requests</h3>
                        <p style="margin-bottom: 20px; color: #6b7280; font-size: 14px;">
                            Share these links with your friends. Each friend must pay their share within 10 minutes.
                        </p>
                        
                        <div id="timerDisplay" class="timer-display">
                            <i class="fas fa-hourglass-half"></i> Time remaining: <span id="timer">10:00</span>
                        </div>
                        
                        <div id="paymentLinksList">${paymentLinksHtml}</div>
                        
                        <div id="splitProgress" class="progress-bar">
                            <strong>0/${splitFriends.length} friends paid</strong>
                        </div>
                        
                        <div id="warningMessage" class="warning-message" style="display: none;">
                            <i class="fas fa-exclamation-triangle"></i> If all friends don't pay within 10 minutes, the booking will be cancelled.
                        </div>
                        
                        <div class="btn-group">
                            <button onclick="closeSplitModal(true)" class="btn-cancel">
                                <i class="fas fa-times"></i> Cancel Booking
                            </button>
                            <button onclick="closeSplitModal(false)" class="btn-close">
                                <i class="fas fa-chevron-left"></i> Close
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            startPaymentTimer();
        }
        
        // Start 10-minute countdown timer
        function startPaymentTimer() {
            timeLeft = 600; // 10 minutes = 600 seconds
            const timerDisplay = document.getElementById('timer');
            const warningMsg = document.getElementById('warningMessage');
            
            if(timerDisplay) {
                warningMsg.style.display = 'block';
                
                paymentTimer = setInterval(() => {
                    if(timeLeft <= 0) {
                        clearInterval(paymentTimer);
                        handlePaymentTimeout();
                    } else {
                        timeLeft--;
                        const minutes = Math.floor(timeLeft / 60);
                        const seconds = timeLeft % 60;
                        timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                        
                        // Change color when less than 1 minute remaining
                        if(timeLeft <= 60) {
                            timerDisplay.style.color = '#dc2626';
                            timerDisplay.style.fontSize = '20px';
                        }
                        
                        // Warning at 30 seconds
                        if(timeLeft === 30) {
                            warningMsg.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ⚠️ Only 30 seconds left! All friends must pay now!';
                            warningMsg.style.background = '#dc2626';
                            warningMsg.style.color = 'white';
                        }
                    }
                }, 1000);
            }
        }
        
        // Handle payment timeout - cancel booking if not all friends paid
        function handlePaymentTimeout() {
            const allPaid = splitFriends.every(f => f.paid);
            if(!allPaid) {
                const unpaidFriends = splitFriends.filter(f => !f.paid);
                const unpaidNames = unpaidFriends.map(f => f.name).join(', ');
                
                alert(`⏰ TIME EXPIRED!\n\nThe following friends did not complete payment:\n${unpaidNames}\n\nBooking has been CANCELLED. No charges were made.`);
                
                closeSplitModal(true);
                window.location.href = 'home.php';
            }
        }
        
        // Mark a friend as paid
        function markFriendPaid(index) {
            if(splitFriends[index] && !splitFriends[index].paid) {
                splitFriends[index].paid = true;
                
                // Update UI
                const statusSpan = document.getElementById(`status_${index}`);
                const payBtn = document.getElementById(`pay_btn_${index}`);
                
                if(statusSpan) {
                    statusSpan.innerHTML = '✅ Paid';
                    statusSpan.className = 'status-paid';
                }
                if(payBtn) {
                    payBtn.innerHTML = '✓ Paid';
                    payBtn.disabled = true;
                    payBtn.style.background = '#6c757d';
                    payBtn.style.cursor = 'not-allowed';
                }
                
                updateSplitProgress();
                
                // Check if all friends have paid
                const allPaid = splitFriends.every(f => f.paid);
                if(allPaid) {
                    // Stop the timer
                    if(paymentTimer) {
                        clearInterval(paymentTimer);
                        paymentTimer = null;
                    }
                    
                    // Show success message and complete booking
                    setTimeout(() => {
                        alert('🎉 All friends have paid successfully! Completing your booking...');
                        completeSplitBooking();
                    }, 500);
                } else {
                    const paidCount = splitFriends.filter(f => f.paid).length;
                    alert(`✅ ${splitFriends[index].name} has paid ₹${splitFriends[index].amount.toFixed(2)}!\n\nProgress: ${paidCount}/${splitFriends.length} friends paid`);
                }
            }
        }
        
        // Update progress bar
        function updateSplitProgress() {
            const paidCount = splitFriends.filter(f => f.paid).length;
            const total = splitFriends.length;
            const progressDiv = document.getElementById('splitProgress');
            
            if(progressDiv) {
                progressDiv.innerHTML = `<strong>${paidCount}/${total} friends paid</strong>`;
                if(paidCount === total) {
                    progressDiv.style.background = '#d1fae5';
                    progressDiv.innerHTML = '<strong>✅ All friends have paid! Completing booking...</strong>';
                }
            }
        }
        
        // Complete split payment booking
        function completeSplitBooking() {
            const finalTotal = totalAmount;
            const bookingId = 'SPLIT' + Date.now() + Math.floor(Math.random() * 1000);
            
            // Prepare complete friend details for storage - THIS IS THE KEY PART
            const friendPayments = splitFriends.map(f => ({
                name: f.name,
                email: f.email,
                phone: f.phone || '',
                seat: f.seat,
                amount: f.amount,
                paid: true,
                paid_at: new Date().toISOString(),
                ticket_id: bookingId + '-' + String(f.id).padStart(2, '0')
            }));
            
            // Store complete booking data including friend details
            const bookingData = {
                movie_id: movieId,
                hall_id: hallId,
                seats: selectedSeats,
                total: finalTotal,
                original_total: originalTotal,
                points_discount: pointsDiscount,
                applied_points: appliedPoints,
                payment_id: 'SPLIT_' + Date.now(),
                booking_id: bookingId,
                payment_method: 'split',
                split_friends: friendPayments,
                booking_details: {
                    movie_title: movieTitle,
                    hall_name: hallName,
                    booking_date: new Date().toISOString()
                }
            };
            
            // Show loading indicator
            const modal = document.getElementById('splitPaymentModal');
            if(modal) {
                const modalContent = modal.querySelector('.modal-content');
                if(modalContent) {
                    modalContent.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 40px; color: #667eea;"></i><p style="margin-top: 15px;">Processing booking...</p></div>';
                }
            }
            
            fetch('php/save_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(bookingData)
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert(`✅ SPLIT PAYMENT SUCCESSFUL!\n\nTotal Amount: ₹${finalTotal}\nSplit between ${splitFriends.length} friends\nAll friends have paid!\n\nBooking ID: ${data.booking_id}`);
                    window.location.href = `receipt.php?booking_id=${data.booking_id}`;
                } else {
                    alert('Booking failed: ' + (data.error || 'Unknown error'));
                    closeSplitModal(false);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Booking failed. Please try again.');
                closeSplitModal(false);
            });
        }
        
        // Close split payment modal
        function closeSplitModal(isCancel) {
            if(paymentTimer) {
                clearInterval(paymentTimer);
                paymentTimer = null;
            }
            
            const modal = document.getElementById('splitPaymentModal');
            if(modal) modal.remove();
            modalOpen = false;
            
            if(isCancel) {
                const paidCount = splitFriends.filter(f => f.paid).length;
                if(paidCount > 0 && paidCount < splitFriends.length) {
                    alert(`⚠️ BOOKING CANCELLED\n\nOnly ${paidCount} out of ${splitFriends.length} friends paid.\nSince not all friends completed payment, the booking has been cancelled.\nNo charges were made.`);
                } else if(paidCount === 0) {
                    alert('Booking cancelled. No payment was processed.');
                }
                window.location.href = 'home.php';
            }
        }
        
        // Escape HTML to prevent XSS
        function escapeHtml(str) {
            if(!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if(m === '&') return '&amp;';
                if(m === '<') return '&lt;';
                if(m === '>') return '&gt;';
                return m;
            });
        }
        
        // Payment method switching
        document.querySelectorAll('.method-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                if(modalOpen) {
                    alert('Please complete or cancel the current split payment first.');
                    return;
                }
                
                document.querySelectorAll('.method-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                currentPaymentMethod = this.dataset.method;
                
                document.querySelectorAll('.payment-form').forEach(form => {
                    form.style.display = 'none';
                });
                
                if(currentPaymentMethod === 'card') {
                    document.getElementById('cardForm').style.display = 'block';
                    document.getElementById('mainPayBtn').style.display = 'flex';
                } else if(currentPaymentMethod === 'upi') {
                    document.getElementById('upiForm').style.display = 'block';
                    document.getElementById('mainPayBtn').style.display = 'flex';
                } else if(currentPaymentMethod === 'netbanking') {
                    document.getElementById('netbankingForm').style.display = 'block';
                    document.getElementById('mainPayBtn').style.display = 'flex';
                } else if(currentPaymentMethod === 'wallet') {
                    document.getElementById('walletForm').style.display = 'block';
                    document.getElementById('mainPayBtn').style.display = 'flex';
                } else if(currentPaymentMethod === 'split') {
                    document.getElementById('splitForm').style.display = 'block';
                    document.getElementById('mainPayBtn').style.display = 'none';
                    // Regenerate friend inputs in case seat count changed
                    generateSplitFriendInputs();
                }
            });
        });
        
        // Format card number with spaces
        document.getElementById('cardNumber')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '');
            if(value.length > 16) value = value.slice(0, 16);
            value = value.replace(/(\d{4})/g, '$1 ').trim();
            e.target.value = value;
        });
        
        // Format expiry date
        document.getElementById('expiryDate')?.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\//g, '');
            if(value.length > 4) value = value.slice(0, 4);
            if(value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2);
            }
            e.target.value = value;
        });
        
        // Restrict CVV to numbers only
        document.getElementById('cvv')?.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 3);
        });
        
        // Process regular payment (non-split)
        function processPayment() {
            // Validate based on payment method
            let isValid = true;
            let errorMessage = '';
            let paymentDetails = {};
            
            if(currentPaymentMethod === 'card') {
                const cardNum = document.getElementById('cardNumber').value.replace(/\s/g, '');
                const expiry = document.getElementById('expiryDate').value;
                const cvv = document.getElementById('cvv').value;
                const cardName = document.getElementById('cardName').value;
                
                if(!cardNum || cardNum.length < 15) {
                    errorMessage = 'Please enter a valid card number';
                    isValid = false;
                } else if(!expiry || !expiry.match(/^\d{2}\/\d{2}$/)) {
                    errorMessage = 'Please enter valid expiry date (MM/YY)';
                    isValid = false;
                } else if(!cvv || cvv.length < 3) {
                    errorMessage = 'Please enter valid CVV';
                    isValid = false;
                } else if(!cardName) {
                    errorMessage = 'Please enter cardholder name';
                    isValid = false;
                } else {
                    paymentDetails = { card_number: cardNum, expiry: expiry, card_name: cardName };
                }
            } else if(currentPaymentMethod === 'upi') {
                const upiId = document.getElementById('upiId').value;
                const upiApp = document.getElementById('upiApp').value;
                
                if(!upiId || !upiId.includes('@')) {
                    errorMessage = 'Please enter a valid UPI ID (example@bank)';
                    isValid = false;
                } else if(!upiApp) {
                    errorMessage = 'Please select your UPI app';
                    isValid = false;
                } else {
                    paymentDetails = { upi_id: upiId, upi_app: upiApp };
                }
            } else if(currentPaymentMethod === 'netbanking') {
                const bank = document.getElementById('bankSelect').value;
                const accountName = document.getElementById('accountName').value;
                const password = document.getElementById('netPassword').value;
                
                if(!bank) {
                    errorMessage = 'Please select your bank';
                    isValid = false;
                } else if(!accountName) {
                    errorMessage = 'Please enter account holder name';
                    isValid = false;
                } else if(!password) {
                    errorMessage = 'Please enter your net banking password';
                    isValid = false;
                } else {
                    paymentDetails = { bank: bank, account_name: accountName };
                }
            } else if(currentPaymentMethod === 'wallet') {
                const wallet = document.getElementById('walletSelect').value;
                const walletId = document.getElementById('walletId').value;
                const walletPin = document.getElementById('walletPin').value;
                
                if(!wallet) {
                    errorMessage = 'Please select your wallet';
                    isValid = false;
                } else if(!walletId) {
                    errorMessage = 'Please enter wallet ID (mobile/email)';
                    isValid = false;
                } else if(!walletPin || walletPin.length < 4) {
                    errorMessage = 'Please enter valid wallet PIN';
                    isValid = false;
                } else {
                    paymentDetails = { wallet: wallet, wallet_id: walletId };
                }
            }
            
            if(!isValid) {
                alert(errorMessage);
                return;
            }
            
            // Show loading
            const payBtn = document.querySelector('#mainPayBtn');
            const originalText = payBtn.innerHTML;
            payBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Payment...';
            payBtn.disabled = true;
            
            // Process payment
            setTimeout(() => {
                const bookingId = 'CCW' + Date.now() + Math.floor(Math.random() * 1000);
                const finalTotal = totalAmount;
                
                // Deduct points from database if points were used
                if(appliedPoints > 0) {
                    fetch('php/redeem_points.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ points: appliedPoints, for_booking: true })
                    }).catch(err => console.log('Points redemption error:', err));
                }
                
                fetch('php/save_booking.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        movie_id: movieId,
                        hall_id: hallId,
                        seats: selectedSeats,
                        total: finalTotal,
                        original_total: originalTotal,
                        points_discount: pointsDiscount,
                        applied_points: appliedPoints,
                        payment_id: 'PAY_' + Date.now() + '_' + currentPaymentMethod.toUpperCase(),
                        booking_id: bookingId,
                        payment_method: currentPaymentMethod,
                        payment_details: paymentDetails
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        alert(`✅ Payment Successful!\n\nAmount Paid: ₹${finalTotal}\nPayment Method: ${currentPaymentMethod.toUpperCase()}\nPoints Saved: ${pointsDiscount > 0 ? '₹' + pointsDiscount : 'None'}\n\nBooking ID: ${data.booking_id}`);
                        window.location.href = `receipt.php?booking_id=${data.booking_id}`;
                    } else {
                        alert('Payment failed: ' + (data.error || 'Unknown error'));
                        payBtn.innerHTML = originalText;
                        payBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Payment failed. Please try again.');
                    payBtn.innerHTML = originalText;
                    payBtn.disabled = false;
                });
            }, 2000);
        }
    </script>
</body>
</html>