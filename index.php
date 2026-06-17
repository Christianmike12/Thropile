<?php
session_start();
require 'koneksi.php';

// Cek jika user sudah login
if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role == 'Super Admin') header("Location: dashboard/super_admin/dashboard.php");
    else if ($role == 'Admin TU') header("Location: dashboard/admin_tu/dashboard.php");
    else if ($role == 'Guru') header("Location: dashboard/guru/dashboard.php");
    else if ($role == 'Kepala Sekolah') header("Location: dashboard/kepsek/dashboard.php");
    else if ($role == 'Siswa') header("Location: dashboard/siswa/dashboard.php");
    exit();
}

$pesan = "";
$is_forgot_mode = false;
$step_aktif = 1; // 1: Minta Kode, 2: Punya Kode (Input Password Baru)
$username_req = "";

// ============================================================
// PROSES 1: MINTA KODE RESET KE ADMIN TU
// ============================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] == 'minta_kode') {
    $is_forgot_mode = true;
    $username_req = mysqli_real_escape_string($conn, $_POST['username_req']);

    $cek1 = mysqli_query($conn, "SELECT nisn FROM siswa WHERE nisn='$username_req'");
    $cek2 = mysqli_query($conn, "SELECT nip FROM guru WHERE nip='$username_req'");

    if (mysqli_num_rows($cek1) > 0 || mysqli_num_rows($cek2) > 0) {
        mysqli_query($conn, "UPDATE request_reset SET status_req='Selesai' WHERE username='$username_req'");
        mysqli_query($conn, "INSERT INTO request_reset (username, status_req) VALUES ('$username_req', 'Pending')");

        $pesan = "<div class='alert-box alert-success'><b>Terkirim!</b> Notifikasi telah masuk ke sistem TU.<br>Silakan temui Admin untuk mengambil kode.</div>";
        $step_aktif = 2;
    } else {
        $pesan = "<div class='alert-box alert-error'>ID Pengguna tidak terdaftar!</div>";
    }
}

// ============================================================
// PROSES 2: EKSEKUSI RESET PASSWORD DENGAN KODE TU
// ============================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] == 'reset_password') {
    $is_forgot_mode = true;
    $step_aktif = 2;

    $username        = mysqli_real_escape_string($conn, $_POST['username']);
    $kode_admin      = mysqli_real_escape_string($conn, $_POST['kode_admin']);
    $password_baru   = $_POST['password_baru'];
    $verif_password  = $_POST['verifikasi_password_baru'];

    if ($password_baru !== $verif_password) {
        $pesan = "<div class='alert-box alert-error'>Gagal! Verifikasi password baru tidak cocok.</div>";
        $username_req = $username;
    } else {
        $cek_req = mysqli_query($conn, "SELECT * FROM request_reset WHERE username='$username' AND kode_unik='$kode_admin' AND status_req='Approved'");

        if (mysqli_num_rows($cek_req) > 0) {
            $data_req = mysqli_fetch_assoc($cek_req);

            $waktu_bikin = strtotime($data_req['waktu_req']);
            $waktu_sekarang = time();
            $selisih_menit = round(abs($waktu_sekarang - $waktu_bikin) / 60, 2);

            if ($selisih_menit > 5) {
                mysqli_query($conn, "UPDATE request_reset SET status_req='Selesai' WHERE id_request=" . $data_req['id_request']);
                $pesan = "<div class='alert-box alert-error'><b>Kadaluarsa!</b> Kode otorisasi lewat 5 menit. Silakan ulangi request.</div>";
                $step_aktif = 1;
            } else {
                $password_fix = mysqli_real_escape_string($conn, $password_baru);
                $cek_siswa = mysqli_query($conn, "SELECT * FROM siswa WHERE nisn='$username'");

                if (mysqli_num_rows($cek_siswa) > 0) {
                    mysqli_query($conn, "UPDATE siswa SET password='$password_fix' WHERE nisn='$username'");
                } else {
                    mysqli_query($conn, "UPDATE guru SET password='$password_fix' WHERE nip='$username'");
                }

                mysqli_query($conn, "UPDATE request_reset SET status_req='Selesai' WHERE id_request=" . $data_req['id_request']);
                $pesan = "<div class='alert-box alert-success'><b>Berhasil!</b> Password Anda telah direset.<br>Silakan kembali ke halaman login.</div>";
                $step_aktif = 1;
            }
        } else {
            $pesan = "<div class='alert-box alert-error'><b>Gagal!</b> Kode Otorisasi salah atau belum di-ACC oleh TU.</div>";
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css?v=<?php echo time(); ?>">
</head>

<body>

    <div class="auth-wrapper <?php echo $is_forgot_mode ? 'forgot-active' : ''; ?>" id="authWrapper">

        <div class="image-panel">
            <div class="image-overlay">
                <img src="assets/images/SMANSA.png" alt="Logo SMANSA" class="logo">
                <h1>Trophile</h1>
                <p>Sistem Informasi Manajemen Prestasi Siswa<br>SMA Negeri 1 Kesamben</p>
            </div>
        </div>

        <div class="form-panel">

            <div class="form-view login-view">
                <div class="auth-header">
                    <h2>Selamat Datang</h2>
                    <p>Silakan masuk menggunakan akun Anda</p>
                </div>

                <form action="cek_login.php" method="POST">
                    <div class="form-group">
                        <label>ID Pengguna</label>
                        <input type="text" name="username" class="form-input" placeholder="NISN / NIP / Username" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Kata Sandi</label>
                        <input type="password" name="password" class="form-input" placeholder="Masukkan kata sandi" required>
                    </div>
                    <div class="login-actions">
                        <label class="remember-me"><input type="checkbox" name="remember"><span>Ingat saya</span></label>
                        <a onclick="toggleForm()" class="link-action">Lupa Password?</a>
                    </div>
                    <button type="submit" class="btn-auth">Masuk</button>
                </form>

                <div class="auth-footer">
                    <p>&copy; <?php echo date('Y'); ?> Trophile SMANSA</p>
                </div>
            </div>

            <div class="form-view forgot-view">
                <div class="auth-header">
                    <h2>Reset Password</h2>
                    <p>Notifikasi sistem akan dikirim ke Admin TU.</p>
                </div>

                <?php echo $pesan; ?>

                <div id="box-minta-kode" style="display: <?php echo ($step_aktif == 1) ? 'block' : 'none'; ?>;">
                    <form action="index.php" method="POST">
                        <input type="hidden" name="form_type" value="minta_kode">
                        <div class="form-group">
                            <label>ID Pengguna</label>
                            <input type="text" name="username_req" class="form-input" placeholder="Masukkan NISN / NIP / Username" required>
                        </div>
                        <button type="submit" class="btn-auth">Kirim Request TU</button>
                    </form>
                </div>

                <div id="box-eksekusi" style="display: <?php echo ($step_aktif == 2) ? 'block' : 'none'; ?>;">
                    <div class="timer-box">
                        <span>Sisa Waktu Kode Otorisasi:</span>
                        <div id="waktu-mundur">05:00</div>
                    </div>

                    <form action="index.php" method="POST">
                        <input type="hidden" name="form_type" value="reset_password">
                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($username_req); ?>">

                        <div class="form-group">
                            <label class="text-danger">Kode Otorisasi (5 Digit)</label>
                            <input type="text" name="kode_admin" class="form-input input-danger" placeholder="Contoh: X7A9P" required>
                        </div>
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="password_baru" class="form-input" placeholder="Masukkan sandi baru" required>
                        </div>
                        <div class="form-group">
                            <label>Verifikasi Password</label>
                            <input type="password" name="verifikasi_password_baru" class="form-input" placeholder="Ulangi sandi baru" required>
                        </div>

                        <button type="submit" id="btn-submit-reset" class="btn-auth">Simpan Password Baru</button>
                    </form>
                </div>

                <div class="login-actions center-action">
                    <a onclick="toggleForm()" class="link-action">&larr; Kembali ke Halaman Login</a>
                </div>
            </div>

        </div>
    </div>

    <script>
        let timerInterval;

        function toggleForm() {
            document.getElementById('authWrapper').classList.toggle('forgot-active');
            setTimeout(() => {
                switchStep(1);
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
                startCountdown(300);
            }
        }

        function startCountdown(duration) {
            let timer = duration,
                minutes, seconds;
            let display = document.getElementById('waktu-mundur');
            let btnSubmit = document.getElementById('btn-submit-reset');

            btnSubmit.disabled = false;
            btnSubmit.innerHTML = 'Simpan Password Baru';
            btnSubmit.style.backgroundColor = '#003366';
            btnSubmit.style.color = '#ffffff';

            clearInterval(timerInterval);

            timerInterval = setInterval(function() {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    clearInterval(timerInterval);
                    display.textContent = "KADALUARSA";
                    btnSubmit.disabled = true;
                    btnSubmit.innerHTML = 'Waktu Habis! Silakan Ulangi';
                    btnSubmit.style.backgroundColor = '#ced4da';
                    btnSubmit.style.color = '#6c757d';
                }
            }, 1000);
        }

        <?php if ($is_forgot_mode && $step_aktif == 2): ?>
            switchStep(2);
        <?php endif; ?>
    </script>
</body>

</html>