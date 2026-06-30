<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Kepala Sekolah") {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
}

if (isset($_POST['edit_profil_kepsek'])) {
    $nip_kepsek  = trim($_POST['nip_kepsek']);
    $nama_kepsek = trim($_POST['nama_kepsek']);
    $user_kepsek = trim($_POST['user_kepsek']);
    $pass_kepsek = trim($_POST['pass_kepsek']);

    if (!empty($pass_kepsek)) {
        if (strlen($pass_kepsek) < 8 || !preg_match("/[a-zA-Z]/", $pass_kepsek) || !preg_match("/\d/", $pass_kepsek)) {
            echo "<script>alert('Password minimal 8 karakter, serta harus mengandung kombinasi huruf dan angka!'); window.history.back();</script>";
            exit();
        }
        $pass_kepsek_hash = password_hash($pass_kepsek, PASSWORD_DEFAULT);
        db_query($conn, "UPDATE kepala_sekolah SET nama_kepala_sekolah=?, username=?, PASSWORD=? WHERE nip=?", "ssss", $nama_kepsek, $user_kepsek, $pass_kepsek_hash, $_SESSION['nip']);
    } else {
        db_query($conn, "UPDATE kepala_sekolah SET nama_kepala_sekolah=?, username=? WHERE nip=?", "sss", $nama_kepsek, $user_kepsek, $_SESSION['nip']);
    }

    $_SESSION['nama'] = $nama_kepsek;
    $_SESSION['username'] = $user_kepsek;

    echo "<script>alert('Profil Kepala Sekolah berhasil diperbarui!'); window.location='dashboard.php';</script>";
    exit();
}

$active_tab = $_GET['tab'] ?? 'indikator';

$tahun_ini  = date('Y');
$bulan_ini  = date('m');
$bulan_lalu = date('m', strtotime('-1 month'));

$t_akademik     = mysqli_fetch_assoc(db_query($conn, "SELECT COUNT(*) as jml FROM prestasi WHERE status_data='Approved' AND kategori LIKE '%Akademik%'"))['jml'] ?? 0;
$t_non_akademik = mysqli_fetch_assoc(db_query($conn, "SELECT COUNT(*) as jml FROM prestasi WHERE status_data='Approved' AND kategori NOT LIKE '%Akademik%'"))['jml'] ?? 0;

$data_jumlah_per_bulan = [];
for ($i = 1; $i <= 12; $i++) {
    $q_chart = db_query($conn, "SELECT COUNT(*) as jml FROM prestasi p WHERE status_data='Approved' AND YEAR(p.tanggal_pelaksanaan)=? AND MONTH(p.tanggal_pelaksanaan)=?", "ii", $tahun_ini, $i);
    $data_jumlah_per_bulan[] = $q_chart ? (mysqli_fetch_assoc($q_chart)['jml'] ?? 0) : 0;
}

$ai_sekarang = mysqli_fetch_assoc(db_query($conn, "SELECT COUNT(*) as jml FROM prestasi WHERE status_data='Approved' AND YEAR(tanggal_pelaksanaan)=? AND MONTH(tanggal_pelaksanaan)=?", "ii", $tahun_ini, $bulan_ini))['jml'] ?? 0;
$ai_lalu     = mysqli_fetch_assoc(db_query($conn, "SELECT COUNT(*) as jml FROM prestasi WHERE status_data='Approved' AND YEAR(tanggal_pelaksanaan)=? AND MONTH(tanggal_pelaksanaan)=?", "ii", $tahun_ini, $bulan_lalu))['jml'] ?? 0;

if ($ai_sekarang == 0 && $ai_lalu == 0) {
    $teks_ai = "Data belum mencukupi untuk analisis prediktif.";
} elseif ($ai_sekarang > $ai_lalu) {
    $kenaikan = $ai_sekarang - $ai_lalu;
    $teks_ai = "<b>Insight Cerdas:</b> Luar biasa! Terdapat tren <b>kenaikan $kenaikan prestasi</b> di bulan ini dibandingkan bulan lalu. Algoritma memprediksi semangat kompetitif siswa sedang tinggi.";
} elseif ($ai_sekarang < $ai_lalu) {
    $penurunan = $ai_lalu - $ai_sekarang;
    $teks_ai = "<b>Insight Cerdas:</b> Perhatian. Terdapat <b>penurunan $penurunan prestasi</b> di bulan ini. Sistem menyarankan evaluasi peningkatan motivasi kompetisi siswa.";
} else {
    $teks_ai = "<b>Insight Cerdas:</b> Performa stabil. Jumlah prestasi bulan ini sama dengan bulan lalu.";
}

$bulanIndo = [1 => "Januari", 2 => "Februari", 3 => "Maret", 4 => "April", 5 => "Mei", 6 => "Juni", 7 => "Juli", 8 => "Agustus", 9 => "September", 10 => "Oktober", 11 => "November", 12 => "Desember"];
$tahun_list = [];
$res_tahun_tmp = db_query($conn, "SELECT DISTINCT YEAR(tanggal_pelaksanaan) as tahun FROM prestasi WHERE status_data='Approved'");
if ($res_tahun_tmp) {
    while ($yt = mysqli_fetch_assoc($res_tahun_tmp)) {
        if (!empty($yt['tahun'])) $tahun_list[] = (int)$yt['tahun'];
    }
}
if (!in_array((int)date('Y'), $tahun_list)) $tahun_list[] = (int)date('Y');
rsort($tahun_list);

$q_profil_kepsek = db_query($conn, "SELECT * FROM kepala_sekolah WHERE username=?", "s", $_SESSION['username']);
$dt_profil_kepsek = mysqli_fetch_assoc($q_profil_kepsek);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kepala Sekolah - Trophile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/kepsek_dashboard.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <nav class="navbar-custom">
        <div class="nav-container">
            <div class="brand-wrapper"><img src="../../assets/images/logo.png" width="35" alt="Logo"><a href="dashboard.php" class="brand-logo text-decoration-none">TROPHILE</a></div>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" style="border: 1px solid rgba(255,255,255,0.3); padding: 6px 15px; border-radius: 8px;">
                    <span class="me-2 fw-medium d-none d-md-block">Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; border:none; margin-top:10px;">
                    <li>
                        <h6 class="dropdown-header text-muted">Akses Kepala Sekolah</h6>
                    </li>
                    <li><a class="dropdown-item fw-medium" href="#" data-bs-toggle="modal" data-bs-target="#editProfilKepsek">Pengaturan Akun</a></li>
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
            <div class="header-title">Sistem Informasi Kepala Sekolah</div>

            <ul class="nav nav-pills custom-pills mb-4" role="tablist">
                <li class="nav-item"><button type="button" class="nav-link <?php echo $active_tab == 'indikator' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-indikator">Indikator Utama</button></li>
                <li class="nav-item"><button type="button" class="nav-link <?php echo $active_tab == 'rekap' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-rekap">Rekapitulasi Prestasi</button></li>
                <li class="nav-item"><button type="button" class="nav-link <?php echo $active_tab == 'galeri' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-galeri">Galeri Prestasi</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade <?php echo $active_tab == 'indikator' ? 'show active' : ''; ?>" id="tab-indikator">
                    <div class="ai-card-wrapper mt-2">
                        <div class="ai-card" id="aiCard">
                            <div class="ai-header">
                                <div style="display:flex;align-items:center;">
                                    <span style="font-size:20px;margin-right:10px;"></span>
                                    <h5 style="margin:0;font-weight:700;color:#002b5c;font-size:15px;">Trophile AI™ Insights</h5>
                                </div>
                                <span class="ai-indicator" id="aiIndicator">Klik untuk baca</span>
                            </div>
                            <div class="ai-content">
                                <p><?php echo $teks_ai; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="stat-box b-navy">
                                <p>TOTAL PRESTASI AKADEMIK</p>
                                <h3 class="text-navy"><?php echo $t_akademik; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-box b-navy">
                                <p>TOTAL PRESTASI NON-AKADEMIK</p>
                                <h3 class="text-navy"><?php echo $t_non_akademik; ?></h3>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-navy mb-3" style="font-size: 1.1rem;">Tren Pertumbuhan Prestasi Tahunan (<?php echo $tahun_ini; ?>)</h6>
                    <div style="height:320px;width:100%; border: 1px solid #e1e5eb; border-radius: 12px; padding: 20px;">
                        <canvas id="grafikPrestasi"></canvas>
                    </div>
                </div>

                <div class="tab-pane fade <?php echo $active_tab == 'rekap' ? 'show active' : ''; ?>" id="tab-rekap">
                    <div class="filter-box">
                        <form id="formFilterRekap">
                            <?php echo csrf_field(); ?>
                            <div class="d-flex align-items-end gap-3 flex-wrap">
                                <div>
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Mode Filter</label>
                                    <select name="filter_mode" class="form-select mode-select" data-target="rekap">
                                        <option value="all">Semua Waktu (All)</option>
                                        <option value="tahun" selected>Per Tahun</option>
                                        <option value="bulan">Per Bulan</option>
                                        <option value="ta">Tahun Akademik</option>
                                        <option value="rentang">Rentang Waktu</option>
                                    </select>
                                </div>
                                <div id="grp-tahun-rekap" class="filter-group" style="display:block;">
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Tahun</label>
                                    <select name="tahun" class="form-select">
                                        <?php foreach ($tahun_list as $yr): ?><option value="<?php echo $yr; ?>"><?php echo $yr; ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="grp-bulan-rekap" class="filter-group" style="display:none;">
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Bulan</label>
                                    <select name="bulan" class="form-select">
                                        <?php foreach ($bulanIndo as $i => $bn): ?><option value="<?php echo $i; ?>"><?php echo $bn; ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="grp-ta-rekap" class="filter-group" style="display:none;">
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Tahun Awal TA</label>
                                    <select name="ta_awal" class="form-select">
                                        <?php foreach (range(date('Y'), 2020) as $ya): ?>
                                            <option value="<?php echo $ya; ?>"><?php echo "$ya/" . ($ya + 1); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div id="grp-rentang-rekap" class="filter-group" style="display:none; gap:10px; align-items: center;">
                                    <div><label class="fw-semibold text-navy mb-1" style="font-size:13px;">Dari Tanggal</label><input type="date" name="tanggal_awal" class="form-control" value="<?php echo date('Y-m-01'); ?>"></div>
                                    <div class="fw-bold text-muted pb-1">S/D</div>
                                    <div><label class="fw-semibold text-navy mb-1" style="font-size:13px;">Sampai Tanggal</label><input type="date" name="tanggal_akhir" class="form-control" value="<?php echo date('Y-m-t'); ?>"></div>
                                </div>

                                <div><button type="button" onclick="loadDataKepsek('rekap')" class="btn btn-warning fw-bold px-3">Terapkan Filter</button></div>
                                <div class="ms-auto d-flex gap-2">
                                    <button type="button" onclick="bukaCetakResmi('rekap')" class="btn btn-primary fw-bold px-4 text-nowrap" style="background-color: #002b5c; border-color: #002b5c;">Cetak Resmi</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="row mb-4 g-3">
                        <div class="col-6 col-md">
                            <div class="stat-box b-navy">
                                <p>TOTAL PRESTASI</p>
                                <h3 class="text-navy" id="statTotal">0</h3>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="stat-box b-danger">
                                <p>INTERNASIONAL</p>
                                <h3 class="text-danger" id="statIntl">0</h3>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="stat-box b-primary">
                                <p>NASIONAL</p>
                                <h3 class="text-primary" id="statNas">0</h3>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="stat-box b-info">
                                <p>PROVINSI</p>
                                <h3 class="text-info" id="statProv">0</h3>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="stat-box b-warning">
                                <p>KOTA/KAB</p>
                                <h3 style="color:#d97706;" id="statKota">0</h3>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 mt-4 flex-wrap gap-3">
                        <h6 class="fw-bold m-0 text-navy" style="font-size: 1.1rem;">Rincian Data Prestasi Sekolah</h6>
                        <input type="text" id="searchRekap" class="form-control search-input" placeholder="Cari Siswa atau Lomba..." style="width: 280px;" onkeydown="if(event.key === 'Enter') { event.preventDefault(); return false; }">
                    </div>

                    <div class="table-responsive">
                        <table class="table text-center align-middle custom-table">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="20%">NAMA SISWA</th>
                                    <th width="10%">KELAS</th>
                                    <th width="25%">LOMBA</th>
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

                <div class="tab-pane fade <?php echo $active_tab == 'galeri' ? 'show active' : ''; ?>" id="tab-galeri">
                    <div class="filter-box">
                        <form id="formFilterGaleri">
                            <?php echo csrf_field(); ?>
                            <div class="d-flex align-items-end gap-3 flex-wrap">
                                <div>
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Mode Filter Galeri</label>
                                    <select name="filter_mode" class="form-select mode-select" data-target="galeri">
                                        <option value="all">Semua Waktu (All)</option>
                                        <option value="tahun" selected>Per Tahun</option>
                                        <option value="bulan">Per Bulan</option>
                                        <option value="ta">Tahun Akademik</option>
                                        <option value="rentang">Rentang Waktu</option>
                                    </select>
                                </div>
                                <div id="grp-tahun-galeri" class="filter-group" style="display:block;">
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Tahun</label>
                                    <select name="tahun" class="form-select"><?php foreach ($tahun_list as $yr): ?><option value="<?php echo $yr; ?>"><?php echo $yr; ?></option><?php endforeach; ?></select>
                                </div>
                                <div id="grp-bulan-galeri" class="filter-group" style="display:none;">
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Bulan</label>
                                    <select name="bulan" class="form-select"><?php foreach ($bulanIndo as $i => $bn): ?><option value="<?php echo $i; ?>"><?php echo $bn; ?></option><?php endforeach; ?></select>
                                </div>
                                <div id="grp-ta-galeri" class="filter-group" style="display:none;">
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Tahun Awal TA</label>
                                    <select name="ta_awal" class="form-select"><?php foreach (range(date('Y'), 2020) as $ya): ?><option value="<?php echo $ya; ?>"><?php echo "$ya/" . ($ya + 1); ?></option><?php endforeach; ?></select>
                                </div>
                                <div id="grp-rentang-galeri" class="filter-group" style="display:none; gap:10px; align-items: center;">
                                    <div><label class="fw-semibold text-navy mb-1" style="font-size:13px;">Dari Tanggal</label><input type="date" name="tanggal_awal" class="form-control" value="<?php echo date('Y-m-01'); ?>"></div>
                                    <div class="fw-bold text-muted pb-1">S/D</div>
                                    <div><label class="fw-semibold text-navy mb-1" style="font-size:13px;">Sampai Tanggal</label><input type="date" name="tanggal_akhir" class="form-control" value="<?php echo date('Y-m-t'); ?>"></div>
                                </div>

                                <div><button type="button" onclick="loadDataKepsek('galeri')" class="btn btn-warning fw-bold px-3">Terapkan Filter</button></div>
                            </div>
                        </form>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                        <h6 class="fw-bold m-0 text-navy">Galeri Prestasi Siswa</h6>
                        <input type="text" id="searchGaleri" class="form-control search-input" placeholder="Cari Nama Lomba / Siswa..." style="max-width: 300px;" onkeydown="if(event.key === 'Enter') { event.preventDefault(); return false; }">
                    </div>

                    <div class="row g-4" id="galeriContainer">
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div id="modalContainer"></div>

    <div class="modal fade text-start" id="editProfilKepsek" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal shadow">
                <?php echo csrf_field(); ?>
                <div class="modal-header" style="background-color: #002b5c; color: white;">
                    <h5 class="modal-title fw-bold">Pengaturan Akun Kepala Sekolah</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="nip_kepsek" value="<?php echo e($dt_profil_kepsek['nip'] ?? ''); ?>">
                    <div class="mb-3"><label class="form-label text-navy fw-semibold small">NIP</label><input type="text" class="form-control bg-light" value="<?php echo e($dt_profil_kepsek['nip'] ?? ''); ?>" readonly></div>
                    <div class="mb-3"><label class="form-label text-navy fw-semibold small">Nama Lengkap & Gelar</label><input type="text" name="nama_kepsek" class="form-control" value="<?php echo e($dt_profil_kepsek['nama_kepala_sekolah'] ?? ''); ?>" required></div>
                    <div class="mb-3"><label class="form-label text-navy fw-semibold small">Username</label><input type="text" name="user_kepsek" class="form-control" value="<?php echo e($dt_profil_kepsek['username'] ?? ''); ?>" required></div>
                    <div class="mb-2"><label class="form-label text-navy fw-semibold small">Password Akun (Kosongkan jika tidak diubah)</label><input type="password" name="pass_kepsek" class="form-control" placeholder="Min 8 karakter, kombinasi huruf & angka" pattern="(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}" title="Password minimal 8 karakter, mengandung huruf dan angka"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <button type="submit" name="edit_profil_kepsek" class="btn w-100 fw-bold" style="background-color: #ffcc00; color:#002b5c; border-radius:8px; padding:10px;">Simpan Pengaturan</button>
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
                const target = this.getAttribute('data-target');
                document.querySelectorAll(`#tab-${target} .filter-group`).forEach(el => el.style.display = 'none');
                if (mode === 'tahun') document.getElementById(`grp-tahun-${target}`).style.display = 'block';
                if (mode === 'bulan') {
                    document.getElementById(`grp-tahun-${target}`).style.display = 'block';
                    document.getElementById(`grp-bulan-${target}`).style.display = 'block';
                }
                if (mode === 'ta') document.getElementById(`grp-ta-${target}`).style.display = 'block';
                if (mode === 'rentang') document.getElementById(`grp-rentang-${target}`).style.display = 'flex';
            });
        });

        async function loadDataKepsek(target) {
            const formId = target === 'rekap' ? 'formFilterRekap' : 'formFilterGaleri';
            const form = document.getElementById(formId);
            const formData = new FormData(form);

            if (target === 'rekap') document.getElementById('tabelRekapBody').innerHTML = "<tr><td colspan='7' class='py-4 fw-bold text-navy'>Memuat Data...</td></tr>";
            else document.getElementById('galeriContainer').innerHTML = "<div class='col-12 text-center py-4 fw-bold text-navy'>Memuat Galeri...</div>";

            try {
                const response = await fetch('api_rekap.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (target === 'rekap') {
                    document.getElementById('tabelRekapBody').innerHTML = result.tabel;
                    document.getElementById('modalContainer').innerHTML = result.modal;
                    document.getElementById('statTotal').innerText = result.stats.total;
                    document.getElementById('statIntl').innerText = result.stats.intl;
                    document.getElementById('statNas').innerText = result.stats.nas;
                    document.getElementById('statProv').innerText = result.stats.prov;
                    document.getElementById('statKota').innerText = result.stats.kota;
                } else {
                    document.getElementById('galeriContainer').innerHTML = result.galeri;
                    document.getElementById('modalContainer').innerHTML = result.modal;
                }
            } catch (error) {
                alert("Gagal memuat data dari server!");
            }
        }

        document.addEventListener("DOMContentLoaded", () => {
            loadDataKepsek('rekap');
            loadDataKepsek('galeri');
        });

        function bukaCetakResmi(target) {
            const formId = target === 'rekap' ? 'formFilterRekap' : 'formFilterGaleri';
            const form = document.getElementById(formId);
            const params = new URLSearchParams(new FormData(form)).toString();
            window.open('cetak_rekap.php?' + params, '_blank');
        }

        const aiCard = document.getElementById('aiCard');
        const aiIndicator = document.getElementById('aiIndicator');
        let isPinned = false;
        if (aiCard) {
            aiCard.addEventListener('mouseenter', () => {
                if (!isPinned) aiCard.classList.add('buka');
            });
            aiCard.addEventListener('mouseleave', () => {
                if (!isPinned) aiCard.classList.remove('buka');
            });
            aiCard.addEventListener('click', () => {
                isPinned = !isPinned;
                if (isPinned) {
                    aiCard.classList.add('buka');
                    aiIndicator.innerHTML = 'Tersemat';
                    aiIndicator.style.background = '#ffcc00';
                    aiIndicator.style.color = '#002b5c';
                } else {
                    aiIndicator.innerHTML = 'Klik untuk baca';
                    aiIndicator.style.background = '#f4f6f9';
                    aiIndicator.style.color = '#6c757d';
                }
            });
        }

        document.getElementById('searchRekap')?.addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.rekap-row').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
            });
        });

        document.getElementById('searchGaleri')?.addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.galeri-item').forEach(item => {
                item.style.display = item.textContent.toLowerCase().includes(filter) ? '' : 'none';
            });
        });

        const ctx = document.getElementById('grafikPrestasi')?.getContext('2d');
        if (ctx) {
            Chart.defaults.font.family = "'Poppins', sans-serif";
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                    datasets: [{
                        label: 'Pertumbuhan Prestasi',
                        data: <?php echo json_encode($data_jumlah_per_bulan); ?>,
                        borderColor: '#ffcc00',
                        backgroundColor: 'rgba(255, 204, 0, 0.2)',
                        borderWidth: 3,
                        pointBackgroundColor: '#002b5c',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        fill: true,
                        tension: 0.4
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