<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Siswa") {
    header("Location: ../../index.php");
    exit();
}

$nisn_siswa = isset($_SESSION['nisn']) ? $_SESSION['nisn'] : (isset($_SESSION['username']) ? $_SESSION['username'] : '');

// ================= LOGIKA PENGATURAN AKUN SISWA =================
if (isset($_POST['edit_profil_siswa'])) {
    $nisn_edit  = $_POST['nisn_siswa'];
    $nama_edit  = $_POST['nama_siswa'];
    $pass_edit  = $_POST['pass_siswa'];

    mysqli_query($conn, "UPDATE siswa SET nama_siswa='$nama_edit', PASSWORD='$pass_edit' WHERE nisn='$nisn_edit'");

    // Sinkronisasi session nama agar langsung berubah tanpa relogin
    $_SESSION['nama'] = $nama_edit;

    echo "<script>alert('Profil Anda berhasil diperbarui!'); window.location='dashboard.php';</script>";
    exit();
}

// Ambil data profil siswa terbaru langsung dari database SQL untuk isi form modal
$q_profil = mysqli_query($conn, "SELECT * FROM siswa WHERE nisn='$nisn_siswa'");
$dt_profil = mysqli_fetch_assoc($q_profil);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Siswa - Trophile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/siswa_dashboard.css?v=<?php echo time(); ?>">
</head>

<body>
    <nav class="navbar-custom">
        <div class="nav-container">
            <div class="brand-wrapper">
                <img src="../../assets/images/SMANSA.png" alt="Logo" width="35">
                <a href="dashboard.php" class="brand-logo">TROPHILE</a>
            </div>

            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" style="border: 1px solid rgba(255,255,255,0.3); padding: 6px 15px; border-radius: 8px;">
                    <span class="me-2 fw-medium d-none d-md-block">Halo, <?php echo $_SESSION['nama']; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; border:none; margin-top:10px;">
                    <li>
                        <h6 class="dropdown-header text-muted">Akses Siswa</h6>
                    </li>
                    <li><a class="dropdown-item fw-medium" href="#" data-bs-toggle="modal" data-bs-target="#editProfilSiswa">⚙️ Pengaturan Akun</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger fw-bold" href="../../logout.php">🚪 Keluar</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="floating-card">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div class="header-title mb-0">Riwayat Prestasi Saya</div>
                <a href="portofolio.php" target="_blank" class="btn btn-action">Unduh Portofolio</a>
            </div>

            <div class="table-wrapper">
                <table class="table text-center align-middle custom-table">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="30%" class="text-start">NAMA KOMPETISI</th>
                            <th width="15%">KATEGORI</th>
                            <th width="15%">TINGKAT</th>
                            <th width="10%">TAHUN</th>
                            <th width="10%">HASIL</th>
                            <th width="15%">STATUS</th>
                            <th width="10%">BERKAS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        // FILTER KETAT: HANYA TAMPILKAN YANG APPROVED SAJA
                        $q = mysqli_query($conn, "SELECT * FROM prestasi WHERE nisn = '$nisn_siswa' AND status_data = 'Approved' ORDER BY id_prestasi DESC");

                        if (mysqli_num_rows($q) > 0) {
                            while ($r = mysqli_fetch_assoc($q)) {
                                $file_aman   = rawurlencode($r['file_sertifikat']);
                                $tahun_lomba = !empty($r['tahun']) ? $r['tahun'] : (!empty($r['tanggal_pelaksanaan']) ? date('Y', strtotime($r['tanggal_pelaksanaan'])) : '-');

                                echo "<tr>
                                    <td><span class='badge-nip'>$no</span></td>
                                    <td class='text-start fw-medium text-navy'>{$r['nama_lomba']}</td>
                                    <td>{$r['kategori']}</td>
                                    <td><span class='badge bg-light text-dark border px-2'>{$r['tingkat']}</span></td>
                                    <td>$tahun_lomba</td>
                                    <td><span class='badge bg-warning text-dark px-2'>{$r['peringkat']}</span></td>
                                    <td><span class='badge bg-success px-3 py-2 rounded-pill'>Disetujui</span></td>
                                    <td><a href='../../assets/uploads/$file_aman' target='_blank' class='btn btn-edit-outline btn-sm'>Lihat</a></td>
                                  </tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='8' class='py-4 text-muted fw-medium'>Belum ada data prestasi resmi yang disetujui.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade text-start" id="editProfilSiswa" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Pengaturan Akun</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="nisn_siswa" value="<?php echo $dt_profil['nisn'] ?? ''; ?>">

                    <div class="mb-3">
                        <label class="form-label small">NISN (Nomor Induk Siswa Nasional)</label>
                        <input type="text" class="form-control bg-light" value="<?php echo $dt_profil['nisn'] ?? ''; ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Kelas</label>
                        <input type="text" class="form-control bg-light" value="<?php echo $dt_profil['kelas'] ?? ''; ?>" readonly>
                        <div class="form-text small">*NISN & Kelas dikunci demi integritas relasi data prestasi sekolah.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nama Lengkap Siswa</label>
                        <input type="text" name="nama_siswa" class="form-control" value="<?php echo $dt_profil['nama_siswa'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Password Akun</label>
                        <input type="text" name="pass_siswa" class="form-control" value="<?php echo $dt_profil['PASSWORD'] ?? ''; ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" name="edit_profil_siswa" class="btn btn-action w-100">Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>