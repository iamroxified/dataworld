<?php 
session_start();

// Create a blank image
$width = 180;
$height = 60;
$image = imagecreate($width, $height);

// Set background and text colors
$background_color = imagecolorallocate($image, 255, 255, 255); // White background
$text_color = imagecolorallocate($image, 0, 0, 0); // Black text

// Generate random text for the CAPTCHA
$characters = '0123456789';
$captcha_text = substr(str_shuffle($characters), 0, 6);

// Store the CAPTCHA text in the session
$_SESSION['captcha'] = $captcha_text;

// Add text to the image using built-in fonts if TTF not available
if (function_exists('imagettftext') && file_exists(__DIR__ . '/assets/captcha.ttf')) {
    $font = __DIR__ . '/assets/captcha.ttf';
    imagettftext($image, 30, 0, 20, 40, $text_color, $font, $captcha_text);
} else {
    // Fallback to built-in font
   // imagettftext($image, 50, 0, 10, 30, $text_color, $font, $captcha_text);

    imagestring($image, 50, 60, 20, $captcha_text, $text_color);
}

// Add noise (optional)
for ($i = 0; $i < 50; $i++) {
    $noise_color = imagecolorallocate($image, rand(100, 255), rand(100, 255), rand(100, 255));
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
}

// Add some lines for extra security
for ($i = 0; $i < 5; $i++) {
    $line_color = imagecolorallocate($image, rand(150, 255), rand(150, 255), rand(150, 255));
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $line_color);
}

// Output the image as a PNG
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Expires: Thu, 19 Nov 1981 08:52:00 GMT');
header('Pragma: no-cache');

imagepng($image);

// Free up memory
imagedestroy($image);
?>