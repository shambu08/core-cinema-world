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

// Get user's referrals
$referralsStmt = $pdo->prepare("SELECT * FROM referrals WHERE referrer_id = ? ORDER BY created_at DESC");
$referralsStmt->execute([$user_id]);
$referrals = $referralsStmt->fetchAll();

// Get user's discount coupons
$couponsStmt = $pdo->prepare("SELECT * FROM discount_coupons WHERE user_id = ? AND is_used = 0 AND valid_until >= CURDATE() ORDER BY valid_until ASC");
$couponsStmt->execute([$user_id]);
$coupons = $couponsStmt->fetchAll();

// Get redeemed discounts
$redeemedStmt = $pdo->prepare("SELECT r.*, d.coupon_code, d.discount_percentage, d.discount_amount 
                               FROM redeemed_discounts r 
                               JOIN discount_coupons d ON r.coupon_id = d.id 
                               WHERE r.user_id = ? 
                               ORDER BY r.redeemed_at DESC LIMIT 10");
$redeemedStmt->execute([$user_id]);
$redeemed = $redeemedStmt->fetchAll();

// Calculate total points from referrals
$totalBonus = 0;
foreach($referrals as $ref) {
    if($ref['status'] == 'completed') {
        $totalBonus += $ref['referrer_bonus'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referrals & Discounts - Core Cinema World</title>
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
        
        .referral-banner {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .referral-banner h2 {
            color: #92400e;
            margin-bottom: 10px;
        }
        
        .referral-banner p {
            color: #78350f;
        }
        
        .referral-code-box {
            background: white;
            padding: 15px 20px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            gap: 15px;
            margin-top: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .referral-code {
            font-size: 24px;
            font-weight: 800;
            font-family: monospace;
            color: #667eea;
            letter-spacing: 2px;
        }
        
        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .copy-btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        
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
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin: 30px 0 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .coupon-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border-left: 4px solid #28a745;
        }
        
        .coupon-code {
            font-size: 20px;
            font-weight: 800;
            font-family: monospace;
            color: #667eea;
        }
        
        .coupon-discount {
            font-size: 24px;
            font-weight: 800;
            color: #28a745;
        }
        
        .use-coupon-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .use-coupon-btn:hover {
            transform: scale(1.02);
        }
        
        .referral-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .coupon-card {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .referral-code {
                font-size: 18px;
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
        
        <!-- Referral Banner -->
        <div class="referral-banner">
            <h2><i class="fas fa-gift"></i> Invite Friends, Earn Rewards!</h2>
            <p>Share your referral code with friends. When they sign up, you both get bonus points!</p>
            <div class="referral-code-box">
                <span class="referral-code"><?php echo htmlspecialchars($user['referral_code']); ?></span>
                <button class="copy-btn" onclick="copyReferralCode()"><i class="fas fa-copy"></i> Copy Code</button>
                <button class="copy-btn" onclick="shareReferralLink()"><i class="fas fa-share-alt"></i> Share Link</button>
            </div>
            <p style="margin-top: 15px; font-size: 13px;">
                <i class="fas fa-info-circle"></i> You earn 50 points | Your friend earns 25 points + 10% welcome coupon
            </p>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?php echo count($referrals); ?></h3>
                <p>Friends Referred</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-coins"></i>
                <h3><?php echo $totalBonus; ?></h3>
                <p>Bonus Points Earned</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-ticket-alt"></i>
                <h3><?php echo count($coupons); ?></h3>
                <p>Available Coupons</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-tag"></i>
                <h3><?php echo number_format($user['loyalty_points']); ?></h3>
                <p>Total Points</p>
            </div>
        </div>
        
        <!-- Available Coupons -->
        <h2 class="section-title"><i class="fas fa-ticket-alt"></i> Your Discount Coupons</h2>
        <div class="card">
            <?php if(count($coupons) > 0): ?>
                <?php foreach($coupons as $coupon): ?>
                <div class="coupon-card">
                    <div>
                        <div class="coupon-code"><?php echo htmlspecialchars($coupon['coupon_code']); ?></div>
                        <div style="font-size: 12px; color: #6b7280;">Valid until: <?php echo date('d M Y', strtotime($coupon['valid_until'])); ?></div>
                    </div>
                    <div>
                        <div class="coupon-discount">
                            <?php if($coupon['discount_percentage'] > 0): ?>
                                <?php echo $coupon['discount_percentage']; ?>% OFF
                            <?php else: ?>
                                ₹<?php echo $coupon['discount_amount']; ?> OFF
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 12px;">Min. order: ₹<?php echo $coupon['min_order_amount']; ?></div>
                    </div>
                    <button class="use-coupon-btn" onclick="useCoupon('<?php echo $coupon['coupon_code']; ?>')">
                        <i class="fas fa-tag"></i> Apply Coupon
                    </button>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 20px;">No coupons available. Refer friends to earn coupons!</p>
            <?php endif; ?>
        </div>
        
        <!-- Referral History -->
        <h2 class="section-title"><i class="fas fa-history"></i> Referral History</h2>
        <div class="card">
            <?php if(count($referrals) > 0): ?>
                <?php foreach($referrals as $ref): ?>
                <div class="referral-item">
                    <div>
                        <strong><?php echo htmlspecialchars($ref['referred_name']); ?></strong>
                        <div><small><?php echo htmlspecialchars($ref['referred_email']); ?></small></div>
                        <div><small>Referred on: <?php echo date('d M Y', strtotime($ref['created_at'])); ?></small></div>
                    </div>
                    <div>
                        <?php if($ref['status'] == 'completed'): ?>
                            <span class="badge-success"><i class="fas fa-check-circle"></i> Completed</span>
                            <div style="margin-top: 5px; color: #28a745;">+<?php echo $ref['referrer_bonus']; ?> points</div>
                        <?php else: ?>
                            <span class="badge-pending"><i class="fas fa-clock"></i> Pending</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 20px;">No referrals yet. Share your referral code to earn points!</p>
            <?php endif; ?>
        </div>
        
        <!-- Redeemed Discounts -->
        <h2 class="section-title"><i class="fas fa-receipt"></i> Redeemed Discounts</h2>
        <div class="card">
            <?php if(count($redeemed) > 0): ?>
                <?php foreach($redeemed as $red): ?>
                <div class="referral-item">
                    <div>
                        <strong>Coupon: <?php echo htmlspecialchars($red['coupon_code']); ?></strong>
                        <div><small>Redeemed on: <?php echo date('d M Y', strtotime($red['redeemed_at'])); ?></small></div>
                    </div>
                    <div>
                        <span style="color: #28a745;">Saved: ₹<?php echo $red['saved_amount']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 20px;">No discounts redeemed yet.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function copyReferralCode() {
            const code = '<?php echo $user['referral_code']; ?>';
            navigator.clipboard.writeText(code);
            alert('✅ Referral code copied! Share it with your friends.\n\nThey will get 25 bonus points and a welcome coupon on signup!');
        }
        
        function shareReferralLink() {
            const link = `<?php echo "http://localhost/core-cinema-world/signup.php?ref=" . $user['referral_code']; ?>`;
            navigator.clipboard.writeText(link);
            alert('✅ Referral link copied! Share it with your friends.\n\nThey will get 25 bonus points and a 10% welcome coupon!');
        }
        
        function useCoupon(couponCode) {
            localStorage.setItem('discount_coupon', couponCode);
            alert(`✅ Coupon ${couponCode} applied! You will get discount on your next booking.`);
            window.location.href = 'home.php';
        }
    </script>
</body>
</html>