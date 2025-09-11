<?php
//Note: This file should be included first in every php page.
error_reporting(E_ALL);
ini_set('display_errors', 'On');
define('CURRENT_PAGE', basename($_SERVER['REQUEST_URI']));

// echo(CURRENT_PAGE);
// die();
/*
|--------------------------------------------------------------------------
| DATABASE CONFIGURATION
|--------------------------------------------------------------------------

 */

require __DIR__ . '/../vendor/autoload.php';
// DOTENV
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// e.g., localhost
$servername = $_ENV['DB_SERVERNAME'];
$username = $_ENV['DB_USERNAME'];
$password = $_ENV['DB_PASSWORD'];
$dbname = $_ENV['DB_NAME'];
// Main domain
// $servername = "localhost";
// $username = "ucdxq5ianv1b0"; // Komal 2
// $password = "Komal@1989";
// $dbname = "dbidlykwgpjizo";




// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
/*
|--------------------------------------------------------------------------
| baseurl CONFIGURATION //for live replace with your domain
|--------------------------------------------------------------------------
 */

// Hardcoded admin credentials
// $adminEmail = 'admin@example.com';
// $adminPassword = '123456';
$apiBaseUrl='http://localhost/hunt/admin/api/index.php';
$draw_passkey = '9876';


// $adminArr = [
//     [
//         'email' => 'admin@masterstroke.com',
//         'password' => '123456',
//         'role' => 'admin'
//     ]
// ];



//timezone
date_default_timezone_set("Asia/Calcutta");   //India time (GMT+5:30)
$dbTimeZone="'+05:30'";



?>