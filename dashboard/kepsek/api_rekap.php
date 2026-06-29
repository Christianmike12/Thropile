<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Kepala Sekolah") {
    echo json_encode(['tabel' => '<tr><td colspan="7">Unauthorized</td></tr>', 'galeri' => '', 'modal' => '', 'stats' => []]);
    exit();
}

$filter_mode = $_POST['filter_mode'] ?? 'all';
$tahun_filter = $_POST['tahun'] ?? date('Y');
$bulan_filter = $_POST['bulan'] ?? date('n');
$ta_awal_filter = $_POST['ta_awal'] ?? date('Y');
$tanggal_awal = $_POST['tanggal_awal'] ?? date('Y-m-01');
$tanggal_akhir = $_POST['tanggal_akhir'] ?? date('Y-m-t');

$where = "p.status_data='Approved'";

if ($filter_mode == 'tahun') {
    $where .= " AND YEAR(p.tanggal_pelaksanaan)='$tahun_filter'";
} elseif ($filter_mode == 'bulan') {
    $where .= " AND YEAR(p.tanggal_pelaksanaan)='$tahun_filter' AND MONTH(p.tanggal_pelaksanaan)='$bulan_filter'";
} elseif ($filter_mode == 'ta') {
    $ta_akhir = $ta_awal_filter + 1;
    $where .= " AND ((YEAR(p.tanggal_pelaksanaan)='$ta_awal_filter' AND MONTH(p.tanggal_pelaksanaan) >= 7) OR (YEAR(p.tanggal_pelaksanaan)='$ta_akhir' AND MONTH(p.tanggal_pelaksanaan) <= 6))";
} elseif ($filter_mode == 'rentang') {
    $start_date = date('Y-m-d', strtotime($tanggal_awal));
    $end_date = date('Y-m-d', strtotime($tanggal_akhir));
    $where .= " AND p.tanggal_pelaksanaan BETWEEN '$start_date' AND '$end_date'";
}

$q_stats = mysqli_query($conn, "SELECT tingkat, COUNT(*) as jml FROM prestasi p WHERE $where GROUP BY tingkat");
$stats_data = ['Internasional' => 0, 'Nasional' => 0, 'Provinsi' => 0, 'Kota/Kabupaten' => 0];
if ($q_stats) {
    while ($st = mysqli_fetch_assoc($q_stats)) {
        if (isset($stats_data[$st['tingkat']])) $stats_data[$st['tingkat']] = $st['jml'];
    }
}
$stats = [
    'total' => array_sum($stats_data),
    'intl' => $stats_data['Internasional'],
    'nas' => $stats_data['Nasional'],
    'prov' => $stats_data['Provinsi'],
    'kota' => $stats_data['Kota/Kabupaten']
];

$q_data = mysqli_query($conn, "SELECT p.*, s.nama_siswa, s.kelas FROM prestasi p JOIN siswa s ON p.nisn = s.nisn WHERE $where ORDER BY p.tanggal_pelaksanaan DESC");

$html_tabel = "";
$html_galeri = "";
$html_modal = "";
$no = 1;

if ($q_data && mysqli_num_rows($q_data) > 0) {
    while ($r = mysqli_fetch_assoc($q_data)) {
        $warna = match ($r['tingkat']) {
            'Internasional' => 'bg-danger',
            'Nasional' => 'bg-primary',
            'Provinsi' => 'bg-info text-dark',
            default => 'bg-secondary'
        };
        $id_p = $r['id_prestasi'];
        $nama = htmlspecialchars($r['nama_siswa']);
        $kelas = htmlspecialchars($r['kelas']);
        $lomba = htmlspecialchars($r['nama_lomba']);
        $tingkat = htmlspecialchars($r['tingkat']);
        $peringkat = htmlspecialchars($r['peringkat']);
        $kategori = htmlspecialchars($r['kategori']);
        $tgl = date('d M Y', strtotime($r['tanggal_pelaksanaan']));

        $html_tabel .= "<tr class='rekap-row'>
            <td><span class='badge bg-light text-dark border'>$no</span></td>
            <td class='text-start fw-medium r-nama'>$nama</td>
            <td><span class='badge bg-light text-dark border'>$kelas</span></td>
            <td class='text-start r-lomba'>$lomba</td>
            <td><span class='badge $warna px-2'>$tingkat</span></td>
            <td><span class='badge bg-warning text-dark px-2'>$peringkat</span></td>
            <td><button type='button' class='btn btn-outline-dark btn-sm rounded-pill' data-bs-toggle='modal' data-bs-target='#previewModalKepsek$id_p'>Lihat</button></td>
        </tr>";

        $images = [];
        if (!empty($r['file_trofi'])) $images[] = $r['file_trofi'];
        if (!empty($r['file_sertifikat'])) $images[] = $r['file_sertifikat'];
        if (empty($images)) $images[] = 'logo.png';

        $html_galeri .= "<div class='col-12 col-md-6 col-lg-4 galeri-item'>
            <div class='galeri-card' data-bs-toggle='modal' data-bs-target='#previewModalKepsek$id_p' style='cursor: pointer;'>
                <div id='cardSlider$id_p' class='carousel slide galeri-img-wrapper' data-bs-ride='carousel' data-bs-interval='3000'>
                    <div class='carousel-inner h-100'>";
        foreach ($images as $idx => $img) {
            $active = $idx === 0 ? 'active' : '';
            $enc_img = rawurlencode($img);
            $html_galeri .= "<div class='carousel-item $active h-100'>
                <img src='../../assets/uploads/$enc_img' class='galeri-img d-block w-100' alt='Dokumentasi' onerror=\"this.onerror=null; this.src='../../assets/images/logo.png';\">
            </div>";
        }
        $html_galeri .= "</div><div class='galeri-badge-top'>🏆 $kategori</div></div>
                <div class='galeri-body'>
                    <h5 class='galeri-title g-lomba'>$lomba</h5>
                    <div class='galeri-student g-nama'>👨‍🎓 $nama (Kelas $kelas)</div>
                    <div class='galeri-footer'>
                        <div class='galeri-rank'>$peringkat - $tingkat</div>
                        <div class='galeri-date'>$tgl</div>
                    </div>
                </div>
            </div>
        </div>";

        $html_modal .= "<div class='modal fade' id='previewModalKepsek$id_p' tabindex='-1'>
            <div class='modal-dialog modal-dialog-centered modal-lg'>
                <div class='modal-content custom-modal shadow-lg'>
                    <div class='modal-header' style='background-color:#002b5c;color:white;'>
                        <h6 class='modal-title fw-bold'>Dokumentasi: $nama</h6>
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body text-center p-4 bg-light'>
                        <div id='modalSlider$id_p' class='carousel slide carousel-dark' data-bs-ride='carousel'>
                            <div class='carousel-inner'>";
        foreach ($images as $idx => $img) {
            $active = $idx === 0 ? 'active' : '';
            $enc_img = rawurlencode($img);
            $html_modal .= "<div class='carousel-item $active'>
                <img src='../../assets/uploads/$enc_img' class='img-fluid rounded shadow-sm d-block mx-auto' style='max-height:70vh;object-fit:contain;' onerror=\"this.onerror=null; this.src='../../assets/images/logo.png';\">
            </div>";
        }
        $html_modal .= "</div>";
        if (count($images) > 1) {
            $html_modal .= "<button class='carousel-control-prev' type='button' data-bs-target='#modalSlider$id_p' data-bs-slide='prev'><span class='carousel-control-prev-icon bg-dark rounded-circle p-2'></span></button>
                            <button class='carousel-control-next' type='button' data-bs-target='#modalSlider$id_p' data-bs-slide='next'><span class='carousel-control-next-icon bg-dark rounded-circle p-2'></span></button>";
        }
        $html_modal .= "</div></div></div></div></div>";
        $no++;
    }
} else {
    $html_tabel = "<tr><td colspan='7' class='py-4 text-muted fw-medium'>Belum ada data prestasi resmi yang tervalidasi pada filter ini.</td></tr>";
    $html_galeri = "<div class='col-12'><div class='alert alert-light border text-center py-5 text-muted'>Belum ada galeri dokumentasi prestasi pada filter ini.</div></div>";
}

echo json_encode(['tabel' => $html_tabel, 'galeri' => $html_galeri, 'modal' => $html_modal, 'stats' => $stats]);
