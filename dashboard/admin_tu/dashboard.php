<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Admin TU") {
    header("Location: ../../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Admin TU - Trophile</title>
    <link rel="stylesheet" href="../../assets/css/admin_tu_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar-custom">
        <div class="nav-container">
            <div class="d-flex align-items-center">
                <a href="dashboard.php" style="font-size:22px;font-weight:bold;letter-spacing:2px;text-decoration:none;color:white;margin-right:20px;">TROPHILE</a>
                <a href="dashboard.php" class="text-white fw-bold text-decoration-none me-3">Dashboard (Verifikasi)</a>
                <a href="permintaan_reset.php" class="text-secondary text-decoration-none">Notif Reset Password</a>
            </div>
            <div>
                <span class="me-3">Halo, <?php echo $_SESSION['nama']; ?></span>
                <a href="../../logout.php" class="btn-logout">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="floating-card">
            <div class="header-title">Verifikasi Prestasi Siswa</div>
            <div class="table-responsive">
                <table class="table table-hover text-center align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Siswa</th>
                            <th>Lomba</th>
                            <th>Peringkat</th>
                            <th>Berkas</th>
                            <th>Opsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $kumpulan_modal = ""; // <-- Variabel buat nampung semua modal di luar tabel

                        $query = "SELECT p.*, s.nama_siswa FROM prestasi p JOIN siswa s ON p.nisn = s.nisn WHERE p.status_data = 'Pending'";
                        $res = mysqli_query($conn, $query);
                        if (mysqli_num_rows($res) > 0) {
                            while ($row = mysqli_fetch_assoc($res)) {
                                $file_aman = rawurlencode($row['file_sertifikat']);
                                $id_p = $row['id_prestasi'];

                                // Cetak baris tabel
                                echo "<tr>
                                    <td>$no</td>
                                    <td class='fw-bold'>{$row['nama_siswa']}</td>
                                    <td>{$row['nama_lomba']}</td>
                                    <td><span class='badge bg-warning text-dark'>{$row['peringkat']}</span></td>
                                    <td><a href='../../assets/uploads/$file_aman' target='_blank' class='btn btn-sm btn-outline-dark'>Lihat</a></td>
                                    <td>
                                        <a href='verifikasi.php?id=$id_p&status=Approved' class='btn btn-sm btn-success' onclick='return confirm(\"Setujui prestasi ini?\")'>ACC</a>
                                        <button class='btn btn-sm btn-danger' data-bs-toggle='modal' data-bs-target='#tolakModal$id_p'>Tolak</button>
                                    </td>
                                  </tr>";

                                // Simpan kode modal ke variabel (jangan di-echo di sini)
                                $kumpulan_modal .= "
                                <div class='modal fade' id='tolakModal$id_p' tabindex='-1' aria-hidden='true'>
                                    <div class='modal-dialog modal-dialog-centered'>
                                        <form action='verifikasi.php' method='POST' class='modal-content'>
                                            <div class='modal-header'>
                                                <h5 class='modal-title fw-bold'>Alasan Penolakan</h5>
                                                <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                                            </div>
                                            <div class='modal-body text-start'>
                                                <input type='hidden' name='id_prestasi' value='$id_p'>
                                                <p>Siswa: <b>{$row['nama_siswa']}</b><br>Lomba: {$row['nama_lomba']}</p>
                                                <label class='form-label fw-bold'>Tuliskan Alasan:</label>
                                                <textarea name='alasan_tolak' class='form-control' rows='3' placeholder='Contoh: Dokumen sertifikat buram / Lomba tidak valid' required></textarea>
                                            </div>
                                            <div class='modal-footer'>
                                                <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Batal</button>
                                                <button type='submit' name='tolak_data' class='btn btn-danger'>Tolak & Simpan Alasan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='6' class='py-4 text-muted'>Belum ada sertifikat yang perlu diverifikasi.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php echo $kumpulan_modal; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>