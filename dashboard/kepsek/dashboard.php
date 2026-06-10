<?php
session_start();
require '../../koneksi.php';
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != "Kepala Sekolah") {
    header("Location: ../../index.php");
    exit();
}

$tahun_ini  = date('Y');
$bulan_ini  = date('m');
$bulan_lalu = date('m', strtotime('-1 month'));

$t_akademik     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM prestasi WHERE status_data='Approved' AND kategori LIKE '%Akademik%'"))['jml'] ?? 0;
$t_non_akademik = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM prestasi WHERE status_data='Approved' AND kategori NOT LIKE '%Akademik%'"))['jml'] ?? 0;

$data_jumlah_per_bulan = [];
for ($i = 1; $i <= 12; $i++) {
    $q = mysqli_query($conn, "SELECT COUNT(*) as jml FROM prestasi WHERE status_data='Approved' AND YEAR(tanggal_pelaksanaan)='$tahun_ini' AND MONTH(tanggal_pelaksanaan)='$i'");
    $data_jumlah_per_bulan[] = mysqli_fetch_assoc($q)['jml'];
}

$ai_sekarang = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM prestasi WHERE status_data='Approved' AND YEAR(tanggal_pelaksanaan)='$tahun_ini' AND MONTH(tanggal_pelaksanaan)='$bulan_ini'"))['jml'];
$ai_lalu     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as jml FROM prestasi WHERE status_data='Approved' AND YEAR(tanggal_pelaksanaan)='$tahun_ini' AND MONTH(tanggal_pelaksanaan)='$bulan_lalu'"))['jml'];

if ($ai_sekarang == 0 && $ai_lalu == 0) {
    $teks_ai = "Data belum mencukupi untuk analisis prediktif.";
} elseif ($ai_sekarang > $ai_lalu) {
    $kenaikan = $ai_sekarang - $ai_lalu;
    $teks_ai = "⚡ <b>Insight Cerdas:</b> Luar biasa! Terdapat tren <b>kenaikan $kenaikan prestasi</b> di bulan ini dibandingkan bulan lalu. Algoritma memprediksi semangat kompetitif siswa sedang tinggi. Pertahankan program bimbingan saat ini!";
} elseif ($ai_sekarang < $ai_lalu) {
    $penurunan = $ai_lalu - $ai_sekarang;
    $teks_ai = "⚠️ <b>Insight Cerdas:</b> Perhatian. Terdapat <b>penurunan $penurunan prestasi</b> di bulan ini. Sistem menyarankan evaluasi pada kegiatan ekstrakurikuler atau peningkatan motivasi kompetisi siswa untuk bulan depan.";
} else {
    $teks_ai = "📊 <b>Insight Cerdas:</b> Performa stabil. Jumlah prestasi bulan ini sama dengan bulan lalu. Algoritma menyarankan pencarian bibit unggul baru di kelas X untuk mendongkrak grafik di kuartal berikutnya.";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Dashboard Kepala Sekolah - Trophile</title>
    <link rel="stylesheet" href="../../assets/css/kepsek_dashboard.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <nav class="navbar-custom">
        <div class="nav-container">
            <div class="d-flex align-items-center">
                <a href="dashboard.php" style="font-size:20px;font-weight:800;letter-spacing:1px;color:white;margin-right:15px;text-decoration:none;">SMAN 1 KESAMBEN</a>
                <div class="d-none d-md-flex align-items-center ms-3">
                    <a href="dashboard.php" class="nav-link-item active">Dashboard</a>
                    <a href="rekap.php" class="nav-link-item">Rekap Prestasi</a>
                </div>
            </div>

            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                    <div style="width:38px;height:38px;background-color:#fbc02d;color:#1a2b56;border-radius:50%;display:flex;justify-content:center;align-items:center;font-weight:bold;margin-right:10px;font-size:16px;">
                        <?php echo substr($_SESSION['nama'], 0, 1); ?>
                    </div>
                    <span class="d-none d-md-inline" style="font-weight:600;letter-spacing:0.5px;"><?php echo $_SESSION['nama']; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="dropdownUser">
                    <li>
                        <h6 class="dropdown-header text-muted">Menu Kepala Sekolah</h6>
                    </li>
                    <li><a class="dropdown-item text-danger fw-bold" href="../../logout.php">🚪 Keluar</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="stat-box">
                    <p>PRESTASI AKADEMIK</p>
                    <h3><?php echo $t_akademik; ?></h3>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-box">
                    <p>PRESTASI NON-AKADEMIK</p>
                    <h3><?php echo $t_non_akademik; ?></h3>
                </div>
            </div>
        </div>

        <div class="floating-card mb-4">
            <div class="header-title">Grafik Pertumbuhan Prestasi <?php echo $tahun_ini; ?></div>
            <div style="height:350px;width:100%;">
                <canvas id="grafikPrestasi"></canvas>
            </div>
        </div>

        <div class="ai-card-wrapper">
            <div class="ai-card" id="aiCard">
                <div class="ai-header">
                    <div style="display:flex;align-items:center;">
                        <span style="font-size:22px;margin-right:10px;">🤖</span>
                        <h5 style="margin:0;font-weight:800;color:#1a2b56;letter-spacing:0.5px;font-size:16px;">Trophile AI™ Insights</h5>
                    </div>
                    <span class="ai-indicator" id="aiIndicator">Klik untuk kunci 📌</span>
                </div>
                <div class="ai-content">
                    <p><?php echo $teks_ai; ?></p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const ctx = document.getElementById('grafikPrestasi').getContext('2d');
        const dataDariPHP = <?php echo json_encode($data_jumlah_per_bulan); ?>;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [{
                    label: 'Prestasi Diverifikasi',
                    data: dataDariPHP,
                    backgroundColor: '#1a2b56',
                    borderRadius: 6,
                    borderSkipped: false,
                    barPercentage: 0.6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#e9ecef',
                            drawBorder: false
                        },
                        ticks: {
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        const aiCard = document.getElementById('aiCard');
        const aiIndicator = document.getElementById('aiIndicator');
        let isPinned = false;

        aiCard.addEventListener('mouseenter', () => {
            if (!isPinned) aiCard.classList.add('buka');
        });
        aiCard.addEventListener('mouseleave', () => {
            if (!isPinned) aiCard.classList.remove('buka');
        });
        aiCard.addEventListener('click', () => {
            isPinned = !isPinned;
            if (isPinned) {
                aiCard.classList.add('buka');
                aiIndicator.innerHTML = '📌 Tersemat';
                aiIndicator.style.background = '#fbc02d';
                aiIndicator.style.color = '#1a2b56';
            } else {
                aiIndicator.innerHTML = 'Klik untuk kunci 📌';
                aiIndicator.style.background = '#f4f6f9';
                aiIndicator.style.color = '#6c757d';
            }
        });
    </script>
</body>

</html>