<?php
$host = "localhost";
$user = "root";  // Default XAMPP MySQL user
$pass = "";  // Default password is empty
$dbname = "mmhr_census";  // Your database name

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
