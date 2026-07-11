<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'php/db_connect.php';

$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : '';
$hall_id = isset($_GET['hall_id']) ? (int)$_GET['hall_id'] : 0;

// Complete cinema halls data with exact coordinates
$cinemaHallsData = [
    1 => ['name' => 'PVR Forum Mall', 'city' => 'Bengaluru', 'state' => 'Karnataka', 'address' => 'Forum Mall, Koramangala, Bengaluru - 560095', 'lat' => 12.9279, 'lng' => 77.6271, 'contact' => '+91 80 7123 4567', 'screens' => 8, 'facilities' => 'IMAX,4K,Dolby Atmos,Parking,Food Court', 'open' => '09:00', 'close' => '23:00'],
    2 => ['name' => 'Cinepolis Orion Mall', 'city' => 'Bengaluru', 'state' => 'Karnataka', 'address' => 'Orion Mall, Rajajinagar, Bengaluru - 560010', 'lat' => 12.9915, 'lng' => 77.5535, 'contact' => '+91 80 2345 6789', 'screens' => 6, 'facilities' => '4K,3D,Premium Seats,Parking,Food Court', 'open' => '10:00', 'close' => '22:30'],
    3 => ['name' => 'INOX Lido Mall', 'city' => 'Bengaluru', 'state' => 'Karnataka', 'address' => 'Lido Mall, Ulsoor, Bengaluru - 560008', 'lat' => 12.9762, 'lng' => 77.6248, 'contact' => '+91 80 3456 7890', 'screens' => 5, 'facilities' => 'Dolby Atmos,Recliners,Parking,Food Court', 'open' => '09:30', 'close' => '23:00'],
    4 => ['name' => 'INOX Mysuru', 'city' => 'Mysuru', 'state' => 'Karnataka', 'address' => 'Nazarbad, Mysuru - 570010', 'lat' => 12.3086, 'lng' => 76.6532, 'contact' => '+91 821 4567890', 'screens' => 5, 'facilities' => 'IMAX,4K,Parking,Food Court', 'open' => '09:00', 'close' => '23:00'],
    5 => ['name' => 'Cinepolis Mysuru', 'city' => 'Mysuru', 'state' => 'Karnataka', 'address' => 'Mall of Mysore, Mysuru - 570020', 'lat' => 12.3112, 'lng' => 76.6459, 'contact' => '+91 821 5678901', 'screens' => 6, 'facilities' => '4K,3D,Parking,Food Court', 'open' => '10:00', 'close' => '22:30'],
    6 => ['name' => 'PVR Cinepoint', 'city' => 'Davangere', 'state' => 'Karnataka', 'address' => 'SS Mall, Davangere - 577001', 'lat' => 14.4644, 'lng' => 75.9218, 'contact' => '+91 8192 234567', 'screens' => 4, 'facilities' => '4K,Dolby Atmos,Parking,Food Court', 'open' => '10:00', 'close' => '22:00'],
    7 => ['name' => 'Cinepolis Hubli', 'city' => 'Hubli', 'state' => 'Karnataka', 'address' => 'Urban Oasis Mall, Hubli - 580031', 'lat' => 15.3647, 'lng' => 75.1240, 'contact' => '+91 836 2345678', 'screens' => 5, 'facilities' => '4K,Dolby Atmos,Parking,Food Court', 'open' => '10:00', 'close' => '22:30'],
    8 => ['name' => 'PVR Mangaluru', 'city' => 'Mangaluru', 'state' => 'Karnataka', 'address' => 'City Centre Mall, Mangaluru - 575001', 'lat' => 12.9141, 'lng' => 74.8560, 'contact' => '+91 824 4567890', 'screens' => 4, 'facilities' => '4K,3D,Parking,Food Court', 'open' => '09:30', 'close' => '22:30'],
    9 => ['name' => 'PVR ICON', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'address' => 'Phoenix Palladium, Lower Parel, Mumbai - 400013', 'lat' => 18.9929, 'lng' => 72.8209, 'contact' => '+91 22 6121 2345', 'screens' => 9, 'facilities' => 'IMAX,4K,Dolby Atmos,Parking,Valet,Food Court', 'open' => '09:00', 'close' => '23:30'],
    10 => ['name' => 'INOX Megaplex', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'address' => 'Inorbit Mall, Malad West, Mumbai - 400064', 'lat' => 19.1859, 'lng' => 72.8424, 'contact' => '+91 22 2878 9012', 'screens' => 12, 'facilities' => '4K,3D,Parking,Food Court', 'open' => '10:00', 'close' => '22:30'],
    11 => ['name' => 'PVR Select CITYWALK', 'city' => 'Delhi', 'state' => 'Delhi NCR', 'address' => 'Select CITYWALK, Saket, New Delhi - 110017', 'lat' => 28.5275, 'lng' => 77.2182, 'contact' => '+91 11 4266 7777', 'screens' => 10, 'facilities' => 'IMAX,4K,Dolby Atmos,Parking,Valet,Food Court', 'open' => '09:00', 'close' => '23:30'],
    12 => ['name' => 'Cinepolis DLF', 'city' => 'Delhi', 'state' => 'Delhi NCR', 'address' => 'DLF Avenue, Saket, New Delhi - 110017', 'lat' => 28.5280, 'lng' => 77.2190, 'contact' => '+91 11 4060 6060', 'screens' => 8, 'facilities' => '4K,Recliners,Parking,Food Court', 'open' => '10:00', 'close' => '22:30'],
    13 => ['name' => 'PVR Grand Galada', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'address' => 'Grand Galada Mall, Chennai - 600028', 'lat' => 13.0827, 'lng' => 80.2707, 'contact' => '+91 44 4297 8888', 'screens' => 7, 'facilities' => '4K,Dolby Atmos,Parking,Food Court', 'open' => '09:30', 'close' => '23:00'],
    14 => ['name' => 'INOX Chennai', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'address' => 'Phoenix Market City, Velachery, Chennai - 600042', 'lat' => 12.9782, 'lng' => 80.2189, 'contact' => '+91 44 4224 4242', 'screens' => 9, 'facilities' => 'IMAX,4K,Parking,Food Court', 'open' => '10:00', 'close' => '22:30'],
    15 => ['name' => 'PVR Next Galleria', 'city' => 'Hyderabad', 'state' => 'Telangana', 'address' => 'Next Galleria Mall, Hyderabad - 500034', 'lat' => 17.4156, 'lng' => 78.4347, 'contact' => '+91 40 6767 6767', 'screens' => 8, 'facilities' => '4K,Dolby Atmos,Parking,Food Court', 'open' => '09:00', 'close' => '23:00'],
    16 => ['name' => 'Cinepolis Hyderabad', 'city' => 'Hyderabad', 'state' => 'Telangana', 'address' => 'GVK One Mall, Hyderabad - 500034', 'lat' => 17.4125, 'lng' => 78.4385, 'contact' => '+91 40 2345 6789', 'screens' => 6, 'facilities' => 'IMAX,4K,Parking,Food Court', 'open' => '10:00', 'close' => '22:30'],
    17 => ['name' => 'INOX South City', 'city' => 'Kolkata', 'state' => 'West Bengal', 'address' => 'South City Mall, Kolkata - 700068', 'lat' => 22.5029, 'lng' => 88.3651, 'contact' => '+91 33 2442 4242', 'screens' => 7, 'facilities' => '4K,Dolby Atmos,Parking,Food Court', 'open' => '10:00', 'close' => '22:30']
];

// Get booking details to find the cinema hall
$selected_hall = null;
$booking_details = null;

if($booking_id) {
    $stmt = $pdo->prepare("SELECT b.*, h.hall_name, h.city, h.state, h.address, h.latitude, h.longitude, h.total_screens, h.facilities, h.contact_number
                           FROM bookings b 
                           JOIN cinema_halls h ON b.hall_id = h.id 
                           WHERE b.booking_id = ? AND b.user_id = ?");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking_details = $stmt->fetch();
    
    if($booking_details) {
        $selected_hall = [
            'id' => $booking_details['hall_id'],
            'name' => $booking_details['hall_name'],
            'city' => $booking_details['city'],
            'state' => $booking_details['state'],
            'address' => $booking_details['address'],
            'lat' => $booking_details['latitude'],
            'lng' => $booking_details['longitude'],
            'contact' => $booking_details['contact_number'],
            'screens' => $booking_details['total_screens'],
            'facilities' => $booking_details['facilities']
        ];
    }
} elseif($hall_id > 0 && isset($cinemaHallsData[$hall_id])) {
    $selected_hall = $cinemaHallsData[$hall_id];
    $selected_hall['id'] = $hall_id;
}

// If no hall selected, show error message
if(!$selected_hall) {
    echo '<div style="text-align: center; padding: 50px; font-family: Arial, sans-serif;">
            <h2>No Cinema Hall Selected</h2>
            <p>Please go back to your booking receipt and click "Get Travel Assistance" again.</p>
            <a href="home.php" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 10px;">Go to Home</a>
          </div>';
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Assistance - Core Cinema World</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        
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
        .nav-brand { display: flex; align-items: center; gap: 12px; }
        .nav-brand i { font-size: 28px; background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-brand h2 { font-size: 22px; color: #1f2937; }
        .nav-user { display: flex; align-items: center; gap: 20px; }
        .logout-btn { color: #ef4444; text-decoration: none; font-size: 18px; }
        
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        
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
        .back-btn:hover { transform: translateX(-5px); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        
        .destination-card {
            background: white;
            border-radius: 24px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            text-align: center;
        }
        .cinema-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .cinema-icon i { font-size: 40px; color: white; }
        .destination-card h2 { color: #1f2937; font-size: 24px; margin-bottom: 10px; }
        .booking-badge {
            display: inline-block;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .destination-address { color: #6b7280; margin: 10px 0; display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: wrap; }
        .destination-details { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 15px; }
        .detail-chip { background: #f3f4f6; padding: 6px 14px; border-radius: 20px; font-size: 13px; color: #374151; }
        
        .location-permission {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            margin-bottom: 25px;
        }
        .get-location-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 15px;
            transition: all 0.3s;
        }
        .get-location-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102,126,234,0.4); }
        
        .ride-section {
            background: white;
            border-radius: 24px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .ride-section h3 { color: #1f2937; margin-bottom: 20px; font-size: 20px; text-align: center; }
        .ride-apps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        .ride-card {
            background: #f9fafb;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .ride-card:hover { transform: translateY(-5px); border-color: #667eea; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .ride-icon { font-size: 50px; margin-bottom: 15px; }
        .ride-card h4 { font-size: 18px; color: #1f2937; margin-bottom: 5px; }
        .ride-card p { font-size: 12px; color: #6b7280; margin-bottom: 10px; }
        .ride-type { display: inline-block; background: #e8f0fe; padding: 4px 12px; border-radius: 20px; font-size: 11px; color: #667eea; }
        .estimated-fare { margin-top: 10px; font-size: 14px; font-weight: bold; color: #28a745; }
        
        .fare-section {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .fare-section h3 { margin-bottom: 20px; color: #1f2937; text-align: center; }
        .fare-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        .fare-card {
            background: #f9fafb;
            border-radius: 16px;
            padding: 15px;
            text-align: center;
        }
        .fare-card i { font-size: 28px; color: #667eea; margin-bottom: 8px; }
        .fare-card .vehicle { font-weight: 600; color: #1f2937; font-size: 13px; }
        .fare-card .price { font-size: 20px; font-weight: 700; color: #28a745; margin-top: 5px; }
        
        .map-section {
            background: white;
            border-radius: 24px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .map-section h3 { margin-bottom: 15px; color: #1f2937; text-align: center; }
        #map { height: 350px; width: 100%; border-radius: 16px; background: #f0f2f5; }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        .action-btn {
            padding: 12px 24px;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .maps-btn { background: #34a853; color: white; border: none; }
        .uber-btn { background: #000000; color: white; border: none; }
        .ola-btn { background: #2b7a3e; color: white; border: none; }
        .action-btn:hover { transform: translateY(-2px); filter: brightness(0.95); }
        
        .info-note { text-align: center; font-size: 12px; color: white; margin-top: 20px; opacity: 0.8; }
        
        @media (max-width: 768px) {
            .container { padding: 0 15px; }
            .ride-apps-grid { grid-template-columns: 1fr; }
            .fare-grid { grid-template-columns: 1fr; }
            .destination-card h2 { font-size: 20px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand"><i class="fas fa-film"></i><h2>Core Cinema World</h2></div>
        <div class="nav-user">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </nav>
    
    <div class="container">
        <a href="receipt.php?booking_id=<?php echo $booking_id; ?>" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Receipt</a>
        
        <!-- Destination Info -->
        <div class="destination-card">
            <div class="cinema-icon">
                <i class="fas fa-film"></i>
            </div>
            <h2><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($selected_hall['name']); ?></h2>
            <?php if($booking_details): ?>
            <div class="booking-badge"><i class="fas fa-ticket-alt"></i> Booking ID: <?php echo $booking_id; ?></div>
            <?php endif; ?>
            <div class="destination-address">
                <i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($selected_hall['address']); ?>, <?php echo htmlspecialchars($selected_hall['city']); ?>
            </div>
            <div class="destination-details">
                <span class="detail-chip"><i class="fas fa-tv"></i> <?php echo $selected_hall['screens']; ?> Screens</span>
                <span class="detail-chip"><i class="fas fa-phone"></i> <?php echo $selected_hall['contact']; ?></span>
            </div>
        </div>
        
        <!-- Location Permission -->
        <div class="location-permission" id="locationPermission">
            <i class="fas fa-map-marker-alt" style="font-size: 40px; color: #667eea;"></i>
            <h3>Allow Location Access</h3>
            <p>Enable location to find the best travel options from your current location to <?php echo htmlspecialchars($selected_hall['name']); ?></p>
            <button class="get-location-btn" onclick="getUserLocation()"><i class="fas fa-location-dot"></i> Share My Location</button>
        </div>
        
        <!-- Ride Booking Apps -->
        <div class="ride-section">
            <h3><i class="fas fa-taxi"></i> Book a Ride to <?php echo htmlspecialchars($selected_hall['name']); ?></h3>
            <div class="ride-apps-grid">
                <div class="ride-card" onclick="openRideApp('uber', '<?php echo urlencode($selected_hall['address']); ?>')">
                    <div class="ride-icon" style="color: #000000;"><i class="fab fa-uber"></i></div>
                    <h4>Uber</h4>
                    <p>Premium & affordable rides</p>
                    <span class="ride-type">Go • Premier • Auto • Moto</span>
                    <div class="estimated-fare" id="uberFare">📍 Allow location to see fare</div>
                </div>
                
                <div class="ride-card" onclick="openRideApp('ola', '<?php echo urlencode($selected_hall['address']); ?>')">
                    <div class="ride-icon" style="color: #2b7a3e;"><i class="fas fa-taxi"></i></div>
                    <h4>Ola Cabs</h4>
                    <p>Micro, Mini, Prime, Auto</p>
                    <span class="ride-type">Budget to Luxury</span>
                    <div class="estimated-fare" id="olaFare">📍 Allow location to see fare</div>
                </div>
                
                <div class="ride-card" onclick="openRideApp('rapido', '<?php echo urlencode($selected_hall['address']); ?>')">
                    <div class="ride-icon" style="color: #ff6b35;"><i class="fas fa-motorcycle"></i></div>
                    <h4>Rapido</h4>
                    <p>Bike Taxi - Fast & Affordable</p>
                    <span class="ride-type">Bike Taxi</span>
                    <div class="estimated-fare" id="rapidoFare">📍 Allow location to see fare</div>
                </div>
                
                <div class="ride-card" onclick="openRideApp('olaauto', '<?php echo urlencode($selected_hall['address']); ?>')">
                    <div class="ride-icon" style="color: #e67e22;"><i class="fas fa-truck-pickup"></i></div>
                    <h4>Ola Auto</h4>
                    <p>Auto Rickshaw - Best for short distances</p>
                    <span class="ride-type">Auto Rickshaw</span>
                    <div class="estimated-fare" id="autoFare">📍 Allow location to see fare</div>
                </div>
            </div>
        </div>
        
        <!-- Estimated Fare Comparison -->
        <div class="fare-section" id="fareSection" style="display: none;">
            <h3><i class="fas fa-chart-line"></i> Fare Comparison (Estimated)</h3>
            <div class="fare-grid">
                <div class="fare-card"><i class="fas fa-motorcycle"></i><div class="vehicle">Rapido (Bike)</div><div class="price">₹<span id="bikeFareEstimate">-</span></div></div>
                <div class="fare-card"><i class="fas fa-truck-pickup"></i><div class="vehicle">Auto Rickshaw</div><div class="price">₹<span id="autoFareEstimate">-</span></div></div>
                <div class="fare-card"><i class="fas fa-car"></i><div class="vehicle">Uber Go / Ola Mini</div><div class="price">₹<span id="miniFareEstimate">-</span></div></div>
                <div class="fare-card"><i class="fas fa-car-side"></i><div class="vehicle">Uber Premier / Ola Prime</div><div class="price">₹<span id="sedanFareEstimate">-</span></div></div>
            </div>
            <p style="font-size: 11px; color: #6b7280; margin-top: 15px; text-align: center;">
                <i class="fas fa-info-circle"></i> Fares are estimates based on distance. Actual fares may vary.
            </p>
        </div>
        
        <!-- Google Maps -->
        <div class="map-section">
            <h3><i class="fas fa-map"></i> Route to <?php echo htmlspecialchars($selected_hall['name']); ?></h3>
            <div id="map"></div>
            <div class="action-buttons">
                <button onclick="openGoogleMaps('<?php echo urlencode($selected_hall['address']); ?>')" class="action-btn maps-btn">
                    <i class="fab fa-google"></i> Open in Google Maps
                </button>
                <button onclick="openRideApp('uber', '<?php echo urlencode($selected_hall['address']); ?>')" class="action-btn uber-btn">
                    <i class="fab fa-uber"></i> Book Uber
                </button>
                <button onclick="openRideApp('ola', '<?php echo urlencode($selected_hall['address']); ?>')" class="action-btn ola-btn">
                    <i class="fas fa-taxi"></i> Book Ola
                </button>
            </div>
        </div>
        
        <div class="info-note">
            <i class="fas fa-shield-alt"></i> Safe & Secure Rides | 24/7 Customer Support
        </div>
    </div>
    
    <script>
        let userLat = null;
        let userLng = null;
        let destLat = <?php echo $selected_hall['lat']; ?>;
        let destLng = <?php echo $selected_hall['lng']; ?>;
        let destAddress = '<?php echo addslashes($selected_hall['address']); ?>';
        
        function getUserLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        userLat = position.coords.latitude;
                        userLng = position.coords.longitude;
                        document.getElementById('locationPermission').innerHTML = `
                            <i class="fas fa-check-circle" style="font-size: 40px; color: #28a745;"></i>
                            <h3>Location Access Granted!</h3>
                            <p>We've found your location. Ride fares are now available below.</p>
                        `;
                        document.getElementById('fareSection').style.display = 'block';
                        calculateAllFares();
                        updateRideFares();
                        showMap();
                    },
                    function(error) {
                        document.getElementById('locationPermission').innerHTML = `
                            <i class="fas fa-exclamation-triangle" style="font-size: 40px; color: #f59e0b;"></i>
                            <h3>Location Access Denied</h3>
                            <p>Please enable location to get accurate fare estimates and travel options.</p>
                            <button class="get-location-btn" onclick="getUserLocation()">Try Again</button>
                        `;
                    }
                );
            } else {
                alert('Geolocation is not supported by this browser.');
            }
        }
        
        function calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = (lat2 - lat1) * Math.PI / 180;
            const dLon = (lon2 - lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
        }
        
        function calculateAllFares() {
            if(!userLat || !userLng || !destLat || !destLng) return;
            const distance = calculateDistance(userLat, userLng, destLat, destLng);
            
            const bikeFare = Math.round(distance * 12 + 20);
            const autoFare = Math.round(distance * 15 + 25);
            const miniFare = Math.round(distance * 20 + 40);
            const sedanFare = Math.round(distance * 25 + 50);
            
            document.getElementById('bikeFareEstimate').textContent = bikeFare;
            document.getElementById('autoFareEstimate').textContent = autoFare;
            document.getElementById('miniFareEstimate').textContent = miniFare;
            document.getElementById('sedanFareEstimate').textContent = sedanFare;
        }
        
        function updateRideFares() {
            if(!userLat || !userLng || !destLat || !destLng) return;
            const distance = calculateDistance(userLat, userLng, destLat, destLng);
            
            const bikeFare = Math.round(distance * 12 + 20);
            const autoFare = Math.round(distance * 15 + 25);
            const miniFare = Math.round(distance * 20 + 40);
            
            document.getElementById('uberFare').innerHTML = `≈ ₹${miniFare} - ₹${miniFare + 30}`;
            document.getElementById('olaFare').innerHTML = `≈ ₹${miniFare} - ₹${miniFare + 30}`;
            document.getElementById('rapidoFare').innerHTML = `≈ ₹${bikeFare} - ₹${bikeFare + 20}`;
            document.getElementById('autoFare').innerHTML = `≈ ₹${autoFare} - ₹${autoFare + 20}`;
        }
        
        function showMap() {
            const mapDiv = document.getElementById('map');
            if(!mapDiv) return;
            if(userLat && userLng && destLat && destLng) {
                mapDiv.innerHTML = `<iframe width="100%" height="100%" frameborder="0" style="border:0; border-radius: 16px;" src="https://www.google.com/maps/embed/v1/directions?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&origin=${userLat},${userLng}&destination=${destLat},${destLng}&mode=driving" allowfullscreen></iframe>`;
            } else if(destLat && destLng) {
                mapDiv.innerHTML = `<iframe width="100%" height="100%" frameborder="0" style="border:0; border-radius: 16px;" src="https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q=${destLat},${destLng}" allowfullscreen></iframe>`;
            }
        }
        
        function openRideApp(app, destination) {
            const encodedDest = encodeURIComponent(destination);
            let urls = {
                'uber': `https://m.uber.com/ul/?action=setPickup&pickup=my_location&dropoff=${encodedDest}`,
                'ola': `https://book.olacabs.com/?pickup=My%20Location&drop=${encodedDest}`,
                'rapido': `https://rapido.bike/`,
                'olaauto': `https://book.olacabs.com/auto?pickup=My%20Location&drop=${encodedDest}`
            };
            window.open(urls[app] || '#', '_blank');
        }
        
        function openGoogleMaps(destination) {
            window.open(`https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(destination)}&travelmode=driving`, '_blank');
        }
        
        // Auto-detect location on page load
        if(destLat && destLng) {
            showMap();
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        userLat = position.coords.latitude;
                        userLng = position.coords.longitude;
                        document.getElementById('locationPermission').style.display = 'none';
                        document.getElementById('fareSection').style.display = 'block';
                        calculateAllFares();
                        updateRideFares();
                        showMap();
                    },
                    function(error) { console.log('Location permission denied'); }
                );
            }
        }
    </script>
</body>
</html>