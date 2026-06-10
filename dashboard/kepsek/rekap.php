<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Kepala Sekolah") {
    header("Location: ../../index.php");
    exit();
}

$filter_mode = isset($_GET['filter_mode']) ? $_GET['filter_mode'] : 'tahun';

$tahun_filter   = isset($_GET['tahun'])   ? (int)$_GET['tahun']   : (int)date('Y');
$bulan_filter   = isset($_GET['bulan'])   ? (int)$_GET['bulan']   : (int)date('n');
$ta_awal_filter = isset($_GET['ta_awal']) ? (int)$_GET['ta_awal'] : ((int)date('n') >= 7 ? (int)date('Y') : (int)date('Y') - 1);

switch ($filter_mode) {
    case 'bulan':
        $where_filter = "YEAR(p.tanggal_pelaksanaan)='$tahun_filter' AND MONTH(p.tanggal_pelaksanaan)='$bulan_filter'";
        $label_filter = "Bulan " . ["", "Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"][$bulan_filter] . " $tahun_filter";
        break;
    case 'ta':
        $ta_akhir_filter = $ta_awal_filter + 1;
        $where_filter = "((YEAR(p.tanggal_pelaksanaan)='$ta_awal_filter' AND MONTH(p.tanggal_pelaksanaan) >= 7) OR (YEAR(p.tanggal_pelaksanaan)='$ta_akhir_filter' AND MONTH(p.tanggal_pelaksanaan) <= 6))";
        $label_filter = "Tahun Akademik $ta_awal_filter/$ta_akhir_filter";
        break;
    default:
        $filter_mode  = 'tahun';
        $where_filter = "YEAR(p.tanggal_pelaksanaan)='$tahun_filter'";
        $label_filter = "Tahun $tahun_filter";
        break;
}

$kepsek = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM kepala_sekolah LIMIT 1"));
$bulanIndo = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
$tanggal_cetak = date('d') . " " . $bulanIndo[date('n') - 1] . " " . date('Y');

if (isset($_GET['export']) && $_GET['export'] == 'word') {
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-Type: application/vnd.ms-word");
    $safe_label = preg_replace('/[^A-Za-z0-9_]/', '_', $label_filter);
    header("Content-Disposition: attachment; filename=Rekap_Prestasi_SMANSA_{$safe_label}.doc");

    $q_wd = mysqli_query(
        $conn,
        "SELECT p.*, s.nama_siswa, s.kelas
         FROM prestasi p
         JOIN siswa s ON p.nisn = s.nisn
         WHERE p.status_data='Approved' AND $where_filter
         ORDER BY FIELD(p.tingkat,'Internasional','Nasional','Provinsi','Kota/Kabupaten'), p.peringkat ASC, s.kelas ASC"
    );

    $logo_path    = '../../assets/images/SMANSA.png';
    $stempel_path = '../../assets/images/stempel.PNG';
    $ttd_path     = '../../assets/images/tandatangan.png';

    $logo_base64    = file_exists($logo_path)    ? base64_encode(file_get_contents($logo_path))    : '';
    $stempel_base64 = file_exists($stempel_path) ? base64_encode(file_get_contents($stempel_path)) : '';
    $ttd_base64     = file_exists($ttd_path)     ? base64_encode(file_get_contents($ttd_path))     : '';

    $stat_q = mysqli_query($conn, "SELECT tingkat, COUNT(*) as jml FROM prestasi p WHERE status_data='Approved' AND $where_filter GROUP BY tingkat");
    $stats_word = ['Internasional' => 0, 'Nasional' => 0, 'Provinsi' => 0, 'Kota/Kabupaten' => 0];
    while ($sw = mysqli_fetch_assoc($stat_q)) {
        if (isset($stats_word[$sw['tingkat']])) $stats_word[$sw['tingkat']] = $sw['jml'];
    }
    $total_word = array_sum($stats_word);
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
                font-size: 12pt;
                color: #000000;
                line-height: 1.5;
            }

            .kop-table {
                width: 100%;
                border-bottom: 3px double #000000;
                margin-bottom: 14pt;
            }

            .kop-table td {
                vertical-align: middle;
                padding: 4px 6px;
            }

            .judul-rekap {
                text-align: center;
                font-size: 13pt;
                font-weight: bold;
                margin: 10pt 0 4pt 0;
            }

            .sub-judul {
                text-align: center;
                font-size: 11pt;
                margin: 0 0 10pt 0;
            }

            .narasi {
                text-align: justify;
                font-size: 11pt;
                margin: 8pt 0 12pt 0;
            }

            .stat-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 14pt;
                font-size: 11pt;
            }

            .stat-table td {
                border: 1px solid #000;
                padding: 5px 10px;
                text-align: center;
            }

            .stat-table .lbl {
                text-align: left;
                font-weight: bold;
                background: #f2f2f2;
            }

            .tabel-data {
                border-collapse: collapse;
                width: 100%;
                font-size: 10.5pt;
            }

            .tabel-data th,
            .tabel-data td {
                border: 1px solid #000000;
                padding: 5px 7px;
            }

            .tabel-data th {
                background-color: #1a2b56;
                color: #ffffff;
                text-align: center;
                font-size: 10.5pt;
            }

            .tabel-data td {
                vertical-align: middle;
            }

            .ttd-area {
                margin-top: 18pt;
            }
        </style>
    </head>

    <body>
        <table class="kop-table">
            <tr>
                <td width="12%" align="center">
                    <?php if ($logo_base64): ?><img src="data:image/png;base64,<?php echo $logo_base64; ?>" width="80" height="85"><?php endif; ?>
                </td>
                <td width="88%" align="center">
                    <div style="font-size:11pt;">PEMERINTAH PROVINSI JAWA TIMUR</div>
                    <div style="font-size:11pt;">DINAS PENDIDIKAN</div>
                    <div style="font-size:16pt; font-weight:bold; letter-spacing:1px;">SMA NEGERI 1 KESAMBEN</div>
                    <div style="font-size:9.5pt;">Jalan Bromo Kesamben, Blitar 66191 &nbsp;|&nbsp; Telepon (0342) 331397</div>
                    <div style="font-size:9.5pt;">Website: www.sman1kesamben.sch.id &nbsp;|&nbsp; Email: info@sman1kesamben.com</div>
                </td>
            </tr>
        </table>

        <div class="judul-rekap">REKAPITULASI PRESTASI SISWA</div>
        <div class="sub-judul">SMA NEGERI 1 KESAMBEN &mdash; <?php echo strtoupper($label_filter); ?></div>

        <p class="narasi">Data berikut merupakan rekapitulasi prestasi siswa yang telah diverifikasi oleh pihak sekolah berdasarkan periode <b><?php echo $label_filter; ?></b> melalui Sistem Informasi Manajemen Prestasi Siswa (<b>Trophile</b>).</p>

        <table class="stat-table">
            <tr>
                <td class="lbl" width="40%">Total Prestasi Terverifikasi</td>
                <td width="15%"><b><?php echo $total_word; ?></b></td>
                <td class="lbl" width="15%">Internasional</td>
                <td width="15%"><?php echo $stats_word['Internasional']; ?></td>
            </tr>
            <tr>
                <td class="lbl">Nasional</td>
                <td><?php echo $stats_word['Nasional']; ?></td>
                <td class="lbl">Provinsi</td>
                <td><?php echo $stats_word['Provinsi']; ?></td>
            </tr>
        </table>

        <table class="tabel-data">
            <thead>
                <tr>
                    <th width="5%">No</th>
                    <th width="25%">Nama Siswa</th>
                    <th width="10%">Kelas</th>
                    <th width="30%">Nama Lomba</th>
                    <th width="10%">Kategori</th>
                    <th width="10%">Tingkat</th>
                    <th width="10%">Hasil</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                if (mysqli_num_rows($q_wd) > 0) {
                    while ($row = mysqli_fetch_assoc($q_wd)) {
                        $kategori_tampil = !empty($row['kategori']) ? htmlspecialchars($row['kategori']) : '-';
                        $tingkat_tampil  = !empty($row['tingkat'])  ? htmlspecialchars($row['tingkat'])  : '-';
                        $bg = match ($row['tingkat']) {
                            'Internasional' => '#fff3cd',
                            'Nasional' => '#cfe2ff',
                            'Provinsi' => '#d1ecf1',
                            default => '#f8f9fa'
                        };
                        echo "<tr style='background-color:{$bg};'>
                <td align='center'>$no</td><td><b>" . htmlspecialchars($row['nama_siswa']) . "</b></td><td align='center'>" . htmlspecialchars($row['kelas']) . "</td>
                <td>" . htmlspecialchars($row['nama_lomba']) . "</td><td align='center'>{$kategori_tampil}</td><td align='center'><b>{$tingkat_tampil}</b></td><td align='center'>" . htmlspecialchars($row['peringkat']) . "</td>
            </tr>";
                        $no++;
                    }
                } else {
                    echo "<tr><td colspan='7' align='center' style='padding:12px;'>Belum ada data prestasi resmi.</td></tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="ttd-area">
            <table width="100%" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="55%"></td>
                    <td width="45%" style="font-size:11pt;">
                        Kesamben, <?php echo $tanggal_cetak; ?><br>Kepala SMA Negeri 1 Kesamben,<br><br>
                        <table border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td valign="middle"><?php if ($stempel_base64): ?><img src="data:image/png;base64,<?php echo $stempel_base64; ?>" width="90" height="90"><?php endif; ?></td>
                                <td valign="middle" style="padding-left:8px;"><?php if ($ttd_base64): ?><img src="data:image/png;base64,<?php echo $ttd_base64; ?>" width="120" height="60"><?php endif; ?></td>
                            </tr>
                        </table>
                        <b><u><?php echo htmlspecialchars($kepsek['nama_kepala_sekolah'] ?? ''); ?></u></b><br>NIP. <?php echo htmlspecialchars($kepsek['nip'] ?? ''); ?>
                    </td>
                </tr>
            </table>
        </div>
    </body>

    </html>
<?php exit();
}

$res_tahun = mysqli_query($conn, "SELECT DISTINCT YEAR(tanggal_pelaksanaan) as tahun FROM prestasi WHERE status_data='Approved' ORDER BY tahun DESC");

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

$tahun_list = [];
$res_tahun_tmp = mysqli_query($conn, "SELECT DISTINCT YEAR(tanggal_pelaksanaan) as tahun FROM prestasi WHERE status_data='Approved' ORDER BY tahun DESC");
while ($yt = mysqli_fetch_assoc($res_tahun_tmp)) {
    $tahun_list[] = (int)$yt['tahun'];
}
if (!in_array((int)date('Y'), $tahun_list)) {
    array_unshift($tahun_list, (int)date('Y'));
}

$export_url_params = http_build_query(['export' => 'word', 'filter_mode' => $filter_mode, 'tahun' => $tahun_filter, 'bulan' => $bulan_filter, 'ta_awal' => $ta_awal_filter]);
$cetak_url_params = http_build_query(['tahun' => $tahun_filter, 'filter_mode' => $filter_mode, 'bulan' => $bulan_filter, 'ta_awal' => $ta_awal_filter]);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Rekap Prestasi — Trophile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/kepsek_dashboard.css">
</head>

<body>

    <nav class="navbar-custom">
        <div class="nav-container">
            <div class="d-flex align-items-center">
                <a href="dashboard.php" style="font-size:20px;font-weight:800;letter-spacing:1px;color:white;margin-right:15px;text-decoration:none;">SMAN 1 KESAMBEN</a>
                <div class="d-none d-md-flex align-items-center ms-3">
                    <a href="dashboard.php" class="nav-link-item">Dashboard</a>
                    <a href="rekap.php" class="nav-link-item active">Rekap Prestasi</a>
                </div>
            </div>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <div style="width:38px;height:38px;background-color:#fbc02d;color:#1a2b56;border-radius:50%;display:flex;justify-content:center;align-items:center;font-weight:bold;margin-right:10px;">
                        <?php echo substr($_SESSION['nama'], 0, 1); ?>
                    </div>
                    <span class="d-none d-md-inline" style="font-weight:600;"><?php echo $_SESSION['nama']; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><a class="dropdown-item text-danger fw-bold" href="../../logout.php">🚪 Keluar</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="floating-card mb-4">
            <form method="GET" id="filterForm">
                <div class="d-flex align-items-end gap-3 flex-wrap">
                    <div>
                        <label class="fw-bold mb-1" style="font-size:12px;">Mode Filter</label>
                        <select name="filter_mode" id="filter_mode" class="form-select" onchange="this.form.submit()">
                            <option value="tahun" <?php echo $filter_mode == 'tahun' ? 'selected' : ''; ?>>Per Tahun</option>
                            <option value="bulan" <?php echo $filter_mode == 'bulan' ? 'selected' : ''; ?>>Per Bulan</option>
                            <option value="ta" <?php echo $filter_mode == 'ta' ? 'selected' : ''; ?>>Tahun Akademik</option>
                        </select>
                    </div>

                    <div id="grp-tahun" style="display:<?php echo $filter_mode == 'ta' ? 'none' : 'block'; ?>;">
                        <label class="fw-bold mb-1" style="font-size:12px;">Tahun</label>
                        <select name="tahun" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($tahun_list as $yr): ?><option value="<?php echo $yr; ?>" <?php echo $yr == $tahun_filter ? 'selected' : ''; ?>><?php echo $yr; ?></option><?php endforeach; ?>
                        </select>
                    </div>

                    <div id="grp-bulan" style="display:<?php echo $filter_mode == 'bulan' ? 'block' : 'none'; ?>;">
                        <label class="fw-bold mb-1" style="font-size:12px;">Bulan</label>
                        <select name="bulan" class="form-select" onchange="this.form.submit()">
                            <?php foreach ($bulanIndo as $i => $bn): ?><option value="<?php echo $i + 1; ?>" <?php echo ($i + 1) == $bulan_filter ? 'selected' : ''; ?>><?php echo $bn; ?></option><?php endforeach; ?>
                        </select>
                    </div>

                    <div id="grp-ta" style="display:<?php echo $filter_mode == 'ta' ? 'block' : 'none'; ?>;">
                        <label class="fw-bold mb-1" style="font-size:12px;">Tahun Awal TA</label>
                        <select name="ta_awal" class="form-select" onchange="this.form.submit()">
                            <?php $ta_options = range((int)date('Y'), max(2018, (int)date('Y') - 10));
                            foreach ($ta_options as $ya): ?>
                                <option value="<?php echo $ya; ?>" <?php echo $ya == $ta_awal_filter ? 'selected' : ''; ?>><?php echo "$ya/" . ($ya + 1); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ms-auto d-flex gap-2">
                        <a href="cetak_rekap.php?<?php echo $cetak_url_params; ?>" target="_blank" class="btn btn-dark fw-bold">🖨️ Cetak Resmi</a>
                        <a href="rekap.php?<?php echo $export_url_params; ?>" class="btn btn-gold fw-bold">📝 Export Word</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="row mb-4 g-3">
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <p>TOTAL</p>
                    <h3><?php echo $t_total; ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <p>INTERNASIONAL</p>
                    <h3><?php echo $t_intl; ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <p>NASIONAL</p>
                    <h3><?php echo $t_nasional; ?></h3>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-box">
                    <p>PROVINSI</p>
                    <h3><?php echo $t_provinsi; ?></h3>
                </div>
            </div>
        </div>

        <div class="floating-card">
            <div class="header-title">Rekap Prestasi - <?php echo htmlspecialchars($label_filter); ?></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle text-center border">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th class="text-start">Nama Siswa</th>
                            <th>Kelas</th>
                            <th class="text-start">Lomba</th>
                            <th>Kategori</th>
                            <th>Tingkat</th>
                            <th>Hasil</th>
                            <th>Berkas</th>
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
                                echo "<tr>
                                <td>$no</td><td class='text-start fw-bold'>{$r['nama_siswa']}</td><td>{$r['kelas']}</td><td class='text-start'>{$r['nama_lomba']}</td>
                                <td>{$r['kategori']}</td><td><span class='badge $warna px-2'>{$r['tingkat']}</span></td><td><span class='badge bg-warning text-dark'>{$r['peringkat']}</span></td>
                                <td><a href='../../assets/uploads/$file_aman' target='_blank' class='btn btn-sm btn-outline-dark'>Lihat</a></td>
                              </tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='8' class='py-4 text-muted'>Belum ada data prestasi.</td></tr>";
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