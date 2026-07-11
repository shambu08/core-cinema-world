<?php
// =====================================================
// IMAGE RESIZER - Make all movie posters uniform (300x450)
// Run this file once to resize all images
// =====================================================

$source_dir = 'images/posters/';
$target_width = 300;
$target_height = 450;
$quality = 90;

echo "<!DOCTYPE html>
<html>
<head>
    <title>Image Resizer - Core Cinema World</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #667eea; }
        .success { color: green; padding: 5px; }
        .error { color: red; padding: 5px; }
        .info { color: orange; padding: 5px; }
        .container { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .progress { margin: 10px 0; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🎬 Core Cinema World - Image Resizer</h1>
        <p>Resizing all movie posters to {$target_width}x{$target_height} pixels...</p>
        <div class='progress'>";

// Create placeholder if not exists
if(!file_exists($source_dir . 'placeholder.jpg')) {
    createPlaceholder($source_dir, $target_width, $target_height);
    echo "<div class='success'>✅ Created placeholder.jpg</div>";
}

$success_count = 0;
$missing_count = 0;

// Process all movie posters from 1 to 50
for($i = 1; $i <= 50; $i++) {
    $filename = $source_dir . $i . '.jpg';
    
    if(file_exists($filename)) {
        if(resizeImage($filename, $filename, $target_width, $target_height, $quality)) {
            echo "<div class='success'>✅ Resized: {$i}.jpg</div>";
            $success_count++;
        } else {
            echo "<div class='error'>❌ Failed: {$i}.jpg</div>";
            $missing_count++;
        }
    } else {
        // Try .png format
        $png_file = $source_dir . $i . '.png';
        if(file_exists($png_file)) {
            if(convertPngToJpg($png_file, $filename, $target_width, $target_height, $quality)) {
                echo "<div class='info'>🔄 Converted PNG to JPG: {$i}.jpg</div>";
                $success_count++;
            } else {
                echo "<div class='error'>❌ Failed to convert: {$i}.png</div>";
                $missing_count++;
            }
        } else {
            // Copy placeholder for missing images
            copy($source_dir . 'placeholder.jpg', $filename);
            echo "<div class='error'>⚠️ Missing: {$i}.jpg - Placeholder created</div>";
            $missing_count++;
        }
    }
}

echo "</div>";
echo "<hr>";
echo "<h3>📊 Summary</h3>";
echo "<div class='success'>✅ Successfully processed: {$success_count} images</div>";
echo "<div class='error'>⚠️ Missing/Placeholder: {$missing_count} images</div>";
echo "<br>";
echo "<a href='home.php' style='background: #667eea; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Home Page</a>";
echo "</div></body></html>";

// =====================================================
// FUNCTIONS
// =====================================================

function resizeImage($source, $destination, $target_width, $target_height, $quality) {
    // Get original image info
    list($orig_width, $orig_height, $type) = getimagesize($source);
    
    // Create source image based on type
    switch($type) {
        case IMAGETYPE_JPEG:
            $source_img = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $source_img = imagecreatefrompng($source);
            break;
        default:
            return false;
    }
    
    // Calculate cropping dimensions (center crop)
    $src_x = 0;
    $src_y = 0;
    $src_w = $orig_width;
    $src_h = $orig_height;
    
    // Calculate aspect ratios
    $target_ratio = $target_width / $target_height;
    $orig_ratio = $orig_width / $orig_height;
    
    if($orig_ratio > $target_ratio) {
        // Image is wider - crop width
        $src_w = $orig_height * $target_ratio;
        $src_x = ($orig_width - $src_w) / 2;
    } else {
        // Image is taller - crop height
        $src_h = $orig_width / $target_ratio;
        $src_y = ($orig_height - $src_h) / 2;
    }
    
    // Create target image
    $target_img = imagecreatetruecolor($target_width, $target_height);
    
    // Resize and crop
    imagecopyresampled(
        $target_img, $source_img,
        0, 0, $src_x, $src_y,
        $target_width, $target_height, $src_w, $src_h
    );
    
    // Save image
    imagejpeg($target_img, $destination, $quality);
    
    // Free memory
    imagedestroy($source_img);
    imagedestroy($target_img);
    
    return true;
}

function convertPngToJpg($source, $destination, $target_width, $target_height, $quality) {
    $png = imagecreatefrompng($source);
    
    // Create white background
    $jpg = imagecreatetruecolor($target_width, $target_height);
    $white = imagecolorallocate($jpg, 255, 255, 255);
    imagefill($jpg, 0, 0, $white);
    
    // Resize PNG to fit
    $png_width = imagesx($png);
    $png_height = imagesy($png);
    
    // Calculate scaling
    $scale = min($target_width / $png_width, $target_height / $png_height);
    $new_width = $png_width * $scale;
    $new_height = $png_height * $scale;
    $x = ($target_width - $new_width) / 2;
    $y = ($target_height - $new_height) / 2;
    
    imagecopyresampled($jpg, $png, $x, $y, 0, 0, $new_width, $new_height, $png_width, $png_height);
    imagejpeg($jpg, $destination, $quality);
    
    imagedestroy($png);
    imagedestroy($jpg);
    
    return true;
}

function createPlaceholder($dir, $width, $height) {
    $img = imagecreatetruecolor($width, $height);
    
    // Gradient background
    $color1 = imagecolorallocate($img, 102, 126, 234);
    $color2 = imagecolorallocate($img, 118, 75, 162);
    
    // Fill with gradient
    for($i = 0; $i < $height; $i++) {
        $ratio = $i / $height;
        $r = 102 + (118 - 102) * $ratio;
        $g = 126 + (75 - 126) * $ratio;
        $b = 234 + (162 - 234) * $ratio;
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $i, $width, $i, $color);
    }
    
    // Draw film icon
    $white = imagecolorallocate($img, 255, 255, 255);
    $font_size = 80;
    $text = "🎬";
    
    // Center text
    $x = ($width - 50) / 2;
    $y = ($height - 50) / 2;
    
    imagettftext($img, $font_size, 0, $x, $y, $white, 'C:/Windows/Fonts/Arial.ttf', $text);
    
    // Add text
    $small_font = 14;
    $text2 = "Image Coming Soon";
    $text_width = strlen($text2) * 7;
    imagettftext($img, $small_font, 0, ($width - $text_width) / 2, $y + 60, $white, 'C:/Windows/Fonts/Arial.ttf', $text2);
    
    // Save
    imagejpeg($img, $dir . 'placeholder.jpg', 90);
    imagedestroy($img);
    
    return true;
}
?>