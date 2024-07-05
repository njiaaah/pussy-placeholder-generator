<?php
function readStats($filename) {
    if (file_exists($filename)) {
        $data = file_get_contents($filename);
        return json_decode($data, true);
    }
    return [];
}

$stats = readStats('stats.json');
$totalRequests = count($stats);
$statsJson = json_encode($stats);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Placeholder Images - Get Random Picture of Cat 😸</title>
    <meta name="description" content="Get random kitten, cat images at specified dimensions. Perfect for placeholders, design mockups, and more!">
    <meta name="keywords" content="kitten images,can images, placeholder images, random kitten pictures, design mockups, 😸">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <script src="script.js" defer></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400..700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
</head>

<body>

    <main>

        <h1>Kitten <b>Placeholder Image</b> Generator</h1>

        <div class="input-wrap">
            <span>
                &ltimg src="
            </span>
            <input id="input" type="text" readonly placeholder="" value="https://njiah.ru/pussy/250/250">
            <span>
                "&gt
            </span>
        </div>

        <div class="img-container">

            <img src="https://njiah.ru/pussy/145/150" alt="image of a cat">
            <img src="https://njiah.ru/pussy/400/150" alt="image of a cat">
            <img src="https://njiah.ru/pussy/135/150" alt="image of a cat">
            <img src="https://njiah.ru/pussy/425/150" alt="image of a cat">
            <img src="https://njiah.ru/pussy/160/150" alt="image of a cat">
            <img src="https://njiah.ru/pussy/360/150" alt="image of a cat">
            <img src="https://njiah.ru/pussy/260/150" alt="image of a cat">
            <img src="https://njiah.ru/pussy/560/150" alt="image of a cat">

        </div>

        <div class="counter-wrapper">
            <p>Images delivered: <span id="deliver-counter"><?php echo $totalRequests; ?></span></p>
        </div>

    </main>




</body>

</html>