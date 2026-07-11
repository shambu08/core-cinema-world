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

// Get user's booking history
$bookingStmt = $pdo->prepare("
    SELECT b.*, m.title, m.poster_url, h.hall_name 
    FROM bookings b 
    JOIN movies m ON b.movie_id = m.id 
    JOIN cinema_halls h ON b.hall_id = h.id 
    WHERE b.user_id = ? 
    ORDER BY b.booking_date DESC 
    LIMIT 10
");
$bookingStmt->execute([$user_id]);
$bookings = $bookingStmt->fetchAll();

// Get user's reviews
$reviewStmt = $pdo->prepare("
    SELECT r.*, m.title 
    FROM movie_reviews r 
    JOIN movies m ON r.movie_id = m.id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT 5
");
$reviewStmt->execute([$user_id]);
$reviews = $reviewStmt->fetchAll();

// Get user's total points and statistics
$pointsStmt = $pdo->prepare("SELECT SUM(points) as total_earned FROM loyalty_transactions WHERE user_id = ? AND points > 0");
$pointsStmt->execute([$user_id]);
$totalEarned = $pointsStmt->fetch(PDO::FETCH_ASSOC)['total_earned'] ?? 0;

$bookingCount = count($bookings);
$totalSpent = 0;
foreach($bookings as $booking) {
    $totalSpent += $booking['total_amount'];
}

// Get user's referral info
$referralStmt = $pdo->prepare("SELECT COUNT(*) as count FROM referrals WHERE referrer_id = ? AND status = 'completed'");
$referralStmt->execute([$user_id]);
$referralCount = $referralStmt->fetch(PDO::FETCH_ASSOC)['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Core Cinema World</title>
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
        
        /* Profile Header */
        .profile-header {
            background: white;
            border-radius: 28px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: white;
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-info h1 {
            font-size: 32px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .profile-info .username {
            color: #667eea;
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        .profile-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 15px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f3f4f6;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
        }
        
        .detail-item i {
            color: #667eea;
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
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
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
        
        /* Section Styles */
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
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        
        .booking-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e5e7eb;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .booking-item:last-child {
            border-bottom: none;
        }
        
        .booking-info h4 {
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .booking-info p {
            font-size: 12px;
            color: #6b7280;
        }
        
        .booking-amount {
            font-weight: 700;
            color: #28a745;
        }
        
        .review-item {
            padding: 15px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .review-item:last-child {
            border-bottom: none;
        }
        
        .review-movie {
            font-weight: 700;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .review-stars {
            color: #f59e0b;
            margin-bottom: 8px;
        }
        
        .review-text {
            font-size: 14px;
            color: #6b7280;
            line-height: 1.5;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-completed {
            background: #d1fae5;
            color: #065f46;
        }
        
        .edit-profile-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
            transition: all 0.3s;
        }
        
        .edit-profile-btn:hover {
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-details {
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .booking-item {
                flex-direction: column;
                text-align: center;
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
        
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <div class="username">@<?php echo htmlspecialchars($user['username']); ?></div>
                <div class="profile-details">
                    <div class="detail-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($user['phone']); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Member since: <?php echo date('M Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-gift"></i>
                        <span>Referral Code: <?php echo htmlspecialchars($user['referral_code']); ?></span>
                    </div>
                </div>
                <button class="edit-profile-btn" onclick="showEditModal()">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-ticket-alt"></i>
                <h3><?php echo $bookingCount; ?></h3>
                <p>Movies Watched</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-coins"></i>
                <h3><?php echo number_format($user['loyalty_points']); ?></h3>
                <p>Loyalty Points</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-star"></i>
                <h3><?php echo count($reviews); ?></h3>
                <p>Reviews Written</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-rupee-sign"></i>
                <h3>₹<?php echo number_format($totalSpent); ?></h3>
                <p>Total Spent</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?php echo $referralCount; ?></h3>
                <p>Friends Referred</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-trophy"></i>
                <h3><?php echo number_format($totalEarned); ?></h3>
                <p>Total Points Earned</p>
            </div>
        </div>
        
        <!-- Booking History -->
        <h2 class="section-title"><i class="fas fa-history"></i> Booking History</h2>
        <div class="card">
            <?php if(count($bookings) > 0): ?>
                <?php foreach($bookings as $booking): ?>
                <div class="booking-item">
                    <div class="booking-info">
                        <h4><?php echo htmlspecialchars($booking['title']); ?></h4>
                        <p><i class="fas fa-building"></i> <?php echo htmlspecialchars($booking['hall_name']); ?></p>
                        <p><i class="fas fa-calendar"></i> <?php echo date('d M Y, h:i A', strtotime($booking['booking_date'])); ?></p>
                        <p><i class="fas fa-chair"></i> Seats: <?php echo $booking['seats']; ?></p>
                    </div>
                    <div>
                        <div class="booking-amount">₹<?php echo number_format($booking['total_amount'], 2); ?></div>
                        <span class="badge badge-completed"><?php echo $booking['payment_status']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 20px;">No bookings yet. Start watching movies!</p>
            <?php endif; ?>
        </div>
        
        <!-- Reviews -->
        <h2 class="section-title"><i class="fas fa-star"></i> My Reviews</h2>
        <div class="card">
            <?php if(count($reviews) > 0): ?>
                <?php foreach($reviews as $review): ?>
                <div class="review-item">
                    <div class="review-movie"><?php echo htmlspecialchars($review['title']); ?></div>
                    <div class="review-stars">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'far'; ?>" style="color: #f59e0b;"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="review-text"><?php echo nl2br(htmlspecialchars($review['review'])); ?></div>
                    <small style="color: #9ca3af;">Posted on <?php echo date('d M Y', strtotime($review['created_at'])); ?></small>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; padding: 20px;">No reviews yet. Share your thoughts about movies!</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Edit Profile Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div style="background: white; border-radius: 28px; padding: 30px; max-width: 500px; width: 90%;">
            <h2 style="margin-bottom: 20px;">Edit Profile</h2>
            <form id="editProfileForm">
                <div class="input-group" style="margin-bottom: 15px;">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 12px;">
                </div>
                <div class="input-group" style="margin-bottom: 15px;">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 12px;">
                </div>
                <div class="input-group" style="margin-bottom: 15px;">
                    <label>Phone</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" style="width: 100%; padding: 12px; border: 2px solid #e5e7eb; border-radius: 12px;">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" style="flex: 1; padding: 12px; background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; border-radius: 12px; cursor: pointer;">Save Changes</button>
                    <button type="button" onclick="closeEditModal()" style="flex: 1; padding: 12px; background: #6c757d; color: white; border: none; border-radius: 12px; cursor: pointer;">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showEditModal() {
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        document.getElementById('editProfileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('php/update_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Profile updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update profile');
            });
        });
    </script>
</body>
</html>