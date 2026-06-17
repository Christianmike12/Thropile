<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Kepala Sekolah") {
    header("Location: ../../index.php");
    exit();
}

// ================= LOGIKA PENGATURAN AKUN KEPSEK (DIRI SENDIRI) =================
if (isset($_POST['edit_profil_kepsek'])) {
    $nip_kepsek  = $_POST['nip_kepsek'];
    $nama_kepsek = $_POST['nama_kepsek'];
    $user_kepsek = $_POST['user_kepsek'];
    $pass_kepsek = $_POST['pass_kepsek'];

    mysqli_query($conn, "UPDATE kepala_sekolah SET nama_kepala_sekolah='$nama_kepsek', username='$user_kepsek', PASSWORD='$pass_kepsek' WHERE nip='$nip_kepsek'");

    // Update session
    $_SESSION['nama'] = $nama_kepsek;
    $_SESSION['username'] = $user_kepsek;

    echo "<script>alert('Profil Kepala Sekolah berhasil diperbarui!'); window.location='dashboard.php';</script>";
    exit();
}

$active_tab = (isset($_GET['filter_mode']) || isset($_GET['tahun'])) ? 'rekap' : 'indikator';

// ================= TAB 1: INDIKATOR =================
$tahun_ini  = date('Y');
$bulan_ini  = date('m');
$bulan_lalu = date('m', strtotime('-1 month'));

$t_akademik     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM prestasi WHERE status_data='Approved' AND kategori LIKE '%Akademik%'"))['jml'] ?? 0;
$t_non_akademik = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM prestasi WHERE status_data='Approved' AND kategori NOT LIKE '%Akademik%'"))['jml'] ?? 0;

$data_jumlah_per_bulan = [];
for ($i = 1; $i <= 12; $i++) {
    $q = mysqli_query($conn, "SELECT COUNT(*) as jml FROM prestasi WHERE status_data='Approved' AND YEAR(tanggal_pelaksanaan)='$tahun_ini' AND MONTH(tanggal_pelaksanaan)='$i'");
    $data_jumlah_per_bulan[] = mysqli_fetch_assoc($q)['jml'];
}

$ai_sekarang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM prestasi WHERE status_data='Approved' AND YEAR(tanggal_pelaksanaan)='$tahun_ini' AND MONTH(tanggal_pelaksanaan)='$bulan_ini'"))['jml'];
$ai_lalu     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM prestasi WHERE status_data='Approved' AND YEAR(tanggal_pelaksanaan)='$tahun_ini' AND MONTH(tanggal_pelaksanaan)='$bulan_lalu'"))['jml'];

if ($ai_sekarang == 0 && $ai_lalu == 0) {
    $teks_ai = "Data belum mencukupi untuk analisis prediktif.";
} elseif ($ai_sekarang > $ai_lalu) {
    $kenaikan = $ai_sekarang - $ai_lalu;
    $teks_ai = "⚡ <b>Insight Cerdas:</b> Luar biasa! Terdapat tren <b>kenaikan $kenaikan prestasi</b> di bulan ini dibandingkan bulan lalu. Algoritma memprediksi semangat kompetitif siswa sedang tinggi.";
} elseif ($ai_sekarang < $ai_lalu) {
    $penurunan = $ai_lalu - $ai_sekarang;
    $teks_ai = "⚠️ <b>Insight Cerdas:</b> Perhatian. Terdapat <b>penurunan $penurunan prestasi</b> di bulan ini. Sistem menyarankan evaluasi peningkatan motivasi kompetisi siswa.";
} else {
    $teks_ai = "📊 <b>Insight Cerdas:</b> Performa stabil. Jumlah prestasi bulan ini sama dengan bulan lalu.";
}

// ================= TAB 2: REKAP PRESTASI & FILTER =================
$bulanIndo = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

$filter_mode    = isset($_GET['filter_mode']) ? $_GET['filter_mode'] : 'tahun';
$tahun_filter   = isset($_GET['tahun'])   ? (int)$_GET['tahun']   : (int)date('Y');
$bulan_filter   = isset($_GET['bulan'])   ? (int)$_GET['bulan']   : (int)date('n');
$ta_awal_filter = isset($_GET['ta_awal']) ? (int)$_GET['ta_awal'] : ((int)date('n') >= 7 ? (int)date('Y') : (int)date('Y') - 1);

switch ($filter_mode) {
    case 'bulan':
        $where_filter = "YEAR(p.tanggal_pelaksanaan)='$tahun_filter' AND MONTH(p.tanggal_pelaksanaan)='$bulan_filter'";
        $label_filter = "Bulan " . $bulanIndo[$bulan_filter - 1] . " $tahun_filter";
        $judul_periode = $bulanIndo[$bulan_filter - 1] . " " . $tahun_filter;
        $narasi = "Dokumen rekapitulasi ini memuat data prestasi siswa yang telah diverifikasi secara resmi oleh pihak sekolah pada periode bulan <b>$judul_periode</b>. Data digunakan sebagai bahan dokumentasi, evaluasi capaian siswa, serta arsip pelaporan prestasi akademik dan non-akademik sekolah.";
        break;
    case 'ta':
        $ta_akhir_filter = $ta_awal_filter + 1;
        $where_filter = "((YEAR(p.tanggal_pelaksanaan)='$ta_awal_filter' AND MONTH(p.tanggal_pelaksanaan) >= 7) OR (YEAR(p.tanggal_pelaksanaan)='$ta_akhir_filter' AND MONTH(p.tanggal_pelaksanaan) <= 6))";
        $label_filter = "Tahun Akademik $ta_awal_filter/$ta_akhir_filter";
        $judul_periode = "$ta_awal_filter / $ta_akhir_filter";
        $narasi = "Rekapitulasi berikut merupakan dokumentasi resmi capaian prestasi siswa selama Tahun Akademik <b>$judul_periode</b> yang telah melalui proses validasi dan verifikasi oleh pihak sekolah.";
        break;
    default:
        $filter_mode  = 'tahun';
        $where_filter = "YEAR(p.tanggal_pelaksanaan)='$tahun_filter'";
        $label_filter = "Tahun $tahun_filter";
        $judul_periode = $tahun_filter;
        $narasi = "Rekapitulasi berikut memuat keseluruhan data prestasi siswa tingkat sekolah pada tahun <b>$judul_periode</b> yang telah tervalidasi sebagai bagian dari dokumentasi resmi sekolah.";
        break;
}

// ================= EXPORT MS WORD =================
if (isset($_GET['export']) && $_GET['export'] == 'word') {
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: application/vnd.ms-word");
    $safe_label = preg_replace('/[^A-Za-z0-9_]/', '_', $label_filter);
    header("Content-Disposition: attachment; filename=Rekap_Prestasi_SMANSA_{$safe_label}.doc");

    $q_wd = mysqli_query($conn, "SELECT p.*, s.nama_siswa, s.kelas FROM prestasi p JOIN siswa s ON p.nisn = s.nisn WHERE p.status_data='Approved' AND $where_filter ORDER BY FIELD(p.tingkat,'Internasional','Nasional','Provinsi','Kota/Kabupaten'), p.peringkat ASC, s.kelas ASC");

    $logo_path    = '../../assets/images/SMANSA.png';
    $stempel_path = '../../assets/images/stempel.PNG';
    $ttd_path     = '../../assets/images/tandatangan.png';

    $logo_base64    = file_exists($logo_path)    ? base64_encode(file_get_contents($logo_path))    : '';
    $stempel_base64 = file_exists($stempel_path) ? base64_encode(file_get_contents($stempel_path)) : '';
    $ttd_base64     = file_exists($ttd_path)     ? base64_encode(file_get_contents($ttd_path))     : '';

    $kepsek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM kepala_sekolah LIMIT 1"));
    $tanggal_cetak = date('d') . " " . $bulanIndo[date('n') - 1] . " " . date('Y');
?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">

    <head>
        <meta charset="UTF-8">
        <style>
            @page {
                margin: 2.5cm 2.5cm 2.5cm 3cm;
            }

            body {
                font-family: "Times New Roman", Times, serif;
                font-size: 11pt;
                color: #000000;
                line-height: 1.5;
            }
        </style>
    </head>

    <body>
        <table width="100%" style="border-bottom: 3px double #000000; margin-bottom: 20px;">
            <tr>
                <td width="15%" align="center" valign="middle">
                    <?php if ($logo_base64): ?><img src="data:image/png;base64,<?php echo $logo_base64; ?>" width="80"><?php endif; ?>
                </td>
                <td width="85%" align="center">
                    <div style="font-size:12pt;">PEMERINTAH PROVINSI JAWA TIMUR</div>
                    <div style="font-size:12pt;">DINAS PENDIDIKAN</div>
                    <div style="font-size:16pt; font-weight:bold;">SMA NEGERI 1 KESAMBEN</div>
                    <div style="font-size:9.5pt;">Jalan Bromo Kesamben, Blitar 66191. Telepon (0342) 331397</div>
                    <div style="font-size:9.5pt;">Website: www.sman1kesamben.sch.id | Email: info@sman1kesamben.com</div>
                </td>
            </tr>
        </table>

        <div style="text-align: center; font-weight: bold; font-size: 13pt; margin-bottom: 5px;">REKAPITULASI PRESTASI SISWA</div>
        <div style="text-align: center; font-size: 11pt; margin-bottom: 20px;">PERIODE: <?php echo strtoupper($judul_periode); ?></div>

        <p style="text-align: justify; text-indent: 0; font-size: 11pt; margin-bottom: 20px;">
            <?php echo $narasi; ?>
        </p>

        <table border="1" cellspacing="0" cellpadding="6" width="100%" style="border-collapse: collapse; font-size: 10pt; text-align: center;">
            <thead>
                <tr style="font-weight: bold;">
                    <th width="5%">NO</th>
                    <th width="20%">NAMA SISWA</th>
                    <th width="10%">KELAS</th>
                    <th width="30%">NAMA KOMPETISI / KEJUARAAN</th>
                    <th width="15%">KATEGORI</th>
                    <th width="10%">TINGKAT</th>
                    <th width="10%">HASIL</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                if (mysqli_num_rows($q_wd) > 0) {
                    while ($row = mysqli_fetch_assoc($q_wd)) {
                        $kategori_tampil = !empty($row['kategori']) ? htmlspecialchars($row['kategori']) : '-';
                        $tingkat_tampil  = !empty($row['tingkat'])  ? htmlspecialchars($row['tingkat'])  : '-';
                        $hasil_tampil    = !empty($row['peringkat'])  ? htmlspecialchars($row['peringkat'])  : '-';

                        echo "<tr>
                                <td align='center'>$no</td>
                                <td align='left'><b>" . htmlspecialchars($row['nama_siswa']) . "</b></td>
                                <td align='center'>" . htmlspecialchars($row['kelas']) . "</td>
                                <td align='left'>" . htmlspecialchars($row['nama_lomba']) . "</td>
                                <td align='center'>{$kategori_tampil}</td>
                                <td align='center'>{$tingkat_tampil}</td>
                                <td align='center'>{$hasil_tampil}</td>
                              </tr>";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='7' align='center' style='padding:15px;'>Belum ada data prestasi resmi.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <br><br>

        <table width="100%" border="0" cellpadding="0" cellspacing="0">
            <tr>
                <td width="60%"></td>
                <td width="40%" align="center" style="font-size:11pt;">
                    Kesamben, <?php echo $tanggal_cetak; ?><br>
                    Kepala SMAN 1 Kesamben,<br><br>
                    <table border="0" cellpadding="0" cellspacing="0" align="center">
                        <tr>
                            <td valign="middle"><?php if ($stempel_base64): ?><img src="data:image/png;base64,<?php echo $stempel_base64; ?>" width="80"><?php endif; ?></td>
                            <td valign="middle" style="padding-left:10px;"><?php if ($ttd_base64): ?><img src="data:image/png;base64,<?php echo $ttd_base64; ?>" width="110"><?php endif; ?></td>
                        </tr>
                    </table>
                    <b><u><?php echo htmlspecialchars($kepsek['nama_kepala_sekolah'] ?? ''); ?></u></b><br>
                    NIP. <?php echo htmlspecialchars($kepsek['nip'] ?? ''); ?>
                </td>
            </tr>
        </table>
    </body>

    </html>
<?php
    exit();
}

// ================= DATA VIEW WEB REKAP =================
$q_stats = mysqli_query($conn, "SELECT tingkat, COUNT(*) as jml FROM prestasi p WHERE status_data='Approved' AND $where_filter GROUP BY tingkat");
$stats = ['Internasional' => 0, 'Nasional' => 0, 'Provinsi' => 0, 'Kota/Kabupaten' => 0];
while ($st = mysqli_fetch_assoc($q_stats)) {
    if (isset($stats[$st['tingkat']])) $stats[$st['tingkat']] = $st['jml'];
}

$t_total    = array_sum($stats);
$t_intl     = $stats['Internasional'];
$t_nasional = $stats['Nasional'];
$t_provinsi = $stats['Provinsi'];
$t_kota     = $stats['Kota/Kabupaten'];

$q_rekap = mysqli_query($conn, "SELECT p.*, s.nama_siswa, s.kelas FROM prestasi p JOIN siswa s ON p.nisn = s.nisn WHERE p.status_data='Approved' AND $where_filter ORDER BY FIELD(p.tingkat,'Internasional','Nasional','Provinsi','Kota/Kabupaten'), p.peringkat ASC, s.kelas ASC");

$tahun_sekarang = (int)date('Y');
$tahun_list = [];
for ($i = $tahun_sekarang; $i >= 2020; $i--) {
    $tahun_list[] = $i;
}
$res_tahun_tmp = mysqli_query($conn, "SELECT DISTINCT YEAR(tanggal_pelaksanaan) as tahun FROM prestasi WHERE status_data='Approved'");
while ($yt = mysqli_fetch_assoc($res_tahun_tmp)) {
    if (!in_array((int)$yt['tahun'], $tahun_list)) {
        $tahun_list[] = (int)$yt['tahun'];
    }
}
rsort($tahun_list);

$export_url_params = http_build_query(['export' => 'word', 'filter_mode' => $filter_mode, 'tahun' => $tahun_filter, 'bulan' => $bulan_filter, 'ta_awal' => $ta_awal_filter]);
$cetak_url_params = http_build_query(['tahun' => $tahun_filter, 'filter_mode' => $filter_mode, 'bulan' => $bulan_filter, 'ta_awal' => $ta_awal_filter]);

// AMBIL DATA PROFIL KEPSEK YANG LAGI LOGIN
$user_aktif = $_SESSION['username'] ?? '';
$q_profil_kepsek = mysqli_query($conn, "SELECT * FROM kepala_sekolah WHERE username='$user_aktif'");
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
            <div class="brand-wrapper">
                <img src="../../assets/images/SMANSA.png" alt="Logo" width="35">
                <a href="#" class="brand-logo">TROPHILE</a>
            </div>

            <!-- DROPDOWN PROFIL KEPSEK -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" style="border: 1px solid rgba(255,255,255,0.3); padding: 6px 15px; border-radius: 8px;">
                    <span class="me-2 fw-medium d-none d-md-block">Halo, <?php echo $_SESSION['nama']; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; border:none; margin-top:10px;">
                    <li>
                        <h6 class="dropdown-header text-muted">Akses Kepala Sekolah</h6>
                    </li>
                    <li><a class="dropdown-item fw-medium" href="#" data-bs-toggle="modal" data-bs-target="#editProfilKepsek">⚙️ Pengaturan Akun</a></li>
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
            <div class="header-title">Sistem Informasi Kepala Sekolah</div>

            <ul class="nav nav-pills custom-pills" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link <?php echo $active_tab == 'indikator' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-indikator">Indikator Utama</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link <?php echo $active_tab == 'rekap' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-rekap">Rekapitulasi Prestasi</button>
                </li>
            </ul>

            <div class="tab-content">
                <!-- ================= TAB 1: INDIKATOR UTAMA ================= -->
                <div class="tab-pane fade <?php echo $active_tab == 'indikator' ? 'show active' : ''; ?>" id="tab-indikator">

                    <div class="ai-card-wrapper mt-2">
                        <div class="ai-card" id="aiCard">
                            <div class="ai-header">
                                <div style="display:flex;align-items:center;">
                                    <span style="font-size:20px;margin-right:10px;">🤖</span>
                                    <h5 style="margin:0;font-weight:700;color:#002b5c;font-size:15px;">Trophile AI™ Insights</h5>
                                </div>
                                <span class="ai-indicator" id="aiIndicator">Klik untuk baca 📌</span>
                            </div>
                            <div class="ai-content">
                                <p><?php echo $teks_ai; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="stat-box" style="border-left-color: #002b5c;">
                                <p>TOTAL PRESTASI AKADEMIK</p>
                                <h3 class="text-navy"><?php echo $t_akademik; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="stat-box" style="border-left-color: #002b5c;">
                                <p>TOTAL PRESTASI NON-AKADEMIK</p>
                                <h3 class="text-navy"><?php echo $t_non_akademik; ?></h3>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-navy mb-3" style="font-size: 1.1rem;">Grafik Pertumbuhan Prestasi <?php echo $tahun_ini; ?></h6>
                    <div style="height:320px;width:100%; border: 1px solid #e1e5eb; border-radius: 12px; padding: 20px;">
                        <canvas id="grafikPrestasi"></canvas>
                    </div>
                </div>

                <!-- ================= TAB 2: REKAP PRESTASI ================= -->
                <div class="tab-pane fade <?php echo $active_tab == 'rekap' ? 'show active' : ''; ?>" id="tab-rekap">

                    <div class="filter-box">
                        <form method="GET" id="filterForm">
                            <div class="d-flex align-items-end gap-3 flex-wrap">
                                <div>
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Mode Filter</label>
                                    <select name="filter_mode" id="filter_mode" class="form-select" onchange="this.form.submit()">
                                        <option value="tahun" <?php echo $filter_mode == 'tahun' ? 'selected' : ''; ?>>Per Tahun</option>
                                        <option value="bulan" <?php echo $filter_mode == 'bulan' ? 'selected' : ''; ?>>Per Bulan</option>
                                        <option value="ta" <?php echo $filter_mode == 'ta' ? 'selected' : ''; ?>>Tahun Akademik</option>
                                    </select>
                                </div>

                                <div id="grp-tahun" style="display:<?php echo $filter_mode == 'ta' ? 'none' : 'block'; ?>;">
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Tahun</label>
                                    <select name="tahun" class="form-select" onchange="this.form.submit()">
                                        <?php foreach ($tahun_list as $yr): ?><option value="<?php echo $yr; ?>" <?php echo $yr == $tahun_filter ? 'selected' : ''; ?>><?php echo $yr; ?></option><?php endforeach; ?>
                                    </select>
                                </div>

                                <div id="grp-bulan" style="display:<?php echo $filter_mode == 'bulan' ? 'block' : 'none'; ?>;">
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Bulan</label>
                                    <select name="bulan" class="form-select" onchange="this.form.submit()">
                                        <?php foreach ($bulanIndo as $i => $bn): ?><option value="<?php echo $i + 1; ?>" <?php echo ($i + 1) == $bulan_filter ? 'selected' : ''; ?>><?php echo $bn; ?></option><?php endforeach; ?>
                                    </select>
                                </div>

                                <div id="grp-ta" style="display:<?php echo $filter_mode == 'ta' ? 'block' : 'none'; ?>;">
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Tahun Awal TA</label>
                                    <select name="ta_awal" class="form-select" onchange="this.form.submit()">
                                        <?php
                                        $ta_options = range((int)date('Y'), 2020);
                                        foreach ($ta_options as $ya): ?>
                                            <option value="<?php echo $ya; ?>" <?php echo $ya == $ta_awal_filter ? 'selected' : ''; ?>><?php echo "$ya/" . ($ya + 1); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="ms-auto d-flex gap-2">
                                    <a href="cetak_rekap.php?<?php echo $cetak_url_params; ?>" target="_blank" class="btn btn-edit-outline fw-bold text-nowrap">🖨️ Cetak Resmi</a>
                                    <a href="dashboard.php?<?php echo $export_url_params; ?>" class="btn btn-hapus-outline fw-bold text-nowrap">📝 Export Word</a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- 5 KOLOM KOTAK -->
                    <div class="row mb-4 g-3">
                        <div class="col-6 col-md">
                            <div class="stat-box" style="border-left-color: #002b5c;">
                                <p>TOTAL PRESTASI</p>
                                <h3 class="text-navy"><?php echo $t_total; ?></h3>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="stat-box" style="border-left-color: #dc3545;">
                                <p>INTERNASIONAL</p>
                                <h3 class="text-danger"><?php echo $t_intl; ?></h3>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="stat-box" style="border-left-color: #0d6efd;">
                                <p>NASIONAL</p>
                                <h3 class="text-primary"><?php echo $t_nasional; ?></h3>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="stat-box" style="border-left-color: #17a2b8;">
                                <p>PROVINSI</p>
                                <h3 class="text-info"><?php echo $t_provinsi; ?></h3>
                            </div>
                        </div>
                        <div class="col-6 col-md">
                            <div class="stat-box" style="border-left-color: #ffcc00;">
                                <p>KOTA/KAB</p>
                                <h3 style="color:#d97706;"><?php echo $t_kota; ?></h3>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3 mt-4 flex-wrap gap-3">
                        <h6 class="fw-bold m-0 text-navy" style="font-size: 1.1rem;">Rincian Data - <?php echo htmlspecialchars($label_filter); ?></h6>
                        <input type="text" id="searchRekap" class="form-control search-input" placeholder="Cari Siswa atau Lomba..." style="width: 280px;">
                    </div>

                    <div class="table-wrapper">
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
                            <tbody>
                                <?php
                                $no = 1;
                                if (mysqli_num_rows($q_rekap) > 0) {
                                    while ($r = mysqli_fetch_assoc($q_rekap)) {
                                        $warna = match ($r['tingkat']) {
                                            'Internasional' => 'bg-danger',
                                            'Nasional' => 'bg-primary',
                                            'Provinsi' => 'bg-info text-dark',
                                            default => 'bg-secondary'
                                        };
                                        $file_aman = rawurlencode($r['file_sertifikat']);
                                        echo "<tr class='rekap-row'>
                                        <td><span class='badge-nip'>$no</span></td>
                                        <td class='text-start fw-medium r-nama'>{$r['nama_siswa']}</td>
                                        <td><span class='badge-nip'>{$r['kelas']}</span></td>
                                        <td class='text-start r-lomba'>{$r['nama_lomba']}</td>
                                        <td><span class='badge $warna px-2'>{$r['tingkat']}</span></td>
                                        <td><span class='badge bg-warning text-dark px-2'>{$r['peringkat']}</span></td>
                                        <td><a href='../../assets/uploads/$file_aman' target='_blank' class='btn btn-edit-outline'>Lihat</a></td>
                                      </tr>";
                                        $no++;
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='py-4 text-muted fw-medium'>Belum ada data prestasi pada periode ini.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PENGATURAN AKUN KEPSEK -->
    <div class="modal fade text-start" id="editProfilKepsek" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Pengaturan Akun Kepala Sekolah</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="nip_kepsek" value="<?php echo $dt_profil_kepsek['nip'] ?? ''; ?>">

                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">NIP (Nomor Induk Pegawai)</label>
                        <input type="text" class="form-control bg-light" value="<?php echo $dt_profil_kepsek['nip'] ?? ''; ?>" readonly>
                        <div class="form-text small">*NIP dikunci oleh sistem sebagai identitas utama.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">Nama Lengkap & Gelar</label>
                        <input type="text" name="nama_kepsek" class="form-control" value="<?php echo $dt_profil_kepsek['nama_kepala_sekolah'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-navy fw-semibold small">Username Login</label>
                        <input type="text" name="user_kepsek" class="form-control" value="<?php echo $dt_profil_kepsek['username'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-navy fw-semibold small">Password Akun</label>
                        <input type="text" name="pass_kepsek" class="form-control" value="<?php echo $dt_profil_kepsek['PASSWORD'] ?? ''; ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" name="edit_profil_kepsek" class="btn btn-action w-100" style="background-color: #ffcc00; color:#002b5c; font-weight:bold; border-radius:8px; padding:10px;">Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // AI Logic
        const aiCard = document.getElementById('aiCard');
        const aiIndicator = document.getElementById('aiIndicator');
        let isPinned = false;

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
                aiIndicator.innerHTML = '📌 Tersemat';
                aiIndicator.style.background = '#ffcc00';
                aiIndicator.style.color = '#002b5c';
            } else {
                aiIndicator.innerHTML = 'Klik untuk baca 📌';
                aiIndicator.style.background = '#f4f6f9';
                aiIndicator.style.color = '#6c757d';
            }
        });

        // Chart Logic
        const ctx = document.getElementById('grafikPrestasi').getContext('2d');
        const dataDariPHP = <?php echo json_encode($data_jumlah_per_bulan); ?>;
        Chart.defaults.font.family = "'Poppins', sans-serif";
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [{
                    label: 'Prestasi',
                    data: dataDariPHP,
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

        // Search Rekap Logic
        document.getElementById('searchRekap').addEventListener('input', function() {
            let filter = this.value.toLowerCase();
            document.querySelectorAll('.rekap-row').forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    </script>
</body>

</html>