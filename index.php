<?php
require 'koneksi.php';
$q_galeri = mysqli_query($conn, "SELECT p.*, s.nama_siswa, s.kelas FROM prestasi p JOIN siswa s ON p.nisn = s.nisn WHERE p.status_data = 'Approved' ORDER BY p.tanggal_pelaksanaan DESC LIMIT 6");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Trophile - Galeri Prestasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero {
            background: #1e3c72;
            color: white;
            padding: 80px 0;
            border-radius: 0 0 50px 50px;
        }

        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">TROPHILE</a>
            <a href="login.php" class="btn btn-outline-light rounded-pill">Login Sistem</a>
        </div>
    </nav>

    <header class="hero text-center">
        <h1 class="fw-bold">Selamat Datang di Trophile</h1>
        <p>Rekam Jejak Prestasi Siswa Kebanggaan Kami</p>
    </header>

    <div class="container my-5">
        <h2 class="text-center fw-bold mb-4">Prestasi Terbaru</h2>
        <div class="row g-4">
            <?php while ($row = mysqli_fetch_assoc($q_galeri)) { ?>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <img src="assets/uploads/<?php echo $row['file_sertifikat']; ?>" class="card-img-top">
                        <div class="card-body">
                            <h5 class="fw-bold"><?php echo $row['nama_lomba']; ?></h5>
                            <p class="text-muted small"><?php echo $row['nama_siswa']; ?> - <?php echo $row['kelas']; ?></p>
                            <span class="badge bg-primary"><?php echo $row['peringkat']; ?></span>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</body>

</html>