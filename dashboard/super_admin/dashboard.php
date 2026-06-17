<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Super Admin") {
    header("Location: ../../index.php");
    exit();
}

// ================= LOGIKA EDIT PROFIL SUPER ADMIN (DIRI SENDIRI) =================
if (isset($_POST['edit_profil_sa'])) {
    $id_sa = $_POST['id_sa'];
    $nama_sa = $_POST['nama_sa'];
    $user_sa = $_POST['user_sa'];
    $pass_sa = $_POST['pass_sa'];

    mysqli_query($conn, "UPDATE super_admin SET nama_super_admin='$nama_sa', username='$user_sa', PASSWORD='$pass_sa' WHERE id_super_admin='$id_sa'");

    // Update session biar nama di pojok kanan langsung berubah tanpa perlu relogin
    $_SESSION['username'] = $user_sa;
    $_SESSION['nama'] = $nama_sa;

    echo "<script>alert('Profil Super Admin berhasil diperbarui!'); window.location='dashboard.php';</script>";
    exit();
}

// ================= LOGIKA TAMBAH PENGGUNA =================
if (isset($_POST['tambah_admin'])) {
    $nip = $_POST['nip'];
    $nama = $_POST['nama_admin'];
    $user = $_POST['username'];
    mysqli_query($conn, "INSERT INTO admin_tu (nip, nama_admin, username, password) VALUES ('$nip', '$nama', '$user', '123')");
    header("Location: dashboard.php");
    exit();
}
if (isset($_POST['tambah_guru'])) {
    $nip = $_POST['nip'];
    $nama = $_POST['nama_guru'];
    mysqli_query($conn, "INSERT INTO guru (nip, nama_guru, password) VALUES ('$nip', '$nama', '123')");
    header("Location: dashboard.php");
    exit();
}
if (isset($_POST['tambah_siswa'])) {
    $nisn = $_POST['nisn'];
    $nama = $_POST['nama_siswa'];
    $kelas = $_POST['kelas'];
    mysqli_query($conn, "INSERT INTO siswa (nisn, nama_siswa, kelas, password) VALUES ('$nisn', '$nama', '$kelas', '123')");
    header("Location: dashboard.php");
    exit();
}

// ================= LOGIKA EDIT PENGGUNA =================
if (isset($_POST['edit_admin'])) {
    $nip_lama = $_POST['nip_lama'];
    $nip = $_POST['nip'];
    $nama = $_POST['nama_admin'];
    $user = $_POST['username'];
    mysqli_query($conn, "UPDATE admin_tu SET nip='$nip', nama_admin='$nama', username='$user' WHERE nip='$nip_lama'");
    header("Location: dashboard.php");
    exit();
}
if (isset($_POST['edit_guru'])) {
    $nip_lama = $_POST['nip_lama'];
    $nip = $_POST['nip'];
    $nama = $_POST['nama_guru'];
    mysqli_query($conn, "UPDATE guru SET nip='$nip', nama_guru='$nama' WHERE nip='$nip_lama'");
    header("Location: dashboard.php");
    exit();
}
if (isset($_POST['edit_siswa'])) {
    $nisn_lama = $_POST['nisn_lama'];
    $nisn = $_POST['nisn'];
    $nama = $_POST['nama_siswa'];
    $kelas = $_POST['kelas'];
    mysqli_query($conn, "UPDATE siswa SET nisn='$nisn', nama_siswa='$nama', kelas='$kelas' WHERE nisn='$nisn_lama'");
    header("Location: dashboard.php");
    exit();
}

// ================= LOGIKA HAPUS PENGGUNA =================
if (isset($_GET['hapus_admin'])) {
    $id = $_GET['hapus_admin'];
    mysqli_query($conn, "DELETE FROM admin_tu WHERE nip='$id'");
    header("Location: dashboard.php");
    exit();
}
if (isset($_GET['hapus_guru'])) {
    $id = $_GET['hapus_guru'];
    mysqli_query($conn, "DELETE FROM guru WHERE nip='$id'");
    header("Location: dashboard.php");
    exit();
}
if (isset($_GET['hapus_siswa'])) {
    $id = $_GET['hapus_siswa'];
    mysqli_query($conn, "DELETE FROM siswa WHERE nisn='$id'");
    header("Location: dashboard.php");
    exit();
}

// Ambil data super admin yang lagi login buat di modal profil
$user_aktif = $_SESSION['username'] ?? '';
$q_sa = mysqli_query($conn, "SELECT * FROM super_admin WHERE username='$user_aktif'");
$dt_sa = mysqli_fetch_assoc($q_sa);
// Jika session nama kosong, ambil dari database
$nama_tampil = $_SESSION['nama'] ?? ($dt_sa['nama_super_admin'] ?? 'Master Admin');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - Trophile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/super_admin_dashboard.css?v=<?php echo time(); ?>">
</head>

<body>
    <nav class="navbar-custom">
        <div class="nav-container">
            <div class="brand-wrapper d-flex align-items-center gap-2">
                <img src="../../assets/images/SMANSA.png" alt="Logo" width="35">
                <a href="#" class="brand-logo">TROPHILE</a>
            </div>

            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" style="border: 1px solid rgba(255,255,255,0.3); padding: 6px 15px; border-radius: 8px;">
                    <span class="me-2 fw-medium d-none d-md-block">Halo, <?php echo $nama_tampil; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; border:none; margin-top:10px;">
                    <li>
                        <h6 class="dropdown-header text-muted">Super Admin Menu</h6>
                    </li>
                    <li><a class="dropdown-item fw-medium" href="#" data-bs-toggle="modal" data-bs-target="#editProfilSA">⚙️ Pengaturan Akun</a></li>
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
            <div class="header-title">Manajemen Data Pengguna</div>

            <ul class="nav nav-pills custom-pills mb-4" id="pills-tab" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-admin">Admin TU</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-guru">Guru</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-siswa">Siswa</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-admin">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                        <h6 class="fw-bold m-0 text-navy" style="font-size: 1.1rem;">Data Petugas TU</h6>
                        <div class="d-flex gap-2">
                            <input type="text" id="searchAdmin" class="form-control search-input" placeholder="Cari NIP / Nama Admin..." style="width: 280px;">
                            <button class="btn btn-action text-nowrap" data-bs-toggle="modal" data-bs-target="#addAdmin">+ Tambah Admin</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover text-center align-middle custom-table">
                            <thead>
                                <tr>
                                    <th width="15%">NIP</th>
                                    <th width="35%">NAMA PETUGAS</th>
                                    <th width="25%">USERNAME</th>
                                    <th width="25%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q = mysqli_query($conn, "SELECT * FROM admin_tu");
                                $data_admins = [];
                                while ($r = mysqli_fetch_assoc($q)) {
                                    $data_admins[] = $r;
                                }

                                foreach ($data_admins as $r) {
                                ?>
                                    <tr class="admin-row">
                                        <td class="admin-nip"><span class='badge bg-light text-dark border'><?php echo $r['nip']; ?></span></td>
                                        <td class='fw-medium admin-name'><?php echo $r['nama_admin']; ?></td>
                                        <td><?php echo $r['username']; ?></td>
                                        <td>
                                            <button class='btn btn-outline-primary btn-sm rounded-pill px-3 me-1' data-bs-toggle='modal' data-bs-target='#editAdmin<?php echo $r['nip']; ?>'>Edit</button>
                                            <a href='?hapus_admin=<?php echo $r['nip']; ?>' class='btn btn-outline-danger btn-sm rounded-pill px-3' onclick='return confirm("Yakin ingin menghapus admin ini?")'>Hapus</a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <?php foreach ($data_admins as $r) { ?>
                        <div class="modal fade text-start" id="editAdmin<?php echo $r['nip']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="POST" class="modal-content custom-modal shadow">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Edit Admin TU</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="nip_lama" value="<?php echo $r['nip']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label text-navy fw-semibold small">NIP</label>
                                            <input type="text" name="nip" class="form-control" value="<?php echo $r['nip']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-navy fw-semibold small">Nama Lengkap</label>
                                            <input type="text" name="nama_admin" class="form-control" value="<?php echo $r['nama_admin']; ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label text-navy fw-semibold small">Username</label>
                                            <input type="text" name="username" class="form-control" value="<?php echo $r['username']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="submit" name="edit_admin" class="btn btn-action w-100">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="tab-pane fade" id="tab-guru">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                        <h6 class="fw-bold m-0 text-navy" style="font-size: 1.1rem;">Data Guru Pembina</h6>
                        <div class="d-flex gap-2">
                            <input type="text" id="searchGuru" class="form-control search-input" placeholder="Cari NIP / Nama Guru..." style="width: 280px;">
                            <button class="btn btn-action text-nowrap" data-bs-toggle="modal" data-bs-target="#addGuru">+ Tambah Guru</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover text-center align-middle custom-table">
                            <thead>
                                <tr>
                                    <th width="20%">NIP</th>
                                    <th width="50%">NAMA GURU</th>
                                    <th width="30%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q = mysqli_query($conn, "SELECT * FROM guru");
                                $data_gurus = [];
                                while ($r = mysqli_fetch_assoc($q)) {
                                    $data_gurus[] = $r;
                                }

                                foreach ($data_gurus as $r) {
                                ?>
                                    <tr class="guru-row">
                                        <td class="guru-nip"><span class='badge bg-light text-dark border'><?php echo $r['nip']; ?></span></td>
                                        <td class='fw-medium guru-name'><?php echo $r['nama_guru']; ?></td>
                                        <td>
                                            <button class='btn btn-outline-primary btn-sm rounded-pill px-3 me-1' data-bs-toggle='modal' data-bs-target='#editGuru<?php echo $r['nip']; ?>'>Edit</button>
                                            <a href='?hapus_guru=<?php echo $r['nip']; ?>' class='btn btn-outline-danger btn-sm rounded-pill px-3' onclick='return confirm("Yakin ingin menghapus guru ini?")'>Hapus</a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <?php foreach ($data_gurus as $r) { ?>
                        <div class="modal fade text-start" id="editGuru<?php echo $r['nip']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="POST" class="modal-content custom-modal shadow">
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Edit Guru Pembina</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="nip_lama" value="<?php echo $r['nip']; ?>">
                                        <div class="mb-3">
                                            <label class="form-label text-navy fw-semibold small">NIP</label>
                                            <input type="text" name="nip" class="form-control" value="<?php echo $r['nip']; ?>" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label text-navy fw-semibold small">Nama Guru</label>
                                            <input type="text" name="nama_guru" class="form-control" value="<?php echo $r['nama_guru']; ?>" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-0 pt-0">
                                        <button type="submit" name="edit_guru" class="btn btn-action w-100">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="tab-pane fade" id="tab-siswa">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                        <h6 class="fw-bold m-0 text-navy" style="font-size: 1.1rem;">Data Siswa SMAN 1 Kesamben</h6>
                        <div class="d-flex gap-2">
                            <input type="text" id="searchSiswa" class="form-control search-input" placeholder="Cari NISN / Nama Siswa..." style="width: 280px;">
                            <button class="btn btn-action text-nowrap" data-bs-toggle="modal" data-bs-target="#addSiswa">+ Tambah Siswa</button>
                        </div>
                    </div>

                    <div class="accordion" id="accordionSiswa">
                        <?php
                        $q_siswa = mysqli_query($conn, "SELECT * FROM siswa ORDER BY kelas, nama_siswa");
                        $data_siswa = [];
                        while ($row = mysqli_fetch_assoc($q_siswa)) {
                            $kls = $row['kelas'] ?: 'Tanpa Kelas';
                            $data_siswa[$kls][] = $row;
                        }

                        foreach ($data_siswa as $kelas => $siswas) {
                            $md5_kelas = md5($kelas);
                        ?>
                            <div class="accordion-item mb-3 border-0 shadow-sm rounded student-accordion-item">
                                <h2 class="accordion-header" id="heading<?php echo $md5_kelas; ?>">
                                    <button class="accordion-button collapsed fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $md5_kelas; ?>" aria-expanded="false" aria-controls="collapse<?php echo $md5_kelas; ?>">
                                        Kelas <?php echo $kelas; ?>
                                        <span class="badge bg-navy ms-3 px-2 rounded-pill"><?php echo count($siswas); ?> Siswa</span>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $md5_kelas; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $md5_kelas; ?>" data-bs-parent="#accordionSiswa">
                                    <div class="accordion-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover text-center align-middle custom-table mb-0 student-table">
                                                <thead>
                                                    <tr>
                                                        <th width="15%">NISN</th>
                                                        <th width="50%">NAMA LENGKAP</th>
                                                        <th width="35%">AKSI</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($siswas as $r) { ?>
                                                        <tr class="student-row">
                                                            <td class="student-nisn"><span class='badge bg-light text-dark border'><?php echo $r['nisn']; ?></span></td>
                                                            <td class='fw-medium text-start ps-4 student-name'><?php echo $r['nama_siswa']; ?></td>
                                                            <td>
                                                                <button class='btn btn-outline-primary btn-sm rounded-pill px-3 me-1' data-bs-toggle='modal' data-bs-target='#editSiswa<?php echo $r['nisn']; ?>'>Edit</button>
                                                                <a href='?hapus_siswa=<?php echo $r['nisn']; ?>' class='btn btn-outline-danger btn-sm rounded-pill px-3' onclick='return confirm("Yakin ingin menghapus siswa ini?")'>Hapus</a>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php foreach ($siswas as $r) { ?>
                                <div class="modal fade text-start" id="editSiswa<?php echo $r['nisn']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <form method="POST" class="modal-content custom-modal shadow">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Edit Siswa</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="nisn_lama" value="<?php echo $r['nisn']; ?>">
                                                <div class="mb-3">
                                                    <label class="form-label text-navy fw-semibold small">NISN</label>
                                                    <input type="text" name="nisn" class="form-control" value="<?php echo $r['nisn']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label text-navy fw-semibold small">Nama Lengkap</label>
                                                    <input type="text" name="nama_siswa" class="form-control" value="<?php echo $r['nama_siswa']; ?>" required>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label text-navy fw-semibold small">Kelas</label>
                                                    <input type="text" name="kelas" class="form-control" value="<?php echo $r['kelas']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pt-0">
                                                <button type="submit" name="edit_siswa" class="btn btn-action w-100">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php } ?>

                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade text-start" id="editProfilSA" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Pengaturan Akun</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_sa" value="<?php echo $dt_sa['id_super_admin'] ?? ''; ?>">
                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">Nama Tampilan</label>
                        <input type="text" name="nama_sa" class="form-control" value="<?php echo $dt_sa['nama_super_admin'] ?? ''; ?>" required>
                        <div class="form-text small">*Nama ini akan muncul di pojok kanan atas</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">Username Login</label>
                        <input type="text" name="user_sa" class="form-control" value="<?php echo $dt_sa['username'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-navy fw-semibold small">Password Akun</label>
                        <input type="text" name="pass_sa" class="form-control" value="<?php echo $dt_sa['PASSWORD'] ?? ''; ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" name="edit_profil_sa" class="btn btn-action w-100">Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="addAdmin" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Admin TU</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">NIP</label>
                        <input type="text" name="nip" class="form-control" placeholder="Masukkan NIP" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">Nama Lengkap</label>
                        <input type="text" name="nama_admin" class="form-control" placeholder="Masukkan Nama Admin" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-navy fw-semibold small">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="Buat Username" required>
                    </div>
                    <div class="form-text text-danger mt-3 small">*Password default akun baru adalah: <b>123</b></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" name="tambah_admin" class="btn btn-action w-100">Simpan Data Admin</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="addGuru" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Guru Pembina</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">NIP</label>
                        <input type="text" name="nip" class="form-control" placeholder="Masukkan NIP" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-navy fw-semibold small">Nama Guru</label>
                        <input type="text" name="nama_guru" class="form-control" placeholder="Masukkan Nama Lengkap beserta Gelar" required>
                    </div>
                    <div class="form-text text-danger mt-3 small">*Password default akun baru adalah: <b>123</b></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" name="tambah_guru" class="btn btn-action w-100">Simpan Data Guru</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="addSiswa" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Siswa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">NISN</label>
                        <input type="text" name="nisn" class="form-control" placeholder="Masukkan NISN Siswa" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">Nama Lengkap</label>
                        <input type="text" name="nama_siswa" class="form-control" placeholder="Masukkan Nama Siswa" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-navy fw-semibold small">Kelas</label>
                        <input type="text" name="kelas" class="form-control" placeholder="Contoh: XII MIPA 1" required>
                    </div>
                    <div class="form-text text-danger mt-3 small">*Password default akun baru adalah: <b>123</b></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" name="tambah_siswa" class="btn btn-action w-100">Simpan Data Siswa</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // 1. Pencarian Admin TU
        document.getElementById('searchAdmin').addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.admin-row');

            rows.forEach(function(row) {
                let nip = row.querySelector('.admin-nip').textContent.toLowerCase();
                let name = row.querySelector('.admin-name').textContent.toLowerCase();

                if (nip.includes(filter) || name.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // 2. Pencarian Guru
        document.getElementById('searchGuru').addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('.guru-row');

            rows.forEach(function(row) {
                let nip = row.querySelector('.guru-nip').textContent.toLowerCase();
                let name = row.querySelector('.guru-name').textContent.toLowerCase();

                if (nip.includes(filter) || name.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // 3. Pencarian Siswa (Accordion Logic)
        document.getElementById('searchSiswa').addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            let accordionItems = document.querySelectorAll('.student-accordion-item');

            accordionItems.forEach(function(item) {
                let rows = item.querySelectorAll('.student-row');
                let hasVisibleRow = false;

                rows.forEach(function(row) {
                    let nisn = row.querySelector('.student-nisn').textContent.toLowerCase();
                    let name = row.querySelector('.student-name').textContent.toLowerCase();

                    if (nisn.includes(filter) || name.includes(filter)) {
                        row.style.display = '';
                        hasVisibleRow = true;
                    } else {
                        row.style.display = 'none';
                    }
                });

                let collapseDiv = item.querySelector('.accordion-collapse');
                let buttonDiv = item.querySelector('.accordion-button');

                if (filter !== '') {
                    if (hasVisibleRow) {
                        item.style.display = '';
                        collapseDiv.classList.add('show');
                        buttonDiv.classList.remove('collapsed');
                    } else {
                        item.style.display = 'none';
                        collapseDiv.classList.remove('show');
                        buttonDiv.classList.add('collapsed');
                    }
                } else {
                    item.style.display = '';
                    collapseDiv.classList.remove('show');
                    buttonDiv.classList.add('collapsed');
                }
            });
        });
    </script>
</body>

</html>