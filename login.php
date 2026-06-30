<?php
session_start();
require 'koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) && isset($_COOKIE['user_login']) && isset($_COOKIE['user_role'])) {
    $_SESSION['username'] = $_COOKIE['user_login'];
    $_SESSION['role'] = $_COOKIE['user_role'];
}

if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role == 'Super Admin') header("Location: dashboard/super_admin/dashboard.php");
    else if ($role == 'Admin TU') header("Location: dashboard/admin_tu/dashboard.php");
    else if ($role == 'Guru') header("Location: dashboard/guru/dashboard.php");
    else if ($role == 'Kepala Sekolah') header("Location: dashboard/kepsek/dashboard.php");
    else if ($role == 'Siswa') header("Location: dashboard/siswa/dashboard.php");
    exit();
}

$pesan_login = "";
if (isset($_SESSION['error_login'])) {
    $pesan_login = "<div class='alert-box alert-error' style='margin-bottom:15px;'>" . $_SESSION['error_login'] . "</div>";
    unset($_SESSION['error_login']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type'])) {
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

    if ($_POST['form_type'] == 'minta_kode') {
        $username_req = mysqli_real_escape_string($conn, $_POST['username_req']);
        $cek1 = mysqli_query($conn, "SELECT nisn FROM siswa WHERE nisn='$username_req'");
        $cek2 = mysqli_query($conn, "SELECT nip FROM guru WHERE nip='$username_req'");
        $cek3 = mysqli_query($conn, "SELECT nip FROM admin_tu WHERE username='$username_req' OR nip='$username_req'");
        $cek4 = mysqli_query($conn, "SELECT nip FROM kepala_sekolah WHERE username='$username_req' OR nip='$username_req'");
        $cek5 = mysqli_query($conn, "SELECT username FROM super_admin WHERE username='$username_req'");

        if (mysqli_num_rows($cek1) > 0 || mysqli_num_rows($cek2) > 0 || mysqli_num_rows($cek3) > 0 || mysqli_num_rows($cek4) > 0 || mysqli_num_rows($cek5) > 0) {
            mysqli_query($conn, "UPDATE request_reset SET status_req='Selesai' WHERE username='$username_req'");
            mysqli_query($conn, "INSERT INTO request_reset (username, status_req) VALUES ('$username_req', 'Pending')");
            $response = ['status' => 'success', 'msg' => "<b>Terkirim!</b> Notifikasi telah masuk ke sistem TU.<br>Silakan temui Admin untuk mengambil kode."];
        } else {
            $response = ['status' => 'error', 'msg' => "ID Pengguna tidak terdaftar!"];
        }
        if ($is_ajax) {
            echo json_encode($response);
            exit;
        }
    }

    if ($_POST['form_type'] == 'reset_password') {
        $username        = mysqli_real_escape_string($conn, $_POST['username']);
        $kode_admin      = mysqli_real_escape_string($conn, $_POST['kode_admin']);
        $password_baru   = $_POST['password_baru'];
        $verif_password  = $_POST['verifikasi_password_baru'];

        if ($password_baru !== $verif_password) {
            $response = ['status' => 'error', 'msg' => "Gagal! Verifikasi password baru tidak cocok."];
        } else if (strlen($password_baru) < 8 || !preg_match("/[a-zA-Z]/", $password_baru) || !preg_match("/\d/", $password_baru)) {
            $response = ['status' => 'error', 'msg' => "Password minimal 8 karakter, serta mengandung huruf dan angka."];
        } else {
            $cek_req = mysqli_query($conn, "SELECT * FROM request_reset WHERE username='$username' AND kode_unik='$kode_admin' AND status_req='Approved'");
            if (mysqli_num_rows($cek_req) > 0) {
                $data_req = mysqli_fetch_assoc($cek_req);
                $waktu_bikin = strtotime($data_req['waktu_req']);
                $selisih_menit = round(abs(time() - $waktu_bikin) / 60, 2);

                if ($selisih_menit > 5) {
                    mysqli_query($conn, "UPDATE request_reset SET status_req='Selesai' WHERE id_request=" . $data_req['id_request']);
                    $response = ['status' => 'expired', 'msg' => "<b>Kadaluarsa!</b> Kode otorisasi lewat 5 menit. Silakan ulangi request."];
                } else {
                    $password_fix = password_hash($password_baru, PASSWORD_DEFAULT);
                    
                    if (mysqli_num_rows(mysqli_query($conn, "SELECT nisn FROM siswa WHERE nisn='$username'")) > 0) {
                        mysqli_query($conn, "UPDATE siswa SET password='$password_fix' WHERE nisn='$username'");
                    } else if (mysqli_num_rows(mysqli_query($conn, "SELECT nip FROM guru WHERE nip='$username'")) > 0) {
                        mysqli_query($conn, "UPDATE guru SET password='$password_fix' WHERE nip='$username'");
                    } else if (mysqli_num_rows(mysqli_query($conn, "SELECT nip FROM admin_tu WHERE username='$username' OR nip='$username'")) > 0) {
                        mysqli_query($conn, "UPDATE admin_tu SET PASSWORD='$password_fix' WHERE username='$username' OR nip='$username'");
                    } else if (mysqli_num_rows(mysqli_query($conn, "SELECT nip FROM kepala_sekolah WHERE username='$username' OR nip='$username'")) > 0) {
                        mysqli_query($conn, "UPDATE kepala_sekolah SET PASSWORD='$password_fix' WHERE username='$username' OR nip='$username'");
                    } else if (mysqli_num_rows(mysqli_query($conn, "SELECT username FROM super_admin WHERE username='$username'")) > 0) {
                        mysqli_query($conn, "UPDATE super_admin SET password='$password_fix' WHERE username='$username'");
                    }
                    mysqli_query($conn, "UPDATE request_reset SET status_req='Selesai' WHERE id_request=" . $data_req['id_request']);
                    $response = ['status' => 'success_reset', 'msg' => "<b>Berhasil!</b> Password Anda telah direset.<br>Silakan kembali ke halaman login."];
                }
            } else {
                $response = ['status' => 'error', 'msg' => "<b>Gagal!</b> Kode Otorisasi salah atau belum di-ACC oleh TU."];
            }
        }
        if ($is_ajax) {
            echo json_encode($response);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trophile</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="auth-wrapper" id="authWrapper">
        <div class="image-panel">
            <div class="image-overlay">
                <img src="assets/images/logo.png" alt="Logo" class="logo">
                <h1>Trophile</h1>
                <p>Sistem Informasi Manajemen Prestasi Siswa</p>
            </div>
        </div>

        <div class="form-panel">
            <div class="form-view login-view">
                <div class="auth-header">
                    <h2>Selamat Datang</h2>
                    <p>Silakan masuk menggunakan akun Anda</p>
                </div>

                <?php echo $pesan_login; ?>
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
                    <p>&copy; <?php echo date('Y'); ?> Trophile</p>
                </div>
            </div>

            <div class="form-view forgot-view">
                <div class="auth-header">
                    <h2>Reset Password</h2>
                    <p>Notifikasi sistem akan dikirim ke Admin TU.</p>
                </div>

                <div id="ajax-pesan"></div>
                <div id="box-minta-kode" style="display: block;">
                    <form id="formMintaKode" onsubmit="prosesAjax(event, 'formMintaKode')">
                        <input type="hidden" name="form_type" value="minta_kode">
                        <div class="form-group">
                            <label>ID Pengguna</label>
                            <input type="text" name="username_req" id="username_req" class="form-input" placeholder="Masukkan NISN / NIP / Username" required>
                        </div>
                        <button type="submit" id="btn-minta" class="btn-auth">Kirim Request TU</button>
                    </form>
                </div>

                <div id="box-eksekusi" style="display: none;">
                    <div class="timer-box">
                        <span>Sisa Waktu Kode Otorisasi:</span>
                        <div id="waktu-mundur">05:00</div>
                    </div>
                    <form id="formReset" onsubmit="prosesAjax(event, 'formReset')">
                        <input type="hidden" name="form_type" value="reset_password">
                        <input type="hidden" name="username" id="hidden_username">

                        <div class="form-group">
                            <label class="text-danger">Kode Otorisasi (5 Digit)</label>
                            <input type="text" name="kode_admin" class="form-input input-danger" placeholder="Contoh: X7A9P" required>
                        </div>
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="password_baru" class="form-input" placeholder="Min 8 karakter, kombinasi huruf & angka" required pattern="(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}" title="Password minimal 8 karakter, mengandung huruf dan angka">
                        </div>
                        <div class="form-group">
                            <label>Verifikasi Password</label>
                            <input type="password" name="verifikasi_password_baru" class="form-input" placeholder="Ulangi sandi baru" required pattern="(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}" title="Password minimal 8 karakter, mengandung huruf dan angka">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let timerInterval;

        function toggleForm() {
            document.getElementById('authWrapper').classList.toggle('forgot-active');
            setTimeout(() => {
                document.getElementById('box-minta-kode').style.display = 'block';
                document.getElementById('box-eksekusi').style.display = 'none';
                document.getElementById('ajax-pesan').innerHTML = '';
                clearInterval(timerInterval);
            }, 500);
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
                }
            }, 1000);
        }

        async function prosesAjax(e, formId) {
            e.preventDefault();
            const form = document.getElementById(formId);
            const formData = new FormData(form);
            const btnSubmit = form.querySelector('button[type="submit"]');
            const teksAwal = btnSubmit.innerHTML;

            btnSubmit.innerHTML = 'Memproses...';
            btnSubmit.disabled = true;

            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();

                let pesanBox = document.getElementById('ajax-pesan');

                if (data.status === 'success') {
                    pesanBox.innerHTML = `<div class='alert-box alert-success'>${data.msg}</div>`;
                    document.getElementById('hidden_username').value = document.getElementById('username_req').value;
                    document.getElementById('box-minta-kode').style.display = 'none';
                    document.getElementById('box-eksekusi').style.display = 'block';
                    startCountdown(300);
                } else if (data.status === 'success_reset') {
                    pesanBox.innerHTML = `<div class='alert-box alert-success'>${data.msg}</div>`;
                    document.getElementById('box-eksekusi').style.display = 'none';
                    clearInterval(timerInterval);
                } else if (data.status === 'expired') {
                    pesanBox.innerHTML = `<div class='alert-box alert-error'>${data.msg}</div>`;
                    document.getElementById('box-minta-kode').style.display = 'block';
                    document.getElementById('box-eksekusi').style.display = 'none';
                } else {
                    pesanBox.innerHTML = `<div class='alert-box alert-error'>${data.msg}</div>`;
                }
            } catch (error) {
                console.error("AJAX Error:", error);
            } finally {
                btnSubmit.innerHTML = teksAwal;
                btnSubmit.disabled = false;
            }
        }
    </script>
</body>

</html>