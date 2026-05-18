<?php
// database/generate-images.php
// Generates colored placeholder images for all products
require_once __DIR__ . '/../config.php';

// Increase limits
set_time_limit(600);
ini_set('memory_limit', '128M');

// Target base directory
$baseDir = __DIR__ . '/../images/products/';

// Get all distinct image paths from the products table
$result = mysqli_query($conn, "SELECT DISTINCT image FROM products");
while ($row = mysqli_fetch_assoc($result)) {
    $relativePath = $row['image']; // e.g., "products/fashion-handbags/placeholder-1.jpg"
    $fullPath = __DIR__ . '/../images/' . $relativePath;

    // Skip if file already exists
    if (file_exists($fullPath)) continue;

    // Create directory if not exists
    $dir = dirname($fullPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Generate a simple colored placeholder image
    $width  = 300;
    $height = 300;
    $img = imagecreatetruecolor($width, $height);

    // Random background color (soft colors)
    $bg = imagecolorallocate($img, rand(200,240), rand(200,240), rand(200,240));
    imagefill($img, 0, 0, $bg);

    // Draw a text (product name is not easily known here, so just a label)
    $textColor = imagecolorallocate($img, 50, 50, 50);
    $text = "Product Image";
    // Use built-in font
    imagestring($img, 5, 80, 130, $text, $textColor);

    // Save as JPG
    imagejpeg($img, $fullPath, 85);
    imagedestroy($img);
}

echo "Placeholder images generated for all products.";