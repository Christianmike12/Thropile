<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Siswa") {
    header("Location: ../../index.php");
    exit();
}

$nisn_siswa = isset($_SESSION['nisn']) ? $_SESSION['nisn'] : (isset($_SESSION['username']) ? $_SESSION['username'] : '');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Siswa - Trophile</title>
    <link rel="stylesheet" href="../../assets/css/siswa_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar-custom">
        <div class="nav-container">
            <a href="#" style="font-size:22px;font-weight:bold;letter-spacing:2px;text-decoration:none;color:white;">TROPHILE</a>
            <div>
                <span class="me-3">Halo, <?php echo $_SESSION['nama']; ?></span>
                <a href="../../logout.php" class="btn-logout">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="floating-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="header-title mb-0">Riwayat Prestasi Saya</div>
                <a href="portofolio.php" target="_blank" class="btn btn-dark fw-bold btn-print">Unduh Portofolio</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle text-center border">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Nama Kompetisi</th>
                            <th>Kategori</th>
                            <th>Tingkat</th>
                            <th>Tahun</th>
                            <th>Hasil</th>
                            <th>Status</th>
                            <th>Bukti Fisik</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $q = mysqli_query($conn, "SELECT * FROM prestasi WHERE nisn = '$nisn_siswa' ORDER BY id_prestasi DESC");
                        if (mysqli_num_rows($q) > 0) {
                            while ($r = mysqli_fetch_assoc($q)) {
                                $st = ($r['status_data'] == 'Approved') ? 'bg-success' : (($r['status_data'] == 'Rejected') ? 'bg-danger' : 'bg-warning text-dark');
                                $file_aman   = rawurlencode($r['file_sertifikat']);
                                $tahun_lomba = !empty($r['tahun']) ? $r['tahun'] : (!empty($r['tanggal_pelaksanaan']) ? date('Y', strtotime($r['tanggal_pelaksanaan'])) : '-');
                                echo "<tr>
                                <td>$no</td>
                                <td class='text-start fw-bold'>{$r['nama_lomba']}</td>
                                <td>{$r['kategori']}</td>
                                <td>{$r['tingkat']}</td>
                                <td>$tahun_lomba</td>
                                <td>{$r['peringkat']}</td>
                                <td><span class='badge $st px-3 py-2'>{$r['status_data']}</span></td>
                                <td><a href='../../assets/uploads/$file_aman' target='_blank' class='btn btn-sm btn-outline-dark'>Lihat Berkas</a></td>
                              </tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='8' class='py-4 text-muted'>Belum ada data prestasi. Silakan hubungi Guru Anda.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>