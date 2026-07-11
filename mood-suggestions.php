<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'php/db_connect.php';

$selectedMood = isset($_GET['mood']) ? $_GET['mood'] : '';
$suggestedMovies = [];

if($selectedMood) {
    // Get movies based on mood
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE mood = ? ORDER BY rating DESC LIMIT 12");
    $stmt->execute([$selectedMood]);
    $suggestedMovies = $stmt->fetchAll();
}

// If no movies found for this mood, get top rated movies
if(count($suggestedMovies) == 0) {
    $fallbackStmt = $pdo->prepare("SELECT * FROM movies ORDER BY rating DESC LIMIT 12");
    $fallbackStmt->execute();
    $suggestedMovies = $fallbackStmt->fetchAll();
}

// Get all cinema halls
$hallsStmt = $pdo->query("SELECT * FROM cinema_halls WHERE status = 'active' ORDER BY hall_name");
$allHalls = $hallsStmt->fetchAll();

// Create unique hall mapping for EACH movie (3 different halls per movie)
$movieHallMapping = [];
foreach($suggestedMovies as $index => $movie) {
    $hallIds = [];
    
    // Method 1: Try to get halls from seats table
    $seatStmt = $pdo->prepare("SELECT DISTINCT hall_id FROM seats WHERE movie_id = ?");
    $seatStmt->execute([$movie['id']]);
    $seatHalls = $seatStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if(count($seatHalls) >= 3) {
        // Use first 3 halls from seats
        $hallIds = array_slice($seatHalls, 0, 3);
    } else {
        // Method 2: Assign 3 different halls based on movie ID (different for each movie)
        $hallIds = [];
        $movieSpecificSeed = $movie['id'] * 7; // Unique seed for each movie
        
        for($i = 0; $i < 3; $i++) {
            $hallIndex = ($movieSpecificSeed + $i) % count($allHalls);
            $hallIds[] = $allHalls[$hallIndex]['id'];
        }
        
        // Also add any halls from seats if available
        foreach($seatHalls as $seatHall) {
            if(!in_array($seatHall, $hallIds) && count($hallIds) < 5) {
                $hallIds[] = $seatHall;
            }
        }
    }
    
    // Ensure we have at least 3 unique halls
    $hallIds = array_unique($hallIds);
    if(count($hallIds) < 3) {
        // Add more halls if needed
        $counter = 0;
        while(count($hallIds) < 3 && $counter < count($allHalls)) {
            if(!in_array($allHalls[$counter]['id'], $hallIds)) {
                $hallIds[] = $allHalls[$counter]['id'];
            }
            $counter++;
        }
    }
    
    $movieHallMapping[$movie['id']] = array_slice($hallIds, 0, 3);
}

// Define show timings
$showTimings = [
    'morning' => ['time' => '09:00 AM', 'type' => 'Morning Show', 'price_multiplier' => 0.9],
    'matinee' => ['time' => '12:30 PM', 'type' => 'Matinee Show', 'price_multiplier' => 1.0],
    'evening' => ['time' => '04:00 PM', 'type' => 'Evening Show', 'price_multiplier' => 1.2],
    'night' => ['time' => '08:30 PM', 'type' => 'Night Show', 'price_multiplier' => 1.5]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mood-Based Movie Suggestions - Core Cinema World</title>
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
        
        .mood-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .mood-header h1 {
            font-size: 36px;
            font-weight: 800;
            color: white;
            margin-bottom: 10px;
        }
        
        .mood-header p {
            font-size: 16px;
            color: rgba(255,255,255,0.8);
        }
        
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
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
        
        .rating {
            color: #f59e0b;
            font-weight: 600;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            overflow-y: auto;
            padding: 20px;
        }
        
        .modal-content {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            border-radius: 28px;
            padding: 30px;
            position: relative;
            animation: fadeInScale 0.3s ease;
        }
        
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .close-modal {
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 28px;
            cursor: pointer;
            color: #9ca3af;
            transition: color 0.3s;
        }
        
        .close-modal:hover {
            color: #ef4444;
        }
        
        .modal-movie-header {
            display: flex;
            flex-wrap: wrap;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .modal-movie-poster {
            flex: 0 0 200px;
        }
        
        .modal-movie-poster img {
            width: 100%;
            border-radius: 16px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .modal-movie-info {
            flex: 1;
        }
        
        .modal-movie-info h2 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .modal-movie-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .modal-meta-item {
            background: #f3f4f6;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
        }
        
        .modal-description {
            color: #6b7280;
            line-height: 1.5;
            margin-bottom: 20px;
        }
        
        .cinema-halls-section {
            margin-top: 25px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
        
        .cinema-halls-section h3 {
            margin-bottom: 15px;
            color: #1f2937;
        }
        
        .cinema-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 15px;
        }
        
        .cinema-item {
            background: #f9fafb;
            border-radius: 16px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: 1px solid #e5e7eb;
        }
        
        .cinema-item:hover {
            transform: translateY(-3px);
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .cinema-item h4 {
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .cinema-item p {
            font-size: 12px;
            color: #6b7280;
            margin: 3px 0;
        }
        
        .show-timings {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 12px 0;
        }
        
        .show-time-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.3s;
        }
        
        .show-time-btn:hover {
            background: #764ba2;
            transform: scale(1.02);
        }
        
        .show-time-btn.selected {
            background: #28a745;
        }
        
        .book-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
            width: 100%;
            transition: all 0.3s;
        }
        
        .book-btn:hover {
            transform: scale(1.02);
        }
        
        .no-halls-message {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }
        
        .loading {
            text-align: center;
            padding: 60px;
            color: white;
        }
        
        .spinner {
            border: 4px solid rgba(255,255,255,0.3);
            border-top: 4px solid white;
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
        
        @media (max-width: 768px) {
            .movies-grid {
                grid-template-columns: 1fr;
            }
            
            .movie-poster {
                height: 250px;
            }
            
            .modal-movie-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .modal-movie-poster {
                flex: 0 0 auto;
                max-width: 200px;
            }
            
            .cinema-list {
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
        
        <div class="mood-header">
            <h1>
                <?php
                $moodNames = [
                    'happy' => '😊 Happy Movies',
                    'sad' => '😢 Emotional Movies',
                    'thriller' => '😱 Thriller Movies',
                    'romantic' => '❤️ Romantic Movies',
                    'energetic' => '⚡ Energetic Movies',
                    'relaxing' => '😌 Relaxing Movies'
                ];
                echo $moodNames[$selectedMood] ?? 'Recommended Movies';
                ?>
            </h1>
            <p>Movies curated just for your mood - select any movie to book tickets</p>
        </div>
        
        <div id="moviesGrid" class="movies-grid">
            <?php foreach($suggestedMovies as $movie): ?>
            <div class="movie-card" onclick="openMovieModal(<?php echo $movie['id']; ?>, '<?php echo addslashes($movie['title']); ?>', '<?php echo addslashes($movie['genre']); ?>', '<?php echo addslashes($movie['language']); ?>', '<?php echo addslashes($movie['duration']); ?>', '<?php echo addslashes($movie['description']); ?>', <?php echo $movie['rating']; ?>)">
                <div class="movie-poster">
                    <img src="images/posters/<?php echo $movie['id']; ?>.jpg" 
                         alt="<?php echo htmlspecialchars($movie['title']); ?>"
                         onerror="this.src='images/posters/placeholder.jpg'">
                </div>
                <div class="movie-info">
                    <h3><?php echo htmlspecialchars($movie['title']); ?></h3>
                    <p><i class="fas fa-tag"></i> <?php echo htmlspecialchars($movie['genre']); ?></p>
                    <p><i class="fas fa-language"></i> <?php echo htmlspecialchars($movie['language']); ?></p>
                    <p><i class="fas fa-clock"></i> <?php echo htmlspecialchars($movie['duration']); ?></p>
                    <p><i class="fas fa-star" style="color:#f59e0b;"></i> <span class="rating"><?php echo $movie['rating']; ?>/10</span></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Movie Details Modal -->
    <div id="movieModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeMovieModal()">&times;</span>
            <div id="modalContent"></div>
        </div>
    </div>
    
    <script>
        let currentMovieId = null;
        let selectedHallId = null;
        let selectedShowTime = null;
        let selectedPriceMultiplier = 1;
        let allHalls = <?php echo json_encode($allHalls); ?>;
        let movieHallMapping = <?php echo json_encode($movieHallMapping); ?>;
        
        function openMovieModal(id, title, genre, language, duration, description, rating) {
            currentMovieId = id;
            
            // Get unique halls for this specific movie (3 different halls)
            const hallIds = movieHallMapping[id] || [];
            const availableHalls = allHalls.filter(hall => hallIds.includes(hall.id));
            
            // Ensure we have exactly 3 halls (if less, add more)
            let finalHalls = [...availableHalls];
            if(finalHalls.length < 3) {
                for(let i = 0; i < allHalls.length && finalHalls.length < 3; i++) {
                    if(!finalHalls.some(h => h.id === allHalls[i].id)) {
                        finalHalls.push(allHalls[i]);
                    }
                }
            }
            
            const modalContent = document.getElementById('modalContent');
            modalContent.innerHTML = `
                <div class="modal-movie-header">
                    <div class="modal-movie-poster">
                        <img src="images/posters/${id}.jpg" alt="${title}" onerror="this.src='images/posters/placeholder.jpg'">
                    </div>
                    <div class="modal-movie-info">
                        <h2>${title}</h2>
                        <div class="modal-movie-meta">
                            <span class="modal-meta-item"><i class="fas fa-star"></i> ${rating}/10</span>
                            <span class="modal-meta-item"><i class="fas fa-clock"></i> ${duration}</span>
                            <span class="modal-meta-item"><i class="fas fa-tag"></i> ${genre}</span>
                            <span class="modal-meta-item"><i class="fas fa-language"></i> ${language}</span>
                        </div>
                        <p class="modal-description">${description}</p>
                    </div>
                </div>
                <div class="cinema-halls-section">
                    <h3><i class="fas fa-building"></i> Select Cinema Hall (${finalHalls.length} halls available)</h3>
                    <div id="cinemaHallsList" class="cinema-list">
                        ${finalHalls.map(hall => `
                            <div class="cinema-item" data-hall-id="${hall.id}" data-hall-name="${hall.hall_name}">
                                <h4><i class="fas fa-building"></i> ${hall.hall_name}</h4>
                                <p><i class="fas fa-map-marker-alt"></i> ${hall.city}, ${hall.state}</p>
                                <p><i class="fas fa-tv"></i> ${hall.total_screens} Screens</p>
                                <p><i class="fas fa-clock"></i> ${hall.opening_time} - ${hall.closing_time}</p>
                                <div id="showTimings_${hall.id}" class="show-timings"></div>
                                <button class="book-btn" data-hall-id="${hall.id}" data-hall-name="${hall.hall_name}">Book Tickets</button>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            
            // Load show timings for each hall
            finalHalls.forEach(hall => {
                loadShowTimings(hall.id);
            });
            
            // Add event listeners to book buttons
            document.querySelectorAll('.book-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const hallId = parseInt(btn.dataset.hallId);
                    const hallName = btn.dataset.hallName;
                    bookTickets(hallId, hallName);
                });
            });
            
            // Add event listeners to cinema items for hall selection
            document.querySelectorAll('.cinema-item').forEach(item => {
                const hallId = parseInt(item.dataset.hallId);
                const hallName = item.dataset.hallName;
                
                // Add click handler for show time buttons inside this cinema item
                const showTimeContainer = item.querySelector(`#showTimings_${hallId}`);
                if(showTimeContainer) {
                    const showButtons = showTimeContainer.querySelectorAll('.show-time-btn');
                    showButtons.forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.stopPropagation();
                            // Remove selected class from all show time buttons in this cinema
                            showButtons.forEach(b => b.classList.remove('selected'));
                            btn.classList.add('selected');
                            selectedHallId = hallId;
                            selectedShowTime = btn.dataset.time;
                            selectedPriceMultiplier = parseFloat(btn.dataset.multiplier);
                        });
                    });
                }
            });
            
            document.getElementById('movieModal').style.display = 'block';
        }
        
        function loadShowTimings(hallId) {
            const timingsContainer = document.getElementById(`showTimings_${hallId}`);
            if(!timingsContainer) return;
            
            const timings = [
                { key: 'morning', time: '09:00 AM', multiplier: 0.9 },
                { key: 'matinee', time: '12:30 PM', multiplier: 1.0 },
                { key: 'evening', time: '04:00 PM', multiplier: 1.2 },
                { key: 'night', time: '08:30 PM', multiplier: 1.5 }
            ];
            
            timingsContainer.innerHTML = timings.map(timing => `
                <button class="show-time-btn" data-hall="${hallId}" data-time="${timing.time}" data-multiplier="${timing.multiplier}">
                    ${timing.time}
                </button>
            `).join('');
        }
        
        function bookTickets(hallId, hallName) {
            if(!selectedShowTime) {
                alert('Please select a show time first!');
                return;
            }
            
            // Store in localStorage
            localStorage.setItem('selectedHallId', hallId);
            localStorage.setItem('selectedHallName', hallName);
            localStorage.setItem('selectedShowTime', selectedShowTime);
            localStorage.setItem('priceMultiplier', selectedPriceMultiplier);
            localStorage.setItem('movieId', currentMovieId);
            
            // Get movie title from modal
            const movieTitle = document.querySelector('.modal-movie-info h2')?.textContent || 'Movie';
            localStorage.setItem('movieTitle', movieTitle);
            
            // Redirect to seat selection
            window.location.href = `seat-selection.php?movie_id=${currentMovieId}&title=${encodeURIComponent(movieTitle)}&hall_id=${hallId}&show_time=${encodeURIComponent(selectedShowTime)}`;
        }
        
        function closeMovieModal() {
            document.getElementById('movieModal').style.display = 'none';
            selectedHallId = null;
            selectedShowTime = null;
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('movieModal');
            if (event.target == modal) {
                closeMovieModal();
            }
        }
    </script>
</body>
</html>