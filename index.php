<?php
require 'koneksi.php';
// Query untuk galeri prestasi
$q_galeri = db_query($conn, "SELECT p.*, s.nama_siswa, s.kelas FROM prestasi p JOIN siswa s ON p.nisn = s.nisn WHERE p.status_data = 'Approved' ORDER BY p.tanggal_pelaksanaan DESC LIMIT 6");

// Query untuk statistik singkat
$q_total = db_query($conn, "SELECT COUNT(*) as total FROM prestasi WHERE status_data = 'Approved'");
$total_prestasi = 0;
if($q_total && mysqli_num_rows($q_total) > 0) {
    $row_total = mysqli_fetch_assoc($q_total);
    $total_prestasi = $row_total['total'];
}

// Query untuk total siswa berprestasi
$q_siswa = db_query($conn, "SELECT COUNT(DISTINCT nisn) as total_siswa FROM prestasi WHERE status_data = 'Approved'");
$total_siswa = 0;
if($q_siswa && mysqli_num_rows($q_siswa) > 0) {
    $row_siswa = mysqli_fetch_assoc($q_siswa);
    $total_siswa = $row_siswa['total_siswa'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Manajemen Trophile - Prestasi Siswa</title>
    <meta name="description" content="Trophile (Trophy File): Sistem Informasi Manajemen pencatatan dan galeri prestasi siswa.">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- External CSS -->
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="container nav-container">
            <a href="#" class="brand">
                <img src="assets/images/logo.png" alt="Logo Trophile" height="40"> TROPHILE
            </a>
            <div class="nav-links">
                <a href="login.php" class="btn-login"><i class="bi bi-person-lock"></i> Login</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <div class="hero-content">
            <span class="badge">Sistem Informasi Manajemen</span>
            <h1>Trophile <span>(Trophy File)</span></h1>
            <p>Platform digital terpadu untuk merekam, mengelola, dan mengapresiasi setiap jejak prestasi siswa kebanggaan sekolah. Dari kompetisi akademik hingga olahraga, semuanya tercatat rapi di sini.</p>
            <a href="#gallery" class="btn-primary">Lihat Galeri Prestasi</a>
        </div>
    </header>

    <!-- Tentang & Statistik -->
    <section class="info-section">
        <div class="container">
            <div class="info-grid">
                <div class="info-content">
                    <h2>Apa itu Trophile?</h2>
                    <p>Trophile (Trophy File) adalah Sistem Informasi Manajemen yang didesain khusus untuk mendokumentasikan pencapaian siswa secara profesional. Kami percaya bahwa setiap usaha keras pantas mendapatkan apresiasi dan rekam jejak yang abadi.</p>
                    <p>Melalui sistem ini, pihak sekolah, guru, maupun masyarakat luas dapat melihat kontribusi nyata para siswa dalam mengharumkan nama sekolah di berbagai bidang kompetisi.</p>
                </div>
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="bi bi-award-fill"></i>
                        <div class="stat-number"><?php echo $total_prestasi; ?>+</div>
                        <div class="stat-label">Total Prestasi</div>
                    </div>
                    <div class="stat-card">
                        <i class="bi bi-people-fill"></i>
                        <div class="stat-number"><?php echo $total_siswa; ?>+</div>
                        <div class="stat-label">Siswa Berprestasi</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Kategori Prestasi / Fitur -->
    <section class="features-section">
        <div class="container">
            <div class="section-header">
                <h2>Cakupan Prestasi</h2>
                <div class="divider"></div>
                <p>Mendukung pencatatan berbagai jenis perlombaan dan kejuaraan tingkat lokal hingga internasional.</p>
            </div>
            <div class="feature-grid">
                <div class="feature-box">
                    <div class="feature-icon"><i class="bi bi-book-half"></i></div>
                    <h3>Akademik & Sains</h3>
                    <p>Olimpiade sains, lomba debat, cerdas cermat, hingga kompetisi karya tulis ilmiah.</p>
                </div>
                <div class="feature-box">
                    <div class="feature-icon"><i class="bi bi-dribbble"></i></div>
                    <h3>Olahraga & Fisik</h3>
                    <p>Kejuaraan atletik, turnamen bola basket, pencak silat, dan cabang olahraga lainnya.</p>
                </div>
                <div class="feature-box">
                    <div class="feature-icon"><i class="bi bi-palette-fill"></i></div>
                    <h3>Seni & Kreativitas</h3>
                    <p>Festival musik, lomba tari tradisional, desain poster, dan ajang pencarian bakat.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Galeri Prestasi Utama -->
    <main id="gallery" class="gallery-section">
        <div class="container">
            <div class="section-header">
                <h2>Galeri Kebanggaan</h2>
                <div class="divider"></div>
                <p>Daftar pencapaian terbaru dari siswa-siswi terbaik kami yang telah lolos verifikasi sekolah.</p>
            </div>
            
            <div class="gallery-grid">
                <?php while ($row = mysqli_fetch_assoc($q_galeri)) { ?>
                    <div class="achievement-card">
                        <div class="card-image">
                            <!-- Foto Sertifikat / Dokumentasi Lomba -->
                            <img src="assets/uploads/<?php echo e($row['file_sertifikat']); ?>" alt="Dokumentasi <?php echo e($row['nama_lomba']); ?>" onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300?text=Dokumentasi+Lomba';">
                            <div class="rank-badge">
                                <i class="bi bi-trophy"></i> <?php echo e($row['peringkat']); ?>
                            </div>
                        </div>
                        <div class="card-content">
                            <h3 class="achievement-title"><?php echo e($row['nama_lomba']); ?></h3>
                            <div class="info-group">
                                <div class="student-info">
                                    <i class="bi bi-person-bounding-box"></i>
                                    <span><?php echo e($row['nama_siswa']); ?></span>
                                </div>
                                <div class="class-info">
                                    <i class="bi bi-mortarboard-fill"></i>
                                    <span>Kelas <?php echo e($row['kelas']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                
                <?php if(mysqli_num_rows($q_galeri) == 0) { ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #64748b;">
                        <i>Belum ada data prestasi yang dapat ditampilkan saat ini.</i>
                    </div>
                <?php } ?>
            </div>
        </div>
    </main>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container cta-container">
            <h2>Arsip Prestasi yang Terpercaya</h2>
            <p>Akses khusus bagi administrator dan staf sekolah untuk mengelola data siswa berprestasi.</p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?>Trophile</p>
        </div>
    </footer>
</body>
</html>