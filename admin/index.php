<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['adminLoggedInn']) && !$_SESSION['adminLoggedIn'] === TRUE){
    header('Location:login.php');
}
?>

<h2>Welcome Admin!</h2>