<?php
$host = "localhost";
$user = "root"; // adjust if different
$pass = ""; // your password if any
$dbname = "foodpanda";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
