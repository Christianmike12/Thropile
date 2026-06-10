<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Guru") {
    header("Location: ../../index.php");
    exit();
}

$pesan = "";

// PROSES DIPERKUAT: Cek murni berdasarkan Request Method POST (Biar tombol apapun kedeteksi)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // DETEKSI XAMPP DROP LIMIT: Kalau $_POST tiba-tiba kosong karena file terlalu besar
    if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $pesan = "<div class='alert alert-danger'><b>Gagal!</b> Ukuran file terlalu besar sampai ditolak oleh XAMPP. Coba pakai file PDF/Foto di bawah 2MB.</div>";
    } else {
        $nisn                = mysqli_real_escape_string($conn, $_POST['nisn']);
        $nama_lomba          = mysqli_real_escape_string($conn, $_POST['nama_lomba']);
        $tingkat             = mysqli_real_escape_string($conn, $_POST['tingkat']);
        $tanggal_pelaksanaan = mysqli_real_escape_string($conn, $_POST['tgl_lomba']);
        $status_data         = 'Pending';
        $nip_guru            = $_SESSION['nip'];

        $tahun = date('Y', strtotime($tanggal_pelaksanaan));

        $kategori = (isset($_POST['kategori']) && $_POST['kategori'] == "Lainnya")
            ? mysqli_real_escape_string($conn, $_POST['kategori_lainnya'])
            : mysqli_real_escape_string($conn, $_POST['kategori']);

        $peringkat = (isset($_POST['peringkat']) && $_POST['peringkat'] == "Lainnya")
            ? mysqli_real_escape_string($conn, $_POST['peringkat_lainnya'])
            : mysqli_real_escape_string($conn, $_POST['peringkat']);

        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
        $nama_asli   = $_FILES['sertifikat']['name'];
        $tmp_file    = $_FILES['sertifikat']['tmp_name'];
        $ukuran      = $_FILES['sertifikat']['size'];
        $error_file  = $_FILES['sertifikat']['error'];

        if ($error_file == 4) {
            $pesan = "<div class='alert alert-danger'>Pilih file sertifikat/piagam terlebih dahulu!</div>";
        } else {
            $ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed_ext)) {
                $pesan = "<div class='alert alert-danger'>Format file tidak valid! Gunakan PDF, JPG, atau PNG.</div>";
            } elseif ($ukuran > 5 * 1024 * 1024) {
                $pesan = "<div class='alert alert-danger'>Ukuran file maksimal adalah 5MB!</div>";
            } else {
                $nama_file = time() . '_' . preg_replace("/[^a-zA-Z0-9]/", "", $nisn) . '_' . preg_replace("/[^a-zA-Z0-9]/", "", $nama_lomba) . '.' . $ext;
                $folder_tujuan = "../../assets/uploads/" . $nama_file;

                if (move_uploaded_file($tmp_file, $folder_tujuan)) {
                    $query = "INSERT INTO prestasi (
                                nisn, nip_guru, nama_lomba, kategori, tingkat, peringkat, 
                                tahun, tanggal_pelaksanaan, file_sertifikat, status_data
                              ) VALUES (
                                '$nisn', '$nip_guru', '$nama_lomba', '$kategori', '$tingkat', '$peringkat', 
                                '$tahun', '$tanggal_pelaksanaan', '$nama_file', '$status_data'
                              )";

                    if (mysqli_query($conn, $query)) {
                        echo "<script>alert('Data prestasi berhasil diajukan dan masuk ke database!'); window.location='dashboard.php';</script>";
                        exit();
                    } else {
                        $pesan = "<div class='alert alert-danger'>Gagal database: " . mysqli_error($conn) . "</div>";
                    }
                } else {
                    $pesan = "<div class='alert alert-danger'>Gagal memindahkan file! Pastikan folder <b>assets/uploads</b> beneran ada!</div>";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Input Prestasi - Trophile</title>
    <link rel="stylesheet" href="../../assets/css/guru_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar-custom">
        <div class="nav-container">
            <a href="dashboard.php" style="font-size:22px;font-weight:bold;letter-spacing:2px;text-decoration:none;color:white;">TROPHILE</a>
            <div>
                <span class="me-3">Halo, <?php echo $_SESSION['nama']; ?></span>
                <a href="../../logout.php" class="btn-logout">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="floating-card" style="max-width: 800px; margin: auto;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold m-0">Form Input Prestasi Siswa</h4>
                <a href="dashboard.php" class="btn btn-outline-dark btn-sm">&larr; Kembali</a>
            </div>

            <?php echo $pesan; ?>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Siswa (NISN)</label>
                    <select name="nisn" class="form-select" required>
                        <option value="">-- Pilih Siswa --</option>
                        <?php
                        $q_siswa = mysqli_query($conn, "SELECT nisn, nama_siswa, kelas FROM siswa ORDER BY kelas ASC, nama_siswa ASC");
                        while ($s = mysqli_fetch_assoc($q_siswa)) {
                            echo "<option value='{$s['nisn']}'>{$s['kelas']} - {$s['nama_siswa']} ({$s['nisn']})</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nama Kompetisi / Kejuaraan</label>
                    <input type="text" name="nama_lomba" class="form-control" required placeholder="Contoh: Olimpiade Sains Nasional Matematika">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" id="kategoriSelect" class="form-select" onchange="checkKategori(this.value)" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Akademik">Akademik</option>
                            <option value="Non-Akademik">Non-Akademik</option>
                            <option value="Lainnya">Lainnya (Ketik Manual)</option>
                        </select>
                        <input type="text" name="kategori_lainnya" id="kategoriLainnyaInput" class="form-control mt-2" placeholder="Masukkan Kategori Custom" style="display:none;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tingkat</label>
                        <select name="tingkat" class="form-select" required>
                            <option value="">-- Pilih Tingkat --</option>
                            <option value="Kota/Kabupaten">Kota/Kabupaten</option>
                            <option value="Provinsi">Provinsi</option>
                            <option value="Nasional">Nasional</option>
                            <option value="Internasional">Internasional</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Hasil / Peringkat</label>
                        <select name="peringkat" id="peringkatSelect" class="form-select" onchange="checkPeringkat(this.value)" required>
                            <option value="">-- Pilih Hasil --</option>
                            <option value="Juara 1">Juara 1</option>
                            <option value="Juara 2">Juara 2</option>
                            <option value="Juara 3">Juara 3</option>
                            <option value="Harapan 1">Harapan 1</option>
                            <option value="Lainnya">Lainnya (Ketik Manual)</option>
                        </select>
                        <input type="text" name="peringkat_lainnya" id="peringkatLainnyaInput" class="form-control mt-2" placeholder="Contoh: Medali Emas / Harapan 3" style="display:none;">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Pelaksanaan</label>
                        <input type="date" name="tgl_lomba" class="form-control" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Bukti Fisik (Sertifikat/Piagam)</label>
                    <input type="file" name="sertifikat" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                    <small class="text-muted">Maksimal file 5MB. Format yang didukung: PDF, JPG, PNG.</small>
                </div>

                <button type="submit" class="btn btn-dark w-100 py-2 fw-bold">Simpan & Ajukan Verifikasi</button>
            </form>
        </div>
    </div>

    <script>
        function checkKategori(val) {
            const inputKategori = document.getElementById('kategoriLainnyaInput');
            if (val === 'Lainnya') {
                inputKategori.style.display = 'block';
                inputKategori.required = true;
            } else {
                inputKategori.style.display = 'none';
                inputKategori.required = false;
            }
        }

        function checkPeringkat(val) {
            const inputPeringkat = document.getElementById('peringkatLainnyaInput');
            if (val === 'Lainnya') {
                inputPeringkat.style.display = 'block';
                inputPeringkat.required = true;
            } else {
                inputPeringkat.style.display = 'none';
                inputPeringkat.required = false;
            }
        }
    </script>
</body>

</html>