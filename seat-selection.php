<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 1;
$hall_id = isset($_GET['hall_id']) ? (int)$_GET['hall_id'] : 1;
$movie_title = isset($_GET['title']) ? urldecode($_GET['title']) : 'Movie';
$show_time = isset($_GET['show_time']) ? urldecode($_GET['show_time']) : 'Not Selected';

// Get price multiplier
$priceMultiplier = isset($_GET['multiplier']) ? (float)$_GET['multiplier'] : 1;
if(isset($_GET['show_time'])) {
    if(strpos($show_time, '09:00') !== false) $priceMultiplier = 0.9;
    elseif(strpos($show_time, '12:30') !== false) $priceMultiplier = 1.0;
    elseif(strpos($show_time, '16:00') !== false) $priceMultiplier = 1.2;
    elseif(strpos($show_time, '20:30') !== false) $priceMultiplier = 1.5;
}

require_once 'php/db_connect.php';

// Get movie details with prices
$movieStmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
$movieStmt->execute([$movie_id]);
$movie = $movieStmt->fetch();

if($movie) {
    $movie_title = $movie['title'];
    $movie_genre = $movie['genre'];
    $movie_language = $movie['language'];
    $movie_rating = $movie['rating'];
    $basePremiumPrice = $movie['premium_price'] ?? 350;
    $baseBasePrice = $movie['base_price'] ?? 180;
}

// Get hall details
$hallStmt = $pdo->prepare("SELECT * FROM cinema_halls WHERE id = ?");
$hallStmt->execute([$hall_id]);
$hall = $hallStmt->fetch();

// Calculate final prices
$premiumPrice = round($basePremiumPrice * $priceMultiplier);
$basePrice = round($baseBasePrice * $priceMultiplier);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Seats - <?php echo htmlspecialchars($movie_title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .seat-selection-container {
            max-width: 1000px;
            margin: 30px auto;
            background: white;
            border-radius: 28px;
            padding: 30px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        
        .movie-info-bar {
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .movie-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .movie-info-item i {
            color: #667eea;
            font-size: 18px;
        }
        
        .hall-info {
            background: #f9fafb;
            border-radius: 16px;
            padding: 15px 20px;
            margin-bottom: 25px;
            border-left: 4px solid #667eea;
        }
        
        .show-time-info {
            background: #e8f0fe;
            border-radius: 16px;
            padding: 15px 20px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
        }
        
        .screen {
            background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
            color: white;
            text-align: center;
            padding: 20px;
            margin: 25px auto;
            width: 85%;
            border-radius: 20px;
            font-weight: bold;
            letter-spacing: 4px;
            font-size: 18px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        /* Clean Headers - No Extra Text */
        .premium-header {
            background: #f3f4f6;
            color: #1f2937;
            text-align: center;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 16px;
            border: 1px solid #e5e7eb;
        }
        
        .base-header {
            background: #f3f4f6;
            color: #1f2937;
            text-align: center;
            padding: 12px;
            border-radius: 12px;
            margin: 20px 0 20px 0;
            font-weight: 600;
            font-size: 16px;
            border: 1px solid #e5e7eb;
        }
        
        .seats-table {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            border-collapse: collapse;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }
        
        .seats-table th {
            padding: 12px;
            text-align: center;
            font-weight: 600;
            color: #6b7280;
            background: #f9fafb;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .seats-table td {
            padding: 8px;
            text-align: center;
        }
        
        .row-label-cell {
            background: #f9fafb;
            font-weight: 600;
            color: #6b7280;
            font-size: 16px;
            width: 50px;
            text-align: center;
            border-right: 1px solid #e5e7eb;
        }
        
        .seat {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 600;
            font-size: 13px;
            margin: 0 auto;
            background: white;
            border: 2px solid #d1d5db;
            color: #374151;
        }
        
        .seat.available {
            background: white;
            border: 2px solid #d1d5db;
            color: #374151;
        }
        
        .seat.available:hover {
            transform: scale(1.08);
            background: #f3f4f6;
            border-color: #9ca3af;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .seat.selected {
            background: #28a745 !important;
            color: white !important;
            border: 2px solid #1e7e34 !important;
            transform: scale(0.98);
            box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.3);
        }
        
        .seat.booked {
            background: #dc3545 !important;
            color: white !important;
            border: 2px solid #b91c2c !important;
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .seat.booked:hover {
            transform: none;
        }
        
        .seat-type-legend {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 25px 0;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: #f9fafb;
            border-radius: 40px;
            font-size: 13px;
        }
        
        .legend-color {
            width: 25px;
            height: 25px;
            border-radius: 6px;
        }
        
        .legend-color.available {
            background: white;
            border: 2px solid #d1d5db;
        }
        
        .legend-color.selected {
            background: #28a745;
        }
        
        .legend-color.booked {
            background: #dc3545;
        }
        
        .selected-info {
            margin-top: 30px;
            padding: 25px;
            background: #f9fafb;
            border-radius: 20px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }
        
        .selected-seats-display {
            font-size: 20px;
            font-weight: 700;
            color: #667eea;
            margin: 15px 0;
        }
        
        .total-amount {
            font-size: 28px;
            font-weight: 800;
            color: #28a745;
            margin: 15px 0;
        }
        
        .proceed-btn {
            margin-top: 20px;
            padding: 16px 48px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-size: 18px;
            font-weight: 700;
            transition: all 0.3s;
        }
        
        .proceed-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(40, 167, 69, 0.4);
        }
        
        .proceed-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 28px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        @keyframes selectPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(0.98); }
        }
        
        .seat.selected {
            animation: selectPulse 0.2s ease;
        }
        
        @media (max-width: 768px) {
            .seat-selection-container {
                margin: 15px;
                padding: 20px;
            }
            
            .seats-table {
                font-size: 12px;
            }
            
            .seat {
                width: 35px;
                height: 35px;
                font-size: 9px;
            }
            
            .row-label-cell {
                width: 30px;
                font-size: 12px;
            }
            
            .premium-header, .base-header {
                font-size: 13px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="seat-selection-container">
        <div class="movie-info-bar">
            <div class="movie-info-item"><i class="fas fa-film"></i> <strong><?php echo htmlspecialchars($movie_title); ?></strong></div>
            <div class="movie-info-item"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($movie_genre ?? 'Action'); ?></div>
            <div class="movie-info-item"><i class="fas fa-language"></i> <?php echo htmlspecialchars($movie_language ?? 'English'); ?></div>
            <div class="movie-info-item"><i class="fas fa-star" style="color:#f59e0b;"></i> <?php echo $movie_rating ?? 'N/A'; ?>/10</div>
        </div>
        
        <div class="hall-info">
            <i class="fas fa-building"></i> <strong><?php echo htmlspecialchars($hall['hall_name']); ?></strong><br>
            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($hall['city']); ?> | 
            <i class="fas fa-tv"></i> <?php echo $hall['total_screens']; ?> Screens
        </div>
        
        <div class="show-time-info">
            <i class="fas fa-clock"></i> Selected Show Time: <strong><?php echo htmlspecialchars($show_time); ?></strong>
            <?php if($priceMultiplier != 1): ?>
                <span style="margin-left: 10px;">| Multiplier: <?php echo $priceMultiplier; ?>x</span>
            <?php endif; ?>
        </div>
        
        <div class="screen">S C R E E N</div>
        
        <!-- CLEAN PREMIUM HEADER - No extra text -->
        <div class="premium-header">
            PREMIUM CLASS - ₹<?php echo $premiumPrice; ?>
        </div>
        
        <!-- PREMIUM SEATS (Rows A, B, C) -->
        <div id="premiumSeatsContainer"><div class="loading"><div class="spinner"></div><p>Loading seats...</p></div></div>
        
        <!-- CLEAN BASE HEADER - No extra text -->
        <div class="base-header">
            BASE CLASS - ₹<?php echo $basePrice; ?>
        </div>
        
        <!-- BASE SEATS (Rows D, E, F) -->
        <div id="baseSeatsContainer"><div class="loading"><div class="spinner"></div><p>Loading seats...</p></div></div>
        
        <!-- Legend -->
        <div class="seat-type-legend">
            <div class="legend-item">
                <div class="legend-color available"></div>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-color selected"></div>
                <span>Selected</span>
            </div>
            <div class="legend-item">
                <div class="legend-color booked"></div>
                <span>Booked</span>
            </div>
        </div>
        
        <div id="selectedInfo" class="selected-info" style="display:none;">
            <h3><i class="fas fa-ticket-alt"></i> Your Selected Seats</h3>
            <div class="selected-seats-display" id="selectedSeatsDisplay">None</div>
            <div class="total-amount">Total: ₹<span id="totalAmount">0</span></div>
            <button id="proceedPayment" class="proceed-btn" disabled>Proceed to Terms & Payment</button>
        </div>
        
        <div style="text-align:center;">
            <a href="home.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Home</a>
        </div>
    </div>
    
    <script>
        let movieId = <?php echo $movie_id; ?>;
        let hallId = <?php echo $hall_id; ?>;
        let selectedSeats = [];
        let allSeats = [];
        let priceMultiplier = <?php echo $priceMultiplier; ?>;
        let showTime = '<?php echo addslashes($show_time); ?>';
        
        const premiumPrice = <?php echo $premiumPrice; ?>;
        const basePrice = <?php echo $basePrice; ?>;
        
        const premiumRows = ['A', 'B', 'C'];
        const baseRows = ['D', 'E', 'F'];
        
        function getSeatPrice(row) {
            return premiumRows.includes(row) ? premiumPrice : basePrice;
        }
        
        function loadSeats() {
            fetch(`php/get_seats.php?movie_id=${movieId}&hall_id=${hallId}&t=${Date.now()}`)
                .then(res => res.json())
                .then(data => {
                    allSeats = data;
                    displayPremiumSeats(data);
                    displayBaseSeats(data);
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('premiumSeatsContainer').innerHTML = '<div class="error-message">Error loading seats. Please refresh.</div>';
                    document.getElementById('baseSeatsContainer').innerHTML = '<div class="error-message">Error loading seats. Please refresh.</div>';
                });
        }
        
        function displayPremiumSeats(seats) {
            const container = document.getElementById('premiumSeatsContainer');
            const table = document.createElement('table');
            table.className = 'seats-table';
            
            const thead = document.createElement('thead');
            const headerRow = document.createElement('tr');
            const cornerTh = document.createElement('th');
            cornerTh.innerHTML = '';
            headerRow.appendChild(cornerTh);
            for(let col = 1; col <= 8; col++) {
                const th = document.createElement('th');
                th.textContent = col;
                headerRow.appendChild(th);
            }
            thead.appendChild(headerRow);
            table.appendChild(thead);
            
            const tbody = document.createElement('tbody');
            
            premiumRows.forEach(row => {
                const tr = document.createElement('tr');
                const tdLabel = document.createElement('td');
                tdLabel.className = 'row-label-cell';
                tdLabel.textContent = row;
                tr.appendChild(tdLabel);
                
                for(let col = 1; col <= 8; col++) {
                    const seatNum = row + col;
                    const seat = seats.find(s => s.seat_number === seatNum);
                    const td = document.createElement('td');
                    
                    if(seat) {
                        const seatDiv = document.createElement('div');
                        let seatClass = 'seat ';
                        
                        if(seat.is_booked == 1) {
                            seatClass += 'booked';
                        } else if(selectedSeats.some(s => s.seat === seatNum)) {
                            seatClass += 'selected';
                        } else {
                            seatClass += 'available';
                        }
                        
                        seatDiv.className = seatClass;
                        seatDiv.textContent = seat.seat_number;
                        seatDiv.dataset.seat = seat.seat_number;
                        seatDiv.dataset.row = row;
                        seatDiv.dataset.price = getSeatPrice(row);
                        
                        if(seat.is_booked != 1) {
                            seatDiv.onclick = () => toggleSeat(seatDiv);
                        }
                        td.appendChild(seatDiv);
                    }
                    tr.appendChild(td);
                }
                tbody.appendChild(tr);
            });
            
            table.appendChild(tbody);
            container.innerHTML = '';
            container.appendChild(table);
        }
        
        function displayBaseSeats(seats) {
            const container = document.getElementById('baseSeatsContainer');
            const table = document.createElement('table');
            table.className = 'seats-table';
            
            const thead = document.createElement('thead');
            const headerRow = document.createElement('tr');
            const cornerTh = document.createElement('th');
            cornerTh.innerHTML = '';
            headerRow.appendChild(cornerTh);
            for(let col = 1; col <= 8; col++) {
                const th = document.createElement('th');
                th.textContent = col;
                headerRow.appendChild(th);
            }
            thead.appendChild(headerRow);
            table.appendChild(thead);
            
            const tbody = document.createElement('tbody');
            
            baseRows.forEach(row => {
                const tr = document.createElement('tr');
                const tdLabel = document.createElement('td');
                tdLabel.className = 'row-label-cell';
                tdLabel.textContent = row;
                tr.appendChild(tdLabel);
                
                for(let col = 1; col <= 8; col++) {
                    const seatNum = row + col;
                    const seat = seats.find(s => s.seat_number === seatNum);
                    const td = document.createElement('td');
                    
                    if(seat) {
                        const seatDiv = document.createElement('div');
                        let seatClass = 'seat ';
                        
                        if(seat.is_booked == 1) {
                            seatClass += 'booked';
                        } else if(selectedSeats.some(s => s.seat === seatNum)) {
                            seatClass += 'selected';
                        } else {
                            seatClass += 'available';
                        }
                        
                        seatDiv.className = seatClass;
                        seatDiv.textContent = seat.seat_number;
                        seatDiv.dataset.seat = seat.seat_number;
                        seatDiv.dataset.row = row;
                        seatDiv.dataset.price = getSeatPrice(row);
                        
                        if(seat.is_booked != 1) {
                            seatDiv.onclick = () => toggleSeat(seatDiv);
                        }
                        td.appendChild(seatDiv);
                    }
                    tr.appendChild(td);
                }
                tbody.appendChild(tr);
            });
            
            table.appendChild(tbody);
            container.innerHTML = '';
            container.appendChild(table);
        }
        
        function toggleSeat(seatDiv) {
            const seat = seatDiv.dataset.seat;
            const row = seatDiv.dataset.row;
            const price = getSeatPrice(row);
            
            if(seatDiv.classList.contains('selected')) {
                seatDiv.classList.remove('selected');
                seatDiv.classList.add('available');
                selectedSeats = selectedSeats.filter(s => s.seat !== seat);
            } else {
                seatDiv.classList.remove('available');
                seatDiv.classList.add('selected');
                selectedSeats.push({seat: seat, price: price, row: row});
            }
            
            displayPremiumSeats(allSeats);
            displayBaseSeats(allSeats);
            
            const info = document.getElementById('selectedInfo');
            if(selectedSeats.length > 0) {
                info.style.display = 'block';
                document.getElementById('selectedSeatsDisplay').textContent = selectedSeats.map(s => s.seat).join(', ');
                const total = selectedSeats.reduce((s,item) => s + item.price, 0);
                document.getElementById('totalAmount').textContent = total;
                document.getElementById('proceedPayment').disabled = false;
            } else {
                info.style.display = 'none';
                document.getElementById('proceedPayment').disabled = true;
            }
        }
        
        document.getElementById('proceedPayment').onclick = () => {
            if(selectedSeats.length === 0) {
                alert('Please select at least one seat');
                return;
            }
            
            localStorage.setItem('selectedSeats', JSON.stringify(selectedSeats));
            localStorage.setItem('movieId', movieId);
            localStorage.setItem('hallId', hallId);
            localStorage.setItem('movieTitle', '<?php echo addslashes($movie_title); ?>');
            localStorage.setItem('hallName', '<?php echo addslashes($hall['hall_name']); ?>');
            localStorage.setItem('showTime', showTime);
            localStorage.setItem('priceMultiplier', priceMultiplier);
            window.location.href = 'terms.php';
        };
        
        loadSeats();
    </script>
</body>
</html>