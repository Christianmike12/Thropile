<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Admin TU") {
    header("Location: ../../login.php");
    exit();
}

if (isset($_POST['edit_profil_tu'])) {
    $nip_tu  = $_POST['nip_tu'];
    $nama_tu = $_POST['nama_tu'];
    $user_tu = $_POST['user_tu'];
    $pass_tu = $_POST['pass_tu'];

    if (!empty($pass_tu)) {
        if (strlen($pass_tu) < 8 || !preg_match("/[a-zA-Z]/", $pass_tu) || !preg_match("/\d/", $pass_tu)) {
            echo "<script>alert('Password minimal 8 karakter, serta harus mengandung kombinasi huruf dan angka!'); window.history.back();</script>";
            exit();
        }
        $pass_tu_hash = password_hash($pass_tu, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE admin_tu SET nama_admin='$nama_tu', username='$user_tu', PASSWORD='$pass_tu_hash' WHERE nip='$nip_tu'");
    } else {
        mysqli_query($conn, "UPDATE admin_tu SET nama_admin='$nama_tu', username='$user_tu' WHERE nip='$nip_tu'");
    }
    
    $_SESSION['nama'] = $nama_tu;
    $_SESSION['username'] = $user_tu;
    echo "<script>alert('Profil Admin TU berhasil diperbarui!'); window.location='dashboard.php';</script>";
    exit();
}

@mysqli_query($conn, "DELETE FROM request_reset WHERE status_req='Selesai' OR waktu_req < NOW() - INTERVAL 1 HOUR");

$bulanIndo = [1 => "Januari", 2 => "Februari", 3 => "Maret", 4 => "April", 5 => "Mei", 6 => "Juni", 7 => "Juli", 8 => "Agustus", 9 => "September", 10 => "Oktober", 11 => "November", 12 => "Desember"];
$tahun_list = [];
$res_t = mysqli_query($conn, "SELECT DISTINCT YEAR(tanggal_pelaksanaan) as thn FROM prestasi WHERE status_data='Approved'");
if ($res_t) {
    while ($yt = mysqli_fetch_assoc($res_t)) {
        if (!empty($yt['thn'])) $tahun_list[] = (int)$yt['thn'];
    }
}
if (!in_array((int)date('Y'), $tahun_list)) $tahun_list[] = (int)date('Y');
rsort($tahun_list);

$tahun_ini = date('Y');
$data_jumlah_per_bulan = [];
for ($i = 1; $i <= 12; $i++) {
    $q_c = mysqli_query($conn, "SELECT COUNT(*) as jml FROM prestasi WHERE status_data='Approved' AND YEAR(tanggal_pelaksanaan)='$tahun_ini' AND MONTH(tanggal_pelaksanaan)='$i'");
    $data_jumlah_per_bulan[] = $q_c ? (mysqli_fetch_assoc($q_c)['jml'] ?? 0) : 0;
}

$user_aktif = $_SESSION['username'] ?? '';
$q_profil = mysqli_query($conn, "SELECT * FROM admin_tu WHERE username='$user_aktif'");
$dt_profil = mysqli_fetch_assoc($q_profil);
$nama_tampil = $_SESSION['nama'] ?? 'Petugas TU';
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <nav class="navbar-custom">
        <div class="nav-container">
            <div class="brand-wrapper d-flex align-items-center gap-2">
                <img src="../../assets/images/logo.png" alt="Logo" width="35">
                <a href="dashboard.php" class="brand-logo text-decoration-none">TROPHILE</a>
            </div>

            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" style="border: 1px solid rgba(255,255,255,0.3); padding: 6px 15px; border-radius: 8px;">
                    <span class="me-2 fw-medium d-none d-md-block">Halo, <?php echo htmlspecialchars($nama_tampil); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; border:none; margin-top:10px;">
                    <li>
                        <h6 class="dropdown-header text-muted">Akses Admin TU</h6>
                    </li>
                    <li><a class="dropdown-item fw-medium" href="#" data-bs-toggle="modal" data-bs-target="#editProfilTU">Pengaturan Akun</a></li>
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
            <div class="header-title">Pusat Administrasi Sekolah</div>

            <ul class="nav nav-pills custom-pills mb-4" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-laporan">Laporan Prestasi</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-verifikasi">Approval Prestasi</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-reset">Reset Password</button>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-laporan">
                    <h6 class="fw-bold text-navy mb-3" style="font-size: 1.1rem;">Grafik Pertumbuhan Prestasi (<?php echo $tahun_ini; ?>)</h6>
                    <div style="height:320px;width:100%; border: 1px solid #e1e5eb; border-radius: 12px; padding: 20px; margin-bottom:30px;">
                        <canvas id="grafikPrestasi"></canvas>
                    </div>

                    <div class="filter-box">
                        <form id="formFilterRekap">
                            <div class="d-flex align-items-end gap-3 flex-wrap">
                                <div>
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Mode Filter</label>
                                    <select name="filter_mode" id="filter_mode" class="form-select mode-select">
                                        <option value="all">Semua Waktu (All)</option>
                                        <option value="tahun" selected>Per Tahun</option>
                                        <option value="bulan">Per Bulan</option>
                                        <option value="ta">Tahun Akademik</option>
                                        <option value="rentang">Rentang Tanggal</option>
                                    </select>
                                </div>
                                <div id="grp-tahun" class="filter-group" style="display:block;">
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Tahun</label>
                                    <select name="tahun" class="form-select">
                                        <?php foreach ($tahun_list as $yr): ?><option value="<?php echo $yr; ?>"><?php echo $yr; ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="grp-bulan" class="filter-group" style="display:none;">
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Bulan</label>
                                    <select name="bulan" class="form-select">
                                        <?php foreach ($bulanIndo as $i => $bn): ?><option value="<?php echo $i; ?>"><?php echo $bn; ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="grp-ta" class="filter-group" style="display:none;">
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Tahun Awal TA</label>
                                    <select name="ta_awal" class="form-select">
                                        <?php $ta_options = range(date('Y'), 2020);
                                        foreach ($ta_options as $ya): ?>
                                            <option value="<?php echo $ya; ?>"><?php echo "$ya/" . ($ya + 1); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="grp-rentang" class="filter-group" style="display:none; gap:10px; align-items: center;">
                                    <div>
                                        <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Dari Tanggal</label>
                                        <input type="date" name="tanggal_awal" class="form-control" value="<?php echo date('Y-m-01'); ?>">
                                    </div>
                                    <div class="fw-bold text-muted pb-1">S/D</div>
                                    <div>
                                        <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Sampai Tanggal</label>
                                        <input type="date" name="tanggal_akhir" class="form-control" value="<?php echo date('Y-m-t'); ?>">
                                    </div>
                                </div>

                                <div>
                                    <button type="button" onclick="loadDataRekap()" class="btn btn-warning fw-bold px-3">Terapkan Filter</button>
                                </div>
                                <div class="ms-auto d-flex gap-2">
                                    <button type="button" onclick="bukaCetakRekap()" class="btn btn-primary fw-bold px-4 text-nowrap" style="background-color: #002b5c; border-color: #002b5c;">Cetak Rekapitulasi</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 mt-4 flex-wrap gap-3">
                        <h6 class="fw-bold m-0 text-navy" style="font-size: 1.1rem;" id="judulRekapText">Rincian Data Prestasi Sekolah</h6>
                        <input type="text" id="searchRekap" class="form-control search-input" placeholder="Cari Siswa atau Lomba..." style="width: 280px;" onkeydown="if(event.key === 'Enter') { event.preventDefault(); return false; }">
                    </div>

                    <div class="table-responsive">
                        <table class="table text-center align-middle custom-table">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="20%" class="text-start">NAMA SISWA</th>
                                    <th width="10%">KELAS</th>
                                    <th width="25%" class="text-start">LOMBA</th>
                                    <th width="15%">TINGKAT</th>
                                    <th width="15%">HASIL</th>
                                    <th width="10%">BERKAS</th>
                                </tr>
                            </thead>
                            <tbody id="tabelRekapBody">
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-verifikasi">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3 action-wrapper">
                        <h6 class="fw-bold m-0 text-navy">Antrean Approval Prestasi Siswa</h6>
                        <input type="text" id="searchVerifikasi" class="form-control search-input" placeholder="Cari Nama Siswa / Lomba..." style="max-width: 300px;" onkeydown="if(event.key === 'Enter') { event.preventDefault(); return false; }">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover text-center align-middle custom-table">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Data Siswa & Guru</th>
                                    <th>Lomba</th>
                                    <th>Peringkat</th>
                                    <th>Berkas</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $q = mysqli_query($conn, "SELECT p.*, s.nama_siswa, s.kelas, g.nama_guru FROM prestasi p JOIN siswa s ON p.nisn = s.nisn LEFT JOIN guru g ON p.nip_guru = g.nip WHERE p.status_data = 'Pending'");
                                $data_verifikasi = [];

                                if ($q && mysqli_num_rows($q) > 0) {
                                    while ($r = mysqli_fetch_assoc($q)) {
                                        $data_verifikasi[] = $r;
                                        $file_aman = rawurlencode($r['file_sertifikat'] ?? '');
                                        $nama_guru = $r['nama_guru'] ?? 'Tidak Diketahui';
                                ?>
                                        <tr class='verifikasi-row' id="row-prestasi-<?php echo $r['id_prestasi']; ?>">
                                            <td><span class='badge bg-light text-dark border'><?php echo $no++; ?></span></td>
                                            <td class='text-start'>
                                                <div class='fw-bold v-nama'><?php echo htmlspecialchars($r['nama_siswa']); ?></div>
                                                <div class="small text-muted mb-1">Kelas <?php echo htmlspecialchars($r['kelas']); ?></div>
                                                <div class="small fw-medium text-navy" style="font-size: 0.8rem;">Pembina: <?php echo htmlspecialchars($nama_guru); ?></div>
                                            </td>
                                            <td class='text-start v-lomba fw-medium'><?php echo htmlspecialchars($r['nama_lomba']); ?></td>
                                            <td><span class='badge bg-warning text-dark'><?php echo htmlspecialchars($r['peringkat']); ?></span></td>
                                            <td><button type='button' class='btn btn-outline-dark btn-sm rounded-pill px-3 fw-medium' data-bs-toggle='modal' data-bs-target='#previewModal<?php echo $r['id_prestasi']; ?>'>Lihat Berkas</button></td>
                                            <td class="col-aksi">
                                                <button type='button' onclick="aksiVerify(<?php echo $r['id_prestasi']; ?>, 'acc')" class='btn btn-success btn-sm rounded-pill px-3 me-1'>ACC</button>
                                                <button type='button' class='btn btn-danger btn-sm rounded-pill px-3' data-bs-toggle='modal' data-bs-target='#tolakModal<?php echo $r['id_prestasi']; ?>'>Tolak</button>
                                            </td>
                                        </tr>
                                <?php
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='py-4 text-muted fw-medium' id='empty-verify-msg'>Belum ada sertifikat yang perlu diverifikasi saat ini.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-reset">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 action-wrapper">
                        <h6 class="fw-bold m-0 text-navy">Daftar Permintaan Reset Password</h6>
                        <input type="text" id="searchReset" class="form-control search-input" placeholder="Cari Username..." style="max-width: 300px;" onkeydown="if(event.key === 'Enter') { event.preventDefault(); return false; }">
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
                                $q_reset = @mysqli_query($conn, "SELECT * FROM request_reset ORDER BY waktu_req DESC");
                                if ($q_reset && mysqli_num_rows($q_reset) > 0) {
                                    while ($r = mysqli_fetch_assoc($q_reset)) {
                                        $badge = match ($r['status_req']) {
                                            'Pending' => 'bg-danger',
                                            'Approved' => 'bg-warning text-dark',
                                            'Selesai' => 'bg-success',
                                            default => 'bg-secondary'
                                        };
                                ?>
                                        <tr class='reset-row' id="row-reset-<?php echo $r['id_request']; ?>">
                                            <td class='text-muted small'><?php echo htmlspecialchars($r['waktu_req']); ?></td>
                                            <td class='fw-medium text-navy r-username'><?php echo htmlspecialchars($r['username']); ?></td>
                                            <td><span class='badge <?php echo $badge; ?> px-3 py-2 rounded-pill status-badge'><?php echo htmlspecialchars($r['status_req']); ?></span></td>
                                            <td class="kode-cell">
                                                <?php if ($r['kode_unik']) { ?>
                                                    <b style='letter-spacing:2px; font-size:16px; color:#002b5c;'><?php echo $r['kode_unik']; ?></b>
                                                <?php } else {
                                                    echo "<span class='text-muted'>-</span>";
                                                } ?>
                                            </td>
                                            <td class="col-aksi aksi-cell">
                                                <?php if ($r['status_req'] == 'Pending') { ?>
                                                    <button type='button' onclick="aksiReset(<?php echo $r['id_request']; ?>)" class='btn btn-outline-dark btn-sm rounded-pill px-3 fw-medium btn-code-trigger'>ACC & Beri Kode</button>
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

    <div class="modal fade text-start" id="editProfilTU" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal shadow">
                <div class="modal-header" style="background-color: #002b5c; color: white; border-bottom: none;">
                    <h5 class="modal-title fw-bold">Pengaturan Akun TU</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="nip_tu" value="<?php echo htmlspecialchars($dt_profil['nip'] ?? ''); ?>">
                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">NIP (Nomor Induk Pegawai)</label>
                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($dt_profil['nip'] ?? ''); ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">Nama Lengkap</label>
                        <input type="text" name="nama_tu" class="form-control" value="<?php echo htmlspecialchars($dt_profil['nama_admin'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">Username Login</label>
                        <input type="text" name="user_tu" class="form-control" value="<?php echo htmlspecialchars($dt_profil['username'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-navy fw-semibold small">Password Akun (Kosongkan jika tidak diubah)</label>
                        <input type="password" name="pass_tu" class="form-control" placeholder="Minimal 8 karakter, kombinasi huruf & angka" pattern="(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}" title="Password minimal 8 karakter, mengandung huruf dan angka">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="submit" name="edit_profil_tu" class="btn w-100 fw-bold" style="background-color: #ffcc00; color:#002b5c; border-radius:8px; padding:10px;">Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($data_verifikasi as $r) {
        $images = [];
        if (!empty($r['file_trofi'])) $images[] = $r['file_trofi'];
        if (!empty($r['file_sertifikat'])) $images[] = $r['file_sertifikat'];
        if (empty($images)) $images[] = 'logo.png';
    ?>
        <div class="modal fade text-start" id="tolakModal<?php echo $r['id_prestasi']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content custom-modal shadow">
                    <div class="modal-header" style="background-color: #002b5c; color: white;">
                        <h5 class="modal-title fw-bold">Alasan Penolakan</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" id="close-modal-tolak-<?php echo $r['id_prestasi']; ?>"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3 p-3 bg-light rounded border">
                            <span class="d-block small text-muted">Siswa:</span>
                            <span class="fw-bold text-navy"><?php echo htmlspecialchars($r['nama_siswa'] ?? '-'); ?></span>
                            <hr class="my-2">
                            <span class="d-block small text-muted">Lomba:</span>
                            <span class="fw-bold text-navy"><?php echo htmlspecialchars($r['nama_lomba'] ?? '-'); ?></span>
                        </div>
                        <label class="form-label text-navy fw-semibold small">Tuliskan Alasan Penolakan:</label>
                        <textarea id="alasan-text-<?php echo $r['id_prestasi']; ?>" class="form-control" rows="3" placeholder="Contoh: Sertifikat buram / Lomba tidak sesuai tingkat" required></textarea>
                    </div>
                    <div class="modal-footer border-0 pt-0 px-4 pb-4">
                        <button type="button" class="btn btn-secondary fw-medium rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="button" onclick="submitTolakAjax(<?php echo $r['id_prestasi']; ?>)" class="btn btn-danger fw-medium rounded-pill px-4">Tolak & Simpan</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="previewModal<?php echo $r['id_prestasi']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content custom-modal shadow-lg">
                    <div class="modal-header" style="background-color: #002b5c; color: white;">
                        <h6 class="modal-title fw-bold">Pratinjau Dokumentasi: <?php echo htmlspecialchars($r['nama_siswa'] ?? '-'); ?></h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center p-4 bg-light">
                        <div id="modalSliderApproval<?php echo $r['id_prestasi']; ?>" class="carousel slide carousel-dark" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach ($images as $idx => $img) { 
                                    $active = $idx === 0 ? 'active' : '';
                                    $file_aman = rawurlencode($img);
                                ?>
                                    <div class="carousel-item <?php echo $active; ?>">
                                        <img src="../../assets/uploads/<?php echo $file_aman; ?>" class="img-fluid rounded shadow-sm d-block mx-auto" alt="Dokumentasi" style="max-height: 70vh; object-fit: contain;" onerror="this.onerror=null; this.src='../../assets/images/logo.png';">
                                    </div>
                                <?php } ?>
                            </div>
                            <?php if (count($images) > 1) { ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#modalSliderApproval<?php echo $r['id_prestasi']; ?>" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#modalSliderApproval<?php echo $r['id_prestasi']; ?>" data-bs-slide="next">
                                    <span class="carousel-control-next-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>

    <div id="modalContainerRekap"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/remember-tab.js"></script>
    <script>
        document.querySelectorAll('.mode-select').forEach(select => {
            select.addEventListener('change', function() {
                const mode = this.value;
                document.querySelectorAll('.filter-group').forEach(el => el.style.display = 'none');
                if (mode === 'tahun') {
                    document.getElementById(`grp-tahun`).style.display = 'block';
                }
                if (mode === 'bulan') {
                    document.getElementById(`grp-tahun`).style.display = 'block';
                    document.getElementById(`grp-bulan`).style.display = 'block';
                }
                if (mode === 'ta') {
                    document.getElementById(`grp-ta`).style.display = 'block';
                }
                if (mode === 'rentang') {
                    document.getElementById(`grp-rentang`).style.display = 'flex';
                }
            });
        });

        async function loadDataRekap() {
            const form = document.getElementById('formFilterRekap');
            const formData = new FormData(form);
            const tabelBody = document.getElementById('tabelRekapBody');
            const modalWadah = document.getElementById('modalContainerRekap');

            tabelBody.innerHTML = "<tr><td colspan='7' class='py-4 fw-bold text-navy'>Sedang Memuat Data...</td></tr>";

            try {
                const response = await fetch('api_rekap.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                tabelBody.innerHTML = result.tabel;
                modalWadah.innerHTML = result.modal;
            } catch (error) {
                tabelBody.innerHTML = "<tr><td colspan='7' class='py-4 text-danger fw-bold'>Gagal mengambil data.</td></tr>";
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            loadDataRekap();
        });

        function bukaCetakRekap() {
            const form = document.getElementById('formFilterRekap');
            const params = new URLSearchParams(new FormData(form)).toString();
            window.open('cetak_rekap.php?' + params, '_blank');
        }

        async function aksiVerify(idPrestasi, tipe) {
            if (tipe === 'acc' && !confirm("Setujui prestasi ini?")) return;
            const formData = new FormData();
            formData.append('id', idPrestasi);

            try {
                const response = await fetch(`api_admin.php?action=acc_prestasi`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    const baris = document.getElementById(`row-prestasi-${idPrestasi}`);
                    if (baris) {
                        baris.style.transition = "all 0.5s ease";
                        baris.style.opacity = "0";
                        setTimeout(() => {
                            baris.remove();
                            if (document.querySelectorAll('.verifikasi-row').length === 0) window.location.reload();
                        }, 500);
                    }
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert("Koneksi API Gagal!");
            }
        }

        async function submitTolakAjax(idPrestasi) {
            const alasanInput = document.getElementById(`alasan-text-${idPrestasi}`).value;
            if (!alasanInput.trim()) {
                alert("Alasan penolakan wajib diisi!");
                return;
            }

            const formData = new FormData();
            formData.append('id', idPrestasi);
            formData.append('alasan', alasanInput);

            try {
                const response = await fetch(`api_admin.php?action=tolak_prestasi`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    document.getElementById(`close-modal-tolak-${idPrestasi}`).click();
                    const baris = document.getElementById(`row-prestasi-${idPrestasi}`);
                    if (baris) {
                        baris.style.transition = "all 0.5s ease";
                        baris.style.opacity = "0";
                        setTimeout(() => {
                            baris.remove();
                        }, 500);
                    }
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert("Gagal memproses penolakan via API!");
            }
        }

        async function aksiReset(idRequest) {
            const formData = new FormData();
            formData.append('id_request', idRequest);

            try {
                const response = await fetch(`api_admin.php?action=acc_reset`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.status === 'success') {
                    const row = document.getElementById(`row-reset-${idRequest}`);
                    if (row) {
                        const badge = row.querySelector('.status-badge');
                        badge.className = "badge bg-warning text-dark px-3 py-2 rounded-pill status-badge";
                        badge.textContent = "Approved";
                        row.querySelector('.kode-cell').innerHTML = `<b style='letter-spacing:2px; font-size:16px; color:#002b5c;'>${result.kode}</b>`;
                        row.querySelector('.aksi-cell').innerHTML = `<span class='text-muted'>-</span>`;
                    }
                    alert(`Kode berhasil digenerate di layar: ${result.kode}`);
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert("Koneksi API Pemulihan Gagal!");
            }
        }

        document.getElementById('searchVerifikasi')?.addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.verifikasi-row').forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        document.getElementById('searchReset')?.addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.reset-row').forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        document.getElementById('searchRekap')?.addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.rekap-row').forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        const ctx = document.getElementById('grafikPrestasi')?.getContext('2d');
        if (ctx) {
            Chart.defaults.font.family = "'Poppins', sans-serif";
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                    datasets: [{
                        label: 'Prestasi',
                        data: <?php echo json_encode($data_jumlah_per_bulan); ?>,
                        backgroundColor: '#002b5c',
                        hoverBackgroundColor: '#ffcc00',
                        borderRadius: 6,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#e9ecef',
                                drawBorder: false
                            },
                            ticks: {
                                stepSize: 1
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>