<?php

function getRandomImage($dir) {
    $images = glob($dir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    if (empty($images)) {
        return false;
    }
    return $images[array_rand($images)];
}

function isValidDimension($dim) {
    return is_numeric($dim) && $dim > 0;
}

function readStats($filename) {
    if (file_exists($filename)) {
        $data = file_get_contents($filename);
        return json_decode($data, true);
    }
    return [];
}

function writeStats($filename, $stats) {
    $data = json_encode($stats, JSON_PRETTY_PRINT);
    file_put_contents($filename, $data);
}

function recordStat($filename, $entry) {
    $stats = readStats($filename);
    $stats[] = $entry;
    writeStats($filename, $stats);
}

function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}


$requestUri = $_SERVER['REQUEST_URI'];
$path = trim($requestUri, '/');

$pathParts = explode('/', $path);

$width = isset($pathParts[count($pathParts) - 2]) ? intval($pathParts[count($pathParts) - 2]) : 0;
$height = isset($pathParts[count($pathParts) - 1]) ? intval($pathParts[count($pathParts) - 1]) : 0;

if (isValidDimension($width) && isValidDimension($height)) {
    $imagePath = getRandomImage('images'); 

    if (!$imagePath) {
        http_response_code(404);
        echo 'No images found';
        exit;
    }


    list($originalWidth, $originalHeight, $imageType) = getimagesize($imagePath);

    error_log("Image path: $imagePath");
    error_log("Image type: $imageType");

    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $image = imagecreatefromjpeg($imagePath);
            break;
        case IMAGETYPE_PNG:
            $image = imagecreatefrompng($imagePath);
            break;
        case IMAGETYPE_GIF:
            $image = imagecreatefromgif($imagePath);
            break;
        case IMAGETYPE_WEBP:
            $image = imagecreatefromwebp($imagePath);
            break;
        default:
            http_response_code(415);
            echo 'Unsupported image type';
            exit;
    }

    $aspectRatio = $originalWidth / $originalHeight;
    $targetAspectRatio = $width / $height;

    if ($aspectRatio > $targetAspectRatio) {
        $newHeight = $height;
        $newWidth = intval($height * $aspectRatio);
    } else {
        $newWidth = $width;
        $newHeight = intval($width / $aspectRatio);
    }

    $scaledImage = imagecreatetruecolor($newWidth, $newHeight);

    if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
        imagecolortransparent($scaledImage, imagecolorallocatealpha($scaledImage, 0, 0, 0, 127));
        imagealphablending($scaledImage, false);
        imagesavealpha($scaledImage, true);
    }

    imagecopyresampled($scaledImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);


    $resizedImage = imagecreatetruecolor($width, $height);

    if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
        imagecolortransparent($resizedImage, imagecolorallocatealpha($resizedImage, 0, 0, 0, 127));
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
    }

    $xOffset = intval(($newWidth - $width) / 2);
    $yOffset = intval(($newHeight - $height) / 2);

    imagecopyresampled($resizedImage, $scaledImage, 0, 0, $xOffset, $yOffset, $width, $height, $width, $height);

    $ipAddress = getClientIp();

    $statEntry = [
        'width' => $width,
        'height' => $height,
        'timestamp' => date('Y-m-d H:i:s'),
        'requested_url' => $requestUri,
        'image_path' => $imagePath,
        'ip_address' => $ipAddress,
    ];
    recordStat('stats.json', $statEntry);

    switch ($imageType) {
        case IMAGETYPE_JPEG:
            header('Content-Type: image/jpeg');
            imagejpeg($resizedImage);
            break;
        case IMAGETYPE_PNG:
            header('Content-Type: image/png');
            imagepng($resizedImage);
            break;
        case IMAGETYPE_GIF:
            header('Content-Type: image/gif');
            imagegif($resizedImage);
            break;
        case IMAGETYPE_WEBP:
            header('Content-Type: image/webp');
            imagewebp($resizedImage);
            break;
    }

    imagedestroy($image);
    imagedestroy($scaledImage);
    imagedestroy($resizedImage);
} else {
    http_response_code(400);
    echo 'Invalid dimensions';
}
?>
