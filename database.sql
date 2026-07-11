-- =====================================================
-- CORE CINEMA WORLD - COMPLETE DATABASE
-- Movies with correct ID sequence (1 to 50)
-- =====================================================

DROP DATABASE IF EXISTS core_cinema_world;
CREATE DATABASE core_cinema_world;
USE core_cinema_world;

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100),
    phone VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- CITIES TABLE
-- =====================================================
CREATE TABLE cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city_name VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    region ENUM('karnataka', 'outside') DEFAULT 'outside',
    is_active BOOLEAN DEFAULT TRUE
);

-- =====================================================
-- CINEMA HALLS TABLE
-- =====================================================
CREATE TABLE cinema_halls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hall_name VARCHAR(100) NOT NULL,
    city VARCHAR(50) NOT NULL,
    state VARCHAR(50) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    total_screens INT DEFAULT 1,
    facilities TEXT,
    opening_time TIME,
    closing_time TIME,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

-- =====================================================
-- MOVIES TABLE
-- =====================================================
CREATE TABLE movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    genre VARCHAR(50),
    language VARCHAR(50),
    duration VARCHAR(20),
    description TEXT,
    rating DECIMAL(3,1),
    poster_url VARCHAR(500)
);

-- =====================================================
-- SEATS TABLE
-- =====================================================
CREATE TABLE seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    movie_id INT,
    hall_id INT,
    seat_number VARCHAR(10),
    row_name VARCHAR(2),
    seat_column INT,
    seat_type ENUM('standard', 'premium', 'recliner') DEFAULT 'standard',
    is_booked BOOLEAN DEFAULT FALSE,
    price DECIMAL(10,2),
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (hall_id) REFERENCES cinema_halls(id) ON DELETE CASCADE
);

-- =====================================================
-- BOOKINGS TABLE
-- =====================================================
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id VARCHAR(50) UNIQUE,
    user_id INT,
    movie_id INT,
    hall_id INT,
    seats TEXT,
    total_amount DECIMAL(10,2),
    payment_status VARCHAR(50),
    payment_id VARCHAR(100),
    payment_method VARCHAR(50) DEFAULT 'card',
    friends_data TEXT,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    FOREIGN KEY (hall_id) REFERENCES cinema_halls(id) ON DELETE CASCADE
);

-- =====================================================
-- INSERT USERS
-- =====================================================
INSERT INTO users (username, password, full_name, email, phone) VALUES 
('john_doe', MD5('password123'), 'John Doe', 'john@example.com', '9876543210'),
('jane_smith', MD5('pass456'), 'Jane Smith', 'jane@example.com', '9876543211'),
('raju_kumar', MD5('raju123'), 'Raju Kumar', 'raju@example.com', '9876543212'),
('priya_sharma', MD5('priya456'), 'Priya Sharma', 'priya@example.com', '9876543213');

-- =====================================================
-- INSERT CITIES
-- =====================================================
INSERT INTO cities (city_name, state, region) VALUES 
('Bengaluru', 'Karnataka', 'karnataka'),
('Mysuru', 'Karnataka', 'karnataka'),
('Davangere', 'Karnataka', 'karnataka'),
('Hubli', 'Karnataka', 'karnataka'),
('Mangaluru', 'Karnataka', 'karnataka'),
('Mumbai', 'Maharashtra', 'outside'),
('Delhi', 'Delhi NCR', 'outside'),
('Chennai', 'Tamil Nadu', 'outside'),
('Hyderabad', 'Telangana', 'outside'),
('Kolkata', 'West Bengal', 'outside');

-- =====================================================
-- INSERT CINEMA HALLS
-- =====================================================
INSERT INTO cinema_halls (hall_name, city, state, address, phone, email, total_screens, facilities, opening_time, closing_time) VALUES 
('PVR Forum Mall', 'Bengaluru', 'Karnataka', 'Forum Mall, Koramangala', '+91 80 7123 4567', 'bengaluru.forum@pvr.co.in', 8, 'IMAX, 4K, Dolby Atmos', '09:00:00', '23:00:00'),
('Cinepolis Orion Mall', 'Bengaluru', 'Karnataka', 'Orion Mall, Rajajinagar', '+91 80 2345 6789', 'bengaluru.orion@cinepolis.com', 6, '4K, 3D, Premium Seats', '10:00:00', '22:30:00'),
('INOX Lido Mall', 'Bengaluru', 'Karnataka', 'Lido Mall, Ulsoor', '+91 80 3456 7890', 'bengaluru.lido@inox.com', 5, 'Dolby Atmos, Recliners', '09:30:00', '23:00:00'),
('INOX Mysuru', 'Mysuru', 'Karnataka', 'Nazarbad, Mysuru', '+91 821 4567890', 'mysuru@inox.com', 5, 'IMAX, 4K', '09:00:00', '23:00:00'),
('Cinepolis Mysuru', 'Mysuru', 'Karnataka', 'Mall of Mysore, Mysuru', '+91 821 5678901', 'mysuru@cinepolis.com', 6, '4K, 3D', '10:00:00', '22:30:00'),
('PVR Cinepoint', 'Davangere', 'Karnataka', 'SS Mall, Davangere', '+91 8192 234567', 'davangere@pvr.co.in', 4, '4K, Dolby Atmos', '10:00:00', '22:00:00'),
('Cinepolis Hubli', 'Hubli', 'Karnataka', 'Urban Oasis Mall, Hubli', '+91 836 2345678', 'hubli@cinepolis.com', 5, '4K, Dolby Atmos', '10:00:00', '22:30:00'),
('PVR Mangaluru', 'Mangaluru', 'Karnataka', 'City Centre Mall, Mangaluru', '+91 824 4567890', 'mangaluru@pvr.co.in', 4, '4K, 3D', '09:30:00', '22:30:00'),
('PVR ICON', 'Mumbai', 'Maharashtra', 'Phoenix Palladium, Lower Parel', '+91 22 6121 2345', 'mumbai.palladium@pvr.co.in', 9, 'IMAX, 4K, Dolby Atmos', '09:00:00', '23:30:00'),
('INOX Megaplex', 'Mumbai', 'Maharashtra', 'Inorbit Mall, Malad West', '+91 22 2878 9012', 'mumbai.malad@inox.com', 12, '4K, 3D', '10:00:00', '22:30:00'),
('PVR Select CITYWALK', 'Delhi', 'Delhi NCR', 'Select CITYWALK, Saket', '+91 11 4266 7777', 'delhi.saket@pvr.co.in', 10, 'IMAX, 4K, Dolby Atmos', '09:00:00', '23:30:00'),
('Cinepolis DLF', 'Delhi', 'Delhi NCR', 'DLF Avenue, Saket', '+91 11 4060 6060', 'delhi.dlf@cinepolis.com', 8, '4K, Recliners', '10:00:00', '22:30:00'),
('PVR Grand Galada', 'Chennai', 'Tamil Nadu', 'Grand Galada Mall, Chennai', '+91 44 4297 8888', 'chennai.galada@pvr.co.in', 7, '4K, Dolby Atmos', '09:30:00', '23:00:00'),
('INOX Chennai', 'Chennai', 'Tamil Nadu', 'Phoenix Market City, Velachery', '+91 44 4224 4242', 'chennai@inox.com', 9, 'IMAX, 4K', '10:00:00', '22:30:00'),
('PVR Next Galleria', 'Hyderabad', 'Telangana', 'Next Galleria Mall, Hyderabad', '+91 40 6767 6767', 'hyderabad@pvr.co.in', 8, '4K, Dolby Atmos', '09:00:00', '23:00:00'),
('Cinepolis Hyderabad', 'Hyderabad', 'Telangana', 'GVK One Mall, Hyderabad', '+91 40 2345 6789', 'hyderabad@cinepolis.com', 6, 'IMAX, 4K', '10:00:00', '22:30:00'),
('INOX South City', 'Kolkata', 'West Bengal', 'South City Mall, Kolkata', '+91 33 2442 4242', 'kolkata@inox.com', 7, '4K, Dolby Atmos', '10:00:00', '22:30:00');

-- =====================================================
-- INSERT 50 MOVIES WITH CORRECT ID SEQUENCE (1 to 50)
-- =====================================================

INSERT INTO movies (id, title, genre, language, duration, description, rating) VALUES
(1, '12th Fail', 'Drama', 'Hindi', '147 min', 'The real-life story of IPS officer Manoj Kumar Sharma. A tale of perseverance and never giving up.', 8.9),
(2, 'Brahmastra', 'Fantasy', 'Hindi', '167 min', 'A young man discovers his powers to save the world. India\'s first cinematic universe.', 6.0),
(3, 'Dhurandhar', 'Action', 'Hindi', '158 min', 'A powerful story of a warrior who fights against injustice.', 8.1),
(4, 'Doctor G', 'Comedy', 'Hindi', '125 min', 'A male gynaecology student navigates his way through medical college.', 6.8),
(5, 'Dunki', 'Comedy', 'Hindi', '161 min', 'Friends try to migrate to the UK through illegal means. A hilarious journey.', 7.8),
(6, 'Fighter', 'Action', 'Hindi', '166 min', 'An Indian Air Force story of courage and patriotism.', 7.2),
(7, 'Housefull 5', 'Comedy', 'Hindi', '145 min', 'The fifth installment of the Housefull franchise. More chaos, more laughter.', 7.5),
(8, 'Jawan', 'Action', 'Hindi', '169 min', 'A man driven by a personal vendetta to rectify the wrongs in society.', 7.5),
(9, 'Mission Majnu', 'Thriller', 'Hindi', '129 min', 'An Indian spy goes undercover in Pakistan.', 7.0),
(10, 'Pathaan', 'Action', 'Hindi', '146 min', 'An Indian spy goes on a mission to stop a terrorist attack.', 7.0),
(11, 'The Kerala Story', 'Drama', 'Hindi', '138 min', 'A drama based on true events about religious conversion.', 7.0),
(12, 'Tiger 3', 'Action', 'Hindi', '155 min', 'Tiger and Zoya fight to protect their country and family.', 6.5),
(13, 'Avatar: Fire and Ash', 'Adventure', 'English', '192 min', 'The third installment in the Avatar franchise. New adventures on Pandora.', 7.8),
(14, 'Crime 101', 'Thriller', 'English', '120 min', 'A gripping crime thriller about heists and investigations.', 7.5),
(15, 'Dune: Part Two', 'Adventure', 'English', '166 min', 'Paul Atreides unites with the Fremen for revenge.', 8.5),
(16, 'F1', 'Action', 'English', '150 min', 'A high-octane drama set in the world of Formula 1 racing.', 7.5),
(17, 'Mercy', 'Thriller', 'English', '115 min', 'A psychological thriller about secrets and redemption.', 7.5),
(18, 'Mufasa: The Lion King', 'Family', 'English', '118 min', 'The origin story of Mufasa, the beloved king of the Pride Lands.', 7.2),
(19, 'Oppenheimer', 'Drama', 'English', '180 min', 'The story of J. Robert Oppenheimer and the atomic bomb.', 8.5),
(20, 'Sinners', 'Thriller', 'English', '125 min', 'A dark thriller about sin, redemption, and consequences.', 7.5),
(21, 'The Running Man', 'Action', 'English', '130 min', 'A dystopian action thriller based on Stephen King\'s novel.', 7.0),
(22, 'The Jungle Book', 'Adventure', 'English', '106 min', 'Mowgli learns life lessons from his animal friends.', 7.4),
(23, 'Charlie 777', 'Comedy', 'Kannada', '148 min', 'A man forms an emotional bond with a dog named Charlie.', 8.1),
(24, 'Kantara', 'Drama', 'Kannada', '148 min', 'A rustic tale of conflict between nature and man.', 8.5),
(25, 'Kaatera', 'Action', 'Kannada', '150 min', 'A period action drama set in the historical kingdom of Mysore.', 8.0),
(26, 'Kantara Chapter 1', 'Action', 'Kannada', '155 min', 'The prequel to the blockbuster Kantara. The legend begins.', 8.2),
(27, 'KGF Chapter 2', 'Action', 'Kannada', '168 min', 'The blood-soaked land of Kolar Gold Fields where Rocky rises.', 8.4),
(28, 'Max', 'Action', 'Kannada', '145 min', 'A high-octane action thriller about a fearless cop.', 8.2),
(29, 'Su From So', 'Drama', 'Kannada', '140 min', 'A heartwarming story about relationships and self-discovery.', 7.5),
(30, 'Bison Kaalamaadan', 'Action', 'Tamil', '150 min', 'A powerful action drama about a fierce warrior.', 7.5),
(31, 'Coolie', 'Action', 'Tamil', '160 min', 'An action thriller about a coolie who rises against oppression.', 7.5),
(32, 'Dude', 'Comedy', 'Tamil', '135 min', 'A fun-filled comedy about friendship and youth.', 7.5),
(33, 'Jailer', 'Action', 'Tamil', '168 min', 'A retired jailer goes on a rampage to avenge his son.', 7.6),
(34, 'Leo', 'Action', 'Tamil', '164 min', 'A cafe owner becomes the target of a criminal network.', 7.8),
(35, 'Love Today', 'Comedy', 'Tamil', '155 min', 'A couple exchanges phones to test their relationship.', 8.2),
(36, 'Ponniyin Selvan: II', 'Drama', 'Tamil', '165 min', 'The epic conclusion of the Chola dynasty story.', 7.5),
(37, 'Vikram', 'Thriller', 'Tamil', '175 min', 'A special agent investigates a series of drug-related deaths.', 8.3),
(38, 'Youth', 'Romance', 'Tamil', '145 min', 'A youthful romance about love and self-discovery.', 7.5),
(39, 'Dear Comrade', 'Romance', 'Telugu', '169 min', 'A student leader falls for a state-level cricketer.', 7.5),
(40, 'Hi Nanna', 'Romance', 'Telugu', '155 min', 'A single father\'s journey of love and sacrifice.', 8.0),
(41, 'Jathi Ratnalu', 'Comedy', 'Telugu', '135 min', 'Three naive men get into hilarious trouble in Hyderabad.', 7.5),
(42, 'Kushi', 'Romance', 'Telugu', '165 min', 'A couple navigates the challenges of marriage.', 6.8),
(43, 'Pushpa 2: The Rule', 'Action', 'Telugu', '185 min', 'Pushpa Raj continues his dominance in the red sandalwood smuggling business.', 8.0),
(44, 'RRR', 'Action', 'Telugu', '182 min', 'A fictional story about two real-life Indian revolutionaries.', 8.0),
(45, 'Salaar', 'Action', 'Telugu', '175 min', 'A story of friendship and power set in the city of Khansaar.', 8.0),
(46, 'Sita Ramam', 'Romance', 'Telugu', '163 min', 'An orphaned army officer falls in love with a princess.', 8.3),
(47, 'Aattam', 'Drama', 'Malayalam', '139 min', 'A theater group faces moral dilemmas after an incident.', 8.1),
(48, 'Drishyam 2', 'Thriller', 'Malayalam', '152 min', 'A family faces new challenges years after a crime.', 8.4),
(49, 'Hridayam', 'Romance', 'Malayalam', '172 min', 'A college romance with unexpected twists.', 7.8),
(50, 'Premalu', 'Comedy', 'Malayalam', '142 min', 'A young man\'s hilarious journey through love and career.', 8.3);

-- =====================================================
-- UPDATE POSTER URLS (Image Path = images/posters/ID.jpg)
-- =====================================================
UPDATE movies SET poster_url = CONCAT('images/posters/', id, '.jpg');

-- =====================================================
-- CREATE SEATS PROCEDURE
-- =====================================================
DELIMITER $$
CREATE PROCEDURE CreateSeatsForMovie(IN p_movie_id INT, IN p_hall_id INT)
BEGIN
    DECLARE v_row VARCHAR(2);
    DECLARE v_col INT;
    DECLARE v_seat_num VARCHAR(10);
    DECLARE v_price DECIMAL(10,2);
    DECLARE v_seat_type VARCHAR(20);
    DECLARE i INT DEFAULT 1;
    DECLARE j INT DEFAULT 1;
    
    IF (SELECT COUNT(*) FROM seats WHERE movie_id = p_movie_id AND hall_id = p_hall_id) = 0 THEN
        WHILE i <= 6 DO
            SET v_row = ELT(i, 'A','B','C','D','E','F');
            SET j = 1;
            WHILE j <= 8 DO
                SET v_seat_num = CONCAT(v_row, j);
                IF i <= 2 THEN SET v_price = 350; SET v_seat_type = 'recliner';
                ELSEIF i <= 4 THEN SET v_price = 250; SET v_seat_type = 'premium';
                ELSE SET v_price = 180; SET v_seat_type = 'standard';
                END IF;
                INSERT INTO seats (movie_id, hall_id, seat_number, row_name, seat_column, seat_type, price, is_booked)
                VALUES (p_movie_id, p_hall_id, v_seat_num, v_row, j, v_seat_type, v_price, 0);
                SET j = j + 1;
            END WHILE;
            SET i = i + 1;
        END WHILE;
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- CREATE SEATS FOR MOVIES 1-10
-- =====================================================
CALL CreateSeatsForMovie(1, 1);
CALL CreateSeatsForMovie(2, 2);
CALL CreateSeatsForMovie(3, 3);
CALL CreateSeatsForMovie(4, 4);
CALL CreateSeatsForMovie(5, 5);
CALL CreateSeatsForMovie(6, 6);
CALL CreateSeatsForMovie(7, 7);
CALL CreateSeatsForMovie(8, 8);
CALL CreateSeatsForMovie(9, 9);
CALL CreateSeatsForMovie(10, 10);

-- =====================================================
-- MARK SOME BOOKED SEATS FOR DEMO
-- =====================================================
UPDATE seats SET is_booked = 1 WHERE movie_id = 1 AND seat_number IN ('C4','C5','D4','D5');
UPDATE seats SET is_booked = 1 WHERE movie_id = 2 AND seat_number IN ('A1','A2','A3','A4');
UPDATE seats SET is_booked = 1 WHERE movie_id = 3 AND seat_number IN ('B1','B2','B3','B4');
UPDATE seats SET is_booked = 1 WHERE movie_id = 4 AND seat_number IN ('A6','A7','A8');
UPDATE seats SET is_booked = 1 WHERE movie_id = 5 AND seat_number IN ('E1','E2','E3','E4');
UPDATE seats SET is_booked = 1 WHERE movie_id = 6 AND seat_number IN ('C3','C4','C5','C6');
UPDATE seats SET is_booked = 1 WHERE movie_id = 7 AND seat_number IN ('A1','A8','F1','F8');
UPDATE seats SET is_booked = 1 WHERE movie_id = 8 AND seat_number IN ('A1','A2','A3','A4','A5');
UPDATE seats SET is_booked = 1 WHERE movie_id = 9 AND seat_number IN ('C4','C5','C6','C7');
UPDATE seats SET is_booked = 1 WHERE movie_id = 10 AND seat_number IN ('A4','A5','B4','B5');

-- =====================================================
-- DISPLAY SUMMARY
-- =====================================================
SELECT '✅ DATABASE SETUP COMPLETE!' AS Status;
SELECT CONCAT('📊 Total Movies: ', COUNT(*)) AS Movies FROM movies;
SELECT '=====================================' AS '';
SELECT '🎬 MOVIES WITH IMAGE PATHS:' AS '';
SELECT id, title, poster_url FROM movies ORDER BY id;
SELECT '=====================================' AS '';
SELECT '🔐 DEMO LOGIN: john_doe / password123' AS Credentials;