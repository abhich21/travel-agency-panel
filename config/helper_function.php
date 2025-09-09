<?php
$bucketName = $_ENV['BUCKET_NAME'];
$IAM_KEY = $_ENV['AWS_IAM_KEY'];
$IAM_SECRET = $_ENV['AWS_IAM_SECRET'];
$s3_fileurl="https://commanbucetforevents.s3.ap-south-1.amazonaws.com";



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
