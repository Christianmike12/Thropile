<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Guru") {
    header("Location: ../../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Guru - Trophile</title>
    <link rel="stylesheet" href="../../assets/css/guru_dashboard.css">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="header-title mb-0">Data Prestasi Siswa Binaan</div>
            <a href="input_prestasi.php" class="btn btn-dark fw-bold px-4 py-2">+ Input Data Prestasi</a>
        </div>

        <div class="floating-card">
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center border">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>Nama Siswa</th>
                            <th>Nama Kompetisi</th>
                            <th>Tingkat</th>
                            <th>Hasil</th>
                            <th>Status & Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $nip_guru = $_SESSION['nip'];
                        $no = 1;
                        $query = "SELECT p.*, s.nama_siswa FROM prestasi p JOIN siswa s ON p.nisn = s.nisn WHERE p.nip_guru = '$nip_guru' ORDER BY p.id_prestasi DESC";
                        $res = mysqli_query($conn, $query);

                        if (mysqli_num_rows($res) > 0) {
                            while ($r = mysqli_fetch_assoc($res)) {
                                $st = ($r['status_data'] == 'Approved') ? 'bg-success' : (($r['status_data'] == 'Rejected') ? 'bg-danger' : 'bg-warning text-dark');

                                // Tampilkan alasan di bawah lencana kalau ditolak
                                $catatan = "";
                                if ($r['status_data'] == 'Rejected' && !empty($r['alasan_tolak'])) {
                                    $catatan = "<div class='mt-2' style='font-size:12px; color:#dc3545; text-align:left; line-height:1.2;'><b>Alasan:</b> {$r['alasan_tolak']}</div>";
                                }

                                echo "<tr>
                                    <td>$no</td>
                                    <td class='text-start fw-bold'>{$r['nama_siswa']}</td>
                                    <td class='text-start'>{$r['nama_lomba']}</td>
                                    <td>{$r['tingkat']}</td>
                                    <td>{$r['peringkat']}</td>
                                    <td>
                                        <span class='badge $st px-3 py-2'>{$r['status_data']}</span>
                                        $catatan
                                    </td>
                                </tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='6' class='py-4 text-muted'>Belum ada data prestasi yang Anda input.</td></tr>";
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