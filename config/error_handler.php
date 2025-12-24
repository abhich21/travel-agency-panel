<?php
/**
 * Error Handler Configuration
 * 
 * Centralized error handling based on APP_ENV environment variable.
 * - development: Shows detailed errors on screen for debugging
 * - production: Hides errors from users, logs to file
 * 
 * This file should be included early in config.php after loading dotenv.
 */

// Get environment (defaults to 'production' for safety)
$appEnv = $_ENV['APP_ENV'] ?? 'production';

// Define constant for use elsewhere in the app
define('APP_ENV', $appEnv);

if ($appEnv === 'development') {
    // DEVELOPMENT: Show all errors for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    ini_set('log_errors', '1');

    // Log to a development-specific file
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    ini_set('error_log', $logDir . '/dev-errors.log');

} else {
    // PRODUCTION: Hide errors from users, log everything
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');

    // Log to a production log file
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    ini_set('error_log', $logDir . '/app-errors.log');
}

/**
 * Custom exception handler for uncaught exceptions
 */
set_exception_handler(function ($exception) {
    // Always log the exception
    error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . ":" . $exception->getLine());

    if (APP_ENV === 'development') {
        // Show detailed error in development
        echo "<div style='background:#ff6b6b;color:#fff;padding:20px;margin:20px;border-radius:8px;font-family:monospace;'>";
        echo "<h3>⚠️ Exception</h3>";
        echo "<p><strong>" . htmlspecialchars($exception->getMessage()) . "</strong></p>";
        echo "<p>File: " . htmlspecialchars($exception->getFile()) . ":" . $exception->getLine() . "</p>";
        echo "<pre style='background:#333;padding:10px;overflow:auto;max-height:300px;'>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
        echo "</div>";
    } else {
        // Show generic error in production
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo "<div style='text-align:center;padding:50px;font-family:sans-serif;'>";
        echo "<h1>Something went wrong</h1>";
        echo "<p>We're sorry, but an error occurred. Please try again later.</p>";
        echo "</div>";
    }
    exit(1);
});

/**
 * Custom error handler to convert errors to exceptions
 */
set_error_handler(function ($severity, $message, $file, $line) {
    // Don't throw for suppressed errors (@)
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
?>