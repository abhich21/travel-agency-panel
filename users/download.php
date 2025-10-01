<?php
// download.php - A robust and secure image download proxy

// Ensure a URL is provided
if (!isset($_GET['url']) || empty($_GET['url'])) {
    http_response_code(400);
    die('Error: No image URL specified.');
}

// Sanitize and validate the input URL
$imageUrl = filter_var($_GET['url'], FILTER_VALIDATE_URL);
if ($imageUrl === false) {
    http_response_code(400);
    die('Error: Invalid URL format.');
}

// Sanitize the filename
$filename = isset($_GET['name']) && !empty($_GET['name']) 
            ? basename($_GET['name']) 
            : 'downloaded-image.jpg';

// --- Use cURL to fetch the image (more reliable than file_get_contents) ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $imageUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Follow redirects if any
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); // Connection timeout
$imageContent = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check if the cURL request was successful
if ($httpCode !== 200 || $imageContent === false) {
    http_response_code(404);
    die('Error: Could not retrieve the image from the source. Please check the URL.');
}

// --- CRITICAL: Clean any stray output and send correct headers ---

// Clear any previously buffered output (like whitespace or errors)
if (ob_get_level()) {
    ob_end_clean();
}

// Set the proper headers to trigger a download
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream'); // A generic type to force download
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . strlen($imageContent));

// Flush the system output buffer
flush();

// Output the clean image data and stop the script
echo $imageContent;
exit;

?>