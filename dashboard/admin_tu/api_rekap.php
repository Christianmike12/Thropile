<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Admin TU") {
    echo json_encode(['tabel' => '<tr><td colspan="7">Unauthorized</td></tr>', 'modal' => '']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        echo json_encode(['tabel' => '<tr><td colspan="7">CSRF Token Invalid</td></tr>', 'modal' => '']);
        exit();
    }
}

$filter_mode = trim($_POST['filter_mode'] ?? 'all');
$tahun_filter = (int)($_POST['tahun'] ?? date('Y'));
$bulan_filter = (int)($_POST['bulan'] ?? date('n'));
$ta_awal_filter = (int)($_POST['ta_awal'] ?? date('Y'));
$tanggal_awal = trim($_POST['tanggal_awal'] ?? date('Y-m-01'));
$tanggal_akhir = trim($_POST['tanggal_akhir'] ?? date('Y-m-t'));

$where = "p.status_data='Approved'";
$types = "";
$params = [];

if ($filter_mode == 'tahun') {
    $where .= " AND YEAR(p.tanggal_pelaksanaan)=?";
    $types .= "i";
    $params[] = $tahun_filter;
} elseif ($filter_mode == 'bulan') {
    $where .= " AND YEAR(p.tanggal_pelaksanaan)=? AND MONTH(p.tanggal_pelaksanaan)=?";
    $types .= "ii";
    $params[] = $tahun_filter;
    $params[] = $bulan_filter;
} elseif ($filter_mode == 'ta') {
    $ta_akhir = $ta_awal_filter + 1;
    $where .= " AND ((YEAR(p.tanggal_pelaksanaan)=? AND MONTH(p.tanggal_pelaksanaan) >= 7) OR (YEAR(p.tanggal_pelaksanaan)=? AND MONTH(p.tanggal_pelaksanaan) <= 6))";
    $types .= "ii";
    $params[] = $ta_awal_filter;
    $params[] = $ta_akhir;
} elseif ($filter_mode == 'rentang') {
    $start_date = date('Y-m-d', strtotime($tanggal_awal));
    $end_date = date('Y-m-d', strtotime($tanggal_akhir));
    $where .= " AND p.tanggal_pelaksanaan BETWEEN ? AND ?";
    $types .= "ss";
    $params[] = $start_date;
    $params[] = $end_date;
}

$q_rekap = db_query($conn, "SELECT p.*, s.nama_siswa, s.kelas FROM prestasi p JOIN siswa s ON p.nisn = s.nisn WHERE $where ORDER BY FIELD(p.tingkat,'Internasional','Nasional','Provinsi','Kota/Kabupaten'), p.peringkat ASC, s.kelas ASC", $types, ...$params);

$html_tabel = "";
$html_modal = "";
$no = 1;

if ($q_rekap && mysqli_num_rows($q_rekap) > 0) {
    while ($r = mysqli_fetch_assoc($q_rekap)) {
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
        $file = rawurlencode($r['file_sertifikat']);

        $html_tabel .= "<tr class='rekap-row'>
            <td><span class='badge bg-light text-dark border'>$no</span></td>
            <td class='text-start fw-medium r-nama'>$nama</td>
            <td><span class='badge bg-light text-dark border'>$kelas</span></td>
            <td class='text-start r-lomba'>$lomba</td>
            <td><span class='badge $warna px-2'>$tingkat</span></td>
            <td><span class='badge bg-warning text-dark px-2'>$peringkat</span></td>
            <td><button type='button' class='btn btn-outline-dark btn-sm rounded-pill' data-bs-toggle='modal' data-bs-target='#previewModalRekap$id_p'>Lihat</button></td>
        </tr>";

        $images_modal = [];
        if (!empty($r['file_trofi'])) $images_modal[] = $r['file_trofi'];
        if (!empty($r['file_sertifikat'])) $images_modal[] = $r['file_sertifikat'];
        if (empty($images_modal)) $images_modal[] = 'logo.png';

        $carousel_inner = "";
        foreach ($images_modal as $idx => $img) {
            $active = $idx === 0 ? 'active' : '';
            $img_url = rawurlencode($img);
            $carousel_inner .= "
                <div class='carousel-item $active'>
                    <img src='../../assets/uploads/$img_url' class='img-fluid rounded shadow-sm d-block mx-auto' style='max-height: 70vh; object-fit: contain;' onerror=\"this.onerror=null; this.src='../../assets/images/logo.png';\">
                </div>
            ";
        }
        $carousel_controls = "";
        if (count($images_modal) > 1) {
            $carousel_controls = "
                <button class='carousel-control-prev' type='button' data-bs-target='#modalSliderRekap$id_p' data-bs-slide='prev'>
                    <span class='carousel-control-prev-icon bg-dark rounded-circle p-2' aria-hidden='true'></span>
                    <span class='visually-hidden'>Previous</span>
                </button>
                <button class='carousel-control-next' type='button' data-bs-target='#modalSliderRekap$id_p' data-bs-slide='next'>
                    <span class='carousel-control-next-icon bg-dark rounded-circle p-2' aria-hidden='true'></span>
                    <span class='visually-hidden'>Next</span>
                </button>
            ";
        }

        $html_modal .= "<div class='modal fade' id='previewModalRekap$id_p' tabindex='-1'>
            <div class='modal-dialog modal-dialog-centered modal-lg'>
                <div class='modal-content custom-modal shadow-lg'>
                    <div class='modal-header' style='background-color: #002b5c; color: white;'>
                        <h6 class='modal-title fw-bold'>Dokumentasi: $nama</h6>
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body text-center p-4 bg-light'>
                        <div id='modalSliderRekap$id_p' class='carousel slide carousel-dark' data-bs-ride='carousel'>
                            <div class='carousel-inner'>
                                $carousel_inner
                            </div>
                            $carousel_controls
                        </div>
                    </div>
                </div>
            </div>
        </div>";
        $no++;
    }
} else {
    $html_tabel = "<tr><td colspan='7' class='py-4 text-muted fw-medium'>Belum ada data prestasi resmi yang tervalidasi pada filter ini.</td></tr>";
}

echo json_encode(['tabel' => $html_tabel, 'modal' => $html_modal]);
