<?php
$host     = "localhost";
$user     = "root";
$password = "";
$db       = "trophile";

$conn = mysqli_connect($host, $user, $password, $db);

if (!$conn) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}
