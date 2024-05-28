<?php
require 'vendor/autoload.php';

use Picqer\Barcode\BarcodeGenerator;
use Picqer\Barcode\BarcodeGeneratorPNG;

// Function to generate the barcode
function generateBarcode($data) {
    $generator = new BarcodeGeneratorPNG();
    return $generator->getBarcode($data, $generator::TYPE_CODABAR);
}

$codabarData = "A124354543456B"; // Your Codabar data

// Generate the barcode
$barcode = generateBarcode($codabarData);

// Encode the barcode image as Base64
$barcodeBase64 = base64_encode($barcode);

// Save the barcode image as strip.png
file_put_contents('strip.png', base64_decode($barcodeBase64));

$barcodeImageSrc = 'data:image/png;base64,' . $barcodeBase64;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generated Codabar Barcode</title>
</head>
<body>
    <h1>Generated Codabar Barcode</h1>
    
    <h2>Barcode Image:</h2>
    <img src="<?php echo $barcodeImageSrc; ?>" alt="Codabar Barcode">
    <p>Barcode image saved as strip.png successfully.</p>
</body>
</html>



