<?php
session_start();
require 'koneksi.php';

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username    = $_POST['username'];
    $kode_admin  = $_POST['kode_admin'];
    $reset_password  = "123";
    $kode_rahasia_tu = "RESET-TU-2026";

    if ($kode_admin !== $kode_rahasia_tu) {
        $pesan = "<div class='alert alert-danger mt-3 small'>Gagal! Kode Otorisasi salah. Silakan minta kode yang benar ke Admin TU.</div>";
    } else {
        $cek_siswa = mysqli_query($conn, "SELECT * FROM siswa WHERE nisn='$username'");
        if (mysqli_num_rows($cek_siswa) > 0) {
            mysqli_query($conn, "UPDATE siswa SET password='$reset_password' WHERE nisn='$username'");
            $pesan = "<div class='alert alert-success mt-3 small'>Sukses! Password berhasil direset menjadi: <strong>123</strong><br>Silakan login dan segera ubah password Anda.</div>";
        } else {
            $cek_guru = mysqli_query($conn, "SELECT * FROM guru WHERE nip='$username'");
            if (mysqli_num_rows($cek_guru) > 0) {
                mysqli_query($conn, "UPDATE guru SET password='$reset_password' WHERE nip='$username'");
                $pesan = "<div class='alert alert-success mt-3 small'>Sukses! Password berhasil direset menjadi: <strong>123</strong><br>Silakan login dan segera ubah password Anda.</div>";
            } else {
                $cek_admin = mysqli_query($conn, "SELECT * FROM admin_tu WHERE username='$username'");
                if (mysqli_num_rows($cek_admin) > 0) {
                    mysqli_query($conn, "UPDATE admin_tu SET password='$reset_password' WHERE username='$username'");
                    $pesan = "<div class='alert alert-success mt-3 small'>Sukses! Password Admin berhasil direset menjadi: <strong>123</strong></div>";
                } else {
                    $pesan = "<div class='alert alert-danger mt-3 small'>ID Pengguna / Username tidak ditemukan di sistem!</div>";
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Trophile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/lupa_password.css">
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-sm-10">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-dark">Lupa Password</h3>
                            <p class="text-muted small">Silakan temui Admin TU untuk mendapatkan Kode Otorisasi Reset Password.</p>
                        </div>
                        <form action="" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">ID Pengguna / Username</label>
                                <input type="text" name="username" class="form-control" required placeholder="Masukkan NISN / NIP / Username">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold text-danger">Kode Otorisasi Admin</label>
                                <input type="password" name="kode_admin" class="form-control border-danger" required placeholder="Masukkan kode dari Admin TU">
                            </div>
                            <button type="submit" class="btn btn-dark w-100 fw-bold py-2">Reset Password</button>
                        </form>
                        <?php echo $pesan; ?>
                        <div class="mt-4 text-center">
                            <a href="index.php" class="text-decoration-none small text-secondary fw-semibold">&larr; Kembali ke Halaman Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>