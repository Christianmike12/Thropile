<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Kepala Sekolah") {
    header("Location: ../../index.php");
    exit();
}

$bulanIndo = [1 => "Januari", 2 => "Februari", 3 => "Maret", 4 => "April", 5 => "Mei", 6 => "Juni", 7 => "Juli", 8 => "Agustus", 9 => "September", 10 => "Oktober", 11 => "November", 12 => "Desember"];

$mode_filter = isset($_GET['filter_mode']) ? $_GET['filter_mode'] : 'tahun';
$tahun_filter = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$bulan_filter = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$ta_awal_filter = isset($_GET['ta_awal']) ? (int)$_GET['ta_awal'] : (int)date('Y');

$where = "p.status_data='Approved'";

if ($mode_filter == 'bulan') {
    $where .= " AND YEAR(p.tanggal_pelaksanaan)='$tahun_filter' AND MONTH(p.tanggal_pelaksanaan)='$bulan_filter'";
    $judul_periode = $bulanIndo[$bulan_filter] . " " . $tahun_filter;
    $narasi = "Dokumen rekapitulasi ini memuat data prestasi siswa yang telah diverifikasi secara resmi oleh pihak sekolah pada periode bulan <b>$judul_periode</b>. Data digunakan sebagai bahan dokumentasi, evaluasi capaian siswa, serta arsip pelaporan prestasi akademik dan non-akademik sekolah.";
} elseif ($mode_filter == 'ta') {
    $tahun_akhir = $ta_awal_filter + 1;
    $where .= " AND ((YEAR(p.tanggal_pelaksanaan)='$ta_awal_filter' AND MONTH(p.tanggal_pelaksanaan) >= 7) OR (YEAR(p.tanggal_pelaksanaan)='$tahun_akhir' AND MONTH(p.tanggal_pelaksanaan) <= 6))";
    $judul_periode = "$ta_awal_filter / $tahun_akhir";
    $narasi = "Rekapitulasi berikut merupakan dokumentasi resmi capaian prestasi siswa selama Tahun Akademik <b>$judul_periode</b> yang telah melalui proses validasi dan verifikasi oleh pihak sekolah.";
} else {
    $where .= " AND YEAR(p.tanggal_pelaksanaan)='$tahun_filter'";
    $judul_periode = $tahun_filter;
    $narasi = "Rekapitulasi berikut memuat keseluruhan data prestasi siswa tingkat sekolah pada tahun <b>$tahun_filter</b> yang telah tervalidasi sebagai bagian dari dokumentasi resmi sekolah.";
}

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
    <link rel="stylesheet" href="../../assets/css/kepsek_cetak.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="kop-surat">
        <img src="../../assets/images/SMANSA.png" alt="Logo SMANSA">
        <h2>PEMERINTAH PROVINSI JAWA TIMUR<br>DINAS PENDIDIKAN</h2>
        <h1>SMA NEGERI 1 KESAMBEN</h1>
        <p>Jalan Bromo Kesamben, Blitar 66191. Telepon (0342) 331397</p>
        <p>Website: <a href="http://www.sman1kesamben.sch.id" target="_blank">www.sman1kesamben.sch.id</a> | Email: <a href="mailto:info@sman1kesamben.com">info@sman1kesamben.com</a></p>
    </div>

    <div class="judul-dokumen">
        REKAPITULASI PRESTASI SISWA<br>
        <span style="font-size:13px;font-weight:normal;">PERIODE: <?php echo $judul_periode; ?></span>
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
            <?php
            $no = 1;
            if (mysqli_num_rows($q_rekap) > 0) {
                while ($row = mysqli_fetch_assoc($q_rekap)) {
                    $kategori_tampil = !empty($row['kategori']) ? $row['kategori'] : '-';
                    $tingkat_tampil = !empty($row['tingkat']) ? $row['tingkat'] : '-';
                    echo "<tr>
                        <td class='text-center'>$no</td>
                        <td><strong>{$row['nama_siswa']}</strong></td>
                        <td class='text-center'>{$row['kelas']}</td>
                        <td>{$row['nama_lomba']}</td>
                        <td class='text-center'>$kategori_tampil</td>
                        <td class='text-center'>$tingkat_tampil</td>
                        <td class='text-center'>{$row['peringkat']}</td>
                    </tr>";
                    $no++;
                }
            } else {
                echo "<tr><td colspan='7' class='text-center' style='padding:30px;color:#555;font-style:italic;'>Belum ada data prestasi resmi yang tervalidasi pada periode ini.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div class="ttd-container">
        <div class="ttd-box">
            Kesamben, <?php echo $tanggal_cetak; ?><br>
            Kepala SMAN 1 Kesamben,
            <div class="space-ttd">
                <img src="../../assets/images/tandatangan.png" alt="Tanda Tangan" class="ttd-image">
                <img src="../../assets/images/stempel.PNG" alt="Stempel" class="stempel-image">
            </div>
            <span style="text-decoration:underline;font-weight:bold;position:relative;z-index:3;">
                <?php echo $data_kepsek['nama_kepala_sekolah'] ?? ''; ?>
            </span><br>
            NIP. <?php echo $data_kepsek['nip'] ?? ''; ?>
        </div>
        <div style="clear:both;"></div>
    </div>
    <script>
        window.print();
    </script>
</body>

</html>