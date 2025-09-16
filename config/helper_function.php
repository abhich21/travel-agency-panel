<?php
$bucketName = $_ENV['BUCKET_NAME'];
$IAM_KEY = $_ENV['AWS_IAM_KEY'];
$IAM_SECRET = $_ENV['AWS_IAM_SECRET'];
$s3_fileurl="https://commanbucetforevents.s3.ap-south-1.amazonaws.com";

// In config/helper_function.php
// In config/helper_function.php
// REMOVE all other 'use Endroid\QrCode\...' lines
// ADD these three lines

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
// Configure AWS SDK
// require '../vendor/autoload.php';
require __DIR__ . '/../vendor/autoload.php';
// DOTENV
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// AWS 
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;

// Create an S3 client
$s3Client = new S3Client([
    'version' => 'latest',
    'region'  => 'ap-south-1',
    'credentials' => [
        'key'    => $IAM_KEY,
        'secret' => $IAM_SECRET,
    ],
]);

// Function to upload file to S3
function uploadToS3($s3Client, $bucketName, $file, $title, $s3FolderName) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload failed with error code: ' . $file['error']];
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = str_replace(' ', '-', strtolower($title)) . '.' . $ext;

    try {
        $key = 'travel-agency-panel/' . $title . '/' . $s3FolderName . '/' . $newFileName;
        $result = $s3Client->putObject([
            'Bucket'     => $bucketName,
            'Key'        => $key,
            'SourceFile' => $file['tmp_name'],
        ]);
        
        return ['success' => true, 'url' => $result['ObjectURL']];
    } catch (S3Exception $e) {
        // Log the detailed S3 error
        error_log("S3 upload error: " . $e->getMessage());
        return ['success' => false, 'error' => 'S3 upload failed: ' . $e->getAwsErrorMessage()];
    }
}


/**
 * Deletes an object from an S3 bucket.
 *
 * @param S3Client $s3Client The S3 client instance.
 * @param string $bucketName The name of the S3 bucket.
 * @param string $fileUrl The URL of the object to delete.
 * @return bool True on success, false on failure.
 */
function deleteFromS3($s3Client, $bucketName, $fileUrl)
{
    try {
        // Extract the object key from the URL
        $parsedUrl = parse_url($fileUrl);
        $objectKey = ltrim($parsedUrl['path'], '/');

        // Check for virtual-hosted style URL (bucket name in the path)
        if (strpos($objectKey, $bucketName . '/') === 0) {
            $objectKey = substr($objectKey, strlen($bucketName) + 1);
        }

        if (empty($objectKey)) {
            error_log("Invalid S3 object key derived from URL: " . $fileUrl);
            return false;
        }

        $s3Client->deleteObject([
            'Bucket' => $bucketName,
            'Key'    => $objectKey,
        ]);

        return true;
    } catch (S3Exception $e) {
        error_log("Failed to delete S3 object: " . $e->getMessage());
        return false;
    }
}

/**
 * Downloads a file from an S3 bucket by its public URL.
 *
 * @param S3Client $s3Client The S3 client instance.
 * @param string $bucketName The name of the S3 bucket.
 * @param string $imageUrl The public URL of the object to download.
 * @return string|null The content of the file, or null on failure.
 */
function downloadFromS3($s3Client, $bucketName, $imageUrl)
{
    try {
        // Extract the object key from the URL
        $parsedUrl = parse_url($imageUrl);
        // The key is the path without the leading slash
        $objectKey = ltrim($parsedUrl['path'], '/');

        // Decode URL-encoded characters in the key (e.g., %20 for space)
        $objectKey = urldecode($objectKey);

        // Check if the object key starts with the bucket name (virtual-hosted style)
        $bucketInPath = strpos($objectKey, $bucketName . '/') === 0;
        if ($bucketInPath) {
            $objectKey = substr($objectKey, strlen($bucketName) + 1);
        }

        if (empty($objectKey)) {
            error_log("Invalid S3 object key derived from URL: " . $imageUrl);
            return null;
        }

        $result = $s3Client->getObject([
            'Bucket' => $bucketName,
            'Key'    => $objectKey,
        ]);

        return (string) $result['Body'];
    } catch (S3Exception $e) {
        error_log("Failed to download S3 object from URL: " . $imageUrl . " with key: " . $objectKey . ". Error: " . $e->getMessage());
        return null;
    }
}


/**
 * Generates a QR code using the Builder syntax and uploads it to S3.
 * This is the new function built using the exact logic from your add_user.php file.
 */
function generateAndUploadQrCode($s3Client, $bucketName, $qrData, $orgTitle) {
    if (empty($qrData)) {
        return ['success' => false, 'error' => 'QR code data cannot be empty.'];
    }

    $temp_file = null;

    try {
        // This is the exact logic from your add_user.php file [cite: add_user.php]
        $result = new Builder(
            writer: new PngWriter(),
            writerOptions: [],
            data: $qrData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        $builtResult = $result->build();
        $temp_file = tempnam(sys_get_temp_dir(), 'qr_');
        $builtResult->saveToFile($temp_file);

        $mock_file = [
            'name' => 'qr_' . md5($qrData) . '.png',
            'type' => 'image/png',
            'tmp_name' => $temp_file,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($temp_file),
        ];

        // The folder is changed to 'qrcodes' to match the registration page logic
        $upload_result = uploadToS3($s3Client, $bucketName, $mock_file, $orgTitle, 'qrcodes');
        
        if (isset($upload_result['success']) && $upload_result['success']) {
            $qr_code_link = $upload_result['url'];
            unlink($temp_file);
            return ['success' => true, 'url' => $qr_code_link];
        } else {
            throw new Exception($upload_result['error'] ?? 'Unknown S3 upload error.');
        }

    } catch (Exception $e) {
        if ($temp_file && file_exists($temp_file)) {
            unlink($temp_file);
        }
        return ['success' => false, 'error' => "QR code generation or upload failed: " . $e->getMessage()];
    }
}