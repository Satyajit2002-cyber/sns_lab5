<?php
// Database connection for vulnerable app
$host     = "127.0.0.1";
$db_user  = "root";
$db_pass  = "";           // Default XAMPP has no root password
$database = "lab5";
$port     = 3307;         // XAMPP MySQL runs on port 3307

$conn = mysqli_connect($host, $db_user, $db_pass, $database, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
