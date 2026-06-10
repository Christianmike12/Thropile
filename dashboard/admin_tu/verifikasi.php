<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Admin TU") {
    header("Location: ../../index.php");
    exit();
}

// JALUR 1: ACC DATA (Via Link GET)
if (isset($_GET['id']) && isset($_GET['status']) && $_GET['status'] == 'Approved') {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "UPDATE prestasi SET status_data='Approved', alasan_tolak=NULL WHERE id_prestasi=$id");
}

// JALUR 2: TOLAK DATA (Via Form POST)
if (isset($_POST['tolak_data'])) {
    $id = (int)$_POST['id_prestasi'];
    $alasan = mysqli_real_escape_string($conn, $_POST['alasan_tolak']);
    mysqli_query($conn, "UPDATE prestasi SET status_data='Rejected', alasan_tolak='$alasan' WHERE id_prestasi=$id");
}

header("Location: dashboard.php");
exit();
