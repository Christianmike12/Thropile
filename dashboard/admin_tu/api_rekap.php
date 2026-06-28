<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Admin TU") {
    echo json_encode(['tabel' => '<tr><td colspan="7">Unauthorized</td></tr>', 'modal' => '']);
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

$q_rekap = mysqli_query($conn, "SELECT p.*, s.nama_siswa, s.kelas FROM prestasi p JOIN siswa s ON p.nisn = s.nisn WHERE $where ORDER BY FIELD(p.tingkat,'Internasional','Nasional','Provinsi','Kota/Kabupaten'), p.peringkat ASC, s.kelas ASC");

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

        $html_modal .= "<div class='modal fade' id='previewModalRekap$id_p' tabindex='-1'>
            <div class='modal-dialog modal-dialog-centered modal-lg'>
                <div class='modal-content custom-modal shadow-lg'>
                    <div class='modal-header' style='background-color: #002b5c; color: white;'>
                        <h6 class='modal-title fw-bold'>Dokumentasi: $nama</h6>
                        <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body text-center p-4 bg-light'>
                        <img src='../../assets/uploads/$file' class='img-fluid rounded shadow-sm' style='max-height: 70vh; object-fit: contain;' onerror=\"this.onerror=null; this.src='../../assets/images/logo.png';\">
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
