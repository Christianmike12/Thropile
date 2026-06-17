<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Guru") {
    header("Location: ../../index.php");
    exit();
}

$nip_guru = $_SESSION['nip'];

// ================= LOGIKA PENGATURAN AKUN GURU (DIRI SENDIRI) =================
if (isset($_POST['edit_profil_guru'])) {
    $nip_guru_edit  = $_POST['nip_guru'];
    $nama_guru = $_POST['nama_guru'];
    $pass_guru = $_POST['pass_guru'];

    mysqli_query($conn, "UPDATE guru SET nama_guru='$nama_guru', PASSWORD='$pass_guru' WHERE nip='$nip_guru_edit'");

    $_SESSION['nama'] = $nama_guru;
    echo "<script>alert('Profil Guru berhasil diperbarui!'); window.location='dashboard.php';</script>";
    exit();
}

// ================= LOGIKA HAPUS PRESTASI =================
if (isset($_GET['hapus_prestasi'])) {
    $id_hapus = (int)$_GET['hapus_prestasi'];

    // Cari file sertifikat buat dihapus dari folder biar nggak menuhin memori
    $q_file = mysqli_query($conn, "SELECT file_sertifikat FROM prestasi WHERE id_prestasi=$id_hapus AND nip_guru='$nip_guru'");
    if (mysqli_num_rows($q_file) > 0) {
        $dt_file = mysqli_fetch_assoc($q_file);
        $file_path = "../../assets/uploads/" . $dt_file['file_sertifikat'];
        if (file_exists($file_path)) {
            unlink($file_path); // Hapus file fisik
        }

        mysqli_query($conn, "DELETE FROM prestasi WHERE id_prestasi=$id_hapus");
        echo "<script>alert('Data prestasi berhasil dihapus!'); window.location='dashboard.php';</script>";
    }
    exit();
}

// ================= LOGIKA EDIT/REVISI PRESTASI =================
if (isset($_POST['edit_prestasi_guru'])) {
    $id_p = (int)$_POST['id_prestasi'];
    $nama_lomba = mysqli_real_escape_string($conn, $_POST['nama_lomba']);
    $tingkat = mysqli_real_escape_string($conn, $_POST['tingkat']);
    $peringkat = mysqli_real_escape_string($conn, $_POST['peringkat']);

    // Kalau guru upload file sertifikat baru buat revisi
    if ($_FILES['sertifikat_baru']['name'] != '') {
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
        $nama_asli   = $_FILES['sertifikat_baru']['name'];
        $tmp_file    = $_FILES['sertifikat_baru']['tmp_name'];
        $ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed_ext)) {
            // Hapus file lama
            $q_lama = mysqli_query($conn, "SELECT file_sertifikat FROM prestasi WHERE id_prestasi=$id_p");
            $file_lama = mysqli_fetch_assoc($q_lama)['file_sertifikat'];
            if (file_exists("../../assets/uploads/" . $file_lama)) unlink("../../assets/uploads/" . $file_lama);

            // Upload file baru
            $nama_file = time() . '_revisi_' . preg_replace("/[^a-zA-Z0-9]/", "", $nama_lomba) . '.' . $ext;
            move_uploaded_file($tmp_file, "../../assets/uploads/" . $nama_file);

            // Ubah status balik ke Pending karena abis direvisi
            mysqli_query($conn, "UPDATE prestasi SET nama_lomba='$nama_lomba', tingkat='$tingkat', peringkat='$peringkat', file_sertifikat='$nama_file', status_data='Pending', alasan_tolak=NULL WHERE id_prestasi='$id_p'");
        }
    } else {
        // Kalau cuma edit tulisan (file nggak diganti)
        mysqli_query($conn, "UPDATE prestasi SET nama_lomba='$nama_lomba', tingkat='$tingkat', peringkat='$peringkat', status_data='Pending', alasan_tolak=NULL WHERE id_prestasi='$id_p'");
    }

    echo "<script>alert('Data prestasi berhasil direvisi dan diajukan ulang!'); window.location='dashboard.php';</script>";
    exit();
}

// Ambil data profil terbaru untuk modal
$q_profil = mysqli_query($conn, "SELECT * FROM guru WHERE nip='$nip_guru'");
$dt_profil = mysqli_fetch_assoc($q_profil);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guru - Trophile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/guru_dashboard.css?v=<?php echo time(); ?>">
</head>

<body>
    <nav class="navbar-custom">
        <div class="nav-container">
            <div class="brand-wrapper">
                <img src="../../assets/images/SMANSA.png" alt="Logo" width="35">
                <a href="dashboard.php" class="brand-logo">TROPHILE SMANSA</a>
            </div>

            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" style="border: 1px solid rgba(255,255,255,0.3); padding: 6px 15px; border-radius: 8px;">
                    <span class="me-2 fw-medium d-none d-md-block">Halo, <?php echo $_SESSION['nama']; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; border:none; margin-top:10px;">
                    <li>
                        <h6 class="dropdown-header text-muted">Akses Guru Pembina</h6>
                    </li>
                    <li><a class="dropdown-item fw-medium" href="#" data-bs-toggle="modal" data-bs-target="#editProfilGuru">⚙️ Pengaturan Akun</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger fw-bold" href="../../logout.php">🚪 Keluar</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h4 class="header-title mb-0 border-0 pb-0">Data Prestasi Siswa Binaan</h4>
            <a href="input_prestasi.php" class="btn btn-action">+ Input Data Prestasi</a>
        </div>

        <div class="floating-card p-0" style="background: transparent; box-shadow: none;">
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
                        $data_prestasi = []; // Tampung array buat modal edit

                        if (mysqli_num_rows($res) > 0) {
                            while ($r = mysqli_fetch_assoc($res)) {
                                $data_prestasi[] = $r;

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

                                // JIKA STATUSNYA APPROVED = KUNCI. SELAIN ITU = BISA DIEDIT/DIHAPUS
                                if ($r['status_data'] == 'Approved') {
                                    echo "<span class='badge bg-secondary px-3 py-2 rounded-pill'><small>Terkunci</small></span>";
                                } else {
                                    echo "
                                        <button class='btn btn-edit-outline btn-sm me-1 px-3' data-bs-toggle='modal' data-bs-target='#editPrestasi{$r['id_prestasi']}'>Edit</button>
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
    </div>

    <?php foreach ($data_prestasi as $r) {
        if ($r['status_data'] != 'Approved') { // Cuma di-render kalau belum di-approve
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
                                <div class="form-text small mt-1">*Nama Siswa tidak dapat diubah. Jika salah siswa, silakan hapus data ini dan input ulang.</div>
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

                            <div class="mb-2">
                                <label class="form-label small fw-bold text-navy">Upload Sertifikat Baru (Opsional)</label>
                                <input type="file" name="sertifikat_baru" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                <div class="form-text small text-danger">*Kosongkan jika tidak ingin mengganti file sertifikat.</div>
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
                        <input type="text" name="pass_guru" class="form-control" value="<?php echo $dt_profil['PASSWORD'] ?? ''; ?>" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" name="edit_profil_guru" class="btn btn-action w-100">Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>