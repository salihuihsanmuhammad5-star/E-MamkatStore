<?php
// includes/helpers.php
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function resizeImage($filepath, $maxWidth, $maxHeight) {
    list($width, $height, $type) = getimagesize($filepath);
    $src = null;
    switch ($type) {
        case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($filepath); break;
        case IMAGETYPE_PNG:  $src = imagecreatefrompng($filepath); break;
        case IMAGETYPE_WEBP: $src = imagecreatefromwebp($filepath); break;
        default: return;
    }
    $newWidth  = $maxWidth;
    $newHeight = $maxHeight;
    $dst = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    switch ($type) {
        case IMAGETYPE_JPEG: imagejpeg($dst, $filepath, 85); break;
        case IMAGETYPE_PNG:  imagepng($dst, $filepath); break;
        case IMAGETYPE_WEBP: imagewebp($dst, $filepath, 85); break;
    }
    imagedestroy($src);
    imagedestroy($dst);
}

