<?php
session_start();
require 'koneksi.php';

$pesan = "";
$is_forgot_mode = false;
$step_aktif = 1; // 1: Minta Kode, 2: Punya Kode (Input Password Baru)
$username_req = ""; // Variabel penampung username

// ============================================================
// PROSES 1: MINTA KODE RESET KE ADMIN TU
// ============================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] == 'minta_kode') {
    $is_forgot_mode = true;
    $username_req = mysqli_real_escape_string($conn, $_POST['username_req']);

    // Validasi apakah user terdaftar di sistem
    $cek1 = mysqli_query($conn, "SELECT nisn FROM siswa WHERE nisn='$username_req'");
    $cek2 = mysqli_query($conn, "SELECT nip FROM guru WHERE nip='$username_req'");

    if (mysqli_num_rows($cek1) > 0 || mysqli_num_rows($cek2) > 0) {

        // MATIKAN SEMUA REQUEST LAMA (BUG FIX)
        mysqli_query($conn, "UPDATE request_reset SET status_req='Selesai' WHERE username='$username_req'");

        // BIKIN REQUEST BARU
        mysqli_query($conn, "INSERT INTO request_reset (username, status_req) VALUES ('$username_req', 'Pending')");

        $pesan = "<div class='alert-box alert-success'><b>Terkirim!</b> Notifikasi telah masuk ke sistem TU.<br>Silakan temui Admin TU untuk mengambil kode Anda.</div>";
        $step_aktif = 2; // Pindah ke tampilan eksekusi
    } else {
        $pesan = "<div class='alert-box alert-error'>ID Pengguna / Username tidak terdaftar!</div>";
    }
}

// ============================================================
// PROSES 2: EKSEKUSI RESET PASSWORD DENGAN KODE TU
// ============================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] == 'reset_password') {
    $is_forgot_mode = true;
    $step_aktif = 2; // Tetap di tahap 2 kalau ada error

    $username        = mysqli_real_escape_string($conn, $_POST['username']);
    $kode_admin      = mysqli_real_escape_string($conn, $_POST['kode_admin']);
    $password_baru   = $_POST['password_baru'];
    $verif_password  = $_POST['verifikasi_password_baru'];

    if ($password_baru !== $verif_password) {
        $pesan = "<div class='alert-box alert-error'>Gagal! Verifikasi password baru tidak cocok.</div>";
        $username_req = $username;
    } else {
        // Cek validitas kode
        $cek_req = mysqli_query($conn, "SELECT * FROM request_reset WHERE username='$username' AND kode_unik='$kode_admin' AND status_req='Approved'");

        if (mysqli_num_rows($cek_req) > 0) {
            $data_req = mysqli_fetch_assoc($cek_req);

            // CEK KADALUARSA WAKTU 5 MENIT (BACKEND)
            $waktu_bikin = strtotime($data_req['waktu_req']);
            $waktu_sekarang = time();
            $selisih_menit = round(abs($waktu_sekarang - $waktu_bikin) / 60, 2);

            if ($selisih_menit > 5) {
                mysqli_query($conn, "UPDATE request_reset SET status_req='Selesai' WHERE id_request=" . $data_req['id_request']);
                $pesan = "<div class='alert-box alert-error'><b>Kadaluarsa!</b> Kode otorisasi sudah lewat dari 5 menit. Silakan ulangi request.</div>";
                $step_aktif = 1; // Paksa ngulang minta kode
            } else {
                // EKSEKUSI GANTI PASSWORD
                $password_fix = mysqli_real_escape_string($conn, $password_baru);

                $cek_siswa = mysqli_query($conn, "SELECT * FROM siswa WHERE nisn='$username'");
                if (mysqli_num_rows($cek_siswa) > 0) {
                    mysqli_query($conn, "UPDATE siswa SET password='$password_fix' WHERE nisn='$username'");
                } else {
                    mysqli_query($conn, "UPDATE guru SET password='$password_fix' WHERE nip='$username'");
                }

                // Matikan kode agar tidak bisa dipakai ulang
                mysqli_query($conn, "UPDATE request_reset SET status_req='Selesai' WHERE id_request=" . $data_req['id_request']);

                $pesan = "<div class='alert-box alert-success'><b>Berhasil!</b> Password Anda telah direset.<br>Silakan kembali ke halaman login.</div>";
                $step_aktif = 1;
            }
        } else {
            $pesan = "<div class='alert-box alert-error'><b>Gagal!</b> Kode Otorisasi salah atau belum di-ACC oleh Admin TU.</div>";
            $username_req = $username;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Trophile SMANSA</title>
    <link rel="stylesheet" href="assets/css/login.css?v=<?php echo time(); ?>">
</head>

<body>

    <div class="auth-wrapper <?php echo $is_forgot_mode ? 'forgot-active' : ''; ?>" id="authWrapper">
        <div class="image-panel"></div>
        <div class="form-panel">

            <div class="form-view login-view">
                <div class="auth-header">
                    <h2>Trophile</h2>
                    <p>Sistem Informasi Manajemen Prestasi Siswa</p>
                    <span class="nama-sekolah">SMAN 1 Kesamben</span>
                </div>
                <form action="cek_login.php" method="POST">
                    <div class="form-group"><input type="text" name="username" class="form-input" placeholder="NISN / NIP / Username" required autocomplete="off"></div>
                    <div class="form-group"><input type="password" name="password" class="form-input" placeholder="Password" required></div>
                    <div class="login-actions">
                        <label style="color: #6c757d; cursor: pointer;"><input type="checkbox" name="remember"> Ingat saya</label>
                        <a onclick="toggleForm()">Lupa Password?</a>
                    </div>
                    <button type="submit" class="btn-auth">MASUK</button>
                </form>
                <div class="auth-footer">
                    <p>&copy; <?php echo date('Y'); ?> Trophile SMANSA</p>
                </div>
            </div>


            <div class="form-view forgot-view">
                <div class="auth-header">
                    <h2 style="font-size: 28px;">Lupa Password?</h2>
                    <p>Masukkan Username/NISN Anda untuk mengirimkan request notifikasi reset ke Admin TU.</p>
                </div>

                <?php echo $pesan; ?>

                <div id="box-minta-kode" style="display: <?php echo ($step_aktif == 1) ? 'block' : 'none'; ?>;">
                    <form action="index.php" method="POST">
                        <input type="hidden" name="form_type" value="minta_kode">
                        <div class="form-group" style="text-align: left;">
                            <input type="text" name="username_req" class="form-input" placeholder="Masukkan NISN / NIP / Username" required>
                        </div>
                        <button type="submit" class="btn-auth">Kirim Notifikasi ke Admin TU</button>
                    </form>
                </div>

                <div id="box-eksekusi" style="display: <?php echo ($step_aktif == 2) ? 'block' : 'none'; ?>;">

                    <div style="background:#fff5f5; border:1px solid #fee2e2; padding:15px; border-radius:6px; margin-bottom:20px; text-align:center;">
                        <span style="font-size:13px; font-weight:bold; color:#991b1b; display:block; margin-bottom:5px;">Sisa Waktu Kode Otorisasi:</span>
                        <div id="waktu-mundur" style="font-size: 32px; font-weight: 900; color: #dc3545; font-family: monospace;">05:00</div>
                    </div>

                    <form action="index.php" method="POST">
                        <input type="hidden" name="form_type" value="reset_password">

                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($username_req); ?>">

                        <div class="form-group" style="text-align: left;">
                            <label style="font-size: 13px; font-weight: bold; margin-bottom: 5px; display: block; color: #dc3545;">Kode Otorisasi (5 Digit)</label>
                            <input type="text" name="kode_admin" class="form-input input-danger" placeholder="Contoh: X7A9P" required>
                        </div>
                        <div class="form-group" style="text-align: left;">
                            <label style="font-size: 13px; font-weight: bold; margin-bottom: 5px; display: block;">Password Baru</label>
                            <input type="password" name="password_baru" class="form-input" placeholder="Masukkan sandi baru" required>
                        </div>
                        <div class="form-group" style="text-align: left;">
                            <label style="font-size: 13px; font-weight: bold; margin-bottom: 5px; display: block;">Verifikasi Password Baru</label>
                            <input type="password" name="verifikasi_password_baru" class="form-input" placeholder="Ulangi sandi baru" required>
                        </div>

                        <button type="submit" id="btn-submit-reset" class="btn-auth">Simpan Password Baru</button>
                    </form>
                </div>

                <div class="auth-footer" style="margin-top: 25px;">
                    <a onclick="toggleForm()" style="color: #6c757d; text-decoration: none; font-weight: bold; cursor: pointer;">&larr; Kembali ke Halaman Login</a>
                </div>
            </div>

        </div>
    </div>

    <script>
        let timerInterval;

        function toggleForm() {
            document.getElementById('authWrapper').classList.toggle('forgot-active');
            setTimeout(() => {
                switchStep(1); // Selalu reset ke langkah 1 kalau ditutup
            }, 500);
        }

        function switchStep(step) {
            if (step === 1) {
                document.getElementById('box-minta-kode').style.display = 'block';
                document.getElementById('box-eksekusi').style.display = 'none';
                clearInterval(timerInterval);
            } else {
                document.getElementById('box-minta-kode').style.display = 'none';
                document.getElementById('box-eksekusi').style.display = 'block';
                startCountdown(300); // Mulai dari 300 detik (5 Menit)
            }
        }

        function startCountdown(duration) {
            let timer = duration,
                minutes, seconds;
            let display = document.getElementById('waktu-mundur');
            let btnSubmit = document.getElementById('btn-submit-reset');

            // Nyalakan tombol
            btnSubmit.disabled = false;
            btnSubmit.style.backgroundColor = '#212529';
            btnSubmit.innerHTML = 'Simpan Password Baru';

            clearInterval(timerInterval); // Bersihkan timer lama

            timerInterval = setInterval(function() {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    clearInterval(timerInterval);
                    display.textContent = "KADALUARSA";

                    // Matikan tombol submit
                    btnSubmit.disabled = true;
                    btnSubmit.style.backgroundColor = '#6c757d';
                    btnSubmit.innerHTML = 'Waktu Habis! Silakan Ulangi';
                }
            }, 1000);
        }

        // Kalau halaman direfresh/disubmit dan statusnya di Tahap 2, jalankan timer
        <?php if ($is_forgot_mode && $step_aktif == 2): ?>
            switchStep(2);
        <?php endif; ?>
    </script>
</body>

</html>