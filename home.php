<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'php/db_connect.php';

// Get user loyalty points
$userStmt = $pdo->prepare("SELECT loyalty_points, total_points_earned, referral_code FROM users WHERE id = ?");
$userStmt->execute([$_SESSION['user_id']]);
$userData = $userStmt->fetch();
$userPoints = $userData ? $userData['loyalty_points'] : 0;
$userReferralCode = $userData ? $userData['referral_code'] : '';

// Get referral stats
$referralCount = 0;
$referralBonus = 0;

try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'referrals'");
    if($tableCheck->rowCount() > 0) {
        $referralCountStmt = $pdo->prepare("SELECT COUNT(*) as count FROM referrals WHERE referrer_id = ? AND status = 'completed'");
        $referralCountStmt->execute([$_SESSION['user_id']]);
        $referralCount = $referralCountStmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $referralBonusStmt = $pdo->prepare("SELECT SUM(referrer_bonus) as total FROM referrals WHERE referrer_id = ? AND status = 'completed'");
        $referralBonusStmt->execute([$_SESSION['user_id']]);
        $referralBonus = $referralBonusStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }
} catch(Exception $e) {
    $referralCount = 0;
    $referralBonus = 0;
}

// Get all cinema halls - ONLY UNIQUE HALLS (NO DUPLICATES)
$hallsStmt = $pdo->query("SELECT * FROM cinema_halls WHERE status = 'active' OR status IS NULL ORDER BY FIELD(state, 'Karnataka'), city, hall_name");
$allHalls = $hallsStmt->fetchAll();

// Get unique cities from cinema_halls (PHP method - no SQL error)
$uniqueCities = [];
foreach($allHalls as $hall) {
    $cityKey = $hall['city'] . '|' . $hall['state'];
    if(!isset($uniqueCities[$cityKey])) {
        $uniqueCities[$cityKey] = [
            'city_name' => $hall['city'],
            'state' => $hall['state']
        ];
    }
}
$allCities = array_values($uniqueCities);

// Get all movies
$moviesStmt = $pdo->query("SELECT id, title, genre, language, rating, duration FROM movies ORDER BY id");
$allMovies = $moviesStmt->fetchAll();
$totalMovies = count($allMovies);

// Get movie counts by genre
$genreCounts = [];
$genreStmt = $pdo->query("SELECT genre, COUNT(*) as count FROM movies GROUP BY genre");
while($row = $genreStmt->fetch()) {
    $genreCounts[$row['genre']] = $row['count'];
}

// Get movie counts by language
$languageCounts = [];
$langStmt = $pdo->query("SELECT language, COUNT(*) as count FROM movies GROUP BY language");
while($row = $langStmt->fetch()) {
    $languageCounts[$row['language']] = $row['count'];
}

// Create proper movie-to-hall mapping without duplicates
$movieHallMapping = [];
$hallIds = array_column($allHalls, 'id');
$numHalls = count($hallIds);

if($numHalls > 0) {
    foreach($allMovies as $index => $movie) {
        $assignedHalls = [];
        $startIndex = ($movie['id'] * 3) % $numHalls;
        for($i = 0; $i < 3 && $i < $numHalls; $i++) {
            $hallIndex = ($startIndex + $i) % $numHalls;
            $assignedHalls[] = $hallIds[$hallIndex];
        }
        $movieHallMapping[$movie['id']] = array_unique($assignedHalls);
    }
}

// Create hall-to-movie mapping (no duplicates)
$hallMovieMapping = [];
foreach($allHalls as $hall) {
    $hallMovieMapping[$hall['id']] = [];
    foreach($allMovies as $movie) {
        if(isset($movieHallMapping[$movie['id']]) && in_array($hall['id'], $movieHallMapping[$movie['id']])) {
            $hallMovieMapping[$hall['id']][] = $movie;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Core Cinema World - Home</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .main-nav-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .main-nav-btn {
            padding: 12px 24px;
            background: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            color: #1f2937;
        }
        
        .main-nav-btn i {
            font-size: 18px;
            color: #667eea;
        }
        
        .main-nav-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .main-nav-btn.active i {
            color: white;
        }
        
        .main-nav-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        
        .points-badge {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 8px;
        }
        
        .content-panel {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .content-panel.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .cinema-hall-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .cinema-hall-card:hover {
            transform: translateX(5px);
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .cinema-info h3 {
            color: #1f2937;
            margin-bottom: 8px;
            font-size: 18px;
        }
        
        .cinema-info p {
            color: #6b7280;
            font-size: 13px;
            margin: 3px 0;
        }
        
        .movie-count-badge {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            margin-left: 10px;
        }
        
        .view-movies-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .view-movies-btn:hover {
            transform: scale(1.02);
        }
        
        .movies-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }
        
        .back-btn-panel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .back-btn-panel:hover {
            background: #5a6268;
        }
        
        .breadcrumb {
            margin-bottom: 20px;
            padding: 12px 15px;
            background: #f8f9fa;
            border-radius: 12px;
            font-size: 14px;
        }
        
        .movie-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .movie-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);
        }
        
        .movie-poster {
            width: 100%;
            height: 320px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .movie-poster img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .movie-info {
            padding: 18px;
        }
        
        .movie-info h3 {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .movie-info p {
            font-size: 13px;
            color: #6b7280;
            margin: 6px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .movie-info p i {
            width: 20px;
            color: #667eea;
        }
        
        .genre-grid, .language-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .genre-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .genre-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .genre-card i {
            font-size: 30px;
            margin-bottom: 10px;
            display: block;
        }
        
        .genre-card h4 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .genre-card p {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .language-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .language-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .language-card i {
            font-size: 30px;
            margin-bottom: 10px;
            display: block;
        }
        
        .language-card h4 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .language-card p {
            font-size: 12px;
            opacity: 0.8;
        }
        
        .location-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .location-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #e5e7eb;
        }
        
        .location-card:hover {
            transform: translateY(-5px);
            border-color: #667eea;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .location-card i {
            font-size: 40px;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .location-card h4 {
            font-size: 18px;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .feature-panel {
            text-align: center;
            padding: 40px;
        }
        
        .feature-panel h2 {
            font-size: 32px;
            font-weight: 700;
            color: white;
            margin-bottom: 15px;
        }
        
        .feature-panel p {
            font-size: 16px;
            color: rgba(255,255,255,0.8);
            margin-bottom: 30px;
        }
        
        .mood-links, .loyalty-links {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .mood-link, .loyalty-link, .chat-link {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 14px 28px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .mood-link:hover, .loyalty-link:hover, .chat-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }
        
        .points-display {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .points-display .points-value {
            font-size: 36px;
            font-weight: 800;
            color: #92400e;
        }
        
        .referral-stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 20px auto;
            max-width: 400px;
            flex-wrap: wrap;
        }
        
        .referral-stat-card {
            background: rgba(255,255,255,0.15);
            border-radius: 16px;
            padding: 15px 25px;
            text-align: center;
            backdrop-filter: blur(10px);
        }
        
        .referral-stat-card h3 {
            font-size: 28px;
            font-weight: 800;
            color: white;
        }
        
        .referral-stat-card p {
            font-size: 12px;
            color: rgba(255,255,255,0.8);
        }
        
        .referral-code-box {
            background: rgba(255,255,255,0.15);
            border-radius: 16px;
            padding: 15px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .referral-code {
            font-size: 20px;
            font-weight: 800;
            font-family: monospace;
            letter-spacing: 2px;
            color: #fde68a;
        }
        
        .copy-btn-sm {
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }
        
        .support-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .contact-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .contact-form input,
        .contact-form textarea {
            padding: 14px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .contact-form input:focus,
        .contact-form textarea:focus {
            border-color: #667eea;
            outline: none;
        }
        
        .submit-btn {
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
        }
        
        .selected-hall {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-radius: 16px;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: none;
            align-items: center;
            justify-content: space-between;
        }
        
        .clear-hall-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .search-section {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .search-bar {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .search-bar input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 14px;
        }
        
        .voice-btn {
            padding: 12px 20px;
            background: #f3f4f6;
            border: none;
            border-radius: 12px;
            cursor: pointer;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .hall-stats {
            font-size: 12px;
            color: #6b7280;
            margin-top: 10px;
        }
        
        @media (max-width: 768px) {
            .main-nav-btn {
                padding: 8px 16px;
                font-size: 12px;
            }
            
            .movies-container {
                grid-template-columns: 1fr;
            }
            
            .movie-poster {
                height: 250px;
            }
            
            .genre-grid, .language-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .mood-link, .loyalty-link, .chat-link {
                padding: 10px 18px;
                font-size: 13px;
            }
            
            .referral-code-box {
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
            <a href="user-profile.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-user-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            </a>
            <span class="points-badge"><i class="fas fa-coins"></i> <?php echo number_format($userPoints); ?> pts</span>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>
    
    <div class="home-container">
        <div id="selectedHallDisplay" class="selected-hall">
            <div>
                <i class="fas fa-building" style="color: #667eea;"></i>
                <strong>Selected Cinema:</strong>
                <span id="selectedHallName"></span>
            </div>
            <button class="clear-hall-btn" onclick="clearSelectedHall()">✕ Clear</button>
        </div>
        
        <div class="main-nav-buttons">
            <button class="main-nav-btn active" data-panel="movies"><i class="fas fa-film"></i> Movies</button>
            <button class="main-nav-btn" data-panel="cinemaHalls"><i class="fas fa-building"></i> Cinema Halls</button>
            <button class="main-nav-btn" data-panel="genre"><i class="fas fa-tag"></i> Genre</button>
            <button class="main-nav-btn" data-panel="languages"><i class="fas fa-language"></i> Languages</button>
            <button class="main-nav-btn" data-panel="location"><i class="fas fa-map-marker-alt"></i> Location</button>
            <button class="main-nav-btn" data-panel="mood"><i class="fas fa-smile-wink"></i> Mood</button>
            <button class="main-nav-btn" data-panel="loyalty"><i class="fas fa-gift"></i> Loyalty</button>
            <button class="main-nav-btn" data-panel="chatbot"><i class="fas fa-robot"></i> AI Chat</button>
            <button class="main-nav-btn" data-panel="support"><i class="fas fa-headset"></i> Support</button>
        </div>
        
        <!-- PANEL 1: MOVIES -->
        <div id="moviesPanel" class="content-panel active">
            <div id="moviesDirectView">
                <div class="search-section">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="movieSearchInput" placeholder="Search movies...">
                        <button id="voiceMovieSearch" class="voice-btn"><i class="fas fa-microphone"></i> Voice</button>
                    </div>
                </div>
                <div id="moviesListDirect" class="movies-container">
                    <div class="loading"><div class="spinner"></div><p>Loading movies...</p></div>
                </div>
            </div>
            <div id="movieCinemaHallsView" style="display:none;">
                <button class="back-btn-panel" onclick="showMoviesDirectList()"><i class="fas fa-arrow-left"></i> Back to Movies</button>
                <div class="breadcrumb" id="movieCinemaBreadcrumb"></div>
                <div id="movieCinemaHallsList" class="cinema-list"></div>
            </div>
        </div>
        
        <!-- PANEL 2: CINEMA HALLS -->
        <div id="cinemaHallsPanel" class="content-panel">
            <div id="cinemaHallsListView">
                <div class="search-section">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" id="cinemaSearchInput" placeholder="Search cinema halls...">
                    </div>
                    <div class="hall-stats">
                        <i class="fas fa-info-circle"></i> Total <?php echo count($allHalls); ?> unique cinema halls across India
                    </div>
                </div>
                <div id="cinemaHallsList"></div>
            </div>
            <div id="cinemaHallsMoviesView" style="display:none;">
                <button class="back-btn-panel" onclick="showCinemaHallsList()"><i class="fas fa-arrow-left"></i> Back to Cinema Halls</button>
                <div class="breadcrumb" id="cinemaHallsBreadcrumb"></div>
                <div id="cinemaHallsMoviesGrid" class="movies-container"></div>
            </div>
        </div>
        
        <!-- PANEL 3: GENRE -->
        <div id="genrePanel" class="content-panel">
            <div id="genreSelectView">
                <div class="genre-grid" id="genreList"></div>
            </div>
            <div id="genreMoviesView" style="display:none;">
                <button class="back-btn-panel" onclick="showGenreSelect()"><i class="fas fa-arrow-left"></i> Back to Genres</button>
                <div class="breadcrumb" id="genreMoviesBreadcrumb"></div>
                <div id="genreMoviesGrid" class="movies-container"></div>
            </div>
        </div>
        
        <!-- PANEL 4: LANGUAGES -->
        <div id="languagesPanel" class="content-panel">
            <div id="languagesSelectView">
                <div class="language-grid" id="languageList"></div>
            </div>
            <div id="languagesMoviesView" style="display:none;">
                <button class="back-btn-panel" onclick="showLanguagesSelect()"><i class="fas fa-arrow-left"></i> Back to Languages</button>
                <div class="breadcrumb" id="languagesMoviesBreadcrumb"></div>
                <div id="languagesMoviesGrid" class="movies-container"></div>
            </div>
        </div>
        
        <!-- PANEL 5: LOCATION -->
        <div id="locationPanel" class="content-panel">
            <div id="locationSelectView">
                <div class="location-grid" id="locationCityList"></div>
            </div>
            <div id="locationHallsView" style="display:none;">
                <button class="back-btn-panel" onclick="showLocationSelect()"><i class="fas fa-arrow-left"></i> Back to Cities</button>
                <div class="breadcrumb" id="locationHallsBreadcrumb"></div>
                <div id="locationHallsList"></div>
            </div>
            <div id="locationMoviesView" style="display:none;">
                <button class="back-btn-panel" onclick="showLocationHalls()"><i class="fas fa-arrow-left"></i> Back to Halls</button>
                <div class="breadcrumb" id="locationMoviesBreadcrumb"></div>
                <div id="locationMoviesGrid" class="movies-container"></div>
            </div>
        </div>
        
        <!-- PANEL 6: MOOD -->
        <div id="moodPanel" class="content-panel">
            <div class="feature-panel">
                <h2><i class="fas fa-magic"></i> Mood-Based Movie Suggestions</h2>
                <p>Select your current mood and get personalized movie recommendations</p>
                <div class="mood-links">
                    <a href="mood-suggestions.php?mood=happy" class="mood-link">😊 Happy</a>
                    <a href="mood-suggestions.php?mood=sad" class="mood-link">😢 Sad / Emotional</a>
                    <a href="mood-suggestions.php?mood=thriller" class="mood-link">😱 Thriller</a>
                    <a href="mood-suggestions.php?mood=romantic" class="mood-link">❤️ Romantic</a>
                    <a href="mood-suggestions.php?mood=energetic" class="mood-link">⚡ Energetic</a>
                    <a href="mood-suggestions.php?mood=relaxing" class="mood-link">😌 Relaxing</a>
                </div>
                <div style="margin-top: 40px; padding: 20px; background: rgba(255,255,255,0.1); border-radius: 20px;">
                    <p style="color: rgba(255,255,255,0.8);">
                        <i class="fas fa-info-circle"></i> Our AI recommends movies based on your mood. 
                        Select a mood to see personalized suggestions!
                    </p>
                </div>
            </div>
        </div>
        
        <!-- PANEL 7: LOYALTY & REWARDS -->
        <div id="loyaltyPanel" class="content-panel">
            <div class="feature-panel">
                <h2><i class="fas fa-gift"></i> Loyalty & Rewards Program</h2>
                <p>Earn points for every booking, review, and referral!</p>
                
                <div class="points-display">
                    <i class="fas fa-coins" style="font-size: 30px; color: #f59e0b;"></i>
                    <div class="points-value"><?php echo number_format($userPoints); ?> Points</div>
                    <div>≈ ₹<?php echo floor($userPoints / 10); ?> value</div>
                </div>
                
                <div style="background: rgba(255,255,255,0.1); border-radius: 20px; padding: 20px; margin: 20px 0;">
                    <h3 style="color: white; margin-bottom: 15px;"><i class="fas fa-share-alt"></i> Refer & Earn</h3>
                    <p style="color: rgba(255,255,255,0.8); font-size: 14px;">Invite friends and earn bonus points for every successful referral!</p>
                    
                    <div class="referral-stats">
                        <div class="referral-stat-card"><h3><?php echo $referralCount; ?></h3><p>Friends Referred</p></div>
                        <div class="referral-stat-card"><h3><?php echo $referralBonus; ?></h3><p>Bonus Points Earned</p></div>
                    </div>
                    
                    <div class="referral-code-box">
                        <span class="referral-code"><i class="fas fa-code"></i> <?php echo htmlspecialchars($userReferralCode); ?></span>
                        <button class="copy-btn-sm" onclick="copyReferralCodeFromLoyalty()"><i class="fas fa-copy"></i> Copy Code</button>
                    </div>
                    
                    <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; margin-top: 10px;">
                        <div style="background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 20px;"><i class="fas fa-user-plus"></i> You earn: <strong>50 points</strong></div>
                        <div style="background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 20px;"><i class="fas fa-gift"></i> Friend earns: <strong>25 points + 10% coupon</strong></div>
                    </div>
                    
                    <a href="referral.php" class="loyalty-link" style="margin-top: 15px; display: inline-block;"><i class="fas fa-chart-line"></i> View All Referrals & Coupons</a>
                </div>
                
                <div class="loyalty-links">
                    <a href="loyalty-points.php" class="loyalty-link"><i class="fas fa-chart-line"></i> View My Points</a>
                    <a href="loyalty-points.php#reviews" class="loyalty-link"><i class="fas fa-star"></i> Write a Review</a>
                    <a href="loyalty-points.php#redeem" class="loyalty-link"><i class="fas fa-tag"></i> Redeem Points</a>
                </div>
                
                <div style="margin-top: 30px; padding: 20px; background: rgba(255,255,255,0.1); border-radius: 20px;">
                    <h4 style="color: white; margin-bottom: 15px;">How to Earn Points</h4>
                    <div style="display: flex; justify-content: center; gap: 30px; flex-wrap: wrap;">
                        <div><i class="fas fa-ticket-alt"></i> 10 pts/ticket</div>
                        <div><i class="fas fa-star"></i> 20 pts/review</div>
                        <div><i class="fas fa-user-friends"></i> 50 pts/referral</div>
                    </div>
                    <p style="margin-top: 15px; color: rgba(255,255,255,0.7);">100 points = ₹10 discount on your next booking!</p>
                </div>
            </div>
        </div>
        
        <!-- PANEL 8: AI CHAT BOT -->
        <div id="chatbotPanel" class="content-panel">
            <div class="feature-panel">
                <h2><i class="fas fa-robot"></i> AI Movie Assistant</h2>
                <p>Ask me anything about movies, show timings, ticket prices, and more!</p>
                <div style="background: rgba(255,255,255,0.1); border-radius: 20px; padding: 30px; margin: 20px 0;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 20px; flex-wrap: wrap;">
                        <div style="text-align: center;"><i class="fas fa-film" style="font-size: 40px; color: #f59e0b;"></i><p style="margin-top: 10px;">Movie Recommendations</p></div>
                        <div style="text-align: center;"><i class="fas fa-clock" style="font-size: 40px; color: #f59e0b;"></i><p style="margin-top: 10px;">Show Timings</p></div>
                        <div style="text-align: center;"><i class="fas fa-tag" style="font-size: 40px; color: #f59e0b;"></i><p style="margin-top: 10px;">Ticket Prices</p></div>
                        <div style="text-align: center;"><i class="fas fa-map-marker-alt" style="font-size: 40px; color: #f59e0b;"></i><p style="margin-top: 10px;">Cinema Locations</p></div>
                    </div>
                </div>
                <a href="chat-bot.php" class="chat-link"><i class="fas fa-comments"></i> Start Chatting with AI Assistant</a>
                <div style="margin-top: 30px; padding: 20px; background: rgba(255,255,255,0.1); border-radius: 20px;">
                    <h4 style="color: white; margin-bottom: 15px;">Try asking me:</h4>
                    <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
                        <span style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 12px;">🎬 Show me action movies</span>
                        <span style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 12px;">⏰ What are show timings?</span>
                        <span style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 12px;">💰 Ticket prices</span>
                        <span style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 12px;">⭐ Top rated movies</span>
                        <span style="background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 20px; font-size: 12px;">📍 Cinema halls near me</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- PANEL 9: SUPPORT -->
        <div id="supportPanel" class="content-panel">
            <div class="support-container">
                <h3 style="text-align:center; margin-bottom:20px;"><i class="fas fa-headset"></i> Customer Support</h3>
                <p style="text-align:center; margin-bottom:20px; color:#6b7280;">Have questions? We're here to help!</p>
                <form id="supportForm" class="contact-form">
                    <input type="text" id="supportName" placeholder="Your Name" required>
                    <input type="email" id="supportEmail" placeholder="Email Address" required>
                    <textarea id="supportMessage" rows="5" placeholder="Your Message / Query" required></textarea>
                    <button type="submit" class="submit-btn"><i class="fas fa-paper-plane"></i> Send Message</button>
                </form>
                <div style="margin-top:30px; padding:20px; background:#f8f9fa; border-radius:12px;">
                    <h4><i class="fas fa-phone"></i> Contact Info</h4>
                    <p>📞 +91 80 1234 5678</p>
                    <p>✉️ support@corecinemaworld.com</p>
                    <p>🏢 Bengaluru, Karnataka</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        window.citiesData = <?php echo json_encode($allCities); ?>;
        window.hallsData = <?php echo json_encode($allHalls); ?>;
        window.allMoviesData = <?php echo json_encode($allMovies); ?>;
        window.genreCounts = <?php echo json_encode($genreCounts); ?>;
        window.languageCounts = <?php echo json_encode($languageCounts); ?>;
        window.movieHallMapping = <?php echo json_encode($movieHallMapping); ?>;
        window.hallMovieMapping = <?php echo json_encode($hallMovieMapping); ?>;
        
        let selectedHallId = localStorage.getItem('selectedHallId') || null;
        let selectedHallName = localStorage.getItem('selectedHallName') || '';
        let selectedGenre = '';
        let selectedLanguage = '';
        let selectedCityName = '';
        
        const genreIcons = {
            'Action': 'fa-fist-raised', 'Comedy': 'fa-laugh-squint', 'Drama': 'fa-mask',
            'Thriller': 'fa-skull', 'Romance': 'fa-heart', 'Family': 'fa-users',
            'Adventure': 'fa-mountain', 'Fantasy': 'fa-magic'
        };
        
        function copyReferralCodeFromLoyalty() {
            const code = '<?php echo htmlspecialchars($userReferralCode); ?>';
            navigator.clipboard.writeText(code);
            alert('✅ Referral code copied! Share it with your friends.\n\nThey will get 25 bonus points and a 10% welcome coupon!');
        }
        
        function updateSelectedHallDisplay() {
            const displayDiv = document.getElementById('selectedHallDisplay');
            if(displayDiv && selectedHallId && selectedHallName) {
                displayDiv.style.display = 'flex';
                document.getElementById('selectedHallName').innerHTML = `<strong>${selectedHallName}</strong>`;
            } else if(displayDiv) {
                displayDiv.style.display = 'none';
            }
        }
        
        function clearSelectedHall() {
            selectedHallId = null;
            selectedHallName = '';
            localStorage.removeItem('selectedHallId');
            localStorage.removeItem('selectedHallName');
            updateSelectedHallDisplay();
            alert('✅ Cinema selection cleared!');
        }
        
        document.querySelectorAll('.main-nav-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.main-nav-btn').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.content-panel').forEach(p => p.classList.remove('active'));
                btn.classList.add('active');
                const panelId = btn.dataset.panel + 'Panel';
                document.getElementById(panelId).classList.add('active');
            });
        });
        
        // ========== MOVIES PANEL ==========
        let currentMovieSearch = '';
        
        function loadDirectMovies() {
            const grid = document.getElementById('moviesListDirect');
            grid.innerHTML = '<div class="loading"><div class="spinner"></div><p>Loading movies...</p></div>';
            fetch(`php/get_movies.php?genre=all&language=all&search=${currentMovieSearch}&t=${Date.now()}`)
                .then(response => response.json())
                .then(movies => {
                    if(movies.error || movies.length === 0) {
                        grid.innerHTML = '<div class="loading">No movies found</div>';
                        return;
                    }
                    grid.innerHTML = movies.map(movie => {
                        const posterPath = `images/posters/${movie.id}.jpg`;
                        return `<div class="movie-card" onclick="selectMovieForDetails(${movie.id}, '${escapeHtml(movie.title)}')">
                            <div class="movie-poster"><img src="${posterPath}" alt="${escapeHtml(movie.title)}" onerror="this.src='images/posters/placeholder.jpg'"></div>
                            <div class="movie-info">
                                <h3>${escapeHtml(movie.title)}</h3>
                                <p><i class="fas fa-tag"></i> ${escapeHtml(movie.genre)}</p>
                                <p><i class="fas fa-language"></i> ${escapeHtml(movie.language)}</p>
                                <p><i class="fas fa-clock"></i> ${escapeHtml(movie.duration)}</p>
                                <p><i class="fas fa-star" style="color:#f59e0b;"></i> <span class="rating">${movie.rating || 'N/A'}</span>/10</p>
                            </div>
                        </div>`;
                    }).join('');
                })
                .catch(error => {
                    console.error('Error:', error);
                    grid.innerHTML = '<div class="error-message">Error loading movies. Please refresh the page.</div>';
                });
        }
        
        function selectMovieForDetails(movieId, movieTitle) {
            window.location.href = `movie-details.php?movie_id=${movieId}&hall_id=0`;
        }
        
        function showMoviesDirectList() {
            document.getElementById('moviesDirectView').style.display = 'block';
            document.getElementById('movieCinemaHallsView').style.display = 'none';
            loadDirectMovies();
        }
        
        document.getElementById('movieSearchInput')?.addEventListener('input', (e) => {
            currentMovieSearch = e.target.value;
            loadDirectMovies();
        });
        
        // ========== CINEMA HALLS PANEL ==========
        let currentCinemaSearch = '';
        
        function displayCinemaHalls() {
            const container = document.getElementById('cinemaHallsList');
            let filteredHalls = window.hallsData;
            if(currentCinemaSearch) {
                filteredHalls = window.hallsData.filter(hall => 
                    hall.hall_name.toLowerCase().includes(currentCinemaSearch.toLowerCase()) || 
                    hall.city.toLowerCase().includes(currentCinemaSearch.toLowerCase())
                );
            }
            
            const karnatakaHalls = filteredHalls.filter(hall => hall.state === 'Karnataka');
            const otherHalls = filteredHalls.filter(hall => hall.state !== 'Karnataka');
            
            let html = '';
            if(karnatakaHalls.length > 0) {
                html += `<h3 style="margin: 15px 0 10px 0; color: #667eea;"><i class="fas fa-map-marker-alt"></i> Karnataka (${karnatakaHalls.length} halls)</h3>`;
                html += karnatakaHalls.map(hall => {
                    const movieCount = window.hallMovieMapping[hall.id] ? window.hallMovieMapping[hall.id].length : 0;
                    return `<div class="cinema-hall-card" onclick="selectHallForMovies(${hall.id}, '${hall.hall_name}')">
                        <div class="cinema-info">
                            <h3><i class="fas fa-building"></i> ${hall.hall_name} <span class="movie-count-badge">${movieCount} Movies</span></h3>
                            <p><i class="fas fa-map-marker-alt"></i> ${hall.city}, ${hall.state}</p>
                            <p><i class="fas fa-tv"></i> ${hall.total_screens} Screens</p>
                            <p><i class="fas fa-clock"></i> ${hall.opening_time} - ${hall.closing_time}</p>
                        </div>
                        <button class="view-movies-btn" onclick="event.stopPropagation(); selectHallForMovies(${hall.id}, '${hall.hall_name}')">
                            <i class="fas fa-ticket-alt"></i> View Movies (${movieCount})
                        </button>
                    </div>`;
                }).join('');
            }
            
            if(otherHalls.length > 0) {
                html += `<h3 style="margin: 25px 0 10px 0; color: #667eea;"><i class="fas fa-globe"></i> Other Cities (${otherHalls.length} halls)</h3>`;
                html += otherHalls.map(hall => {
                    const movieCount = window.hallMovieMapping[hall.id] ? window.hallMovieMapping[hall.id].length : 0;
                    return `<div class="cinema-hall-card" onclick="selectHallForMovies(${hall.id}, '${hall.hall_name}')">
                        <div class="cinema-info">
                            <h3><i class="fas fa-building"></i> ${hall.hall_name} <span class="movie-count-badge">${movieCount} Movies</span></h3>
                            <p><i class="fas fa-map-marker-alt"></i> ${hall.city}, ${hall.state}</p>
                            <p><i class="fas fa-tv"></i> ${hall.total_screens} Screens</p>
                            <p><i class="fas fa-clock"></i> ${hall.opening_time} - ${hall.closing_time}</p>
                        </div>
                        <button class="view-movies-btn" onclick="event.stopPropagation(); selectHallForMovies(${hall.id}, '${hall.hall_name}')">
                            <i class="fas fa-ticket-alt"></i> View Movies (${movieCount})
                        </button>
                    </div>`;
                }).join('');
            }
            container.innerHTML = html;
        }
        
        function selectHallForMovies(hallId, hallName) {
            selectedHallId = hallId;
            selectedHallName = hallName;
            localStorage.setItem('selectedHallId', hallId);
            localStorage.setItem('selectedHallName', hallName);
            updateSelectedHallDisplay();
            const movies = window.hallMovieMapping[hallId] || [];
            document.getElementById('cinemaHallsListView').style.display = 'none';
            document.getElementById('cinemaHallsMoviesView').style.display = 'block';
            document.getElementById('cinemaHallsBreadcrumb').innerHTML = `<i class="fas fa-building"></i> ${hallName} > Select a Movie (${movies.length} movies available)`;
            loadMoviesForHall(movies, hallName);
        }
        
        function loadMoviesForHall(movies, hallName) {
            const grid = document.getElementById('cinemaHallsMoviesGrid');
            if(movies.length === 0) {
                grid.innerHTML = '<div class="error-message">No movies currently playing at this cinema hall.</div>';
                return;
            }
            grid.innerHTML = movies.map(movie => `
                <div class="movie-card" onclick="selectMovieFromHall(${movie.id}, '${escapeHtml(movie.title)}')">
                    <div class="movie-poster"><img src="images/posters/${movie.id}.jpg" alt="${escapeHtml(movie.title)}" onerror="this.src='images/posters/placeholder.jpg'"></div>
                    <div class="movie-info">
                        <h3>${escapeHtml(movie.title)}</h3>
                        <p><i class="fas fa-tag"></i> ${escapeHtml(movie.genre)}</p>
                        <p><i class="fas fa-language"></i> ${escapeHtml(movie.language)}</p>
                        <p><i class="fas fa-clock"></i> ${escapeHtml(movie.duration)}</p>
                        <p><i class="fas fa-star" style="color:#f59e0b;"></i> <span class="rating">${movie.rating || 'N/A'}</span>/10</p>
                        <p style="margin-top:10px; color:#28a745;"><i class="fas fa-check-circle"></i> Playing at ${hallName}</p>
                    </div>
                </div>
            `).join('');
        }
        
        function selectMovieFromHall(movieId, movieTitle) {
            window.location.href = `movie-details.php?movie_id=${movieId}&hall_id=${selectedHallId}`;
        }
        
        function showCinemaHallsList() {
            document.getElementById('cinemaHallsListView').style.display = 'block';
            document.getElementById('cinemaHallsMoviesView').style.display = 'none';
            displayCinemaHalls();
        }
        
        document.getElementById('cinemaSearchInput')?.addEventListener('input', (e) => {
            currentCinemaSearch = e.target.value;
            displayCinemaHalls();
        });
        
        // ========== GENRE PANEL ==========
        function displayGenres() {
            const container = document.getElementById('genreList');
            const genres = ['Action', 'Comedy', 'Drama', 'Thriller', 'Romance', 'Family', 'Adventure', 'Fantasy'];
            container.innerHTML = genres.map(genre => `
                <div class="genre-card" onclick="selectGenre('${genre}')">
                    <i class="fas ${genreIcons[genre] || 'fa-film'}"></i>
                    <h4>${genre}</h4>
                    <p>${window.genreCounts[genre] || 0} Movies</p>
                </div>
            `).join('');
        }
        
        function selectGenre(genre) {
            selectedGenre = genre;
            document.getElementById('genreSelectView').style.display = 'none';
            document.getElementById('genreMoviesView').style.display = 'block';
            document.getElementById('genreMoviesBreadcrumb').innerHTML = `<i class="fas fa-tag"></i> ${genre} Movies`;
            loadMoviesByGenre(genre);
        }
        
        function loadMoviesByGenre(genre) {
            const grid = document.getElementById('genreMoviesGrid');
            grid.innerHTML = '<div class="loading"><div class="spinner"></div><p>Loading movies...</p></div>';
            fetch(`php/get_movies.php?genre=${genre}&language=all&search=&t=${Date.now()}`)
                .then(response => response.json())
                .then(movies => {
                    grid.innerHTML = movies.map(movie => `
                        <div class="movie-card" onclick="selectGenreMovie(${movie.id}, '${escapeHtml(movie.title)}')">
                            <div class="movie-poster"><img src="images/posters/${movie.id}.jpg" alt="${escapeHtml(movie.title)}" onerror="this.src='images/posters/placeholder.jpg'"></div>
                            <div class="movie-info">
                                <h3>${escapeHtml(movie.title)}</h3>
                                <p><i class="fas fa-tag"></i> ${escapeHtml(movie.genre)}</p>
                                <p><i class="fas fa-language"></i> ${escapeHtml(movie.language)}</p>
                                <p><i class="fas fa-clock"></i> ${escapeHtml(movie.duration)}</p>
                                <p><i class="fas fa-star" style="color:#f59e0b;"></i> <span class="rating">${movie.rating || 'N/A'}</span>/10</p>
                            </div>
                        </div>
                    `).join('');
                });
        }
        
        function selectGenreMovie(movieId, movieTitle) {
            window.location.href = `movie-details.php?movie_id=${movieId}&hall_id=0`;
        }
        
        function showGenreSelect() {
            document.getElementById('genreSelectView').style.display = 'block';
            document.getElementById('genreMoviesView').style.display = 'none';
            displayGenres();
        }
        
        // ========== LANGUAGES PANEL ==========
        function displayLanguages() {
            const container = document.getElementById('languageList');
            const languages = ['Hindi', 'English', 'Kannada', 'Tamil', 'Telugu', 'Malayalam'];
            container.innerHTML = languages.map(lang => `
                <div class="language-card" onclick="selectLanguage('${lang}')">
                    <i class="fas fa-language"></i>
                    <h4>${lang}</h4>
                    <p>${window.languageCounts[lang] || 0} Movies</p>
                </div>
            `).join('');
        }
        
        function selectLanguage(language) {
            selectedLanguage = language;
            document.getElementById('languagesSelectView').style.display = 'none';
            document.getElementById('languagesMoviesView').style.display = 'block';
            document.getElementById('languagesMoviesBreadcrumb').innerHTML = `<i class="fas fa-language"></i> ${language} Movies`;
            loadMoviesByLanguage(language);
        }
        
        function loadMoviesByLanguage(language) {
            const grid = document.getElementById('languagesMoviesGrid');
            grid.innerHTML = '<div class="loading"><div class="spinner"></div><p>Loading movies...</p></div>';
            fetch(`php/get_movies.php?genre=all&language=${language}&search=&t=${Date.now()}`)
                .then(response => response.json())
                .then(movies => {
                    grid.innerHTML = movies.map(movie => `
                        <div class="movie-card" onclick="selectLanguageMovie(${movie.id}, '${escapeHtml(movie.title)}')">
                            <div class="movie-poster"><img src="images/posters/${movie.id}.jpg" alt="${escapeHtml(movie.title)}" onerror="this.src='images/posters/placeholder.jpg'"></div>
                            <div class="movie-info">
                                <h3>${escapeHtml(movie.title)}</h3>
                                <p><i class="fas fa-tag"></i> ${escapeHtml(movie.genre)}</p>
                                <p><i class="fas fa-language"></i> ${escapeHtml(movie.language)}</p>
                                <p><i class="fas fa-clock"></i> ${escapeHtml(movie.duration)}</p>
                                <p><i class="fas fa-star" style="color:#f59e0b;"></i> <span class="rating">${movie.rating || 'N/A'}</span>/10</p>
                            </div>
                        </div>
                    `).join('');
                });
        }
        
        function selectLanguageMovie(movieId, movieTitle) {
            window.location.href = `movie-details.php?movie_id=${movieId}&hall_id=0`;
        }
        
        function showLanguagesSelect() {
            document.getElementById('languagesSelectView').style.display = 'block';
            document.getElementById('languagesMoviesView').style.display = 'none';
            displayLanguages();
        }
        
        // ========== LOCATION PANEL ==========
        function displayLocationCities() {
            const container = document.getElementById('locationCityList');
            const uniqueCities = [...new Map(window.hallsData.map(hall => [hall.city, {city: hall.city, state: hall.state}])).values()];
            const karnatakaCities = uniqueCities.filter(c => c.state === 'Karnataka');
            const outsideCities = uniqueCities.filter(c => c.state !== 'Karnataka');
            
            container.innerHTML = `
                <div style="grid-column:1/-1;"><h3>🏙️ Karnataka Cities</h3></div>
                ${karnatakaCities.map(city => `<div class="location-card" onclick="selectLocationCity('${city.city}')"><i class="fas fa-city"></i><h4>${city.city}</h4><p>${city.state}</p></div>`).join('')}
                <div style="grid-column:1/-1; margin-top:20px;"><h3>🌍 Other Cities</h3></div>
                ${outsideCities.map(city => `<div class="location-card" onclick="selectLocationCity('${city.city}')"><i class="fas fa-globe"></i><h4>${city.city}</h4><p>${city.state}</p></div>`).join('')}
            `;
        }
        
        function selectLocationCity(cityName) {
            selectedCityName = cityName;
            document.getElementById('locationSelectView').style.display = 'none';
            document.getElementById('locationHallsView').style.display = 'block';
            document.getElementById('locationHallsBreadcrumb').innerHTML = `<i class="fas fa-map-marker-alt"></i> ${cityName} > Select Cinema Hall`;
            loadLocationHalls(cityName);
        }
        
        function loadLocationHalls(cityName) {
            const container = document.getElementById('locationHallsList');
            const halls = window.hallsData.filter(hall => hall.city === cityName);
            container.innerHTML = halls.map(hall => {
                const movieCount = window.hallMovieMapping[hall.id] ? window.hallMovieMapping[hall.id].length : 0;
                return `<div class="cinema-hall-card" onclick="selectLocationHall(${hall.id}, '${hall.hall_name}')">
                    <div class="cinema-info">
                        <h3><i class="fas fa-building"></i> ${hall.hall_name} <span class="movie-count-badge">${movieCount} Movies</span></h3>
                        <p><i class="fas fa-map-marker-alt"></i> ${hall.address}</p>
                        <p><i class="fas fa-tv"></i> ${hall.total_screens} Screens</p>
                    </div>
                    <button class="view-movies-btn" onclick="event.stopPropagation(); selectLocationHall(${hall.id}, '${hall.hall_name}')">View Movies (${movieCount})</button>
                </div>`;
            }).join('');
        }
        
        function selectLocationHall(hallId, hallName) {
            selectedHallId = hallId;
            selectedHallName = hallName;
            localStorage.setItem('selectedHallId', hallId);
            localStorage.setItem('selectedHallName', hallName);
            updateSelectedHallDisplay();
            const movies = window.hallMovieMapping[hallId] || [];
            document.getElementById('locationHallsView').style.display = 'none';
            document.getElementById('locationMoviesView').style.display = 'block';
            document.getElementById('locationMoviesBreadcrumb').innerHTML = `<i class="fas fa-map-marker-alt"></i> ${selectedCityName} > <i class="fas fa-building"></i> ${hallName} > Select a Movie`;
            loadLocationMovies(movies, hallName);
        }
        
        function loadLocationMovies(movies, hallName) {
            const grid = document.getElementById('locationMoviesGrid');
            if(movies.length === 0) {
                grid.innerHTML = '<div class="error-message">No movies currently playing at this cinema hall.</div>';
                return;
            }
            grid.innerHTML = movies.map(movie => `
                <div class="movie-card" onclick="selectLocationMovie(${movie.id}, '${escapeHtml(movie.title)}')">
                    <div class="movie-poster"><img src="images/posters/${movie.id}.jpg" alt="${escapeHtml(movie.title)}" onerror="this.src='images/posters/placeholder.jpg'"></div>
                    <div class="movie-info">
                        <h3>${escapeHtml(movie.title)}</h3>
                        <p><i class="fas fa-tag"></i> ${escapeHtml(movie.genre)}</p>
                        <p><i class="fas fa-language"></i> ${escapeHtml(movie.language)}</p>
                        <p><i class="fas fa-clock"></i> ${escapeHtml(movie.duration)}</p>
                        <p><i class="fas fa-star" style="color:#f59e0b;"></i> <span class="rating">${movie.rating || 'N/A'}</span>/10</p>
                        <p style="margin-top:10px; color:#28a745;"><i class="fas fa-check-circle"></i> Playing at ${hallName}</p>
                    </div>
                </div>
            `).join('');
        }
        
        function selectLocationMovie(movieId, movieTitle) {
            window.location.href = `movie-details.php?movie_id=${movieId}&hall_id=${selectedHallId}`;
        }
        
        function showLocationSelect() {
            document.getElementById('locationSelectView').style.display = 'block';
            document.getElementById('locationHallsView').style.display = 'none';
            displayLocationCities();
        }
        
        function showLocationHalls() {
            document.getElementById('locationHallsView').style.display = 'block';
            document.getElementById('locationMoviesView').style.display = 'none';
        }
        
        // ========== SUPPORT PANEL ==========
        document.getElementById('supportForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            alert('✅ Thank you! Our support team will contact you within 24 hours.');
            e.target.reset();
        });
        
        // ========== VOICE SEARCH ==========
        if('webkitSpeechRecognition' in window) {
            const recognition = new webkitSpeechRecognition();
            recognition.lang = 'en-US';
            document.getElementById('voiceMovieSearch')?.addEventListener('click', () => {
                recognition.start();
                document.getElementById('voiceMovieSearch').innerHTML = '<i class="fas fa-microphone-slash"></i>';
                recognition.onresult = (event) => {
                    document.getElementById('movieSearchInput').value = event.results[0][0].transcript;
                    currentMovieSearch = event.results[0][0].transcript;
                    loadDirectMovies();
                    document.getElementById('voiceMovieSearch').innerHTML = '<i class="fas fa-microphone"></i>';
                };
                recognition.onend = () => {
                    document.getElementById('voiceMovieSearch').innerHTML = '<i class="fas fa-microphone"></i>';
                };
            });
        }
        
        function escapeHtml(text) {
            if(!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function init() {
            displayGenres();
            displayLanguages();
            displayLocationCities();
            displayCinemaHalls();
            loadDirectMovies();
            updateSelectedHallDisplay();
        }
        
        init();
    </script>
</body>
</html>