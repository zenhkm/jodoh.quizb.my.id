<?php
$host = "localhost";
$user = "quic1934_zenhkm"; // Sesuaikan dengan username database Anda
$pass = "03Maret1990";     // Sesuaikan dengan password database Anda
$db   = "quic1934_jodoh";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}
?>