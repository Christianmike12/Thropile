<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Kepala Sekolah") {
    header("Location: ../../login.php");
    exit();
}

$bulanIndo = [1 => "Januari", 2 => "Februari", 3 => "Maret", 4 => "April", 5 => "Mei", 6 => "Juni", 7 => "Juli", 8 => "Agustus", 9 => "September", 10 => "Oktober", 11 => "November", 12 => "Desember"];

$mode_filter = mysqli_real_escape_string($conn, $_GET['filter_mode'] ?? 'all');
$tahun_filter = (int)($_GET['tahun'] ?? date('Y'));
$bulan_filter = (int)($_GET['bulan'] ?? date('n'));
$ta_awal_filter = (int)($_GET['ta_awal'] ?? date('Y'));
$tanggal_awal = mysqli_real_escape_string($conn, $_GET['tanggal_awal'] ?? date('Y-m-01'));
$tanggal_akhir = mysqli_real_escape_string($conn, $_GET['tanggal_akhir'] ?? date('Y-m-t'));


$where = "p.status_data='Approved'";

if ($mode_filter == 'bulan') {
    $where .= " AND YEAR(p.tanggal_pelaksanaan)='$tahun_filter' AND MONTH(p.tanggal_pelaksanaan)='$bulan_filter'";
    $judul_periode = "Bulan " . $bulanIndo[(int)$bulan_filter] . " " . $tahun_filter;
} elseif ($mode_filter == 'ta') {
    $tahun_akhir_ta = $ta_awal_filter + 1;
    $where .= " AND ((YEAR(p.tanggal_pelaksanaan)='$ta_awal_filter' AND MONTH(p.tanggal_pelaksanaan) >= 7) OR (YEAR(p.tanggal_pelaksanaan)='$tahun_akhir_ta' AND MONTH(p.tanggal_pelaksanaan) <= 6))";
    $judul_periode = "Tahun Akademik $ta_awal_filter / $tahun_akhir_ta";
} elseif ($mode_filter == 'rentang') {
    $start_date = date('Y-m-d', strtotime($tanggal_awal));
    $end_date = date('Y-m-d', strtotime($tanggal_akhir));
    $where .= " AND p.tanggal_pelaksanaan BETWEEN '$start_date' AND '$end_date'";
    $judul_periode = "Rentang Waktu: " . date('d M Y', strtotime($start_date)) . " s/d " . date('d M Y', strtotime($end_date));
} elseif ($mode_filter == 'tahun') {
    $where .= " AND YEAR(p.tanggal_pelaksanaan)='$tahun_filter'";
    $judul_periode = "Tahun $tahun_filter";
} else {
    $judul_periode = "Keseluruhan Waktu";
}

$narasi = "Sistem Informasi Manajemen Prestasi Siswa (Trophile) menyatakan bahwa rekapitulasi di bawah ini memuat rekam jejak prestasi resmi akademik maupun non-akademik siswa yang telah tervalidasi oleh pihak sekolah.";

$q_rekap = mysqli_query($conn, "SELECT p.*, s.nama_siswa, s.kelas FROM prestasi p JOIN siswa s ON p.nisn = s.nisn WHERE $where ORDER BY FIELD(p.tingkat,'Internasional','Nasional','Provinsi','Kota/Kabupaten'), p.peringkat ASC, s.kelas ASC");

$query_kepsek = mysqli_query($conn, "SELECT * FROM kepala_sekolah LIMIT 1");
$data_kepsek = mysqli_fetch_assoc($query_kepsek);
$tanggal_cetak = date('d') . " " . $bulanIndo[date('n')] . " " . date('Y');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Rekap Prestasi - Trophile</title>
    <link rel="stylesheet" href="../../assets/css/cetak_rekap.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="kop-surat">
        <img src="../../assets/images/logo.png" alt="Logo">
        <h2>Student Achievement Management System<br>PORTOFOLIO PRESTASI SISWA</h2>
        <h1><b>TROPHILE</b></h1>
        <p>Dokumen Rekam Jejak Prestasi yang Dihasilkan oleh Sistem</p>
    </div>

    <div class="judul-dokumen">
        REKAPITULASI PRESTASI SISWA<br>
        <span style="font-size:13px;font-weight:normal;text-decoration:none;">PERIODE: <?php echo strtoupper($judul_periode); ?></span>
    </div>

    <div class="narasi-resmi"><?php echo $narasi; ?></div>

    <table class="tabel-rekap">
        <thead>
            <tr>
                <th style="width:5%;">No</th>
                <th style="width:25%;">Nama Siswa</th>
                <th style="width:10%;">Kelas</th>
                <th style="width:30%;">Nama Kompetisi / Kejuaraan</th>
                <th style="width:10%;">Kategori</th>
                <th style="width:10%;">Tingkat</th>
                <th style="width:10%;">Hasil</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            if (mysqli_num_rows($q_rekap) > 0) {
                while ($row = mysqli_fetch_assoc($q_rekap)) { ?>
                    <tr>
                        <td class='text-center'><?php echo $no++; ?></td>
                        <td><strong><?php echo htmlspecialchars($row['nama_siswa']); ?></strong></td>
                        <td class='text-center'><?php echo htmlspecialchars($row['kelas']); ?></td>
                        <td><?php echo htmlspecialchars($row['nama_lomba']); ?></td>
                        <td class='text-center'><?php echo htmlspecialchars($row['kategori'] ?? '-'); ?></td>
                        <td class='text-center'><?php echo htmlspecialchars($row['tingkat'] ?? '-'); ?></td>
                        <td class='text-center'><?php echo htmlspecialchars($row['peringkat']); ?></td>
                    </tr>
            <?php }
            } else {
                echo "<tr><td colspan='7' class='text-center' style='padding:30px;color:#555;font-style:italic;'>Belum ada data prestasi resmi yang tervalidasi pada filter ini.</td></tr>";
            } ?>
        </tbody>
    </table>

    <div class="ttd-container">
        <div class="ttd-box">
            Kesamben, <?php echo $tanggal_cetak; ?>
            Mengetahui,<br>
            <br>Kepala Sekolah,
            <div class="space-ttd">
                <img src="../../assets/images/tandatangan.png" alt="Tanda Tangan" class="ttd-image">
                <img src="../../assets/images/stempel.png" alt="Stempel" class="stempel-image">
            </div>
            <span style="text-decoration:underline;font-weight:bold;position:relative;z-index:3;">
                <?php echo htmlspecialchars($data_kepsek['nama_kepala_sekolah'] ?? ''); ?>
            </span><br>
            NIP. <?php echo htmlspecialchars($data_kepsek['nip'] ?? ''); ?>
        </div>
        <div style="clear:both;"></div>
    </div>
    <script>
        window.print();
    </script>
</body>

</html>