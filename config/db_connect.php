<?php
// Database configuration
$servername = "localhost";
$username = "root";       // Default username sa XAMPP
$password = "";           // Default password sa XAMPP (blanko)
$dbname = "finance_management";   // I-adjust ito kung iba ang pinangalan mo sa database sa phpMyAdmin

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: I-set ang charset para iwas error sa mga special characters (tulad ng ₱)
$conn->set_charset("utf8mb4");
?>