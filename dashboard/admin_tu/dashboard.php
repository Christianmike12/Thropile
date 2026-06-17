<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Admin TU") {
    header("Location: ../../index.php");
    exit();
}

// ================= LOGIKA PENGATURAN AKUN ADMIN TU (DIRI SENDIRI) =================
if (isset($_POST['edit_profil_tu'])) {
    $nip_tu  = $_POST['nip_tu'];
    $nama_tu = $_POST['nama_tu'];
    $user_tu = $_POST['user_tu'];
    $pass_tu = $_POST['pass_tu'];

    mysqli_query($conn, "UPDATE admin_tu SET nama_admin='$nama_tu', username='$user_tu', PASSWORD='$pass_tu' WHERE nip='$nip_tu'");

    // Update session biar nama di pojok kanan langsung berubah
    $_SESSION['nama'] = $nama_tu;
    $_SESSION['username'] = $user_tu;

    echo "<script>alert('Profil Admin TU berhasil diperbarui!'); window.location='dashboard.php';</script>";
    exit();
}

// ================= LOGIKA RESET PASSWORD =================
mysqli_query($conn, "DELETE FROM request_reset WHERE status_req='Selesai' OR waktu_req < NOW() - INTERVAL 1 HOUR");

if (isset($_POST['acc_request'])) {
    $id_req = (int)$_POST['id_request'];
    $kode_baru = strtoupper(substr(md5(time() . rand()), 0, 5));
    mysqli_query($conn, "UPDATE request_reset SET kode_unik='$kode_baru', status_req='Approved' WHERE id_request=$id_req");
    echo "<script>alert('Kode berhasil digenerate: $kode_baru'); window.location='dashboard.php';</script>";
    exit();
}

// ================= LOGIKA VERIFIKASI PRESTASI =================
if (isset($_GET['id']) && isset($_GET['status']) && $_GET['status'] == 'Approved') {
    $id = (int)$_GET['id'];
    mysqli_query($conn, "UPDATE prestasi SET status_data='Approved', alasan_tolak=NULL WHERE id_prestasi=$id");
    header("Location: dashboard.php");
    exit();
}

if (isset($_POST['tolak_data'])) {
    $id = (int)$_POST['id_prestasi'];
    $alasan = mysqli_real_escape_string($conn, $_POST['alasan_tolak']);
    mysqli_query($conn, "UPDATE prestasi SET status_data='Rejected', alasan_tolak='$alasan' WHERE id_prestasi=$id");
    header("Location: dashboard.php");
    exit();
}

// AMBIL DATA PROFIL ADMIN TU YANG LAGI LOGIN
$user_aktif = $_SESSION['username'] ?? '';
$q_profil = mysqli_query($conn, "SELECT * FROM admin_tu WHERE username='$user_aktif'");
$dt_profil = mysqli_fetch_assoc($q_profil);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin TU - Trophile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin_tu_dashboard.css?v=<?php echo time(); ?>">
</head>

<body>
    <nav class="navbar-custom">
        <div class="nav-container">
            <div class="brand-wrapper">
                <img src="../../assets/images/SMANSA.png" alt="Logo" width="40">
                <a href="dashboard.php" class="brand-logo">TROPHILE SMANSA</a>
            </div>

            <!-- DROPDOWN PROFIL ADMIN TU -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" style="border: 1px solid rgba(255,255,255,0.3); padding: 6px 15px; border-radius: 8px;">
                    <span class="me-2 fw-medium d-none d-md-block">Halo, <?php echo $_SESSION['nama']; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; border:none; margin-top:10px;">
                    <li>
                        <h6 class="dropdown-header text-muted">Akses Admin TU</h6>
                    </li>
                    <li><a class="dropdown-item fw-medium" href="#" data-bs-toggle="modal" data-bs-target="#editProfilTU">⚙️ Pengaturan Akun</a></li>
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
            <div class="header-title">Dashboard Admin TU</div>

            <ul class="nav nav-pills custom-pills mb-4" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-verifikasi">Verifikasi Prestasi</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-reset">Notifikasi Reset Password</button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- TAB VERIFIKASI -->
                <div class="tab-pane fade show active" id="tab-verifikasi">
                    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                        <h6 class="fw-bold m-0 text-navy">Antrean Verifikasi Prestasi Siswa</h6>
                        <input type="text" id="searchVerifikasi" class="form-control" placeholder="Cari Nama Siswa / Lomba..." style="width: 250px; border-radius: 8px;">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover text-center align-middle custom-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Siswa</th>
                                    <th>Lomba</th>
                                    <th>Peringkat</th>
                                    <th>Berkas</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $q = mysqli_query($conn, "SELECT p.*, s.nama_siswa FROM prestasi p JOIN siswa s ON p.nisn = s.nisn WHERE p.status_data = 'Pending'");
                                $data_verifikasi = [];

                                if (mysqli_num_rows($q) > 0) {
                                    while ($r = mysqli_fetch_assoc($q)) {
                                        $data_verifikasi[] = $r;
                                        $file_aman = rawurlencode($r['file_sertifikat']);
                                ?>
                                        <tr class='verifikasi-row'>
                                            <td><span class='badge bg-light text-dark border'><?php echo $no++; ?></span></td>
                                            <td class='fw-medium text-start v-nama'><?php echo $r['nama_siswa']; ?></td>
                                            <td class='text-start v-lomba'><?php echo $r['nama_lomba']; ?></td>
                                            <td><span class='badge bg-warning text-dark'><?php echo $r['peringkat']; ?></span></td>
                                            <td><a href='../../assets/uploads/<?php echo $file_aman; ?>' target='_blank' class='btn btn-outline-dark btn-sm rounded-pill px-3 fw-medium'>Lihat Berkas</a></td>
                                            <td>
                                                <a href='dashboard.php?id=<?php echo $r['id_prestasi']; ?>&status=Approved' class='btn btn-outline-success btn-sm rounded-pill px-3 me-1' onclick='return confirm("Setujui prestasi ini?")'>ACC</a>
                                                <button class='btn btn-outline-danger btn-sm rounded-pill px-3' data-bs-toggle='modal' data-bs-target='#tolakModal<?php echo $r['id_prestasi']; ?>'>Tolak</button>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='py-4 text-muted fw-medium'>Belum ada sertifikat yang perlu diverifikasi.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- MODAL TOLAK VERIFIKASI -->
                    <?php foreach ($data_verifikasi as $r) { ?>
                        <div class="modal fade text-start" id="tolakModal<?php echo $r['id_prestasi']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <form action="dashboard.php" method="POST" class="modal-content custom-modal shadow">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Alasan Penolakan</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="id_prestasi" value="<?php echo $r['id_prestasi']; ?>">
                                        <div class="mb-3 p-3 bg-light rounded border">
                                            <span class="d-block small text-muted">Siswa:</span>
                                            <span class="fw-bold text-navy"><?php echo $r['nama_siswa']; ?></span>
                                            <hr class="my-2">
                                            <span class="d-block small text-muted">Lomba:</span>
                                            <span class="fw-bold text-navy"><?php echo $r['nama_lomba']; ?></span>
                                        </div>
                                        <label class="form-label text-navy fw-semibold small">Tuliskan Alasan Penolakan:</label>
                                        <textarea name="alasan_tolak" class="form-control" rows="3" placeholder="Contoh: Sertifikat buram / Lomba tidak sesuai tingkat" required></textarea>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="button" class="btn btn-secondary fw-medium rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" name="tolak_data" class="btn btn-danger fw-medium rounded-pill px-4">Tolak & Simpan Alasan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <!-- TAB RESET PASSWORD -->
                <div class="tab-pane fade" id="tab-reset">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                        <h6 class="fw-bold m-0 text-navy">Daftar Permintaan Reset Password</h6>
                        <input type="text" id="searchReset" class="form-control" placeholder="Cari Username / NISN..." style="width: 250px; border-radius: 8px;">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover text-center align-middle custom-table">
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
                                $q_reset = mysqli_query($conn, "SELECT * FROM request_reset ORDER BY waktu_req DESC");
                                if (mysqli_num_rows($q_reset) > 0) {
                                    while ($r = mysqli_fetch_assoc($q_reset)) {
                                        $badge = match ($r['status_req']) {
                                            'Pending' => 'bg-danger',
                                            'Approved' => 'bg-warning text-dark',
                                            'Selesai' => 'bg-success',
                                            default => 'bg-secondary'
                                        };
                                        $kode = $r['kode_unik'] ? "<b style='letter-spacing:2px; font-size:16px; color:#002b5c;'>{$r['kode_unik']}</b>" : "<span class='text-muted'>-</span>";
                                ?>
                                        <tr class='reset-row'>
                                            <td class='text-muted small'><?php echo $r['waktu_req']; ?></td>
                                            <td class='fw-medium text-navy r-username'><?php echo $r['username']; ?></td>
                                            <td><span class='badge <?php echo $badge; ?> px-3 py-2 rounded-pill'><?php echo $r['status_req']; ?></span></td>
                                            <td><?php echo $kode; ?></td>
                                            <td>
                                                <?php if ($r['status_req'] == 'Pending') { ?>
                                                    <form method='POST' style='display:inline;'>
                                                        <input type='hidden' name='id_request' value='<?php echo $r['id_request']; ?>'>
                                                        <button type='submit' name='acc_request' class='btn btn-outline-dark btn-sm rounded-pill px-3 fw-medium'>ACC & Beri Kode</button>
                                                    </form>
                                                <?php } else {
                                                    echo "<span class='text-muted'>-</span>";
                                                } ?>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='py-4 text-muted fw-medium'>Tidak ada permintaan reset saat ini.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PENGATURAN AKUN ADMIN TU -->
    <div class="modal fade text-start" id="editProfilTU" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Pengaturan Akun TU</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="nip_tu" value="<?php echo $dt_profil['nip'] ?? ''; ?>">

                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">NIP (Nomor Induk Pegawai)</label>
                        <input type="text" class="form-control bg-light" value="<?php echo $dt_profil['nip'] ?? ''; ?>" readonly>
                        <div class="form-text small">*NIP dikunci oleh sistem sebagai identitas utama.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">Nama Lengkap</label>
                        <input type="text" name="nama_tu" class="form-control" value="<?php echo $dt_profil['nama_admin'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">Username Login</label>
                        <input type="text" name="user_tu" class="form-control" value="<?php echo $dt_profil['username'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-navy fw-semibold small">Password Akun</label>
                        <input type="text" name="pass_tu" class="form-control" value="<?php echo $dt_profil['PASSWORD'] ?? ''; ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" name="edit_profil_tu" class="btn btn-action w-100" style="background-color: #ffcc00; color:#002b5c; font-weight:bold; border-radius:8px; padding:10px;">Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('searchVerifikasi').addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.verifikasi-row').forEach(function(row) {
                let nama = row.querySelector('.v-nama').textContent.toLowerCase();
                let lomba = row.querySelector('.v-lomba').textContent.toLowerCase();
                row.style.display = (nama.includes(filter) || lomba.includes(filter)) ? '' : 'none';
            });
        });

        document.getElementById('searchReset').addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.reset-row').forEach(function(row) {
                let username = row.querySelector('.r-username').textContent.toLowerCase();
                row.style.display = (username.includes(filter)) ? '' : 'none';
            });
        });
    </script>
</body>

</html>