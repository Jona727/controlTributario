<?php
$srcBg = 'C:\Users\JONATAN\.gemini\antigravity\brain\4c77d108-2e3d-4922-b548-8c301779a0da\.user_uploaded\media_1785814950997.jpg';
$srcLogo = 'C:\Users\JONATAN\.gemini\antigravity\brain\4c77d108-2e3d-4922-b548-8c301779a0da\.user_uploaded\media_1785814950942.jpg';

// Since the user said Image 1 is background, let's assume the larger one is background.
// If not, we swap them. Let's check dimensions first.
$bgInfo = getimagesize($srcBg);
$logoInfo = getimagesize($srcLogo);

if ($bgInfo[0] < $logoInfo[0]) {
    // Swap if logo is wider than background
    $temp = $srcBg;
    $srcBg = $srcLogo;
    $srcLogo = $temp;
}

$destBg = 'C:\xampp\htdocs\tasas_municipales\public\assets\images\bg-login.jpg';
$destLogo = 'C:\xampp\htdocs\tasas_municipales\public\assets\images\logo-pingo.jpg';

// Optimize Background (resize to max 1920x1080)
$imgBg = imagecreatefromjpeg($srcBg);
$bgW = imagesx($imgBg);
$bgH = imagesy($imgBg);
if ($bgW > 1920) {
    $newW = 1920;
    $newH = (int)($bgH * (1920 / $bgW));
    $resizedBg = imagecreatetruecolor($newW, $newH);
    imagecopyresampled($resizedBg, $imgBg, 0, 0, 0, 0, $newW, $newH, $bgW, $bgH);
    imagejpeg($resizedBg, $destBg, 75); // 75% quality for fast load
} else {
    imagejpeg($imgBg, $destBg, 75);
}

// Optimize Logo (resize to max 500x500)
$imgLogo = imagecreatefromjpeg($srcLogo);
$logoW = imagesx($imgLogo);
$logoH = imagesy($imgLogo);
if ($logoW > 500) {
    $newW = 500;
    $newH = (int)($logoH * (500 / $logoW));
    $resizedLogo = imagecreatetruecolor($newW, $newH);
    imagecopyresampled($resizedLogo, $imgLogo, 0, 0, 0, 0, $newW, $newH, $logoW, $logoH);
    imagejpeg($resizedLogo, $destLogo, 85);
} else {
    imagejpeg($imgLogo, $destLogo, 85);
}

echo "Images optimized and saved.\n";
