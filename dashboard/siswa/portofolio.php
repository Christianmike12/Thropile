<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Siswa") {
    header("Location: ../../index.php");
    exit();
}

$nisn_siswa = isset($_SESSION['nisn']) ? $_SESSION['nisn'] : (isset($_SESSION['username']) ? $_SESSION['username'] : '');

$query_siswa = mysqli_query($conn, "SELECT * FROM siswa WHERE nisn = '$nisn_siswa'");
$data_siswa  = mysqli_fetch_assoc($query_siswa);

$query_prestasi = mysqli_query($conn, "SELECT * FROM prestasi WHERE nisn = '$nisn_siswa' AND status_data = 'Approved' ORDER BY tahun DESC, id_prestasi DESC");

$query_kepsek = mysqli_query($conn, "SELECT * FROM kepala_sekolah LIMIT 1");
$data_kepsek  = mysqli_fetch_assoc($query_kepsek);

$bulanIndo = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
$tanggal_cetak = date('d') . " " . $bulanIndo[date('n') - 1] . " " . date('Y');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Portofolio - Trophile</title>
    <link rel="stylesheet" href="../../assets/css/siswa_portofolio.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="kop-surat">
        <img src="../../assets/images/logo.png" alt="Logo" style="position:absolute;left:15px;top:0;width:120px;height:auto;">
        <h2>Student Achievement Management System<br>PORTOFOLIO PRESTASI SISWA</h2>
        <h1><b>TROPHILE</b></h1>
        <p>Dokumen Rekam Jejak Prestasi yang Dihasilkan oleh Sistem</p>
    </div>

    <div class="judul-dokumen">Portofolio Rekam Jejak Prestasi Siswa</div>

    <table class="tabel-identitas">
        <tr>
            <td style="width:20%;">Nama Lengkap</td>
            <td style="width:3%;">:</td>
            <td style="width:77%;font-weight:bold;"><?php echo htmlspecialchars($data_siswa['nama_siswa'] ?? '-'); ?></td>
        </tr>
        <tr>
            <td>Nomor Induk (NISN)</td>
            <td>:</td>
            <td><?php echo htmlspecialchars($data_siswa['nisn'] ?? '-'); ?></td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td>:</td>
            <td><?php echo htmlspecialchars($data_siswa['kelas'] ?? '-'); ?></td>
        </tr>
    </table>

    <div class="narasi">
        Dokumen ini merupakan portofolio rekam jejak prestasi siswa yang tersimpan dalam Sistem Informasi Manajemen Prestasi Siswa (TROPHILE). Seluruh data prestasi yang ditampilkan telah melalui proses verifikasi berdasarkan dokumen pendukung yang tersedia pada sistem
    </div>

    <table class="tabel-prestasi">
        <thead>
            <tr>
                <th style="width:5%;" class="text-center">No</th>
                <th style="width:38%;">Nama Kompetisi / Kejuaraan</th>
                <th style="width:15%;" class="text-center">Kategori</th>
                <th style="width:15%;" class="text-center">Tingkat</th>
                <th style="width:12%;" class="text-center">Tahun</th>
                <th style="width:15%;" class="text-center">Hasil</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $no = 1;
            if (mysqli_num_rows($query_prestasi) > 0) {
                while ($row = mysqli_fetch_assoc($query_prestasi)) {
                    $tahun_lomba = !empty($row['tahun']) ? $row['tahun'] : (!empty($row['tanggal_pelaksanaan']) ? date('Y', strtotime($row['tanggal_pelaksanaan'])) : '-');
                    echo "<tr>
                        <td class='text-center'>$no</td>
                        <td><strong>" . htmlspecialchars($row['nama_lomba']) . "</strong></td>
                        <td class='text-center'>" . htmlspecialchars($row['kategori']) . "</td>
                        <td class='text-center'>" . htmlspecialchars($row['tingkat']) . "</td>
                        <td class='text-center'>$tahun_lomba</td>
                        <td class='text-center'>" . htmlspecialchars($row['peringkat']) . "</td>
                    </tr>";
                    $no++;
                }
            } else {
                echo "<tr><td colspan='6' class='text-center' style='padding:20px;color:#555;font-style:italic;'>Belum ada rekaman data prestasi resmi yang tervalidasi.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <table class="ttd-container">
        <tr>
            <td style="width: 60%;"></td>
            <td>
                <div class="ttd-box">
                    Kesamben, <?php echo $tanggal_cetak; ?><br>
                    Mengetahui,<br>
                    Kepala Seko,<br><br>
                    <div class="space-ttd">
                        <img src="../../assets/images/tandatangan.png" alt="Tanda Tangan Kepsek" class="ttd-image">
                        <img src="../../assets/images/stempel.png" alt="Stempel Resmi" class="stempel-image">
                    </div>
                    <span style="text-decoration:underline;font-weight:bold;position:relative;z-index:3;">
                        <?php echo htmlspecialchars($data_kepsek['nama_kepala_sekolah'] ?? ''); ?>
                    </span><br>
                    NIP. <?php echo htmlspecialchars($data_kepsek['nip'] ?? ''); ?>
                </div>
            </td>
        </tr>
    </table>

    <script>
        window.print();
    </script>
</body>

</html>