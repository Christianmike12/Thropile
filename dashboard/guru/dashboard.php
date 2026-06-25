<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Guru") {
    header("Location: ../../index.php");
    exit();
}

$nip_guru = $_SESSION['nip'];

if (isset($_POST['edit_profil_guru'])) {
    $nip_guru_edit  = $_POST['nip_guru'];
    $nama_guru = $_POST['nama_guru'];
    $pass_guru = $_POST['pass_guru'];

    mysqli_query($conn, "UPDATE guru SET nama_guru='$nama_guru', PASSWORD='$pass_guru' WHERE nip='$nip_guru_edit'");

    $_SESSION['nama'] = $nama_guru;
    echo "<script>alert('Profil Guru berhasil diperbarui!'); window.location='dashboard.php';</script>";
    exit();
}

if (isset($_GET['hapus_prestasi'])) {
    $id_hapus = (int)$_GET['hapus_prestasi'];

    $q_file = mysqli_query($conn, "SELECT file_sertifikat, foto_penyerahan FROM prestasi WHERE id_prestasi=$id_hapus AND nip_guru='$nip_guru'");
    if (mysqli_num_rows($q_file) > 0) {
        $dt_file = mysqli_fetch_assoc($q_file);

        $path_sertif = "../../assets/uploads/" . $dt_file['file_sertifikat'];
        if (!empty($dt_file['file_sertifikat']) && file_exists($path_sertif)) unlink($path_sertif);

        $path_foto = "../../assets/uploads/" . $dt_file['foto_penyerahan'];
        if (!empty($dt_file['foto_penyerahan']) && file_exists($path_foto)) unlink($path_foto);

        mysqli_query($conn, "DELETE FROM prestasi WHERE id_prestasi=$id_hapus");
        echo "<script>alert('Data prestasi berhasil dihapus!'); window.location='dashboard.php';</script>";
    }
    exit();
}

if (isset($_POST['edit_prestasi_guru'])) {
    $id_p = (int)$_POST['id_prestasi'];
    $nama_lomba = mysqli_real_escape_string($conn, $_POST['nama_lomba']);
    $tingkat = mysqli_real_escape_string($conn, $_POST['tingkat']);
    $peringkat = mysqli_real_escape_string($conn, $_POST['peringkat']);

    $q_lama = mysqli_query($conn, "SELECT file_sertifikat, foto_penyerahan, nisn FROM prestasi WHERE id_prestasi=$id_p");
    $dt_lama = mysqli_fetch_assoc($q_lama);
    $nisn = $dt_lama['nisn'];

    $nama_file_sertif = $dt_lama['file_sertifikat'];
    $nama_file_foto = $dt_lama['foto_penyerahan'];

    if (!empty($_FILES['sertifikat_baru']['name'])) {
        $ext = strtolower(pathinfo($_FILES['sertifikat_baru']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
            if (!empty($nama_file_sertif) && file_exists("../../assets/uploads/" . $nama_file_sertif)) unlink("../../assets/uploads/" . $nama_file_sertif);
            $nama_file_sertif = time() . '_sertif_rev_' . preg_replace("/[^a-zA-Z0-9]/", "", $nisn) . '.' . $ext;
            move_uploaded_file($_FILES['sertifikat_baru']['tmp_name'], "../../assets/uploads/" . $nama_file_sertif);
        }
    }

    if (!empty($_FILES['foto_baru']['name'])) {
        $ext = strtolower(pathinfo($_FILES['foto_baru']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            if (!empty($nama_file_foto) && file_exists("../../assets/uploads/" . $nama_file_foto)) unlink("../../assets/uploads/" . $nama_file_foto);
            $nama_file_foto = time() . '_foto_rev_' . preg_replace("/[^a-zA-Z0-9]/", "", $nisn) . '.' . $ext;
            move_uploaded_file($_FILES['foto_baru']['tmp_name'], "../../assets/uploads/" . $nama_file_foto);
        }
    }

    mysqli_query($conn, "UPDATE prestasi SET nama_lomba='$nama_lomba', tingkat='$tingkat', peringkat='$peringkat', file_sertifikat='$nama_file_sertif', foto_penyerahan='$nama_file_foto', status_data='Pending', alasan_tolak=NULL WHERE id_prestasi='$id_p'");

    echo "<script>alert('Data prestasi berhasil direvisi dan diajukan ulang!'); window.location='dashboard.php';</script>";
    exit();
}

$q_profil = mysqli_query($conn, "SELECT * FROM guru WHERE nip='$nip_guru'");
$dt_profil = mysqli_fetch_assoc($q_profil);
$chart_labels = [];
$chart_data = [];
$chart_colors = [];
$q_chart_status = mysqli_query($conn, "SELECT status_data, COUNT(*) as jml FROM prestasi WHERE nip_guru='$nip_guru' GROUP BY status_data");

if ($q_chart_status && mysqli_num_rows($q_chart_status) > 0) {
    while ($row = mysqli_fetch_assoc($q_chart_status)) {
        $chart_labels[] = $row['status_data'];
        $chart_data[] = $row['jml'];
        if ($row['status_data'] == 'Approved') $chart_colors[] = '#198754';
        elseif ($row['status_data'] == 'Pending') $chart_colors[] = '#ffcc00';
        elseif ($row['status_data'] == 'Rejected') $chart_colors[] = '#dc3545';
    }
} else {
    $chart_labels = ['Belum Ada Pengajuan'];
    $chart_data = [1];
    $chart_colors = ['#e9ecef'];
}

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

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'data';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guru Pembina - Trophile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/guru_dashboard.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .carousel-item img {
            transition: transform 0.5s ease;
        }

        .galeri-card:hover .carousel-item img {
            transform: scale(1.05);
        }
    </style>
</head>

<body>
    <nav class="navbar-custom">
        <div class="nav-container">
            <div class="brand-wrapper">
                <img src="../../assets/images/SMANSA.png" alt="Logo" width="35">
                <a href="dashboard.php" class="brand-logo">TROPHILE</a>
            </div>
            <div class="dropdown">
                <a href="javascript:void(0)" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" style="border: 1px solid rgba(255,255,255,0.3); padding: 6px 15px; border-radius: 8px;">
                    <span class="me-2 fw-medium d-none d-md-block">Halo, <?php echo $_SESSION['nama']; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; border:none; margin-top:10px;">
                    <li>
                        <h6 class="dropdown-header text-muted">Akses Guru Pembina</h6>
                    </li>
                    <li><a class="dropdown-item fw-medium" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editProfilGuru">⚙️ Pengaturan Akun</a></li>
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
            <div class="header-title">Dashboard Guru Pembina</div>

            <ul class="nav nav-pills custom-pills mb-4" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <button type="button" class="nav-link <?php echo $active_tab == 'data' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-data">Data Prestasi Binaan</button>
                </li>
                <li class="nav-item">
                    <button type="button" class="nav-link <?php echo $active_tab == 'galeri' ? 'active' : ''; ?>" data-bs-toggle="pill" data-bs-target="#tab-galeri">Galeri Prestasi (Wall of Fame)</button>
                </li>
            </ul>

            <div class="tab-content">

                <div class="tab-pane fade <?php echo $active_tab == 'data' ? 'show active' : ''; ?>" id="tab-data">

                    <div class="row align-items-center mb-4">
                        <div class="col-md-5 mb-4 mb-md-0">
                            <h6 class="fw-bold text-navy mb-3" style="font-size: 1.1rem;">Rasio Status Pengajuan Prestasi</h6>
                            <div style="height:250px;width:100%; border: 1px solid #e1e5eb; border-radius: 12px; padding: 20px; display:flex; justify-content:center;">
                                <canvas id="grafikGuru"></canvas>
                            </div>
                        </div>

                        <div class="col-md-7">
                            <div class="d-flex justify-content-md-end align-items-center mb-4 flex-wrap gap-3">
                                <a href="input_prestasi.php" class="btn btn-action">+ Input Data Prestasi</a>
                            </div>
                            <h6 class="fw-bold m-0 text-navy mb-2">Riwayat Pengajuan Data Prestasi Anda</h6>
                            <p class="text-muted small">Kelola dan pantau status persetujuan dari prestasi siswa binaan yang telah Anda ajukan kepada Admin TU SMANSA.</p>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table class="table text-center align-middle custom-table">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="20%" class="text-start">NAMA SISWA</th>
                                    <th width="25%" class="text-start">NAMA KOMPETISI</th>
                                    <th width="10%">TINGKAT</th>
                                    <th width="10%">HASIL</th>
                                    <th width="15%">STATUS</th>
                                    <th width="15%">AKSI</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                $query = "SELECT p.*, s.nama_siswa FROM prestasi p JOIN siswa s ON p.nisn = s.nisn WHERE p.nip_guru = '$nip_guru' ORDER BY p.id_prestasi DESC";
                                $res = mysqli_query($conn, $query);
                                $data_prestasi_binaan = [];

                                if (mysqli_num_rows($res) > 0) {
                                    while ($r = mysqli_fetch_assoc($res)) {
                                        $data_prestasi_binaan[] = $r;
                                        $st = match ($r['status_data']) {
                                            'Approved' => 'bg-success',
                                            'Rejected' => 'bg-danger',
                                            default => 'bg-warning text-dark'
                                        };

                                        $catatan = "";
                                        if ($r['status_data'] == 'Rejected' && !empty($r['alasan_tolak'])) {
                                            $catatan = "<div class='mt-2' style='font-size:12px; color:#dc3545; text-align:center; line-height:1.2;'><b>Alasan:</b><br>{$r['alasan_tolak']}</div>";
                                        }

                                        echo "<tr>
                                            <td><span class='badge bg-light text-dark border'>$no</span></td>
                                            <td class='text-start fw-medium'>{$r['nama_siswa']}</td>
                                            <td class='text-start'>{$r['nama_lomba']}</td>
                                            <td><span class='badge bg-info text-dark px-2'>{$r['tingkat']}</span></td>
                                            <td><span class='badge bg-light text-dark border px-2'>{$r['peringkat']}</span></td>
                                            <td>
                                                <span class='badge $st px-3 py-2 rounded-pill'>{$r['status_data']}</span>
                                                $catatan
                                            </td>
                                            <td>";

                                        if ($r['status_data'] == 'Approved') {
                                            echo "<span class='badge bg-secondary px-3 py-2 rounded-pill'><small>Terkunci</small></span>";
                                        } else {
                                            echo "
                                                <button type='button' class='btn btn-edit-outline btn-sm me-1 px-3' data-bs-toggle='modal' data-bs-target='#editPrestasi{$r['id_prestasi']}'>Edit</button>
                                                <a href='?hapus_prestasi={$r['id_prestasi']}' class='btn btn-hapus-outline btn-sm px-3' onclick='return confirm(\"Yakin ingin menghapus data ini secara permanen?\")'>Hapus</a>
                                            ";
                                        }

                                        echo "</td></tr>";
                                        $no++;
                                    }
                                } else {
                                    echo "<tr><td colspan='7' class='py-4 text-muted fw-medium'>Belum ada data prestasi yang Anda input.</td></tr>";
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
                                    <label class="fw-semibold text-navy mb-1" style="font-size:13px;">Mode Filter Galeri</label>
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

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3 action-wrapper">
                        <h6 class="fw-bold m-0 text-navy">Wall of Fame SMANSA</h6>
                        <input type="text" id="searchGaleri" class="form-control search-input" placeholder="Cari Nama Lomba / Siswa..." style="max-width: 300px;" onkeydown="if(event.key === 'Enter') { event.preventDefault(); return false; }">
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
                                if (empty($images)) $images[] = 'SMANSA.png';

                                $tanggal_format = date('d M Y', strtotime($g['tanggal_pelaksanaan'] ?? date('Y-m-d')));
                        ?>
                                <div class="col-12 col-md-6 col-lg-4 galeri-item">
                                    <div class="galeri-card" data-bs-toggle="modal" data-bs-target="#previewModalGaleri<?php echo $g['id_prestasi']; ?>" style="cursor: pointer;">

                                        <div id="cardSlider<?php echo $g['id_prestasi']; ?>" class="carousel slide galeri-img-wrapper" data-bs-ride="carousel" data-bs-interval="3000">
                                            <div class="carousel-inner h-100">
                                                <?php foreach ($images as $idx => $img) {
                                                    $active = $idx === 0 ? 'active' : '';
                                                ?>
                                                    <div class="carousel-item <?php echo $active; ?> h-100">
                                                        <img src="../../assets/uploads/<?php echo rawurlencode($img); ?>" class="galeri-img d-block w-100" alt="Dokumentasi" onerror="this.onerror=null; this.src='../../assets/images/SMANSA.png';">
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

    <?php foreach ($data_prestasi_binaan as $r) {
        if ($r['status_data'] != 'Approved') {
    ?>
            <div class="modal fade text-start" id="editPrestasi<?php echo $r['id_prestasi']; ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="POST" enctype="multipart/form-data" class="modal-content custom-modal shadow">
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Revisi Data Prestasi</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id_prestasi" value="<?php echo $r['id_prestasi']; ?>">

                            <div class="mb-3 p-3 bg-light rounded border">
                                <span class="d-block small text-muted">Siswa Binaan:</span>
                                <span class="fw-bold text-navy"><?php echo $r['nama_siswa']; ?></span>
                                <div class="form-text small mt-1">*Nama Siswa tidak dapat diubah. Jika salah, silakan hapus data ini dan input ulang.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-navy">Nama Kompetisi</label>
                                <input type="text" name="nama_lomba" class="form-control" value="<?php echo htmlspecialchars($r['nama_lomba']); ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label small fw-bold text-navy">Tingkat</label>
                                    <select name="tingkat" class="form-select" required>
                                        <option value="Kota/Kabupaten" <?php echo ($r['tingkat'] == 'Kota/Kabupaten') ? 'selected' : ''; ?>>Kota/Kabupaten</option>
                                        <option value="Provinsi" <?php echo ($r['tingkat'] == 'Provinsi') ? 'selected' : ''; ?>>Provinsi</option>
                                        <option value="Nasional" <?php echo ($r['tingkat'] == 'Nasional') ? 'selected' : ''; ?>>Nasional</option>
                                        <option value="Internasional" <?php echo ($r['tingkat'] == 'Internasional') ? 'selected' : ''; ?>>Internasional</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label small fw-bold text-navy">Peringkat</label>
                                    <input type="text" name="peringkat" class="form-control" value="<?php echo htmlspecialchars($r['peringkat']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-navy">Revisi Sertifikat (Opsional)</label>
                                <input type="file" name="sertifikat_baru" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                <div class="form-text small text-danger">*Kosongkan jika tidak diganti.</div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-bold text-navy">Revisi Foto Penyerahan (Opsional)</label>
                                <input type="file" name="foto_baru" class="form-control" accept=".jpg,.jpeg,.png">
                                <div class="form-text small text-danger">*Kosongkan jika tidak diganti.</div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="submit" name="edit_prestasi_guru" class="btn btn-action w-100">Simpan & Ajukan Ulang</button>
                        </div>
                    </form>
                </div>
            </div>
    <?php }
    } ?>

    <?php
    foreach ($data_galeri_array as $rk) {
        $images_modal = [];
        if (!empty($rk['foto_penyerahan'])) $images_modal[] = $rk['foto_penyerahan'];
        if (!empty($rk['file_sertifikat'])) $images_modal[] = $rk['file_sertifikat'];
        if (empty($images_modal)) $images_modal[] = 'SMANSA.png';
    ?>
        <div class="modal fade" id="previewModalGaleri<?php echo $rk['id_prestasi']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content custom-modal shadow-lg">
                    <div class="modal-header" style="background-color: #002b5c; color: white;">
                        <h6 class="modal-title fw-bold">Dokumentasi: <?php echo htmlspecialchars($rk['nama_siswa'] ?? '-'); ?></h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center p-4 bg-light">

                        <div id="modalSliderGuru<?php echo $rk['id_prestasi']; ?>" class="carousel slide carousel-dark" data-bs-ride="carousel">
                            <div class="carousel-inner">
                                <?php foreach ($images_modal as $idx => $img) {
                                    $active = $idx === 0 ? 'active' : '';
                                ?>
                                    <div class="carousel-item <?php echo $active; ?>">
                                        <img src="../../assets/uploads/<?php echo rawurlencode($img); ?>" class="img-fluid rounded shadow-sm d-block mx-auto" alt="Dokumentasi" style="max-height: 70vh; object-fit: contain;" onerror="this.onerror=null; this.src='../../assets/images/SMANSA.png';">
                                    </div>
                                <?php } ?>
                            </div>

                            <?php if (count($images_modal) > 1) { ?>
                                <button class="carousel-control-prev" type="button" data-bs-target="#modalSliderGuru<?php echo $rk['id_prestasi']; ?>" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon bg-dark rounded-circle p-2" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#modalSliderGuru<?php echo $rk['id_prestasi']; ?>" data-bs-slide="next">
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

    <div class="modal fade text-start" id="editProfilGuru" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content custom-modal shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Pengaturan Akun Guru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="nip_guru" value="<?php echo $dt_profil['nip'] ?? ''; ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-navy">NIP (Nomor Induk Pegawai)</label>
                        <input type="text" class="form-control bg-light" value="<?php echo $dt_profil['nip'] ?? ''; ?>" readonly>
                        <div class="form-text small">*NIP digunakan sebagai Username Login dan tidak bisa diubah.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-navy">Nama Lengkap & Gelar</label>
                        <input type="text" name="nama_guru" class="form-control" value="<?php echo $dt_profil['nama_guru'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-navy">Password Akun</label>
                        <input type="password" name="pass_guru" class="form-control" value="<?php echo $dt_profil['PASSWORD'] ?? ''; ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" name="edit_profil_guru" class="btn btn-action w-100">Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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

        const ctxGuru = document.getElementById('grafikGuru')?.getContext('2d');
        if (ctxGuru) {
            Chart.defaults.font.family = "'Poppins', sans-serif";
            new Chart(ctxGuru, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($chart_labels); ?>,
                    datasets: [{
                        data: <?php echo json_encode($chart_data); ?>,
                        backgroundColor: <?php echo json_encode($chart_colors); ?>,
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                font: {
                                    size: 13,
                                    weight: '500'
                                }
                            }
                        }
                    },
                    cutout: '65%'
                }
            });
        }
    </script>
</body>

</html>