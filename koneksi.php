<?php
$host     = "sql313.infinityfree.com";
$user     = "if0_42302647";
$password = "DoPnPoEFuIiL5H";
$db       = "if0_42302647_trophile";

$conn = mysqli_connect($host, $user, $password, $db);

if (!$conn) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}

require_once __DIR__ . '/core.php';
