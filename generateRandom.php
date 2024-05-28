<?php
require 'vendor/autoload.php';

use Picqer\Barcode\BarcodeGenerator;
use Picqer\Barcode\BarcodeGeneratorPNG;

// Function to generate the barcode
function generateBarcode($data) {
    $generator = new BarcodeGeneratorPNG();
    return $generator->getBarcode($data, $generator::TYPE_CODABAR);
}

// Generate new Codabar data
$codabarData = generateRandomCodabarData();

// Generate the barcode
$barcode = generateBarcode($codabarData);

// Encode the barcode image as Base64
$barcodeBase64 = base64_encode($barcode);

// Prepare response data
$response = [
    'barcodeImageSrc' => 'data:image/png;base64,' . $barcodeBase64,
    'codabarData' => $codabarData
];

// Output JSON response
echo json_encode($response);

// Function to generate random Codabar data
function generateRandomCodabarData() {
    $characters = '0123456789ABCD'; // Codabar allowed characters
    $length = 12; // Adjust length as needed
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}
?>
