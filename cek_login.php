<?php
session_start();
require 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // 0. Super Admin
    $cek = mysqli_query($conn, "SELECT * FROM super_admin WHERE username='$username' AND password='$password'");
    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['role'] = "Super Admin";
        $_SESSION['nama'] = "Super Administrator";
        header("Location: dashboard/super_admin/dashboard.php");
        exit();
    }

    // 1. Admin TU
    $cek = mysqli_query($conn, "SELECT * FROM admin_tu WHERE username='$username' AND password='$password'");
    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        $_SESSION['role'] = "Admin TU";
        $_SESSION['nama'] = $data['nama_admin'];
        $_SESSION['nip']  = $data['nip'];
        header("Location: dashboard/admin_tu/dashboard.php");
        exit();
    }

    // 2. Guru
    $cek = mysqli_query($conn, "SELECT * FROM guru WHERE nip='$username' AND password='$password'");
    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        $_SESSION['role'] = "Guru";
        $_SESSION['nama'] = $data['nama_guru'];
        $_SESSION['nip']  = $data['nip'];
        header("Location: dashboard/guru/dashboard.php");
        exit();
    }

    // 3. Kepala Sekolah
    $cek = mysqli_query($conn, "SELECT * FROM kepala_sekolah WHERE username='$username' AND password='$password'");
    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        $_SESSION['role']     = "Kepala Sekolah";
        $_SESSION['nama']     = $data['nama_kepala_sekolah'];
        $_SESSION['username'] = $username;
        header("Location: dashboard/kepsek/dashboard.php");
        exit();
    }

    // 4. Siswa
    $cek = mysqli_query($conn, "SELECT * FROM siswa WHERE nisn='$username' AND password='$password'");
    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        $_SESSION['role']     = "Siswa";
        $_SESSION['nama']     = $data['nama_siswa'];
        $_SESSION['nisn']     = $data['nisn'];
        $_SESSION['username'] = $data['nisn'];
        header("Location: dashboard/siswa/dashboard.php");
        exit();
    }

    // Gagal
    echo "<script>
            alert('Login Gagal! ID Pengguna atau Password salah.');
            window.location='index.php';
          </script>";
}
