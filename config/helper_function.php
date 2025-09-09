<?php
$bucketName = $_ENV['DB_NAME'];
$IAM_KEY = $_ENV['AWS_IAM_KEY'];
$IAM_SECRET = $_ENV['AWS_IAM_SECRET'];
$s3_fileurl="https://commanbucetforevents.s3.ap-south-1.amazonaws.com";



// Configure AWS SDK
// require '../vendor/autoload.php';
require __DIR__ . '/../vendor/autoload.php';
// DOTENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// AWS 
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

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
function uploadToS3($s3Client, $bucketName, $file, $s3FolderName) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = basename($file['name']);
    $key = "$s3FolderName/$newFileName";

    // Get the MIME type based on the file content
    $fileMimeType = mime_content_type($file['tmp_name']); // or use finfo_file() for more accuracy

    try {
        $result = $s3Client->putObject([
            'Bucket' => $bucketName,
            'Key'    => $key,
            'SourceFile' => $file['tmp_name'],
            'ContentType' => $fileMimeType,  // Set the Content-Type here
        ]);
        
        return $result['ObjectURL'];
    } catch (AwsException $e) {
        error_log('S3 Upload Error: ' . $e->getMessage());
        return null;
    }
}

/**
 * Delete a file from an S3 bucket
 * 
 * @param object $s3Client AWS S3 client instance
 * @param string $bucketName Name of the S3 bucket
 * @param string $fileUrl Full URL of the file to delete
 * @return bool True if deletion was successful, false otherwise
 */
function deleteFromS3($s3Client, $bucketName, $fileUrl) {
    try {
        // Extract the key from the URL and URL-decode it
        $urlParts = parse_url($fileUrl);
        // Get the path and decode it to handle special characters
        $key = isset($urlParts['path']) ? ltrim(urldecode($urlParts['path']), '/') : null;
        
        if ($key === null) {
            error_log('S3 Delete Error: Could not parse key from URL.');
            return false;
        }

        // Delete the object
        $result = $s3Client->deleteObject([
            'Bucket' => $bucketName,
            'Key'    => $key,
        ]);
        
        return true;
    } catch (AwsException $e) {
        error_log('S3 Delete Error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Downloads a file from an S3 bucket and returns its content.
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
        error_log("Failed to download S3 object from URL: " . $imageUrl . " with key: " . $objectKey . " - Error: " . $e->getMessage());
        return null;
    } catch (Exception $e) {
        error_log("An unexpected error occurred: " . $e->getMessage());
        return null;
    }
}

?>

