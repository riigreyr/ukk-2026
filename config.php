<?php

$hostname = 'localhost';
$username = 'root';
$password = '';
$dbname   = 'db_aspirasi';

$conn = mysqli_connect($hostname, $username, $password, $dbname) or die("Koneksi gagal: " . mysqli_connect_error());
mysqli_set_charset($conn, "utf8mb4");

//buat koneksi database 
function getConnection() {
    global $conn;
    return $conn;
}

//buat bersihin variable input
function sanitize($conn, $input) {
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($input)));
}

//redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// mulai session sblum di jalankan
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>