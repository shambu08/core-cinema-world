<?php
session_start();
require_once 'php/db_connect.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$booking_id = $_GET['booking_id'] ?? '';
if(empty($booking_id)) {
    die("Booking ID not provided");
}

// Fetch booking details
$stmt = $pdo->prepare("
    SELECT b.*, u.full_name, u.username, u.phone, u.email,
           m.title, m.duration, m.genre, m.language, m.rating, m.poster_url,
           h.hall_name, h.city, h.state, h.address, h.latitude, h.longitude, h.total_screens, h.facilities, h.contact_number
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN movies m ON b.movie_id = m.id 
    JOIN cinema_halls h ON b.hall_id = h.id 
    WHERE b.booking_id = ?
");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if(!$booking) {
    die("Booking not found");
}

// Check if this is a split payment booking
$isSplitPayment = ($booking['payment_method'] === 'split');
$splitFriends = [];

if($isSplitPayment) {
    // First try to get split friends from payment_details JSON
    if(!empty($booking['payment_details'])) {
        $paymentDetails = json_decode($booking['payment_details'], true);
        if(isset($paymentDetails['split_friends']) && is_array($paymentDetails['split_friends'])) {
            $splitFriends = $paymentDetails['split_friends'];
        }
    }
    
    // If not found in payment_details, try split_payments table
    if(empty($splitFriends)) {
        try {
            $splitStmt = $pdo->prepare("SELECT * FROM split_payments WHERE booking_id = ?");
            $splitStmt->execute([$booking['booking_id']]);
            $splitFriends = $splitStmt->fetchAll();
        } catch (PDOException $e) {
            // Table might not exist, ignore
        }
    }
}

// Safely get values with defaults to prevent undefined array key errors
$pointsDiscount = isset($booking['points_discount']) ? (float)$booking['points_discount'] : 0;
$totalAmount = isset($booking['total_amount']) ? (float)$booking['total_amount'] : 0;
$subtotal = $totalAmount;
$gst = $subtotal * 0.18;
$totalWithGst = $subtotal + $gst;
$finalAmount = $totalWithGst - $pointsDiscount;

// Safely get seats string
$seatsString = isset($booking['seats']) ? $booking['seats'] : '';
$seatsArray = !empty($seatsString) ? explode(',', $seatsString) : [];

// Generate QR Code data (JSON format for scanning)
$qrData = json_encode([
    'booking_id' => $booking['booking_id'],
    'customer_name' => $booking['full_name'],
    'movie' => $booking['title'],
    'cinema' => $booking['hall_name'],
    'seats' => $seatsString,
    'total_amount' => $finalAmount,
    'original_amount' => $totalWithGst,
    'points_discount' => $pointsDiscount,
    'booking_date' => $booking['booking_date'],
    'payment_status' => $booking['payment_status'],
    'payment_method' => $booking['payment_method'] ?? 'N/A',
    'is_split' => $isSplitPayment,
    'split_friends' => $splitFriends
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Receipt - Core Cinema World</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Include QR Code library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <!-- Include HTML5 QR Code Scanner library -->
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        .receipt-container {
            max-width: 800px;
            margin: 30px auto;
        }
        
        .receipt-card {
            background: white;
            border-radius: 28px;
            padding: 30px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 2px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        
        .receipt-header i {
            font-size: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .receipt-header h1 {
            color: #1f2937;
            margin: 10px 0;
        }
        
        .booking-badge {
            display: inline-block;
            padding: 5px 12px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detail-row strong {
            color: #374151;
        }
        
        .detail-row span {
            color: #6b7280;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin: 20px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid #667eea;
        }
        
        /* Individual Tickets Section */
        .individual-tickets {
            margin-top: 15px;
        }
        
        .ticket-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
            transition: transform 0.3s, box-shadow 0.3s;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        .ticket-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #dee2e6;
        }
        
        .ticket-header h4 {
            color: #667eea;
            margin: 0;
            font-size: 16px;
        }
        
        .ticket-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #d1fae5;
            color: #065f46;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .ticket-detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 14px;
        }
        
        .ticket-detail-row strong {
            color: #495057;
            font-weight: 600;
        }
        
        .ticket-detail-row span {
            color: #6b7280;
        }
        
        .seat-number {
            font-weight: bold;
            color: #667eea;
            font-size: 16px;
        }
        
        /* QR Code Styles */
        .qr-section {
            background: #f9fafb;
            border-radius: 20px;
            padding: 25px;
            margin: 25px 0;
            text-align: center;
        }
        
        .qr-title {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
        }
        
        .qr-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 15px 0;
        }
        
        #qrcode {
            padding: 15px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        /* Scanner Modal Styles */
        .scanner-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
        }
        
        .scanner-content {
            background: white;
            border-radius: 28px;
            padding: 25px;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }
        
        .scanner-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .scanner-header h3 {
            color: #1f2937;
        }
        
        .close-scanner {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #6b7280;
        }
        
        .close-scanner:hover {
            color: #ef4444;
        }
        
        #reader {
            width: 100%;
            border-radius: 16px;
            overflow: hidden;
        }
        
        .scan-result {
            margin-top: 20px;
            padding: 15px;
            background: #f0fdf4;
            border-radius: 12px;
            display: none;
        }
        
        .scan-result.success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #28a745;
        }
        
        .scan-result.error {
            background: #fee2e2;
            color: #dc2626;
            border-left: 4px solid #ef4444;
        }
        
        .scan-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin-top: 15px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .scan-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }
        
        .verification-status {
            margin-top: 15px;
            padding: 10px;
            border-radius: 10px;
            font-size: 13px;
            display: none;
        }
        
        .verification-status.verified {
            background: #d1fae5;
            color: #065f46;
            display: block;
        }
        
        .verification-status.invalid {
            background: #fee2e2;
            color: #dc2626;
            display: block;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        
        .print-btn, .home-btn, .scan-qr-btn, .download-pdf-btn, .travel-btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .print-btn {
            background: #667eea;
            color: white;
        }
        
        .home-btn {
            background: #28a745;
            color: white;
        }
        
        .scan-qr-btn {
            background: #f59e0b;
            color: white;
        }
        
        .download-pdf-btn {
            background: #dc2626;
            color: white;
        }
        
        .travel-btn {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        
        .print-btn:hover, .home-btn:hover, .scan-qr-btn:hover, .download-pdf-btn:hover, .travel-btn:hover {
            transform: translateY(-2px);
        }
        
        .split-info-box {
            background: #e8f0fe;
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            text-align: center;
        }
        
        .split-info-box i {
            color: #667eea;
            font-size: 20px;
            margin-bottom: 8px;
        }
        
        .split-info-box p {
            margin: 5px 0 0 0;
            font-size: 13px;
            color: #4b5563;
        }
        
        @media (max-width: 768px) {
            .receipt-container {
                margin: 15px;
            }
            
            .receipt-card {
                padding: 20px;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .print-btn, .home-btn, .scan-qr-btn, .download-pdf-btn, .travel-btn {
                width: 100%;
                justify-content: center;
            }
            
            .ticket-card {
                padding: 15px;
            }
            
            .ticket-detail-row {
                flex-direction: column;
                gap: 5px;
            }
        }
        
        @media print {
            .navbar, .button-group, .scan-qr-btn, .scanner-modal, .download-pdf-btn, .scan-btn, .travel-btn {
                display: none !important;
            }
            
            .receipt-card {
                box-shadow: none;
                padding: 0;
            }
            
            .qr-section {
                background: none;
                padding: 0;
            }
            
            .ticket-card {
                break-inside: avoid;
                page-break-inside: avoid;
                border: 1px solid #ddd;
                margin-bottom: 15px;
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
    
    <div class="receipt-container" id="receipt">
        <div class="receipt-card">
            <div class="receipt-header">
                <i class="fas fa-film"></i>
                <h1>Core Cinema World</h1>
                <p>Movie Ticket Receipt</p>
                <div class="booking-badge">
                    <i class="fas fa-check-circle"></i> Booking Confirmed
                </div>
            </div>
            
            <!-- Booking Details -->
            <div class="section-title">
                <i class="fas fa-info-circle"></i> Booking Information
            </div>
            <div class="detail-row">
                <strong>Booking ID:</strong>
                <span><?php echo htmlspecialchars($booking['booking_id']); ?></span>
            </div>
            <div class="detail-row">
                <strong>Booking Date:</strong>
                <span><?php echo date('d M Y, h:i A', strtotime($booking['booking_date'])); ?></span>
            </div>
            <div class="detail-row">
                <strong>Payment Method:</strong>
                <span><?php echo ucfirst($booking['payment_method'] ?? 'N/A'); ?></span>
            </div>
            <div class="detail-row">
                <strong>Payment Status:</strong>
                <span style="color: #28a745;"><?php echo strtoupper($booking['payment_status']); ?></span>
            </div>
            
            <!-- Customer Details -->
            <div class="section-title">
                <i class="fas fa-user"></i> Customer Details
            </div>
            <div class="detail-row">
                <strong>Name:</strong>
                <span><?php echo htmlspecialchars($booking['full_name']); ?></span>
            </div>
            <div class="detail-row">
                <strong>Email:</strong>
                <span><?php echo htmlspecialchars($booking['email'] ?? 'N/A'); ?></span>
            </div>
            <div class="detail-row">
                <strong>Phone:</strong>
                <span><?php echo htmlspecialchars($booking['phone'] ?? 'N/A'); ?></span>
            </div>
            
            <!-- Movie Details -->
            <div class="section-title">
                <i class="fas fa-film"></i> Movie Details
            </div>
            <div class="detail-row">
                <strong>Movie Name:</strong>
                <span><?php echo htmlspecialchars($booking['title']); ?></span>
            </div>
            <div class="detail-row">
                <strong>Genre:</strong>
                <span><?php echo htmlspecialchars($booking['genre']); ?></span>
            </div>
            <div class="detail-row">
                <strong>Language:</strong>
                <span><?php echo htmlspecialchars($booking['language']); ?></span>
            </div>
            <div class="detail-row">
                <strong>Duration:</strong>
                <span><?php echo htmlspecialchars($booking['duration']); ?></span>
            </div>
            <div class="detail-row">
                <strong>Rating:</strong>
                <span><?php echo $booking['rating']; ?>/10 <i class="fas fa-star" style="color: #f59e0b;"></i></span>
            </div>
            
            <!-- Cinema Details -->
            <div class="section-title">
                <i class="fas fa-building"></i> Cinema Details
            </div>
            <div class="detail-row">
                <strong>Cinema Hall:</strong>
                <span><?php echo htmlspecialchars($booking['hall_name']); ?></span>
            </div>
            <div class="detail-row">
                <strong>Location:</strong>
                <span><?php echo htmlspecialchars($booking['city']); ?>, <?php echo htmlspecialchars($booking['state']); ?></span>
            </div>
            <div class="detail-row">
                <strong>Address:</strong>
                <span><?php echo htmlspecialchars($booking['address']); ?></span>
            </div>
            
            <!-- Seat Details -->
            <div class="section-title">
                <i class="fas fa-chair"></i> Seat Details
            </div>
            <div class="detail-row">
                <strong>Selected Seats:</strong>
                <span><strong><?php echo !empty($seatsString) ? htmlspecialchars($seatsString) : 'N/A'; ?></strong></span>
            </div>
            <div class="detail-row">
                <strong>Number of Seats:</strong>
                <span><?php echo count($seatsArray); ?> Seats</span>
            </div>
            
            <!-- Individual Tickets Section - Shows all friends' tickets for split payment -->
            <?php if($isSplitPayment && !empty($splitFriends)): ?>
                <div class="section-title">
                    <i class="fas fa-ticket-alt"></i> Individual Tickets (<?php echo count($splitFriends); ?> Tickets)
                </div>
                <div class="individual-tickets">
                    <?php 
                    foreach($splitFriends as $index => $friend): 
                        $friendName = $friend['friend_name'] ?? $friend['name'] ?? $friend['full_name'] ?? 'N/A';
                        $friendEmail = $friend['friend_email'] ?? $friend['email'] ?? 'N/A';
                        $assignedSeat = $friend['assigned_seat'] ?? $friend['seat'] ?? ($seatsArray[$index] ?? 'N/A');
                        $amount = isset($friend['amount']) ? (float)$friend['amount'] : ($finalAmount / max(1, count($splitFriends)));
                    ?>
                        <div class="ticket-card">
                            <div class="ticket-header">
                                <h4><i class="fas fa-ticket-alt"></i> Ticket #<?php echo $index + 1; ?></h4>
                                <span class="ticket-badge"><i class="fas fa-check-circle"></i> Confirmed</span>
                            </div>
                            <div class="ticket-detail-row">
                                <strong>Ticket Holder Name:</strong>
                                <span><?php echo htmlspecialchars($friendName); ?></span>
                            </div>
                            <div class="ticket-detail-row">
                                <strong>Email:</strong>
                                <span><?php echo htmlspecialchars($friendEmail); ?></span>
                            </div>
                            <div class="ticket-detail-row">
                                <strong>Assigned Seat:</strong>
                                <span class="seat-number"><?php echo htmlspecialchars($assignedSeat); ?></span>
                            </div>
                            <div class="ticket-detail-row">
                                <strong>Movie:</strong>
                                <span><?php echo htmlspecialchars($booking['title']); ?></span>
                            </div>
                            <div class="ticket-detail-row">
                                <strong>Cinema:</strong>
                                <span><?php echo htmlspecialchars($booking['hall_name']); ?></span>
                            </div>
                            <div class="ticket-detail-row">
                                <strong>Show Date:</strong>
                                <span><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></span>
                            </div>
                            <div class="ticket-detail-row">
                                <strong>Amount Paid:</strong>
                                <span style="color: #28a745; font-weight: bold;">₹<?php echo number_format($amount, 2); ?></span>
                            </div>
                            <div class="ticket-detail-row">
                                <strong>Ticket ID:</strong>
                                <span><?php echo htmlspecialchars($booking['booking_id']); ?>-<?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Regular single ticket display for non-split payments -->
                <div class="section-title">
                    <i class="fas fa-ticket-alt"></i> Ticket Details
                </div>
                <div class="ticket-card">
                    <div class="ticket-header">
                        <h4><i class="fas fa-ticket-alt"></i> Your Ticket</h4>
                        <span class="ticket-badge"><i class="fas fa-check-circle"></i> Confirmed</span>
                    </div>
                    <div class="ticket-detail-row">
                        <strong>Ticket Holder:</strong>
                        <span><?php echo htmlspecialchars($booking['full_name']); ?></span>
                    </div>
                    <div class="ticket-detail-row">
                        <strong>Seats:</strong>
                        <span class="seat-number"><?php echo !empty($seatsString) ? htmlspecialchars($seatsString) : 'N/A'; ?></span>
                    </div>
                    <div class="ticket-detail-row">
                        <strong>Movie:</strong>
                        <span><?php echo htmlspecialchars($booking['title']); ?></span>
                    </div>
                    <div class="ticket-detail-row">
                        <strong>Cinema:</strong>
                        <span><?php echo htmlspecialchars($booking['hall_name']); ?></span>
                    </div>
                    <div class="ticket-detail-row">
                        <strong>Show Date:</strong>
                        <span><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Payment Summary -->
            <div class="section-title">
                <i class="fas fa-receipt"></i> Payment Summary
            </div>
            <div class="detail-row">
                <strong>Ticket Price:</strong>
                <span>₹<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="detail-row">
                <strong>GST (18%):</strong>
                <span>₹<?php echo number_format($gst, 2); ?></span>
            </div>
            <?php if($pointsDiscount > 0): ?>
                <div class="detail-row" style="color: #28a745;">
                    <strong>Points Discount:</strong>
                    <span>- ₹<?php echo number_format($pointsDiscount, 2); ?></span>
                </div>
            <?php endif; ?>
            <div class="detail-row" style="border-top: 2px solid #667eea; margin-top: 5px; padding-top: 15px;">
                <strong style="font-size: 18px;">Total Amount Paid:</strong>
                <strong style="font-size: 20px; color: #28a745;">₹<?php echo number_format($finalAmount, 2); ?></strong>
            </div>
            
            <!-- QR Code Section -->
            <div class="qr-section">
                <div class="qr-title">
                    <i class="fas fa-qrcode"></i> Scan for Ticket Verification
                </div>
                <div class="qr-container">
                    <div id="qrcode"></div>
                </div>
                <p style="font-size: 12px; color: #6b7280; margin-top: 10px;">
                    Show this QR code at the cinema entrance for verification
                </p>
                <button class="scan-btn" onclick="openScanner()">
                    <i class="fas fa-camera"></i> Verify Ticket (Scanner)
                </button>
                <div id="verificationStatus" class="verification-status"></div>
            </div>
            
            <!-- Action Buttons with Travel Assistance -->
            <div class="button-group">
                <button onclick="window.print()" class="print-btn">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
                <button onclick="openScanner()" class="scan-qr-btn">
                    <i class="fas fa-qrcode"></i> Scan QR Code
                </button>
                <a href="travel_assistance.php?booking_id=<?php echo $booking['booking_id']; ?>" class="travel-btn">
                    <i class="fas fa-bus"></i> Get Travel Assistance
                </a>
                <a href="home.php" class="home-btn">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
            
            <!-- Split Payment Information Box -->
            <?php if($isSplitPayment && !empty($splitFriends)): ?>
                <div class="split-info-box">
                    <i class="fas fa-users"></i>
                    <p><strong>Split Payment Booking</strong><br>
                    This booking was made as a split payment with <?php echo count($splitFriends); ?> friends.<br>
                    Each ticket above is assigned to an individual person. Please present your individual ticket at the entrance.</p>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #9ca3af;">
                <p>Thank you for choosing Core Cinema World!</p>
                <p>Please carry this receipt or show the QR code at the cinema counter.</p>
                <?php if($isSplitPayment && !empty($splitFriends)): ?>
                    <p style="margin-top: 5px;"><i class="fas fa-info-circle"></i> Each ticket holder must present their individual ticket for entry.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Scanner Modal -->
    <div id="scannerModal" class="scanner-modal">
        <div class="scanner-content">
            <div class="scanner-header">
                <h3><i class="fas fa-camera"></i> Scan QR Code</h3>
                <button class="close-scanner" onclick="closeScanner()">&times;</button>
            </div>
            <div id="reader"></div>
            <div id="scanResult" class="scan-result"></div>
            <button class="scan-btn" onclick="closeScanner()" style="margin-top: 15px;">
                Close Scanner
            </button>
        </div>
    </div>
    
    <script>
        // Generate QR Code
        const qrData = <?php echo json_encode($qrData); ?>;
        const bookingId = '<?php echo $booking['booking_id']; ?>';
        const isSplitPayment = <?php echo $isSplitPayment ? 'true' : 'false'; ?>;
        const splitFriends = <?php echo json_encode($splitFriends); ?>;
        
        // Generate QR code when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Create QR Code
            new QRCode(document.getElementById("qrcode"), {
                text: JSON.stringify(qrData),
                width: 200,
                height: 200,
                colorDark: "#1f2937",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        });
        
        // Scanner variables
        let html5QrCode = null;
        let isScanning = false;
        
        function openScanner() {
            const modal = document.getElementById('scannerModal');
            modal.style.display = 'flex';
            
            if (!isScanning) {
                startScanner();
            }
        }
        
        function closeScanner() {
            const modal = document.getElementById('scannerModal');
            modal.style.display = 'none';
            stopScanner();
        }
        
        function startScanner() {
            html5QrCode = new Html5Qrcode("reader");
            const qrCodeSuccessCallback = (decodedText, decodedResult) => {
                stopScanner();
                verifyTicket(decodedText);
            };
            
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            
            html5QrCode.start(
                { facingMode: "environment" },
                config,
                qrCodeSuccessCallback,
                (errorMessage) => {
                    console.log("Scanning...");
                }
            ).catch(err => {
                console.error("Unable to start scanning", err);
                const scanResult = document.getElementById('scanResult');
                scanResult.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Unable to access camera. Please ensure camera permissions are granted.';
                scanResult.className = 'scan-result error';
                scanResult.style.display = 'block';
            });
            
            isScanning = true;
        }
        
        function stopScanner() {
            if (html5QrCode && isScanning) {
                html5QrCode.stop().catch(err => {
                    console.error("Error stopping scanner", err);
                });
                isScanning = false;
            }
        }
        
        function verifyTicket(decodedText) {
            const scanResult = document.getElementById('scanResult');
            
            try {
                const ticketData = JSON.parse(decodedText);
                
                if (ticketData.booking_id === bookingId) {
                    let splitInfo = '';
                    if(ticketData.is_split && ticketData.split_friends && ticketData.split_friends.length > 0) {
                        splitInfo = '<br><span style="font-size: 12px;">This is a split payment booking with ' + ticketData.split_friends.length + ' individual tickets.</span>';
                    }
                    
                    scanResult.innerHTML = `
                        <i class="fas fa-check-circle"></i> 
                        <strong>Ticket Verified!</strong><br>
                        Booking ID: ${ticketData.booking_id}<br>
                        Customer: ${ticketData.customer_name}<br>
                        Movie: ${ticketData.movie}<br>
                        Seats: ${ticketData.seats || 'N/A'}<br>
                        Payment Method: ${ticketData.payment_method || 'N/A'}<br>
                        Amount Paid: ₹${ticketData.total_amount || 0}<br>
                        Status: <span style="color: #28a745;">Verified ✓</span>
                        ${splitInfo}
                    `;
                    scanResult.className = 'scan-result success';
                    
                    const verificationStatus = document.getElementById('verificationStatus');
                    verificationStatus.innerHTML = `
                        <i class="fas fa-check-circle"></i> Ticket Verified Successfully!
                        <br>Booking ID: ${ticketData.booking_id} is valid.
                        ${ticketData.is_split ? '<br><strong>Note:</strong> Split payment booking - all individual tickets are valid.' : ''}
                    `;
                    verificationStatus.className = 'verification-status verified';
                    
                } else {
                    scanResult.innerHTML = `
                        <i class="fas fa-times-circle"></i> 
                        <strong>Invalid Ticket!</strong><br>
                        Scanned Booking ID: ${ticketData.booking_id}<br>
                        Expected Booking ID: ${bookingId}<br>
                        This ticket does not match the current booking.
                    `;
                    scanResult.className = 'scan-result error';
                    
                    const verificationStatus = document.getElementById('verificationStatus');
                    verificationStatus.innerHTML = `
                        <i class="fas fa-times-circle"></i> Ticket Verification Failed!
                        <br>Scanned ticket does not match this booking.
                    `;
                    verificationStatus.className = 'verification-status invalid';
                }
                
                setTimeout(() => {
                    scanResult.style.display = 'none';
                    scanResult.innerHTML = '';
                }, 8000);
                
            } catch (e) {
                scanResult.innerHTML = `
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Invalid QR Code!</strong><br>
                    The scanned code is not a valid ticket QR code.
                `;
                scanResult.className = 'scan-result error';
                scanResult.style.display = 'block';
            }
            
            scanResult.style.display = 'block';
        }
        
        function downloadAsPDF() {
            alert('To download as PDF, click "Print Receipt" and then select "Save as PDF" from the print dialog.');
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('scannerModal');
            if (event.target == modal) {
                closeScanner();
            }
        }
        
        document.querySelectorAll('.ticket-card').forEach((card, index) => {
            card.style.animation = `fadeIn 0.5s ease ${index * 0.1}s both`;
        });
        
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>