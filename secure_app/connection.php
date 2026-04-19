<?php
// Database connection for secure app
$host     = "127.0.0.1";
$db_user  = "root";
$db_pass  = "";
$database = "lab5";
$port     = 3307;         // XAMPP MySQL port for this lab

$conn = mysqli_connect($host, $db_user, $db_pass, $database, $port);

if (!$conn) {
    // Generic error — do NOT expose details
    error_log("DB connection failed: " . mysqli_connect_error());
    http_response_code(500);
    die("A server error occurred. Please try again later.");
}
?>
