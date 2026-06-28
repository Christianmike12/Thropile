<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Siswa") {
    header("Location: ../../index.php");
    exit();
}

$nisn_siswa = isset($_SESSION['nisn']) ? $_SESSION['nisn'] : (isset($_SESSION['username']) ? $_SESSION['username'] : '');

if (isset($_POST['edit_profil_siswa'])) {
    $nisn_edit  = $_POST['nisn_siswa'];
    $nama_edit  = $_POST['nama_siswa'];
    $pass_edit  = $_POST['pass_siswa'];

    mysqli_query($conn, "UPDATE siswa SET nama_siswa='$nama_edit', PASSWORD='$pass_edit' WHERE nisn='$nisn_edit'");

    $_SESSION['nama'] = $nama_edit;

    echo "<script>alert('Profil Anda berhasil diperbarui!'); window.location='dashboard.php';</script>";
    exit();
}

$q_profil = mysqli_query($conn, "SELECT * FROM siswa WHERE nisn='$nisn_siswa'");
$dt_profil = mysqli_fetch_assoc($q_profil);

$bulanIndo = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
$filter_mode    = isset($_GET['filter_mode']) ? $_GET['filter_mode'] : 'tahun';
$tahun_filter   = isset($_GET['tahun'])   ? (int)$_GET['tahun']   : (int)date('Y');
$bulan_filter   = isset($_GET['bulan'])   ? (int)$_GET['bulan']   : (int)date('n');
$ta_awal_filter = isset($_GET['ta_awal']) ? (int)$_GET['ta_awal'] : ((int)date('n') >= 7 ? (int)date('Y') : (int)date('Y') - 1);

switch ($filter_mode) {
    case 'bulan':
        $where_filter = "YEAR(p.tanggal_pelaksanaan)='$tahun_filter' AND MONTH(p.tanggal_pelaksanaan)='$bulan_filter'";
        break;
    case 'ta':
        $ta_akhir_filter = $ta_awal_filter + 1;
        $where_filter = "((YEAR(p.tanggal_pelaksanaan)='$ta_awal_filter' AND MONTH(p.tanggal_pelaksanaan) >= 7) OR (YEAR(p.tanggal_pelaksanaan)='$ta_akhir_filter' AND MONTH(p.tanggal_pelaksanaan) <= 6))";
        break;
    default:
        $where_filter = "YEAR(p.tanggal_pelaksanaan)='$tahun_filter'";
        break;
}

$q_galeri = mysqli_query($conn, "SELECT p.*, s.nama_siswa, s.kelas FROM prestasi p JOIN siswa s ON p.nisn = s.nisn WHERE p.status_data='Approved' AND $where_filter ORDER BY p.tanggal_pelaksanaan DESC");

$tahun_list = [];
$res_tahun_tmp = mysqli_query($conn, "SELECT DISTINCT YEAR(tanggal_pelaksanaan) as tahun FROM prestasi WHERE status_data='Approved'");
if ($res_tahun_tmp) {
    while ($yt = mysqli_fetch_assoc($res_tahun_tmp)) {
        $tahun_list[] = (int)$yt['tahun'];
    }
}
if (!in_array((int)date('Y'), $tahun_list)) $tahun_list[] = (int)date('Y');
rsort($tahun_list);

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'riwayat';
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
                <img src="../../assets/images/logo.png" alt="Logo" width="35">
                <a href="dashboard.php" class="brand-logo">TROPHILE</a>
            </div>

            <div class="dropdown">
                <a href="javascript:void(0)" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" style="border: 1px solid rgba(255,255,255,0.3); padding: 6px 15px; border-radius: 8px;">
                    <span class="me-2 fw-medium d-none d-md-block">Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                        <h6 class="dropdown-header text-muted">Akses Siswa</h6>
                    </li>
                    <li><a class="dropdown-item fw-medium" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editProfilSiswa">⚙️ Pengaturan Akun</a></li>
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
            <div class="header-title">Dashboard Siswa Berprestasi</div>

            <ul class="nav nav-pills custom-pills" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link <?php echo $active_tab == 'riwayat' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-riwayat">Riwayat Prestasi Saya</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link <?php echo $active_tab == 'galeri' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-galeri">Galeri Prestasi (Wall of Fame)</button>
                </li>
            </ul>

            <div class="tab-content">

                <div class="tab-pane fade <?php echo $active_tab == 'riwayat' ? 'show active' : ''; ?>" id="tab-riwayat">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                        <div>
                            <h6 class="fw-bold m-0 text-navy mb-1">Daftar Rekam Jejak Prestasi Anda</h6>
                        </div>
                        <a href="portofolio.php" target="_blank" class="btn btn-action">Cetak Portofolio</a>
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
                                    <th width="15%">BERKAS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $data_riwayat_array = [];
                                $q = mysqli_query($conn, "SELECT p.*, s.nama_siswa, s.kelas FROM prestasi p JOIN siswa s ON p.nisn = s.nisn WHERE p.nisn = '$nisn_siswa' AND p.status_data = 'Approved' ORDER BY p.id_prestasi DESC");

                                if (mysqli_num_rows($q) > 0) {
                                    while ($r = mysqli_fetch_assoc($q)) {
                                        $data_riwayat_array[$r['id_prestasi']] = $r;
                                        $tahun_lomba = !empty($r['tahun']) ? $r['tahun'] : (!empty($r['tanggal_pelaksanaan']) ? date('Y', strtotime($r['tanggal_pelaksanaan'])) : '-');
                                        $id_p = $r['id_prestasi'];

                                        echo "<tr>
                                            <td><span class='badge-nip'>$no</span></td>
                                            <td class='text-start fw-medium text-navy'>" . htmlspecialchars($r['nama_lomba']) . "</td>
                                            <td>" . htmlspecialchars($r['kategori']) . "</td>
                                            <td><span class='badge bg-light text-dark border px-2'>" . htmlspecialchars($r['tingkat']) . "</span></td>
                                            <td>$tahun_lomba</td>
                                            <td><span class='badge bg-warning text-dark px-2'>" . htmlspecialchars($r['peringkat']) . "</span></td>
                                            <td><button type='button' class='btn btn-edit-outline btn-sm' data-bs-toggle='modal' data-bs-target='#previewModalSiswa$id_p'>Lihat Berkas</button></td>
                                          </tr>";
                                        $no++;
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='py-4 text-muted fw-medium'>Belum ada data prestasi resmi yang disetujui.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade <?php echo $active_tab == 'galeri' ? 'show active' : ''; ?>" id="tab-galeri">

                    <div class="filter-box">
                        <form method="GET">
                            <input type="hidden" name="tab" value="galeri">
                            <div class="d-flex align-items-end gap-3 flex-wrap">
                                <div>
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Mode Filter</label>
                                    <select name="filter_mode" class="form-select mode-select">
                                        <option value="tahun" <?php echo $filter_mode == 'tahun' ? 'selected' : ''; ?>>Per Tahun</option>
                                        <option value="bulan" <?php echo $filter_mode == 'bulan' ? 'selected' : ''; ?>>Per Bulan</option>
                                        <option value="ta" <?php echo $filter_mode == 'ta' ? 'selected' : ''; ?>>Tahun Akademik</option>
                                    </select>
                                </div>
                                <div id="grp-tahun-galeri" style="display:<?php echo $filter_mode == 'ta' ? 'none' : 'block'; ?>;">
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Tahun</label>
                                    <select name="tahun" class="form-select">
                                        <?php foreach ($tahun_list as $yr): ?><option value="<?php echo $yr; ?>" <?php echo $yr == $tahun_filter ? 'selected' : ''; ?>><?php echo $yr; ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="grp-bulan-galeri" style="display:<?php echo $filter_mode == 'bulan' ? 'block' : 'none'; ?>;">
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Bulan</label>
                                    <select name="bulan" class="form-select">
                                        <?php foreach ($bulanIndo as $i => $bn): ?><option value="<?php echo $i + 1; ?>" <?php echo ($i + 1) == $bulan_filter ? 'selected' : ''; ?>><?php echo $bn; ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="grp-ta-galeri" style="display:<?php echo $filter_mode == 'ta' ? 'block' : 'none'; ?>;">
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Tahun Awal TA</label>
                                    <select name="ta_awal" class="form-select">
                                        <?php
                                        $ta_options = range((int)date('Y'), 2020);
                                        foreach ($ta_options as $ya): ?>
                                            <option value="<?php echo $ya; ?>" <?php echo $ya == $ta_awal_filter ? 'selected' : ''; ?>><?php echo "$ya/" . ($ya + 1); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-warning fw-bold px-3">Terapkan Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                        <h6 class="fw-bold m-0 text-navy">Wall of Fame</h6>
                        <input type="text" id="searchGaleri" class="form-control" placeholder="Cari Nama Lomba / Siswa..." style="max-width: 300px; border-radius:8px;" onkeydown="if(event.key === 'Enter') { event.preventDefault(); return false; }">
                    </div>

                    <div class="row g-4" id="galeriContainer">
                        <?php
                        $data_galeri_array = [];
                        if ($q_galeri && mysqli_num_rows($q_galeri) > 0) {
                            while ($g = mysqli_fetch_assoc($q_galeri)) {
                                $data_galeri_array[$g['id_prestasi']] = $g;

                                $images = [];
                                if (!empty($g['foto_penyerahan'])) $images[] = $g['foto_penyerahan'];
                                if (!empty($g['file_sertifikat'])) $images[] = $g['file_sertifikat'];
                                if (empty($images)) $images[] = 'logo.png';

                                $tanggal_format = date('d M Y', strtotime($g['tanggal_pelaksanaan'] ?? date('Y-m-d')));
                        ?>
                                <div class="col-12 col-md-6 col-lg-4 galeri-item">
                                    <div class="galeri-card" data-bs-toggle="modal" data-bs-target="#previewModalSiswa<?php echo $g['id_prestasi']; ?>" style="cursor: pointer;">

                                        <div id="cardSlider<?php echo $g['id_prestasi']; ?>" class="carousel slide galeri-img-wrapper" data-bs-ride="carousel" data-bs-interval="3000">
                                            <div class="carousel-inner h-100">
                                                <?php foreach ($images as $idx => $img) {
                                                    $active = $idx === 0 ? 'active' : '';
                                                ?>
                                                    <div class="carousel-item <?php echo $active; ?> h-100">
                                                        <img src="../../assets/uploads/<?php echo rawurlencode($img); ?>" class="galeri-img d-block w-100" alt="Dokumentasi" onerror="this.onerror=null; this.src='../../assets/images/logo.png';">
                                                    </div>
                                                <?php } ?>
                                            </div>
                                            <div class="galeri-badge-top">🏆 <?php echo htmlspecialchars($g['kategori'] ?? '-'); ?></div>
                                        </div>

                                        <div class="galeri-body">
                                            <h5 class="galeri-title g-lomba"><?php echo htmlspecialchars($g['nama_lomba'] ?? '-'); ?></h5>
                                            <div class="galeri-student g-nama">👨‍🎓 <?php echo htmlspecialchars($g['nama_siswa'] ?? '-'); ?> (Kelas <?php echo htmlspecialchars($g['kelas'] ?? '-'); ?>)</div>
                                            <div class="galeri-footer">
                                                <div class="galeri-rank"><?php echo htmlspecialchars($g['peringkat'] ?? '-'); ?> - <?php echo htmlspecialchars($g['tingkat'] ?? '-'); ?></div>
                                                <div class="galeri-date"><?php echo $tanggal_format; ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        <?php
                            }
                        } else {
                            echo "<div class='col-12'><div class='alert alert-light border text-center py-5 text-muted'>Belum ada galeri dokumentasi prestasi pada periode ini.</div></div>";
                        }
                        ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php
    $semua_modal_siswa = [];
    if (!empty($data_riwayat_array)) {
        foreach ($data_riwayat_array as $k => $v) $semua_modal_siswa[$k] = $v;
    }
    if (!empty($data_galeri_array)) {
        foreach ($data_galeri_array as $k => $v) $semua_modal_siswa[$k] = $v;
    }

    foreach ($semua_modal_siswa as $rk) {
        $images_modal = [];
        if (!empty($rk['foto_penyerahan'])) $images_modal[] = $rk['foto_penyerahan'];
        if (!empty($rk['file_sertifikat'])) $images_modal[] = $rk['file_sertifikat'];
        if (empty($images_modal)) $images_modal[] = 'logo.png';
    ?>
        <div class="modal fade" id="previewModalSiswa<?php echo $rk['id_prestasi']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content custom-modal shadow-lg">
                    <div class="modal-header" style="background-color: #002b5c; color: white;">
                        <h6 class="modal-title fw-bold">Dokumentasi: <?php echo htmlspecialchars($rk['nama_siswa'] ?? '-'); ?></h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center p-4 bg-light">

                        <div id="modalSliderSiswa<?php echo $rk['id_prestasi']; ?>" class="carousel slide carousel-dark" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach ($images_modal as $idx => $img) {
                                    $active = $idx === 0 ? 'active' : '';
                                ?>
                                    <div class="carousel-item <?php echo $active; ?>">
                                        <img src="../../assets/uploads/<?php echo rawurlencode($img); ?>" class="img-fluid rounded shadow-sm d-block mx-auto" alt="Dokumentasi" style="max-height: 70vh; object-fit: contain;" onerror="this.onerror=null; this.src='../../assets/images/logo.png';">
                                    </div>
                                <?php } ?>
                            </div>

                            <?php if (count($images_modal) > 1) { ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#modalSliderSiswa<?php echo $rk['id_prestasi']; ?>" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#modalSliderSiswa<?php echo $rk['id_prestasi']; ?>" data-bs-slide="next">
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
    <script src="../../assets/js/remember-tab.js"></script>
    <script>
        document.querySelectorAll('.mode-select').forEach(select => {
            select.addEventListener('change', function() {
                const mode = this.value;
                document.getElementById(`grp-tahun-galeri`).style.display = (mode === 'tahun' || mode === 'bulan') ? 'block' : 'none';
                document.getElementById(`grp-bulan-galeri`).style.display = (mode === 'bulan') ? 'block' : 'none';
                document.getElementById(`grp-ta-galeri`).style.display = (mode === 'ta') ? 'block' : 'none';
            });
        });

        document.getElementById('searchGaleri')?.addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.galeri-item').forEach(item => {
                let nama = item.querySelector('.g-nama').textContent.toLowerCase();
                let lomba = item.querySelector('.g-lomba').textContent.toLowerCase();
                item.style.display = (nama.includes(filter) || lomba.includes(filter)) ? '' : 'none';
            });
        });
    </script>
</body>

</html>