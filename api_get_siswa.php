<?php
session_start();
require 'koneksi.php';
/** @var mysqli $conn */

header('Content-Type: application/json');

if (!isset($_SESSION['role'])) {
    echo json_encode(['status' => 'error', 'msg' => 'Akses ditolak!']);
    exit();
}

if (isset($_GET['kelas'])) {
    $kelas = trim($_GET['kelas']);
    $query = db_query($conn, "SELECT nisn, nama_siswa FROM siswa WHERE kelas = ? AND status = 'Aktif' ORDER BY nama_siswa ASC", "s", $kelas);

    $data_siswa = [];
    while ($row = mysqli_fetch_assoc($query)) {
        $data_siswa[] = [
            'nisn' => $row['nisn'],
            'nama' => $row['nama_siswa']
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $data_siswa]);
} else {
    echo json_encode(['status' => 'error', 'msg' => 'Parameter kelas tidak ditemukan']);
}
