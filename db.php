<?php

$host = "localhost";
$user = "kiosk";
$pass = "1234";
$db   = "medical_kiosk";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed");
}
?>