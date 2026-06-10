<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Super Admin") {
    header("Location: ../../index.php");
    exit();
}

// LOGIKA TAMBAH
if (isset($_POST['tambah_admin'])) {
    $nip = $_POST['nip'];
    $nama = $_POST['nama_admin'];
    $user = $_POST['username'];
    mysqli_query($conn, "INSERT INTO admin_tu (nip, nama_admin, username, password) VALUES ('$nip', '$nama', '$user', '123')");
    header("Location: dashboard.php");
    exit();
}
if (isset($_POST['tambah_guru'])) {
    $nip = $_POST['nip'];
    $nama = $_POST['nama_guru'];
    mysqli_query($conn, "INSERT INTO guru (nip, nama_guru, password) VALUES ('$nip', '$nama', '123')");
    header("Location: dashboard.php");
    exit();
}
if (isset($_POST['tambah_siswa'])) {
    $nisn = $_POST['nisn'];
    $nama = $_POST['nama_siswa'];
    $kelas = $_POST['kelas'];
    mysqli_query($conn, "INSERT INTO siswa (nisn, nama_siswa, kelas, password) VALUES ('$nisn', '$nama', '$kelas', '123')");
    header("Location: dashboard.php");
    exit();
}

// LOGIKA HAPUS
if (isset($_GET['hapus_admin'])) {
    $id = $_GET['hapus_admin'];
    mysqli_query($conn, "DELETE FROM admin_tu WHERE nip='$id'");
    header("Location: dashboard.php");
    exit();
}
if (isset($_GET['hapus_guru'])) {
    $id = $_GET['hapus_guru'];
    mysqli_query($conn, "DELETE FROM guru WHERE nip='$id'");
    header("Location: dashboard.php");
    exit();
}
if (isset($_GET['hapus_siswa'])) {
    $id = $_GET['hapus_siswa'];
    mysqli_query($conn, "DELETE FROM siswa WHERE nisn='$id'");
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Super Admin - Trophile</title>
    <link rel="stylesheet" href="../../assets/css/super_admin_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar-custom">
        <div class="nav-container">
            <a href="#" class="brand-logo">TROPHILE</a>
            <div>
                <span class="me-3">Master Admin</span>
                <a href="../../logout.php" class="btn-logout">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="floating-card">
            <div class="header-title">Manajemen Data Pengguna</div>

            <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-admin">Admin TU</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-guru">Guru</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-siswa">Siswa</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-admin">
                    <div class="d-flex justify-content-between mb-3">
                        <h6 class="fw-bold">Data Petugas TU</h6>
                        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#addAdmin">+ Admin TU</button>
                    </div>
                    <table class="table table-hover text-center">
                        <thead>
                            <tr>
                                <th>NIP</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q = mysqli_query($conn, "SELECT * FROM admin_tu");
                            while ($r = mysqli_fetch_assoc($q)) {
                                echo "<tr>
                                <td>{$r['nip']}</td><td>{$r['nama_admin']}</td><td>{$r['username']}</td>
                                <td><a href='?hapus_admin={$r['nip']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Hapus admin ini?\")'>Hapus</a></td>
                            </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="tab-guru">
                    <div class="d-flex justify-content-between mb-3">
                        <h6 class="fw-bold">Data Guru Pembina</h6>
                        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#addGuru">+ Guru</button>
                    </div>
                    <table class="table table-hover text-center">
                        <thead>
                            <tr>
                                <th>NIP</th>
                                <th>Nama Guru</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q = mysqli_query($conn, "SELECT * FROM guru");
                            while ($r = mysqli_fetch_assoc($q)) {
                                echo "<tr>
                                <td>{$r['nip']}</td><td>{$r['nama_guru']}</td>
                                <td><a href='?hapus_guru={$r['nip']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Hapus guru ini?\")'>Hapus</a></td>
                            </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="tab-pane fade" id="tab-siswa">
                    <div class="d-flex justify-content-between mb-3">
                        <h6 class="fw-bold">Data Siswa SMAN 1 Kesamben</h6>
                        <button class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#addSiswa">+ Siswa</button>
                    </div>
                    <table class="table table-hover text-center">
                        <thead>
                            <tr>
                                <th>NISN</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $q = mysqli_query($conn, "SELECT * FROM siswa ORDER BY kelas, nama_siswa");
                            while ($r = mysqli_fetch_assoc($q)) {
                                echo "<tr>
                                <td>{$r['nisn']}</td><td>{$r['nama_siswa']}</td><td>{$r['kelas']}</td>
                                <td><a href='?hapus_siswa={$r['nisn']}' class='btn btn-danger btn-sm' onclick='return confirm(\"Hapus siswa ini?\")'>Hapus</a></td>
                            </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addAdmin" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Admin TU</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="nip" class="form-control mb-2" placeholder="NIP" required>
                    <input type="text" name="nama_admin" class="form-control mb-2" placeholder="Nama Lengkap" required>
                    <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
                </div>
                <div class="modal-footer"><button type="submit" name="tambah_admin" class="btn btn-dark">Simpan</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="addGuru" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Guru</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="nip" class="form-control mb-2" placeholder="NIP" required>
                    <input type="text" name="nama_guru" class="form-control mb-2" placeholder="Nama Guru" required>
                </div>
                <div class="modal-footer"><button type="submit" name="tambah_guru" class="btn btn-dark">Simpan</button></div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="addSiswa" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Siswa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="text" name="nisn" class="form-control mb-2" placeholder="NISN" required>
                    <input type="text" name="nama_siswa" class="form-control mb-2" placeholder="Nama Siswa" required>
                    <input type="text" name="kelas" class="form-control mb-2" placeholder="Kelas (contoh: XII IPA 1)" required>
                </div>
                <div class="modal-footer"><button type="submit" name="tambah_siswa" class="btn btn-dark">Simpan</button></div>
            </form>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>