<?php
session_start();
require 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf();

    $username = trim($_POST['username']);
    $password_input = trim($_POST['password']);
    $remember = isset($_POST['remember']);

    $login_success = false;
    $role = "";
    $nama = "";
    $nip_nisn = "";

    function cek_dan_migrasi_sandi($conn, $tabel, $kolom_id, $val_id, $input_pass, $db_pass)
    {
        if (password_verify($input_pass, $db_pass)) {
            return true;
        }
        
        if ($input_pass === $db_pass) {
            $hash_baru = password_hash($input_pass, PASSWORD_DEFAULT);
            db_query($conn, "UPDATE $tabel SET password=? WHERE $kolom_id=?", "ss", $hash_baru, $val_id);
            return true;
        }
        return false;
    }

    $cek = db_query($conn, "SELECT * FROM super_admin WHERE username=?", "s", $username);
    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        $db_pass = $data['PASSWORD'] ?? $data['password'] ?? '';
        if (cek_dan_migrasi_sandi($conn, 'super_admin', 'username', $username, $password_input, $db_pass)) {
            $login_success = true;
            $role = "Super Admin";
            $nama = "Super Administrator";
            $nip_nisn = $username;
        }
    }

    if (!$login_success) {
        $cek = db_query($conn, "SELECT * FROM admin_tu WHERE username=? OR nip=?", "ss", $username, $username);
        if (mysqli_num_rows($cek) > 0) {
            $data = mysqli_fetch_assoc($cek);
            $db_pass = $data['PASSWORD'] ?? $data['password'] ?? '';
            if (cek_dan_migrasi_sandi($conn, 'admin_tu', 'nip', $data['nip'], $password_input, $db_pass)) {
                $login_success = true;
                $role = "Admin TU";
                $nama = $data['nama_admin'];
                $nip_nisn = $data['nip'];
            }
        }
    }

    if (!$login_success) {
        $cek = db_query($conn, "SELECT * FROM guru WHERE nip=? AND status='Aktif'", "s", $username);
        if (mysqli_num_rows($cek) > 0) {
            $data = mysqli_fetch_assoc($cek);
            $db_pass = $data['PASSWORD'] ?? $data['password'] ?? '';
            if (cek_dan_migrasi_sandi($conn, 'guru', 'nip', $data['nip'], $password_input, $db_pass)) {
                $login_success = true;
                $role = "Guru";
                $nama = $data['nama_guru'];
                $nip_nisn = $data['nip'];
            }
        }
    }

    if (!$login_success) {
        $cek = db_query($conn, "SELECT * FROM kepala_sekolah WHERE (username=? OR nip=?) AND status='Aktif'", "ss", $username, $username);
        if (mysqli_num_rows($cek) > 0) {
            $data = mysqli_fetch_assoc($cek);
            $db_pass = $data['PASSWORD'] ?? $data['password'] ?? '';
            if (cek_dan_migrasi_sandi($conn, 'kepala_sekolah', 'nip', $data['nip'], $password_input, $db_pass)) {
                $login_success = true;
                $role = "Kepala Sekolah";
                $nama = $data['nama_kepala_sekolah'];
                $nip_nisn = $data['nip'];
            }
        }
    }

    if (!$login_success) {
        $cek = db_query($conn, "SELECT * FROM siswa WHERE nisn=? AND status='Aktif'", "s", $username);
        if (mysqli_num_rows($cek) > 0) {
            $data = mysqli_fetch_assoc($cek);
            $db_pass = $data['PASSWORD'] ?? $data['password'] ?? '';
            if (cek_dan_migrasi_sandi($conn, 'siswa', 'nisn', $data['nisn'], $password_input, $db_pass)) {
                $login_success = true;
                $role = "Siswa";
                $nama = $data['nama_siswa'];
                $nip_nisn = $data['nisn'];
            }
        }
    }

    if ($login_success) {
        $_SESSION['role'] = $role;
        $_SESSION['nama'] = $nama;
        $_SESSION['username'] = $nip_nisn;

        if ($role == 'Guru' || $role == 'Admin TU' || $role == 'Kepala Sekolah') $_SESSION['nip'] = $nip_nisn;
        if ($role == 'Siswa') $_SESSION['nisn'] = $nip_nisn;

        if ($remember) {
            setcookie('user_login', $nip_nisn, time() + (86400 * 30), "/");
            setcookie('user_role', $role, time() + (86400 * 30), "/");
        }

        if ($role == 'Super Admin') header("Location: dashboard/super_admin/dashboard.php");
        else if ($role == 'Admin TU') header("Location: dashboard/admin_tu/dashboard.php");
        else if ($role == 'Guru') header("Location: dashboard/guru/dashboard.php");
        else if ($role == 'Kepala Sekolah') header("Location: dashboard/kepsek/dashboard.php");
        else if ($role == 'Siswa') header("Location: dashboard/siswa/dashboard.php");
        exit();
    } else {
        $_SESSION['error_login'] = "ID Pengguna atau Kata Sandi salah / Akun Non-Aktif!";
        header("Location: login.php");
        exit();
    }
}
