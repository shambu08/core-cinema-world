<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'php/db_connect.php';

$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 1;
$hall_id = isset($_GET['hall_id']) ? (int)$_GET['hall_id'] : 0;
$selected_show_time = isset($_GET['show_time']) ? $_GET['show_time'] : '';

// Get movie details from database
$movieStmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$movieStmt->execute([$movie_id]);
$movie = $movieStmt->fetch();

if(!$movie) {
    die("Movie not found");
}

// Define show timings for each movie (4 shows per day)
$showTimings = [
    'morning' => ['time' => '09:00 AM', 'type' => 'Morning Show', 'price_multiplier' => 0.9],
    'matinee' => ['time' => '12:30 PM', 'type' => 'Matinee Show', 'price_multiplier' => 1.0],
    'evening' => ['time' => '04:00 PM', 'type' => 'Evening Show', 'price_multiplier' => 1.2],
    'night' => ['time' => '08:30 PM', 'type' => 'Night Show', 'price_multiplier' => 1.5]
];

// Get all cinema halls
$allHallsStmt = $pdo->query("SELECT * FROM cinema_halls WHERE status = 'active' ORDER BY hall_name");
$allHalls = $allHallsStmt->fetchAll();

// Create movie-specific hall mapping (3 different halls for each movie)
// This ensures every movie has exactly 3 cinema halls
$movieHallList = [];

// First, try to get halls from seats table
$seatHallsStmt = $pdo->prepare("SELECT DISTINCT hall_id FROM seats WHERE movie_id = ?");
$seatHallsStmt->execute([$movie_id]);
$seatHallIds = $seatHallsStmt->fetchAll(PDO::FETCH_COLUMN);

if(count($seatHallIds) >= 3) {
    // Use first 3 halls from seats
    $movieHallList = array_slice($seatHallIds, 0, 3);
} else {
    // Assign 3 different halls based on movie ID (fallback)
    $movieHallList = [];
    $availableHalls = $allHalls;
    for($i = 0; $i < 3 && $i < count($availableHalls); $i++) {
        $hallIndex = ($movie_id + $i) % count($availableHalls);
        $movieHallList[] = $availableHalls[$hallIndex]['id'];
    }
}
$movieHallList = array_unique($movieHallList);

// Get full hall details for the assigned halls
$availableHalls = [];
if(!empty($movieHallList)) {
    $placeholders = implode(',', array_fill(0, count($movieHallList), '?'));
    $hallsStmt = $pdo->prepare("SELECT * FROM cinema_halls WHERE id IN ($placeholders) AND status = 'active'");
    $hallsStmt->execute($movieHallList);
    $availableHalls = $hallsStmt->fetchAll();
}

// If still no halls, assign default halls
if(empty($availableHalls)) {
    $defaultHalls = array_slice($allHalls, 0, 3);
    $availableHalls = $defaultHalls;
}

// Get selected hall name if hall is pre-selected
$selectedHallName = '';
if($hall_id > 0) {
    $hallStmt = $pdo->prepare("SELECT hall_name FROM cinema_halls WHERE id = ?");
    $hallStmt->execute([$hall_id]);
    $selectedHall = $hallStmt->fetch();
    if($selectedHall) {
        $selectedHallName = $selectedHall['hall_name'];
    }
}

// Get movie prices
$premiumPrice = $movie['premium_price'] ?? 350;
$basePrice = $movie['base_price'] ?? 180;

// Format rating display
$ratingValue = $movie['rating'];
$ratingDisplay = '';
$ratingStars = '';

if($ratingValue <= 0 || $ratingValue === null || $ratingValue == 0) {
    $ratingDisplay = 'Coming Soon';
    $ratingStars = '<i class="far fa-clock"></i>';
} else {
    $ratingDisplay = $ratingValue . ' / 10';
    $fullStars = floor($ratingValue / 2);
    $halfStar = ($ratingValue / 2) - $fullStars >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    
    $ratingStars = '';
    for($i = 0; $i < $fullStars; $i++) {
        $ratingStars .= '<i class="fas fa-star" style="color: #f59e0b;"></i>';
    }
    if($halfStar) {
        $ratingStars .= '<i class="fas fa-star-half-alt" style="color: #f59e0b;"></i>';
    }
    for($i = 0; $i < $emptyStars; $i++) {
        $ratingStars .= '<i class="far fa-star" style="color: #f59e0b;"></i>';
    }
}

// Define cast based on movie title (simplified)
$movieCast = [
    ['name' => 'Lead Actor', 'role' => 'Protagonist'],
    ['name' => 'Lead Actress', 'role' => 'Heroine'],
    ['name' => 'Supporting Actor', 'role' => 'Supporting Role'],
    ['name' => 'Character Artist', 'role' => 'Special Appearance'],
    ['name' => 'Comedian', 'role' => 'Comic Relief'],
    ['name' => 'Villain', 'role' => 'Antagonist']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($movie['title']); ?> - Core Cinema World</title>
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
        
        .movie-details-container {
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
        
        .selected-hall-info {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 16px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: <?php echo ($hall_id > 0 && $selectedHallName) ? 'block' : 'none'; ?>;
        }
        
        .selected-hall-info p {
            color: #92400e;
            font-weight: 600;
        }
        
        .selected-hall-info i {
            color: #f59e0b;
            margin-right: 8px;
        }
        
        .hero-section {
            background: white;
            border-radius: 28px;
            overflow: hidden;
            margin-bottom: 30px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        
        .hero-content {
            display: flex;
            flex-wrap: wrap;
        }
        
        .hero-poster {
            flex: 0 0 300px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .hero-poster img {
            width: 100%;
            height: auto;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .hero-info {
            flex: 1;
            padding: 30px;
        }
        
        .movie-title {
            font-size: 36px;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .movie-tagline {
            font-size: 16px;
            color: #667eea;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .movie-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f3f4f6;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
        }
        
        .meta-item i {
            color: #667eea;
        }
        
        .rating-badge {
            background: #f59e0b;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .rating-stars {
            display: inline-flex;
            gap: 3px;
            margin-left: 8px;
        }
        
        .movie-description {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .info-card {
            background: #f9fafb;
            border-radius: 16px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .info-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }
        
        .info-card i {
            font-size: 30px;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .info-card h4 {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 5px;
        }
        
        .info-card p {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .timings-section {
            background: white;
            border-radius: 24px;
            padding: 25px;
            margin: 30px 0;
        }
        
        .timings-section h2 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .timings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
        }
        
        .timing-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .timing-card:hover {
            transform: translateY(-5px);
            border-color: #667eea;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .timing-card.selected {
            border-color: #28a745;
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        }
        
        .timing-time {
            font-size: 24px;
            font-weight: 800;
            color: #1f2937;
        }
        
        .timing-type {
            font-size: 14px;
            color: #6b7280;
            margin: 5px 0;
        }
        
        .cast-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .cast-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
        }
        
        .cast-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .cast-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .cast-avatar i {
            font-size: 40px;
            color: white;
        }
        
        .cast-name {
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .cast-role {
            font-size: 12px;
            color: #6b7280;
        }
        
        .halls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .hall-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            transition: all 0.3s;
            cursor: pointer;
            border: 1px solid #e5e7eb;
        }
        
        .hall-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .hall-card h3 {
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .hall-card p {
            color: #6b7280;
            font-size: 13px;
            margin: 5px 0;
        }
        
        .book-now-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 15px;
            width: 100%;
            transition: all 0.3s;
        }
        
        .book-now-btn:hover {
            transform: scale(1.02);
        }
        
        .direct-book-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 18px;
            margin-top: 20px;
            width: 100%;
            transition: all 0.3s;
        }
        
        .direct-book-btn:hover {
            transform: scale(1.02);
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
        
        @media (max-width: 768px) {
            .hero-poster {
                flex: 0 0 100%;
                max-width: 250px;
                margin: 0 auto;
            }
            
            .movie-title {
                font-size: 28px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .cast-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .halls-grid {
                grid-template-columns: 1fr;
            }
            
            .timings-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .timing-time {
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
    
    <div class="movie-details-container">
        <a href="home.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Home</a>
        
        <!-- Show selected hall info if coming from cinema hall selection -->
        <div class="selected-hall-info">
            <p><i class="fas fa-check-circle"></i> You have selected: <strong><?php echo htmlspecialchars($selectedHallName); ?></strong> cinema hall</p>
        </div>
        
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="hero-content">
                <div class="hero-poster">
                    <img src="images/posters/<?php echo $movie['id']; ?>.jpg" 
                         alt="<?php echo htmlspecialchars($movie['title']); ?>"
                         onerror="this.src='images/posters/placeholder.jpg'">
                </div>
                <div class="hero-info">
                    <h1 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h1>
                    <div class="movie-tagline">Experience the magic of cinema</div>
                    
                    <div class="movie-meta">
                        <span class="meta-item rating-badge">
                            <i class="fas fa-star"></i> 
                            <?php echo $ratingDisplay; ?>
                            <span class="rating-stars"><?php echo $ratingStars; ?></span>
                        </span>
                        <span class="meta-item"><i class="fas fa-clock"></i> <?php echo $movie['duration']; ?></span>
                        <span class="meta-item"><i class="fas fa-tag"></i> <?php echo $movie['genre']; ?></span>
                        <span class="meta-item"><i class="fas fa-language"></i> <?php echo $movie['language']; ?></span>
                        <span class="meta-item"><i class="fas fa-ticket-alt"></i> Now Showing</span>
                    </div>
                    
                    <p class="movie-description"><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
                    
                    <!-- Direct Book Button when hall is pre-selected -->
                    <?php if($hall_id > 0 && $selectedHallName): ?>
                    <button class="direct-book-btn" onclick="directBook()">
                        <i class="fas fa-ticket-alt"></i> Book Tickets at <?php echo htmlspecialchars($selectedHallName); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Movie Information Grid -->
        <div class="info-grid">
            <div class="info-card">
                <i class="fas fa-user-tie"></i>
                <h4>Premium Class Price</h4>
                <p>₹<?php echo $premiumPrice; ?></p>
            </div>
            <div class="info-card">
                <i class="fas fa-chair"></i>
                <h4>Base Class Price</h4>
                <p>₹<?php echo $basePrice; ?></p>
            </div>
            <div class="info-card">
                <i class="fas fa-calendar-alt"></i>
                <h4>Release Date</h4>
                <p>2024</p>
            </div>
            <div class="info-card">
                <i class="fas fa-globe"></i>
                <h4>Country</h4>
                <p>India</p>
            </div>
        </div>
        
        <!-- Show Timings Section -->
        <div class="timings-section">
            <h2><i class="fas fa-clock"></i> Select Show Time</h2>
            <div class="timings-grid">
                <?php foreach($showTimings as $key => $show): ?>
                <div class="timing-card" data-show="<?php echo $key; ?>" data-time="<?php echo $show['time']; ?>" data-multiplier="<?php echo $show['price_multiplier']; ?>" onclick="selectShowTime('<?php echo $key; ?>', '<?php echo $show['time']; ?>', <?php echo $show['price_multiplier']; ?>)">
                    <div class="timing-time"><?php echo $show['time']; ?></div>
                    <div class="timing-type"><?php echo $show['type']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="selectedShowDisplay" style="margin-top: 15px; padding: 10px; background: #e8f0fe; border-radius: 10px; display: none; text-align: center;">
                <i class="fas fa-check-circle" style="color: #28a745;"></i> Selected Show: <strong id="selectedShowTime">-</strong>
            </div>
        </div>
        
        <!-- Cast Section -->
        <h2 class="section-title"><i class="fas fa-users"></i> Star Cast</h2>
        <div class="cast-grid">
            <?php foreach($movieCast as $cast): ?>
            <div class="cast-card">
                <div class="cast-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="cast-name"><?php echo htmlspecialchars($cast['name']); ?></div>
                <div class="cast-role"><?php echo htmlspecialchars($cast['role']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Cinema Halls Showing This Movie (3 halls guaranteed) -->
        <h2 class="section-title"><i class="fas fa-building"></i> Cinema Halls Showing This Movie</h2>
        <div class="halls-grid">
            <?php if(count($availableHalls) > 0): ?>
                <?php foreach($availableHalls as $hall): ?>
                <div class="hall-card" onclick="selectHall(<?php echo $hall['id']; ?>, '<?php echo addslashes($hall['hall_name']); ?>')">
                    <h3><i class="fas fa-building"></i> <?php echo htmlspecialchars($hall['hall_name']); ?></h3>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hall['city']); ?>, <?php echo htmlspecialchars($hall['state']); ?></p>
                    <p><i class="fas fa-tv"></i> <?php echo $hall['total_screens']; ?> Screens</p>
                    <p><i class="fas fa-clock"></i> <?php echo $hall['opening_time']; ?> - <?php echo $hall['closing_time']; ?></p>
                    <p><i class="fas fa-star"></i> Facilities: <?php echo htmlspecialchars($hall['facilities']); ?></p>
                    <button class="book-now-btn" onclick="event.stopPropagation(); selectHall(<?php echo $hall['id']; ?>, '<?php echo addslashes($hall['hall_name']); ?>')">
                        <i class="fas fa-ticket-alt"></i> Select Hall
                    </button>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="hall-card" style="text-align: center;">
                    <i class="fas fa-info-circle" style="font-size: 48px; color: #667eea;"></i>
                    <h3>No halls available</h3>
                    <p>This movie is currently not showing in any cinema hall. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        let selectedShowKey = '';
        let selectedShowTime = '';
        let selectedPriceMultiplier = 1;
        let selectedHallIdForBooking = <?php echo $hall_id; ?>;
        let selectedHallNameForBooking = '<?php echo addslashes($selectedHallName); ?>';
        let movieId = <?php echo $movie_id; ?>;
        let movieTitle = '<?php echo addslashes($movie['title']); ?>';
        
        function selectShowTime(showKey, showTime, multiplier) {
            // Remove selected class from all timing cards
            document.querySelectorAll('.timing-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            const selectedCard = document.querySelector(`.timing-card[data-show="${showKey}"]`);
            if(selectedCard) {
                selectedCard.classList.add('selected');
            }
            
            selectedShowKey = showKey;
            selectedShowTime = showTime;
            selectedPriceMultiplier = multiplier;
            
            // Show selected display
            const displayDiv = document.getElementById('selectedShowDisplay');
            displayDiv.style.display = 'block';
            document.getElementById('selectedShowTime').innerHTML = `${showTime} (${showKey.charAt(0).toUpperCase() + showKey.slice(1)} Show)`;
        }
        
        function selectHall(hallId, hallName) {
            if(!selectedShowTime) {
                alert('Please select a show time first!');
                return;
            }
            
            selectedHallIdForBooking = hallId;
            selectedHallNameForBooking = hallName;
            
            // Store in localStorage
            localStorage.setItem('selectedHallId', hallId);
            localStorage.setItem('selectedHallName', hallName);
            localStorage.setItem('selectedShowTime', selectedShowTime);
            localStorage.setItem('selectedShowKey', selectedShowKey);
            localStorage.setItem('priceMultiplier', selectedPriceMultiplier);
            localStorage.setItem('movieId', movieId);
            localStorage.setItem('movieTitle', movieTitle);
            
            // Proceed to seat selection
            window.location.href = `seat-selection.php?movie_id=${movieId}&title=${encodeURIComponent(movieTitle)}&hall_id=${hallId}&show_time=${encodeURIComponent(selectedShowTime)}`;
        }
        
        function directBook() {
            if(!selectedShowTime) {
                alert('Please select a show time first!');
                return;
            }
            
            localStorage.setItem('selectedHallId', selectedHallIdForBooking);
            localStorage.setItem('selectedHallName', selectedHallNameForBooking);
            localStorage.setItem('selectedShowTime', selectedShowTime);
            localStorage.setItem('selectedShowKey', selectedShowKey);
            localStorage.setItem('priceMultiplier', selectedPriceMultiplier);
            
            window.location.href = `seat-selection.php?movie_id=${movieId}&title=${encodeURIComponent(movieTitle)}&hall_id=${selectedHallIdForBooking}&show_time=${encodeURIComponent(selectedShowTime)}`;
        }
    </script>
</body>
</html>