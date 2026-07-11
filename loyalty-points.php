<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'php/db_connect.php';

$user_id = $_SESSION['user_id'];

// Get user details
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();

// Get loyalty transactions
$transStmt = $pdo->prepare("SELECT * FROM loyalty_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
$transStmt->execute([$user_id]);
$transactions = $transStmt->fetchAll();

// Get user reviews
$reviewsStmt = $pdo->prepare("SELECT r.*, m.title FROM movie_reviews r JOIN movies m ON r.movie_id = m.id WHERE r.user_id = ? ORDER BY r.created_at DESC");
$reviewsStmt->execute([$user_id]);
$reviews = $reviewsStmt->fetchAll();

// Get referrals made by user
$referralsStmt = $pdo->prepare("SELECT * FROM referrals WHERE referrer_id = ? ORDER BY created_at DESC");
$referralsStmt->execute([$user_id]);
$referrals = $referralsStmt->fetchAll();

// Get movies for review dropdown
$moviesStmt = $pdo->query("SELECT id, title FROM movies ORDER BY title");
$allMovies = $moviesStmt->fetchAll();

// Check if user already reviewed a movie
$reviewedMovies = [];
foreach($reviews as $review) {
    $reviewedMovies[] = $review['movie_id'];
}

// Get active discount coupons
$couponsStmt = $pdo->prepare("SELECT * FROM discount_coupons WHERE user_id = ? AND is_used = 0 AND valid_until >= CURDATE() ORDER BY valid_until ASC");
$couponsStmt->execute([$user_id]);
$coupons = $couponsStmt->fetchAll();

// Calculate points value in rupees (100 points = ₹10)
$pointsValue = floor($user['loyalty_points'] / 10);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Points - Core Cinema World</title>
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: white;
            color: #667eea;
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            transform: translateX(-5px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Points Header */
        .points-header {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 28px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .points-balance {
            font-size: 48px;
            font-weight: 800;
            color: #92400e;
        }
        
        .points-label {
            font-size: 16px;
            color: #78350f;
            margin-bottom: 10px;
        }
        
        .points-value {
            font-size: 30px;
            color: #f59e0b;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stat-card i {
            font-size: 40px;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-card h3 {
            font-size: 32px;
            font-weight: 800;
            color: #1f2937;
        }
        
        .stat-card p {
            color: #6b7280;
            font-size: 14px;
        }
        
        /* Redeem Section */
        .redeem-section {
            background: white;
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .redeem-section h2 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .redeem-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .redeem-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .redeem-card:hover {
            transform: translateY(-5px);
            border-color: #667eea;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .redeem-card.selected {
            border-color: #28a745;
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        }
        
        .points-cost {
            font-size: 28px;
            font-weight: 800;
            color: #667eea;
        }
        
        .money-value {
            font-size: 24px;
            font-weight: 800;
            color: #28a745;
            margin: 10px 0;
        }
        
        .redeem-input-group {
            margin: 20px 0;
            text-align: center;
        }
        
        .redeem-input-group input {
            width: 200px;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            text-align: center;
            margin: 0 10px;
        }
        
        .redeem-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .redeem-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(40, 167, 69, 0.4);
        }
        
        .redeem-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 15px;
            border-radius: 12px;
            margin-top: 15px;
            text-align: center;
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            border-bottom: 2px solid rgba(255,255,255,0.2);
            padding-bottom: 10px;
        }
        
        .tab-btn {
            padding: 12px 24px;
            background: transparent;
            border: none;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            background: white;
            color: #667eea;
        }
        
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Cards */
        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .points-earn {
            color: #10b981;
            font-weight: 600;
        }
        
        .points-redeem {
            color: #ef4444;
            font-weight: 600;
        }
        
        .review-card {
            background: #f9fafb;
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .stars {
            color: #f59e0b;
            margin-bottom: 8px;
        }
        
        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        
        .form-group select,
        .form-group textarea,
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .rating-stars {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }
        
        .rating-star {
            font-size: 28px;
            cursor: pointer;
            color: #d1d5db;
            transition: all 0.2s;
        }
        
        .rating-star.active {
            color: #f59e0b;
        }
        
        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        
        .coupon-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 16px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border-left: 4px solid #28a745;
        }
        
        .coupon-code {
            font-size: 18px;
            font-weight: 800;
            font-family: monospace;
            color: #667eea;
        }
        
        .use-coupon-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .points-balance {
                font-size: 36px;
            }
            
            .redeem-options {
                grid-template-columns: 1fr;
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
        <a href="home.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Home</a>
        
        <!-- Points Header -->
        <div class="points-header">
            <div class="points-label">Your Loyalty Points Balance</div>
            <div class="points-balance"><?php echo number_format($user['loyalty_points']); ?></div>
            <div class="points-value">≈ ₹<?php echo number_format($pointsValue); ?> value</div>
            <div style="margin-top: 10px; font-size: 14px;">💡 100 points = ₹10 discount</div>
        </div>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-star"></i>
                <h3><?php echo count($reviews); ?></h3>
                <p>Reviews Written</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?php echo count($referrals); ?></h3>
                <p>Friends Referred</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-ticket-alt"></i>
                <h3 id="bookingCount">0</h3>
                <p>Tickets Booked</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-coins"></i>
                <h3><?php echo number_format($user['total_points_earned']); ?></h3>
                <p>Total Points Earned</p>
            </div>
        </div>
        
        <!-- Redeem Points Section -->
        <div class="redeem-section">
            <h2><i class="fas fa-tag"></i> Redeem Your Points for Discount</h2>
            <p style="margin-bottom: 20px; color: #6b7280;">Convert your points into real money discount on your next booking!</p>
            
            <div class="redeem-options">
                <div class="redeem-card" onclick="selectRedeemOption(100, 10)">
                    <div class="points-cost">100 Points</div>
                    <div class="money-value">= ₹10 OFF</div>
                    <small>Minimum 100 points</small>
                </div>
                <div class="redeem-card" onclick="selectRedeemOption(200, 20)">
                    <div class="points-cost">200 Points</div>
                    <div class="money-value">= ₹20 OFF</div>
                    <small>Save ₹20 on booking</small>
                </div>
                <div class="redeem-card" onclick="selectRedeemOption(500, 50)">
                    <div class="points-cost">500 Points</div>
                    <div class="money-value">= ₹50 OFF</div>
                    <small>Best value!</small>
                </div>
                <div class="redeem-card" onclick="selectRedeemOption(1000, 100)">
                    <div class="points-cost">1000 Points</div>
                    <div class="money-value">= ₹100 OFF</div>
                    <small>Premium discount</small>
                </div>
            </div>
            
            <div class="redeem-input-group">
                <label style="display: block; margin-bottom: 10px;">Or enter custom points:</label>
                <input type="number" id="customPoints" placeholder="Enter points (multiples of 100)" step="100" min="100" max="<?php echo $user['loyalty_points']; ?>">
                <button class="redeem-btn" onclick="redeemCustomPoints()" id="redeemBtn">Redeem Points</button>
            </div>
            
            <div id="redeemMessage"></div>
            
            <div class="redeem-input-group" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
                <p style="margin-bottom: 15px;"><i class="fas fa-info-circle"></i> After redeeming, your discount will be automatically applied on the payment page!</p>
                <a href="home.php" class="btn" style="display: inline-block; text-decoration: none;">
                    <i class="fas fa-shopping-cart"></i> Book Tickets Now
                </a>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" data-tab="transactions">📜 Transactions</button>
            <button class="tab-btn" data-tab="reviews">⭐ Write a Review</button>
            <button class="tab-btn" data-tab="myreviews">📝 My Reviews</button>
            <button class="tab-btn" data-tab="coupons">🎟️ My Coupons</button>
        </div>
        
        <!-- Transactions Tab -->
        <div id="transactionsTab" class="tab-content active">
            <div class="card">
                <h3><i class="fas fa-history"></i> Points History</h3>
                <?php if(count($transactions) > 0): ?>
                    <?php foreach($transactions as $trans): ?>
                    <div class="transaction-item">
                        <div>
                            <strong><?php echo htmlspecialchars($trans['reason']); ?></strong>
                            <div><small><?php echo date('d M Y, h:i A', strtotime($trans['created_at'])); ?></small></div>
                        </div>
                        <div class="<?php echo $trans['points'] > 0 ? 'points-earn' : 'points-redeem'; ?>">
                            <?php echo $trans['points'] > 0 ? '+' : ''; ?><?php echo $trans['points']; ?> points
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; padding:20px;">No transactions yet. Book tickets, write reviews, or refer friends to earn points!</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Write Review Tab -->
        <div id="reviewsTab" class="tab-content">
            <div class="card">
                <h3><i class="fas fa-star"></i> Write a Movie Review</h3>
                <p style="margin-bottom: 15px;">Earn <strong>20 points</strong> for each movie review!</p>
                
                <form id="reviewForm">
                    <div class="form-group">
                        <label>Select Movie</label>
                        <select id="reviewMovieId" required>
                            <option value="">-- Select a Movie --</option>
                            <?php foreach($allMovies as $movie): ?>
                                <?php if(!in_array($movie['id'], $reviewedMovies)): ?>
                                    <option value="<?php echo $movie['id']; ?>"><?php echo htmlspecialchars($movie['title']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Your Rating</label>
                        <div class="rating-stars" id="ratingStars">
                            <i class="fas fa-star rating-star" data-rating="1"></i>
                            <i class="fas fa-star rating-star" data-rating="2"></i>
                            <i class="fas fa-star rating-star" data-rating="3"></i>
                            <i class="fas fa-star rating-star" data-rating="4"></i>
                            <i class="fas fa-star rating-star" data-rating="5"></i>
                        </div>
                        <input type="hidden" id="reviewRating" value="0">
                    </div>
                    
                    <div class="form-group">
                        <label>Your Review</label>
                        <textarea id="reviewText" rows="4" placeholder="Share your thoughts about the movie..." required></textarea>
                    </div>
                    
                    <button type="submit" class="btn">Submit Review (+20 points)</button>
                </form>
            </div>
        </div>
        
        <!-- My Reviews Tab -->
        <div id="myreviewsTab" class="tab-content">
            <div class="card">
                <h3><i class="fas fa-pen"></i> My Reviews</h3>
                <?php if(count($reviews) > 0): ?>
                    <?php foreach($reviews as $review): ?>
                    <div class="review-card">
                        <h4><?php echo htmlspecialchars($review['title']); ?></h4>
                        <div class="stars">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'far'; ?>" style="color: #f59e0b;"></i>
                            <?php endfor; ?>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                        <small style="color: #6b7280;">Posted on <?php echo date('d M Y', strtotime($review['created_at'])); ?></small>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; padding:20px;">You haven't written any reviews yet. Write your first review to earn 20 points!</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Coupons Tab -->
        <div id="couponsTab" class="tab-content">
            <div class="card">
                <h3><i class="fas fa-ticket-alt"></i> My Discount Coupons</h3>
                <?php if(count($coupons) > 0): ?>
                    <?php foreach($coupons as $coupon): ?>
                    <div class="coupon-card">
                        <div>
                            <div class="coupon-code"><?php echo htmlspecialchars($coupon['coupon_code']); ?></div>
                            <div style="font-size: 12px; color: #6b7280;">Valid until: <?php echo date('d M Y', strtotime($coupon['valid_until'])); ?></div>
                        </div>
                        <div>
                            <div style="font-size: 20px; font-weight: 800; color: #28a745;">
                                <?php if($coupon['discount_percentage'] > 0): ?>
                                    <?php echo $coupon['discount_percentage']; ?>% OFF
                                <?php else: ?>
                                    ₹<?php echo $coupon['discount_amount']; ?> OFF
                                <?php endif; ?>
                            </div>
                            <div style="font-size: 12px;">Min. order: ₹<?php echo $coupon['min_order_amount']; ?></div>
                        </div>
                        <button class="use-coupon-btn" onclick="useCoupon('<?php echo $coupon['coupon_code']; ?>')">Apply Coupon</button>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; padding:20px;">No coupons available. Refer friends or redeem points to get coupons!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        let selectedPoints = 0;
        let selectedMoney = 0;
        
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
                btn.classList.add('active');
                const tabId = btn.dataset.tab + 'Tab';
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Rating stars
        let currentRating = 0;
        document.querySelectorAll('.rating-star').forEach(star => {
            star.addEventListener('click', function() {
                currentRating = parseInt(this.dataset.rating);
                document.getElementById('reviewRating').value = currentRating;
                document.querySelectorAll('.rating-star').forEach((s, index) => {
                    if(index < currentRating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
        });
        
        // Select redeem option
        function selectRedeemOption(points, money) {
            selectedPoints = points;
            selectedMoney = money;
            
            // Remove selected class from all redeem cards
            document.querySelectorAll('.redeem-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            event.currentTarget.classList.add('selected');
            
            // Clear custom points input
            document.getElementById('customPoints').value = '';
            
            // Enable redeem button
            document.getElementById('redeemBtn').disabled = false;
        }
        
        // Redeem custom points
        function redeemCustomPoints() {
            const customPoints = parseInt(document.getElementById('customPoints').value);
            const maxPoints = <?php echo $user['loyalty_points']; ?>;
            
            if(isNaN(customPoints) || customPoints < 100) {
                alert('Please enter at least 100 points');
                return;
            }
            
            if(customPoints % 100 !== 0) {
                alert('Points must be in multiples of 100');
                return;
            }
            
            if(customPoints > maxPoints) {
                alert(`You only have ${maxPoints} points available`);
                return;
            }
            
            selectedPoints = customPoints;
            selectedMoney = customPoints / 10;
            
            // Remove selected class from all redeem cards
            document.querySelectorAll('.redeem-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Enable redeem button
            document.getElementById('redeemBtn').disabled = false;
        }
        
        // Redeem points
        async function redeemPoints() {
            if(selectedPoints === 0) {
                alert('Please select a redemption option or enter custom points');
                return;
            }
            
            const response = await fetch('php/redeem_points.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ points: selectedPoints })
            });
            const data = await response.json();
            
            const messageDiv = document.getElementById('redeemMessage');
            if(data.success) {
                messageDiv.innerHTML = `<div class="success-message">
                    <i class="fas fa-check-circle"></i> ✅ Success! ${selectedPoints} points redeemed for ₹${selectedMoney} discount!
                    <br>Your discount will be applied automatically on the payment page.
                </div>`;
                
                // Update points display
                const newPoints = <?php echo $user['loyalty_points']; ?> - selectedPoints;
                document.querySelector('.points-balance').textContent = newPoints.toLocaleString();
                document.querySelector('.points-value').innerHTML = `≈ ₹${Math.floor(newPoints / 10)} value`;
                
                // Reset selection
                selectedPoints = 0;
                selectedMoney = 0;
                document.querySelectorAll('.redeem-card').forEach(card => {
                    card.classList.remove('selected');
                });
                document.getElementById('customPoints').value = '';
                document.getElementById('redeemBtn').disabled = true;
                
                // Refresh page after 2 seconds to show updated balance
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                messageDiv.innerHTML = `<div class="error-message">❌ ${data.error}</div>`;
            }
        }
        
        // Get booking count
        fetch('php/get_booking_count.php')
            .then(res => res.json())
            .then(data => {
                document.getElementById('bookingCount').innerText = data.count || 0;
            });
        
        // Submit review
        document.getElementById('reviewForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const movieId = document.getElementById('reviewMovieId').value;
            const rating = document.getElementById('reviewRating').value;
            const review = document.getElementById('reviewText').value;
            
            if(!movieId || !rating || !review) {
                alert('Please fill all fields');
                return;
            }
            
            const response = await fetch('php/submit_review.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ movie_id: movieId, rating: rating, review: review })
            });
            const data = await response.json();
            
            if(data.success) {
                alert('✅ Review submitted! You earned 20 points!');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        });
        
        // Use coupon
        function useCoupon(couponCode) {
            localStorage.setItem('discount_coupon', couponCode);
            alert(`✅ Coupon ${couponCode} applied! You will get discount on your next booking.`);
            window.location.href = 'home.php';
        }
        
        // Add redeem button event listener
        document.getElementById('redeemBtn').addEventListener('click', redeemPoints);
    </script>
</body>
</html>