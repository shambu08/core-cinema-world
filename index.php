<?php
session_start();
// If user is already logged in, go to welcome page
if(isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    header("Location: welcome.php");
    exit();
} else {
    // Show splash page first (cinema logo intro)
    header("Location: splash.php");
    exit();
}
?>