<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */
// ^^^ INI MANTRA BIAR VS CODE LU GAK NGELUARIN GARIS MERAH ^^^

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Admin TU") {
    header("Location: ../../index.php");
    exit();
}

// FITUR TUKANG SAPU GAIB (Garbage Collector)
// Otomatis menghapus request yang udah selesai ATAU yang udah numpuk lewat dari 1 jam
mysqli_query($conn, "DELETE FROM request_reset WHERE status_req='Selesai' OR waktu_req < NOW() - INTERVAL 1 HOUR");

// 1. LOGIKA ACC & GENERATE KODE
if (isset($_POST['acc_request'])) {
    $id_req = (int)$_POST['id_request'];
    $kode_baru = strtoupper(substr(md5(time() . rand()), 0, 5));

    mysqli_query($conn, "UPDATE request_reset SET kode_unik='$kode_baru', status_req='Approved' WHERE id_request=$id_req") or die(mysqli_error($conn));

    echo "<script>alert('Kode berhasil digenerate: $kode_baru'); window.location='permintaan_reset.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Permintaan Reset - Trophile</title>
    <link rel="stylesheet" href="../../assets/css/admin_tu_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar-custom">
        <div class="nav-container">
            <div class="d-flex align-items-center">
                <a href="dashboard.php" style="font-size:22px;font-weight:bold;letter-spacing:2px;text-decoration:none;color:white;margin-right:20px;">TROPHILE</a>
                <a href="dashboard.php" class="text-white text-decoration-none me-3">Dashboard</a>
                <a href="permintaan_reset.php" class="text-warning fw-bold text-decoration-none">Notif Reset Password</a>
            </div>
            <div>
                <span class="me-3">Halo, <?php echo $_SESSION['nama']; ?></span>
                <a href="../../logout.php" class="btn-logout">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="floating-card">
            <div class="header-title">Daftar Permintaan Reset Password</div>
            <table class="table table-hover text-center align-middle">
                <thead>
                    <tr>
                        <th>Waktu Request</th>
                        <th>Username / NISN</th>
                        <th>Status</th>
                        <th>Kode Otorisasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $q = mysqli_query($conn, "SELECT * FROM request_reset ORDER BY waktu_req DESC") or die(mysqli_error($conn));
                    if (mysqli_num_rows($q) > 0) {
                        while ($r = mysqli_fetch_assoc($q)) {
                            $badge = match ($r['status_req']) {
                                'Pending' => 'bg-danger',
                                'Approved' => 'bg-warning text-dark',
                                'Selesai' => 'bg-success',
                                default => 'bg-secondary'
                            };
                            $kode = $r['kode_unik'] ? "<b style='letter-spacing:2px;font-size:16px;'>{$r['kode_unik']}</b>" : '-';

                            echo "<tr>
                                <td>{$r['waktu_req']}</td>
                                <td class='fw-bold'>{$r['username']}</td>
                                <td><span class='badge $badge px-3'>{$r['status_req']}</span></td>
                                <td>$kode</td>
                                <td>";

                            if ($r['status_req'] == 'Pending') {
                                echo "<form method='POST' style='display:inline;'>
                                        <input type='hidden' name='id_request' value='{$r['id_request']}'>
                                        <button type='submit' name='acc_request' class='btn btn-sm btn-dark'>ACC & Beri Kode</button>
                                      </form>";
                            } else {
                                echo "-";
                            }
                            echo "</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='py-4 text-muted'>Tidak ada permintaan reset.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>