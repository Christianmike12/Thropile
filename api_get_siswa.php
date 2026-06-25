<?php
require '../koneksi.php';
/** @var mysqli $conn */

header('Content-Type: application/json');

if (isset($_GET['kelas'])) {
    $kelas = mysqli_real_escape_string($conn, $_GET['kelas']);
    $query = mysqli_query($conn, "SELECT nisn, nama_siswa FROM siswa WHERE kelas = '$kelas' AND status = 'Aktif' ORDER BY nama_siswa ASC");

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
