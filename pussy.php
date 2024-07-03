<?php
// Function to get a random image from the images directory
function getRandomImage($dir) {
    $images = glob($dir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
    if (empty($images)) {
        return false;
    }
    return $images[array_rand($images)];
}

// Function to validate dimensions
function isValidDimension($dim) {
    return is_numeric($dim) && $dim > 0;
}

// Function to read stats from the JSON file
function readStats($filename) {
    if (file_exists($filename)) {
        $data = file_get_contents($filename);
        return json_decode($data, true);
    }
    return [];
}

// Function to write stats to the JSON file
function writeStats($filename, $stats) {
    $data = json_encode($stats, JSON_PRETTY_PRINT);
    file_put_contents($filename, $data);
}

// Function to record a new stat entry
function recordStat($filename, $entry) {
    $stats = readStats($filename);
    $stats[] = $entry;
    writeStats($filename, $stats);
}

// Function to get the client's IP address
function getClientIp() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Get the requested path
$requestUri = $_SERVER['REQUEST_URI'];
$path = trim($requestUri, '/');

// Extract parts from the path
$pathParts = explode('/', $path);

// Find width and height in the path
$width = isset($pathParts[count($pathParts) - 2]) ? intval($pathParts[count($pathParts) - 2]) : 0;
$height = isset($pathParts[count($pathParts) - 1]) ? intval($pathParts[count($pathParts) - 1]) : 0;

if (isValidDimension($width) && isValidDimension($height)) {
    $imagePath = getRandomImage('images'); // Get a random image from the images directory

    // Ensure a file was found
    if (!$imagePath) {
        http_response_code(404);
        echo 'No images found';
        exit;
    }

    // Get image size and type
    list($originalWidth, $originalHeight, $imageType) = getimagesize($imagePath);

    // Output debugging information
    error_log("Image path: $imagePath");
    error_log("Image type: $imageType");

    // Create the original image resource
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

    // Calculate the aspect ratio
    $aspectRatio = $originalWidth / $originalHeight;
    $targetAspectRatio = $width / $height;

    // Determine the dimensions to scale to while maintaining the aspect ratio
    if ($aspectRatio > $targetAspectRatio) {
        // Image is wider than the target aspect ratio
        $newHeight = $height;
        $newWidth = intval($height * $aspectRatio);
    } else {
        // Image is taller than the target aspect ratio
        $newWidth = $width;
        $newHeight = intval($width / $aspectRatio);
    }

    // Create a new true color image with the desired dimensions
    $scaledImage = imagecreatetruecolor($newWidth, $newHeight);

    // Preserve transparency for PNG and GIF images
    if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
        imagecolortransparent($scaledImage, imagecolorallocatealpha($scaledImage, 0, 0, 0, 127));
        imagealphablending($scaledImage, false);
        imagesavealpha($scaledImage, true);
    }

    // Resize the original image and copy it to the new image
    imagecopyresampled($scaledImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

    // Create a new true color image with the desired dimensions (final cropped image)
    $resizedImage = imagecreatetruecolor($width, $height);

    // Preserve transparency for PNG and GIF images in the final image
    if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
        imagecolortransparent($resizedImage, imagecolorallocatealpha($resizedImage, 0, 0, 0, 127));
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
    }

    // Calculate coordinates to center the scaled image
    $xOffset = intval(($newWidth - $width) / 2);
    $yOffset = intval(($newHeight - $height) / 2);

    // Copy and crop the scaled image to the final image
    imagecopyresampled($resizedImage, $scaledImage, 0, 0, $xOffset, $yOffset, $width, $height, $width, $height);

    // Get the client's IP address
    $ipAddress = getClientIp();

    // Record the stats for this request
    $statEntry = [
        'width' => $width,
        'height' => $height,
        'timestamp' => date('Y-m-d H:i:s'),
        'requested_url' => $requestUri,
        'image_path' => $imagePath,
        'ip_address' => $ipAddress,
    ];
    recordStat('stats.json', $statEntry);

    // Output the resized image
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

    // Free up memory
    imagedestroy($image);
    imagedestroy($scaledImage);
    imagedestroy($resizedImage);
} else {
    http_response_code(400);
    echo 'Invalid dimensions';
}
?>
