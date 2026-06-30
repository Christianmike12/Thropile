<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Guru") {
    header("Location: ../../login.php");
    exit();
}

$nip_guru = $_SESSION['nip'];

// --- [AKSI EDIT PROFIL] ---
if (isset($_POST['edit_profil_guru'])) {
    $nip_guru_edit  = $_POST['nip_guru'];
    $nama_guru = $_POST['nama_guru'];
    $pass_guru = $_POST['pass_guru'];

    if (!empty($pass_guru)) {
        if (strlen($pass_guru) < 8 || !preg_match("/[a-zA-Z]/", $pass_guru) || !preg_match("/\d/", $pass_guru)) {
            echo "<script>alert('Password minimal 8 karakter, serta harus mengandung kombinasi huruf dan angka!'); window.history.back();</script>";
            exit();
        }
        $pass_guru_hash = password_hash($pass_guru, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE guru SET nama_guru='$nama_guru', PASSWORD='$pass_guru_hash' WHERE nip='$nip_guru_edit'");
    } else {
        mysqli_query($conn, "UPDATE guru SET nama_guru='$nama_guru' WHERE nip='$nip_guru_edit'");
    }
    
    $_SESSION['nama'] = $nama_guru;

    echo "<script>alert('Profil Guru berhasil diperbarui!'); window.location='input_prestasi.php';</script>";
    exit();
}

$pesan = "";

// --- [AKSI INPUT PRESTASI] ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['edit_profil_guru'])) {

    if (empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $pesan = "<div class='alert alert-danger fw-medium'><b>Gagal!</b> Ukuran file terlalu besar. Maksimal 5MB per file.</div>";
    } else {
        $nisn                = mysqli_real_escape_string($conn, $_POST['nisn']);
        $nama_lomba          = mysqli_real_escape_string($conn, $_POST['nama_lomba']);
        $tingkat             = mysqli_real_escape_string($conn, $_POST['tingkat']);
        $tanggal_pelaksanaan = mysqli_real_escape_string($conn, $_POST['tgl_lomba']);
        $status_data         = 'Pending';
        $tahun = date('Y', strtotime($tanggal_pelaksanaan));

        $kategori = (isset($_POST['kategori']) && $_POST['kategori'] == "Lainnya") ? mysqli_real_escape_string($conn, $_POST['kategori_lainnya']) : mysqli_real_escape_string($conn, $_POST['kategori']);
        $peringkat = (isset($_POST['peringkat']) && $_POST['peringkat'] == "Lainnya") ? mysqli_real_escape_string($conn, $_POST['peringkat_lainnya']) : mysqli_real_escape_string($conn, $_POST['peringkat']);

        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];

        // Data File Sertifikat
        $nama_asli_sertif  = $_FILES['sertifikat']['name'];
        $tmp_file_sertif   = $_FILES['sertifikat']['tmp_name'];
        $ukuran_sertif     = $_FILES['sertifikat']['size'];
        $error_sertif      = $_FILES['sertifikat']['error'];

        // Data File Foto Trofi
        $nama_asli_foto  = $_FILES['file_trofi']['name'];
        $tmp_file_foto   = $_FILES['file_trofi']['tmp_name'];
        $ukuran_foto     = $_FILES['file_trofi']['size'];
        $error_foto      = $_FILES['file_trofi']['error'];

        if ($error_sertif == 4) {
            $pesan = "<div class='alert alert-danger fw-medium'>Pilih file sertifikat/piagam terlebih dahulu!</div>";
        } else {
            $ext_sertif = strtolower(pathinfo($nama_asli_sertif, PATHINFO_EXTENSION));

            if (!in_array($ext_sertif, $allowed_ext)) {
                $pesan = "<div class='alert alert-danger fw-medium'>Format file Sertifikat tidak valid! Gunakan PDF, JPG, atau PNG.</div>";
            } elseif ($ukuran_sertif > 5 * 1024 * 1024) {
                $pesan = "<div class='alert alert-danger fw-medium'>Ukuran file Sertifikat maksimal 5MB!</div>";
            } else {
                // Upload Sertifikat
                $nama_file_sertif = time() . '_sertif_' . preg_replace("/[^a-zA-Z0-9]/", "", $nisn) . '.' . $ext_sertif;
                move_uploaded_file($tmp_file_sertif, "../../assets/uploads/" . $nama_file_sertif);

                // Upload Foto (Opsional)
                $nama_file_foto = "";
                if ($error_foto == 0) {
                    $ext_foto = strtolower(pathinfo($nama_asli_foto, PATHINFO_EXTENSION));
                    if (in_array($ext_foto, ['jpg', 'jpeg', 'png']) && $ukuran_foto <= 5 * 1024 * 1024) {
                        $nama_file_foto = time() . '_foto_' . preg_replace("/[^a-zA-Z0-9]/", "", $nisn) . '.' . $ext_foto;
                        move_uploaded_file($tmp_file_foto, "../../assets/uploads/" . $nama_file_foto);
                    }
                }

                // Simpan ke Database
                $query = "INSERT INTO prestasi (
                            nisn, nip_guru, nama_lomba, kategori, tingkat, peringkat, 
                            tahun, tanggal_pelaksanaan, file_sertifikat, file_trofi, status_data
                          ) VALUES (
                            '$nisn', '$nip_guru', '$nama_lomba', '$kategori', '$tingkat', '$peringkat', 
                            '$tahun', '$tanggal_pelaksanaan', '$nama_file_sertif', '$nama_file_foto', '$status_data'
                          )";

                if (mysqli_query($conn, $query)) {
                    echo "<script>alert('Data prestasi berhasil diajukan dan masuk antrean verifikasi!'); window.location='dashboard.php';</script>";
                    exit();
                } else {
                    $pesan = "<div class='alert alert-danger fw-medium'>Gagal database: " . mysqli_error($conn) . "</div>";
                }
            }
        }
    }
}

// Mengambil Data Profil Guru
$q_profil = mysqli_query($conn, "SELECT * FROM guru WHERE nip='$nip_guru'");
$dt_profil = mysqli_fetch_assoc($q_profil);

// Mengambil Data Siswa yang dikelompokkan berdasarkan Kelas
$q_all_siswa = mysqli_query($conn, "SELECT nisn, nama_siswa, kelas FROM siswa ORDER BY nama_siswa ASC");
$data_all_siswa = [];
while ($row = mysqli_fetch_assoc($q_all_siswa)) {
    $data_all_siswa[$row['kelas']][] = ['nisn' => $row['nisn'], 'nama' => $row['nama_siswa']];
}
$json_siswa = json_encode($data_all_siswa);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Prestasi - Trophile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/guru_dashboard.css?v=<?php echo time(); ?>">
</head>

<body>
    <nav class="navbar-custom">
        <div class="nav-container">
            <div class="brand-wrapper">
                <img src="../../assets/images/logo.png" alt="Logo" width="35">
                <a href="dashboard.php" class="brand-logo">TROPHILE</a>
            </div>
            <div class="dropdown">
                <a href="javascript:void(0)" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" style="border: 1px solid rgba(255,255,255,0.3); padding: 6px 15px; border-radius: 8px;">
                    <span class="me-2 fw-medium d-none d-md-block">Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="border-radius: 12px; border:none; margin-top:10px;">
                    <li>
                        <h6 class="dropdown-header text-muted">Akses Guru Pembina</h6>
                    </li>
                    <li><a class="dropdown-item fw-medium" href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editProfilGuru">Pengaturan Akun</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item text-danger fw-bold" href="../../logout.php">Keluar</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="floating-card" style="max-width: 850px; margin: auto;">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                <h4 class="fw-bold m-0 text-navy">Form Input Prestasi Siswa</h4>
                <a href="dashboard.php" class="btn btn-outline-dark btn-sm rounded-pill px-3 fw-medium">&larr; Batal & Kembali</a>
            </div>

            <?php echo $pesan; ?>

            <form action="" method="POST" enctype="multipart/form-data">

                <div class="row">
                    <div class="col-md-5 mb-4">
                        <label class="form-label">Pilih Kelas</label>
                        <select id="pilihKelas" class="form-select bg-light" required>
                            <option value="">-- Pilih Kelas --</option>
                            <?php
                            // Mengambil daftar semua kelas (termasuk kelas kosong buatan Super Admin)
                            $q_kelas = mysqli_query($conn, "SELECT nama_kelas FROM master_kelas ORDER BY nama_kelas ASC");
                            while ($k = mysqli_fetch_assoc($q_kelas)) {
                                $kelas_val = htmlspecialchars($k['nama_kelas']);
                                echo "<option value='$kelas_val'>Kelas $kelas_val</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-7 mb-4">
                        <label class="form-label">Nama Siswa Binaan</label>
                        <select name="nisn" id="pilihSiswa" class="form-select bg-light" required disabled>
                            <option value="">-- Silakan Pilih Kelas Dulu --</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Nama Kompetisi / Kejuaraan</label>
                    <input type="text" name="nama_lomba" class="form-control" required placeholder="Contoh: Olimpiade Sains Nasional Matematika">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Kategori Lomba</label>
                        <select name="kategori" id="kategoriSelect" class="form-select" onchange="checkKategori(this.value)" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Akademik">Akademik</option>
                            <option value="Non-Akademik">Non-Akademik</option>
                            <option value="Lainnya">Lainnya (Ketik Manual)</option>
                        </select>
                        <input type="text" name="kategori_lainnya" id="kategoriLainnyaInput" class="form-control mt-2 border-warning" placeholder="Ketik kategori..." style="display:none;">
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Tingkat Penyelenggaraan</label>
                        <select name="tingkat" class="form-select" required>
                            <option value="">-- Pilih Tingkat --</option>
                            <option value="Kota/Kabupaten">Kota / Kabupaten</option>
                            <option value="Provinsi">Provinsi</option>
                            <option value="Nasional">Nasional</option>
                            <option value="Internasional">Internasional</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Hasil / Peringkat Juara</label>
                        <select name="peringkat" id="peringkatSelect" class="form-select" onchange="checkPeringkat(this.value)" required>
                            <option value="">-- Pilih Hasil --</option>
                            <option value="Juara 1">Juara 1</option>
                            <option value="Juara 2">Juara 2</option>
                            <option value="Juara 3">Juara 3</option>
                            <option value="Harapan 1">Harapan 1</option>
                            <option value="Lainnya">Lainnya (Ketik Manual)</option>
                        </select>
                        <input type="text" name="peringkat_lainnya" id="peringkatLainnyaInput" class="form-control mt-2 border-warning" placeholder="Contoh: Medali Emas / Harapan 3" style="display:none;">
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Tanggal Pelaksanaan</label>
                        <input type="date" name="tgl_lomba" class="form-control" required>
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-6 mb-3">
                        <div class="p-3 bg-light rounded border border-secondary border-opacity-25 h-100">
                            <label class="form-label text-dark">File Sertifikat/Piagam <span class="text-danger">*</span></label>
                            <input type="file" name="sertifikat" class="form-control bg-white" accept=".pdf,.jpg,.jpeg,.png" required>
                            <div class="form-text small">Wajib diisi. Format: PDF, JPG, PNG (Max 5MB)</div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="p-3 bg-light rounded border border-secondary border-opacity-25 h-100">
                            <label class="form-label text-dark">Foto Penyerahan Piala (Opsional)</label>
                            <input type="file" name="file_trofi" class="form-control bg-white" accept=".jpg,.jpeg,.png">
                            <div class="form-text small">Disarankan untuk masuk di Galeri Sekolah. Format: JPG, PNG (Max 5MB)</div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="submit_prestasi" class="btn btn-action w-100 py-3 fw-bold" style="font-size: 1.1rem;">Kirim & Ajukan Verifikasi Data</button>
            </form>
        </div>
    </div>

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
                        <input type="text" name="nama_guru" class="form-control" value="<?php echo htmlspecialchars($dt_profil['nama_guru'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold text-navy">Password Akun (Kosongkan jika tidak diubah)</label>
                        <input type="password" name="pass_guru" class="form-control" placeholder="Minimal 8 karakter, kombinasi huruf & angka" pattern="(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}" title="Password minimal 8 karakter, mengandung huruf dan angka">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="submit" name="edit_profil_guru" class="btn btn-action w-100">Simpan Pengaturan</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/remember-tab.js"></script>

    <script>
        // Logika Dropdown Kelas Dinamis
        const dataSiswa = <?php echo $json_siswa; ?>;
        const selectKelas = document.getElementById('pilihKelas');
        const selectSiswa = document.getElementById('pilihSiswa');

        selectKelas.addEventListener('change', function() {
            const kelasTerpilih = this.value;
            selectSiswa.innerHTML = '<option value="">-- Silakan Pilih Siswa --</option>';

            if (kelasTerpilih) {
                if (dataSiswa[kelasTerpilih]) {
                    // Jika ada siswa di kelas tersebut
                    selectSiswa.disabled = false;
                    dataSiswa[kelasTerpilih].forEach(function(siswa) {
                        const option = document.createElement('option');
                        option.value = siswa.nisn;
                        option.textContent = siswa.nama + " (" + siswa.nisn + ")";
                        selectSiswa.appendChild(option);
                    });
                } else {
                    // Jika kelas kosong (baru dibuat Super Admin)
                    selectSiswa.disabled = true;
                    selectSiswa.innerHTML = '<option value="">-- Belum ada siswa di kelas ini --</option>';
                }
            } else {
                selectSiswa.disabled = true;
                selectSiswa.innerHTML = '<option value="">-- Silakan Pilih Kelas Dulu --</option>';
            }
        });

        // Tampilkan Form Input Tambahan untuk Kategori "Lainnya"
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

        // Tampilkan Form Input Tambahan untuk Peringkat "Lainnya"
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