<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Super Admin") {
    header("Location: ../../index.php");
    exit();
}

$pass_default_hash = password_hash('123', PASSWORD_DEFAULT);

if (isset($_POST['edit_profil_sa'])) {
    $id_sa   = mysqli_real_escape_string($conn, $_POST['id_sa']);
    $nama_sa = mysqli_real_escape_string($conn, $_POST['nama_sa']);
    $user_sa = mysqli_real_escape_string($conn, $_POST['user_sa']);
    $pass_sa = trim($_POST['pass_sa']);

    $pass_sa_hash = password_hash($pass_sa, PASSWORD_DEFAULT);
    mysqli_query($conn, "UPDATE super_admin SET nama_super_admin='$nama_sa', username='$user_sa', password='$pass_sa_hash' WHERE id_super_admin='$id_sa'");

    $_SESSION['username'] = $user_sa;
    $_SESSION['nama'] = $nama_sa;

    echo "<script>alert('Profil Super Admin berhasil diperbarui!'); window.location='dashboard.php';</script>";
    exit();
}

if (isset($_POST['tambah_admin'])) {
    $nip  = mysqli_real_escape_string($conn, $_POST['nip']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_admin']);
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    mysqli_query($conn, "INSERT INTO admin_tu (nip, nama_admin, username, password, status) VALUES ('$nip', '$nama', '$user', '$pass_default_hash', 'Aktif')");
    header("Location: dashboard.php");
    exit();
}
if (isset($_POST['tambah_guru'])) {
    $nip  = mysqli_real_escape_string($conn, $_POST['nip']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_guru']);
    mysqli_query($conn, "INSERT INTO guru (nip, nama_guru, password, status) VALUES ('$nip', '$nama', '$pass_default_hash', 'Aktif')");
    header("Location: dashboard.php");
    exit();
}
if (isset($_POST['tambah_siswa'])) {
    $nisn  = mysqli_real_escape_string($conn, $_POST['nisn']);
    $nama  = mysqli_real_escape_string($conn, $_POST['nama_siswa']);
    $kelas = mysqli_real_escape_string($conn, $_POST['kelas']);
    mysqli_query($conn, "INSERT INTO siswa (nisn, nama_siswa, kelas, password, status) VALUES ('$nisn', '$nama', '$kelas', '$pass_default_hash', 'Aktif')");
    header("Location: dashboard.php");
    exit();
}
if (isset($_POST['tambah_kepsek'])) {
    $nip  = mysqli_real_escape_string($conn, $_POST['nip']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama_kepsek']);
    $user = mysqli_real_escape_string($conn, $_POST['username']);
    mysqli_query($conn, "UPDATE kepala_sekolah SET status='Non-Aktif'");
    mysqli_query($conn, "INSERT INTO kepala_sekolah (nip, nama_kepala_sekolah, username, password, status) VALUES ('$nip', '$nama', '$user', '$pass_default_hash', 'Aktif')");
    header("Location: dashboard.php");
    exit();
}

if (isset($_POST['edit_kepsek'])) {
    $nip_lama = mysqli_real_escape_string($conn, $_POST['nip_lama']);
    $nama     = mysqli_real_escape_string($conn, $_POST['nama_kepsek']);
    $user     = mysqli_real_escape_string($conn, $_POST['username']);
    mysqli_query($conn, "UPDATE kepala_sekolah SET nama_kepala_sekolah='$nama', username='$user' WHERE nip='$nip_lama'");
    header("Location: dashboard.php");
    exit();
}
if (isset($_POST['edit_admin'])) {
    $nip_lama = mysqli_real_escape_string($conn, $_POST['nip_lama']);
    $nama     = mysqli_real_escape_string($conn, $_POST['nama_admin']);
    $user     = mysqli_real_escape_string($conn, $_POST['username']);
    mysqli_query($conn, "UPDATE admin_tu SET nama_admin='$nama', username='$user' WHERE nip='$nip_lama'");
    header("Location: dashboard.php");
    exit();
}
if (isset($_POST['edit_guru'])) {
    $nip_lama = mysqli_real_escape_string($conn, $_POST['nip_lama']);
    $nama     = mysqli_real_escape_string($conn, $_POST['nama_guru']);
    mysqli_query($conn, "UPDATE guru SET nama_guru='$nama' WHERE nip='$nip_lama'");
    header("Location: dashboard.php");
    exit();
}
if (isset($_POST['edit_siswa'])) {
    $nisn_lama = mysqli_real_escape_string($conn, $_POST['nisn_lama']);
    $nama      = mysqli_real_escape_string($conn, $_POST['nama_siswa']);
    $kelas     = mysqli_real_escape_string($conn, $_POST['kelas']);
    mysqli_query($conn, "UPDATE siswa SET nama_siswa='$nama', kelas='$kelas' WHERE nisn='$nisn_lama'");
    header("Location: dashboard.php");
    exit();
}

if (isset($_POST['kenaikan_massal'])) {
    $kelas_lama = mysqli_real_escape_string($conn, $_POST['kelas_lama']);
    $kelas_baru = mysqli_real_escape_string($conn, $_POST['kelas_baru']);
    $status_baru = mysqli_real_escape_string($conn, $_POST['status_baru']);

    if ($status_baru == 'Lulus') {
        mysqli_query($conn, "UPDATE siswa SET status='Lulus' WHERE kelas='$kelas_lama' AND status='Aktif'");
    } else {
        mysqli_query($conn, "UPDATE siswa SET kelas='$kelas_baru' WHERE kelas='$kelas_lama' AND status='Aktif'");
    }
    echo "<script>alert('Update kelas massal berhasil!'); window.location='dashboard.php';</script>";
    exit();
}

if (isset($_GET['nonaktif_admin'])) {
    $id = mysqli_real_escape_string($conn, $_GET['nonaktif_admin']);
    mysqli_query($conn, "UPDATE admin_tu SET status='Non-Aktif' WHERE nip='$id'");
    header("Location: dashboard.php");
    exit();
}
if (isset($_GET['nonaktif_guru'])) {
    $id = mysqli_real_escape_string($conn, $_GET['nonaktif_guru']);
    mysqli_query($conn, "UPDATE guru SET status='Pensiun' WHERE nip='$id'");
    header("Location: dashboard.php");
    exit();
}
if (isset($_GET['nonaktif_siswa'])) {
    $id = mysqli_real_escape_string($conn, $_GET['nonaktif_siswa']);
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    mysqli_query($conn, "UPDATE siswa SET status='$status' WHERE nisn='$id'");
    header("Location: dashboard.php");
    exit();
}
if (isset($_GET['set_aktif_kepsek'])) {
    $id = mysqli_real_escape_string($conn, $_GET['set_aktif_kepsek']);
    mysqli_query($conn, "UPDATE kepala_sekolah SET status='Non-Aktif'");
    mysqli_query($conn, "UPDATE kepala_sekolah SET status='Aktif' WHERE nip='$id'");
    header("Location: dashboard.php");
    exit();
}

$user_aktif = $_SESSION['username'] ?? '';
$q_sa = mysqli_query($conn, "SELECT * FROM super_admin WHERE username='$user_aktif'");
$dt_sa = mysqli_fetch_assoc($q_sa);
$nama_tampil = $_SESSION['nama'] ?? ($dt_sa['nama_super_admin'] ?? 'Master Admin');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - Trophile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/super_admin_dashboard.css?v=<?php echo time(); ?>">
</head>

<body>
    <nav class="navbar-custom">
        <div class="nav-container">
            <div class="brand-wrapper d-flex align-items-center gap-2">
                <img src="../../assets/images/SMANSA.png" alt="Logo" width="35">
                <a href="#" class="brand-logo d-none d-md-block">TROPHILE</a>
            </div>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" style="border: 1px solid rgba(255,255,255,0.3); padding: 6px 15px; border-radius: 8px;">
                    <span class="me-2 fw-medium">Halo, <?php echo $nama_tampil; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; border:none; margin-top:10px;">
                    <li>
                        <h6 class="dropdown-header text-muted">Super Admin Menu</h6>
                    </li>
                    <li><a class="dropdown-item fw-medium" href="#" data-bs-toggle="modal" data-bs-target="#editProfilSA">Pengaturan Akun</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger fw-bold" href="../../logout.php">Keluar</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="floating-card">
            <div class="header-title">Manajemen Data Pengguna</div>

            <ul class="nav nav-pills custom-pills mb-4" id="pills-tab" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-admin" type="button" role="tab">Admin TU</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-guru" type="button" role="tab">Guru</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-siswa" type="button" role="tab">Siswa</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-kepsek" type="button" role="tab">Kepala Sekolah</button></li>
            </ul>

            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active" id="tab-admin" role="tabpanel">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
                        <h6 class="fw-bold m-0 text-navy" style="font-size: 1.1rem;">Data Petugas TU</h6>
                        <div class="d-flex action-wrapper gap-2 ms-md-auto">
                            <input type="text" id="searchAdmin" class="form-control search-input" placeholder="Cari NIP / Nama..." style="min-width: 280px;">
                            <button class="btn btn-action text-nowrap" data-bs-toggle="modal" data-bs-target="#addAdmin">+ Tambah Admin</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover text-center align-middle custom-table">
                            <thead>
                                <tr>
                                    <th width="20%">NIP</th>
                                    <th width="50%">NAMA PETUGAS</th>
                                    <th width="30%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q = mysqli_query($conn, "SELECT * FROM admin_tu ORDER BY status ASC");
                                $data_admins = [];
                                while ($r = mysqli_fetch_assoc($q)) {
                                    $data_admins[] = $r;
                                }
                                foreach ($data_admins as $r) {
                                    $status_badge = ($r['status'] != 'Aktif') ? "<span class='badge bg-secondary text-white ms-2'>" . $r['status'] . "</span>" : "";
                                ?>
                                    <tr class="admin-row">
                                        <td class="admin-nip"><span class='badge bg-light text-dark border'><?php echo $r['nip']; ?></span></td>
                                        <td class='fw-medium text-start ps-3 admin-name'><?php echo $r['nama_admin'] . $status_badge; ?></td>
                                        <td class="col-aksi">
                                            <button class='btn btn-outline-primary btn-sm rounded-pill px-3' data-bs-toggle='modal' data-bs-target='#editAdmin<?php echo $r['nip']; ?>'>Edit</button>
                                            <?php if ($r['status'] == 'Aktif') { ?>
                                                <a href='?nonaktif_admin=<?php echo $r['nip']; ?>' class='btn btn-outline-danger btn-sm rounded-pill px-3' onclick='return confirm("Non-Aktifkan Admin TU ini?")'>Non-Aktifkan</a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-guru" role="tabpanel">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
                        <h6 class="fw-bold m-0 text-navy" style="font-size: 1.1rem;">Data Guru Pembina</h6>
                        <div class="d-flex action-wrapper gap-2 ms-md-auto">
                            <input type="text" id="searchGuru" class="form-control search-input" placeholder="Cari NIP / Nama..." style="min-width: 280px;">
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
                                $q = mysqli_query($conn, "SELECT * FROM guru ORDER BY status ASC, nama_guru ASC");
                                $data_gurus = [];
                                while ($r = mysqli_fetch_assoc($q)) {
                                    $data_gurus[] = $r;
                                }
                                foreach ($data_gurus as $r) {
                                    $status_badge = ($r['status'] != 'Aktif') ? "<span class='badge bg-secondary text-white ms-2'>" . $r['status'] . "</span>" : "";
                                ?>
                                    <tr class="guru-row">
                                        <td class="guru-nip"><span class='badge bg-light text-dark border'><?php echo $r['nip']; ?></span></td>
                                        <td class='fw-medium text-start ps-3 guru-name'><?php echo $r['nama_guru'] . $status_badge; ?></td>
                                        <td class="col-aksi">
                                            <button class='btn btn-outline-primary btn-sm rounded-pill px-3' data-bs-toggle='modal' data-bs-target='#editGuru<?php echo $r['nip']; ?>'>Edit</button>
                                            <?php if ($r['status'] == 'Aktif') { ?>
                                                <a href='?nonaktif_guru=<?php echo $r['nip']; ?>' class='btn btn-outline-danger btn-sm rounded-pill px-3' onclick='return confirm("Arsipkan guru ini menjadi Pensiun/Pindah?")'>Non-Aktifkan</a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-siswa" role="tabpanel">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
                        <h6 class="fw-bold m-0 text-navy" style="font-size: 1.1rem;">Data Siswa</h6>
                        <div class="d-flex action-wrapper gap-2 ms-md-auto">
                            <button class="btn btn-warning fw-bold text-nowrap" data-bs-toggle="modal" data-bs-target="#kenaikanMassal">🚀 Kelola Kenaikan Kelas</button>
                            <input type="text" id="searchSiswa" class="form-control search-input" placeholder="Cari NISN / Nama..." style="min-width: 200px;">
                            <button class="btn btn-action text-nowrap" data-bs-toggle="modal" data-bs-target="#addSiswa">+ Tambah Siswa</button>
                        </div>
                    </div>
                    <div class="accordion" id="accordionSiswa">
                        <?php
                        $q_siswa = mysqli_query($conn, "SELECT * FROM siswa ORDER BY kelas, nama_siswa");
                        $data_siswa = [];
                        $list_kelas_unik = [];
                        while ($row = mysqli_fetch_assoc($q_siswa)) {
                            $kls = $row['kelas'] ?: 'Tanpa Kelas';
                            $data_siswa[$kls][] = $row;
                            if (!in_array($kls, $list_kelas_unik) && $kls != 'Tanpa Kelas') $list_kelas_unik[] = $kls;
                        }
                        foreach ($data_siswa as $kelas => $siswas) {
                            $md5_kelas = md5($kelas);
                        ?>
                            <div class="accordion-item mb-3 border-0 shadow-sm rounded student-accordion-item">
                                <h2 class="accordion-header" id="heading<?php echo $md5_kelas; ?>">
                                    <button class="accordion-button collapsed fw-bold rounded" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $md5_kelas; ?>">
                                        Kelas <?php echo htmlspecialchars($kelas); ?>
                                        <span class="badge bg-navy ms-3 px-2 rounded-pill"><?php echo count($siswas); ?> Siswa</span>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $md5_kelas; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionSiswa">
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
                                                    <?php foreach ($siswas as $r) {
                                                        $badge_color = 'bg-secondary';
                                                        if ($r['status'] == 'Lulus') $badge_color = 'bg-success';
                                                        if ($r['status'] == 'Pindah' || $r['status'] == 'Keluar') $badge_color = 'bg-danger';
                                                        $status_badge = ($r['status'] != 'Aktif') ? "<span class='badge $badge_color ms-2'>" . $r['status'] . "</span>" : "";
                                                    ?>
                                                        <tr class="student-row">
                                                            <td class="student-nisn"><span class='badge bg-light text-dark border'><?php echo $r['nisn']; ?></span></td>
                                                            <td class='fw-medium text-start ps-4 student-name'><?php echo $r['nama_siswa'] . $status_badge; ?></td>
                                                            <td class="col-aksi">
                                                                <button class='btn btn-outline-primary btn-sm rounded-pill px-3' data-bs-toggle='modal' data-bs-target='#editSiswa<?php echo $r['nisn']; ?>'>Edit</button>
                                                                <?php if ($r['status'] == 'Aktif') { ?>
                                                                    <div class="btn-group">
                                                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-3 dropdown-toggle" data-bs-toggle="dropdown">Non-Aktifkan</button>
                                                                        <ul class="dropdown-menu">
                                                                            <li><a class="dropdown-item" href="?nonaktif_siswa=<?php echo $r['nisn']; ?>&status=Lulus" onclick='return confirm("Set status Lulus?")'>Lulus</a></li>
                                                                            <li><a class="dropdown-item" href="?nonaktif_siswa=<?php echo $r['nisn']; ?>&status=Pindah" onclick='return confirm("Set status Pindah Sekolah?")'>Pindah</a></li>
                                                                            <li><a class="dropdown-item" href="?nonaktif_siswa=<?php echo $r['nisn']; ?>&status=Keluar" onclick='return confirm("Set status Dikeluarkan?")'>Keluar</a></li>
                                                                        </ul>
                                                                    </div>
                                                                <?php } ?>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-kepsek" role="tabpanel">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 gap-3">
                        <h6 class="fw-bold m-0 text-navy" style="font-size: 1.1rem;">Data Kepala Sekolah & Arsip</h6>
                        <div class="d-flex action-wrapper gap-2 ms-md-auto">
                            <button class="btn btn-action text-nowrap" data-bs-toggle="modal" data-bs-target="#addKepsek">+ Tambah Kepala Sekolah</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover text-center align-middle custom-table">
                            <thead>
                                <tr>
                                    <th width="15%">NIP</th>
                                    <th width="35%">NAMA KEPALA SEKOLAH</th>
                                    <th width="15%">STATUS</th>
                                    <th width="35%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $q = mysqli_query($conn, "SELECT * FROM kepala_sekolah ORDER BY status ASC");
                                while ($k = mysqli_fetch_assoc($q)) {
                                    $badge_status = ($k['status'] == 'Aktif') ? "<span class='badge bg-success px-3 py-2'>Aktif</span>" : "<span class='badge bg-secondary px-3 py-2'>Non-Aktif</span>";
                                ?>
                                    <tr>
                                        <td><span class='badge bg-light text-dark border'><?php echo $k['nip']; ?></span></td>
                                        <td class='fw-medium text-start ps-3'><?php echo $k['nama_kepala_sekolah']; ?></td>
                                        <td><?php echo $badge_status; ?></td>
                                        <td class="col-aksi">
                                            <button class='btn btn-outline-primary btn-sm rounded-pill px-3 m-1' data-bs-toggle='modal' data-bs-target='#editKepsek<?php echo $k['nip']; ?>'>Edit</button>
                                            <?php if ($k['status'] != 'Aktif') { ?>
                                                <a href='?set_aktif_kepsek=<?php echo $k['nip']; ?>' class='btn btn-outline-success btn-sm rounded-pill px-3 m-1' onclick='return confirm("Jadikan akun ini sebagai Kepsek Aktif? Kepsek sebelumnya akan otomatis Non-Aktif.")'>Set Aktif</a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="kenaikanMassal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">🚀 Kenaikan Kelas / Kelulusan Massal</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info border-0 small mb-3">Fitur ini akan memindahkan <b>seluruh siswa aktif</b> di kelas asal ke kelas tujuan atau mengubah status mereka menjadi lulus secara bersamaan.</div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-danger">Pilih Kelas Asal</label>
                        <select name="kelas_lama" class="form-select" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($list_kelas_unik as $lk) {
                                echo "<option value='$lk'>$lk</option>";
                            } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Aksi / Tujuan</label>
                        <select name="status_baru" class="form-select" id="aksiKelasMassal" onchange="toggleKelasBaru()" required>
                            <option value="Aktif">Naik Kelas / Pindah Kelas</option>
                            <option value="Lulus">Lulus (Jadikan Alumni)</option>
                        </select>
                    </div>
                    <div class="mb-2" id="inputKelasBaru">
                        <label class="form-label fw-bold">Nama Kelas Baru</label>
                        <input type="text" name="kelas_baru" class="form-control" placeholder="Contoh: XI IPA 1">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0"><button type="submit" name="kenaikan_massal" class="btn btn-warning w-100 fw-bold" onclick="return confirm('Anda yakin melakukan update massal ini?')">Proses Massal Sekarang</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade text-start" id="editProfilSA" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Pengaturan Akun</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_sa" value="<?php echo $dt_sa['id_super_admin'] ?? ''; ?>">
                    <div class="mb-3"><label class="form-label">Nama Tampilan</label><input type="text" name="nama_sa" class="form-control" value="<?php echo $dt_sa['nama_super_admin'] ?? ''; ?>" required></div>
                    <div class="mb-3"><label class="form-label">Username Login</label><input type="text" name="user_sa" class="form-control" value="<?php echo $dt_sa['username'] ?? ''; ?>" required></div>
                    <div class="mb-2"><label class="form-label">Password Akun Baru</label><input type="password" name="pass_sa" class="form-control" placeholder="Wajib diisi password baru..." required></div>
                </div>
                <div class="modal-footer border-0 pt-0"><button type="submit" name="edit_profil_sa" class="btn btn-action w-100">Simpan Pengaturan</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="addAdmin" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Admin TU</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">NIP</label><input type="text" name="nip" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" name="nama_admin" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
                    <div class="form-text text-danger mt-3 small">*Password default akun baru otomatis di-hash dari: <b>123</b></div>
                </div>
                <div class="modal-footer border-0 pt-0"><button type="submit" name="tambah_admin" class="btn btn-action w-100">Simpan</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="addGuru" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Guru</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">NIP</label><input type="text" name="nip" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Nama Guru</label><input type="text" name="nama_guru" class="form-control" required></div>
                    <div class="form-text text-danger mt-3 small">*Password default akun baru otomatis di-hash dari: <b>123</b></div>
                </div>
                <div class="modal-footer border-0 pt-0"><button type="submit" name="tambah_guru" class="btn btn-action w-100">Simpan</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="addSiswa" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Siswa</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">NISN</label><input type="text" name="nisn" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" name="nama_siswa" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Kelas</label><input type="text" name="kelas" class="form-control" required></div>
                    <div class="form-text text-danger mt-3 small">*Password default akun baru otomatis di-hash dari: <b>123</b></div>
                </div>
                <div class="modal-footer border-0 pt-0"><button type="submit" name="tambah_siswa" class="btn btn-action w-100">Simpan</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="addKepsek" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Kepala Sekolah</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small border-0 mb-3">Sistem akan otomatis me-<b>Non-Aktifkan</b> Kepsek lama jika data baru disimpan.</div>
                    <div class="mb-3"><label class="form-label">NIP</label><input type="text" name="nip" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" name="nama_kepsek" class="form-control" required></div>
                    <div class="mb-2"><label class="form-label">Username Login</label><input type="text" name="username" class="form-control" required></div>
                    <div class="form-text text-danger mt-3 small">*Password default akun baru otomatis di-hash dari: <b>123</b></div>
                </div>
                <div class="modal-footer border-0 pt-0"><button type="submit" name="tambah_kepsek" class="btn btn-action w-100">Simpan Kepsek Baru</button></div>
            </form>
        </div>
    </div>

    <?php foreach ($data_admins as $r) { ?>
        <div class="modal fade text-start" id="editAdmin<?php echo $r['nip']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content custom-modal shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Edit Admin TU</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="nip_lama" value="<?php echo $r['nip']; ?>">
                        <div class="mb-3"><label class="form-label">NIP</label><input type="text" class="form-control bg-light" value="<?php echo $r['nip']; ?>" readonly></div>
                        <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" name="nama_admin" class="form-control" value="<?php echo $r['nama_admin']; ?>" required></div>
                        <div class="mb-2"><label class="form-label">Username</label><input type="text" name="username" class="form-control" value="<?php echo $r['username']; ?>" required></div>
                    </div>
                    <div class="modal-footer border-0 pt-0"><button type="submit" name="edit_admin" class="btn btn-action w-100">Simpan Perubahan</button></div>
                </form>
            </div>
        </div>
    <?php } ?>

    <?php foreach ($data_gurus as $r) { ?>
        <div class="modal fade text-start" id="editGuru<?php echo $r['nip']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content custom-modal shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Edit Guru</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="nip_lama" value="<?php echo $r['nip']; ?>">
                        <div class="mb-3"><label class="form-label">NIP</label><input type="text" class="form-control bg-light" value="<?php echo $r['nip']; ?>" readonly></div>
                        <div class="mb-2"><label class="form-label">Nama Guru</label><input type="text" name="nama_guru" class="form-control" value="<?php echo $r['nama_guru']; ?>" required></div>
                    </div>
                    <div class="modal-footer border-0 pt-0"><button type="submit" name="edit_guru" class="btn btn-action w-100">Simpan Perubahan</button></div>
                </form>
            </div>
        </div>
    <?php } ?>

    <?php
    $q_all_siswa_modal = mysqli_query($conn, "SELECT * FROM siswa");
    while ($r = mysqli_fetch_assoc($q_all_siswa_modal)) { ?>
        <div class="modal fade text-start" id="editSiswa<?php echo $r['nisn']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content custom-modal shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Edit Siswa</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="nisn_lama" value="<?php echo $r['nisn']; ?>">
                        <div class="mb-3"><label class="form-label">NISN</label><input type="text" class="form-control bg-light" value="<?php echo $r['nisn']; ?>" readonly></div>
                        <div class="mb-3"><label class="form-label">Nama Lengkap</label><input type="text" name="nama_siswa" class="form-control" value="<?php echo $r['nama_siswa']; ?>" required></div>
                        <div class="mb-2"><label class="form-label">Kelas</label><input type="text" name="kelas" class="form-control" value="<?php echo $r['kelas']; ?>" required></div>
                    </div>
                    <div class="modal-footer border-0 pt-0"><button type="submit" name="edit_siswa" class="btn btn-action w-100">Simpan Perubahan</button></div>
                </form>
            </div>
        </div>
    <?php } ?>

    <?php
    $q_kepsek_modal = mysqli_query($conn, "SELECT * FROM kepala_sekolah");
    while ($k_modal = mysqli_fetch_assoc($q_kepsek_modal)) {
    ?>
        <div class="modal fade text-start" id="editKepsek<?php echo $k_modal['nip']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" class="modal-content custom-modal shadow">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Edit Kepala Sekolah</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="nip_lama" value="<?php echo $k_modal['nip']; ?>">
                        <div class="mb-3"><label class="form-label">NIP</label><input type="text" class="form-control bg-light" value="<?php echo $k_modal['nip']; ?>" readonly></div>
                        <div class="mb-3"><label class="form-label">Nama Kepala Sekolah</label><input type="text" name="nama_kepsek" class="form-control" value="<?php echo $k_modal['nama_kepala_sekolah']; ?>" required></div>
                        <div class="mb-2"><label class="form-label">Username Login</label><input type="text" name="username" class="form-control" value="<?php echo $k_modal['username']; ?>" required></div>
                    </div>
                    <div class="modal-footer border-0 pt-0"><button type="submit" name="edit_kepsek" class="btn btn-action w-100">Simpan Perubahan</button></div>
                </form>
            </div>
        </div>
    <?php } ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function toggleKelasBaru() {
            var aksi = document.getElementById('aksiKelasMassal').value;
            var inputDiv = document.getElementById('inputKelasBaru');
            if (aksi === 'Lulus') {
                inputDiv.style.display = 'none';
            } else {
                inputDiv.style.display = 'block';
            }
        }
        document.getElementById('searchAdmin').addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.admin-row').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
            });
        });
        document.getElementById('searchGuru').addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.guru-row').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
            });
        });
        document.getElementById('searchSiswa').addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.student-accordion-item').forEach(item => {
                let hasVisible = false;
                item.querySelectorAll('.student-row').forEach(row => {
                    if (row.textContent.toLowerCase().includes(filter)) {
                        row.style.display = '';
                        hasVisible = true;
                    } else {
                        row.style.display = 'none';
                    }
                });
                let collapseDiv = item.querySelector('.accordion-collapse');
                let buttonDiv = item.querySelector('.accordion-button');
                if (filter !== '') {
                    item.style.display = hasVisible ? '' : 'none';
                    if (hasVisible) {
                        collapseDiv.classList.add('show');
                        buttonDiv.classList.remove('collapsed');
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