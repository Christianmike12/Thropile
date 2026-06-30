<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Admin TU") {
    echo json_encode(['status' => 'error', 'message' => 'Akses ditolak!']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'CSRF Token Invalid.']);
        exit();
    }
}

$action = $_GET['action'] ?? '';

if ($action == 'acc_prestasi') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    $query = db_query($conn, "UPDATE prestasi SET status_data='Approved', alasan_tolak=NULL WHERE id_prestasi=?", "i", $id);

    if ($query) {
        echo json_encode(['status' => 'success', 'message' => 'Prestasi berhasil disetujui!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui database.']);
    }
    exit();
}

if ($action == 'tolak_prestasi') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $alasan = isset($_POST['alasan']) ? trim($_POST['alasan']) : '';

    if (empty($alasan)) {
        echo json_encode(['status' => 'error', 'message' => 'Alasan penolakan tidak boleh kosong!']);
        exit();
    }

    $query = db_query($conn, "UPDATE prestasi SET status_data='Rejected', alasan_tolak=? WHERE id_prestasi=?", "si", $alasan, $id);

    if ($query) {
        echo json_encode(['status' => 'success', 'message' => 'Prestasi berhasil ditolak.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui database.']);
    }
    exit();
}

if ($action == 'acc_reset') {
    $id_req = isset($_POST['id_request']) ? (int)$_POST['id_request'] : 0;

    $kode_baru = strtoupper(substr(md5(time() . rand()), 0, 5));

    $query = db_query($conn, "UPDATE request_reset SET kode_unik=?, status_req='Approved' WHERE id_request=?", "si", $kode_baru, $id_req);

    if ($query) {
        echo json_encode(['status' => 'success', 'kode' => $kode_baru]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal men-generate kode unik.']);
    }
    exit();
}

echo json_encode(['status' => 'error', 'message' => 'Aksi API tidak valid.']);
exit();
