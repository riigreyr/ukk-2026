<?php
require_once '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$conn = getConnection();

// ngehitung total aspirasi per status
function countByStatus($conn, $status) {
    $result = $conn->query("SELECT COUNT(*) as total FROM aspirasi WHERE status = '$status'");
    return $result ? (int)$result->fetch_assoc()['total'] : 0;
}

// ngehitung total aspirasi keseluruhan
function countTotal($conn) {
    $result = $conn->query("SELECT COUNT(*) as total FROM aspirasi");
    return $result ? (int)$result->fetch_assoc()['total'] : 0;
}

// ngambil 5 aspirasi terbaru
function getLatestAspirasi($conn) {
    $sql = "SELECT a.id_aspirasi, a.status, ia.lokasi, ia.ket,
                   k.ket_kategori, s.nisn, s.kelas
            FROM aspirasi a
            JOIN input_aspirasi ia ON a.id_pelaporan = ia.id_pelaporan
            JOIN kategori k ON ia.id_kategori = k.id_kategori
            JOIN siswa s ON ia.nisn = s.nisn
            ORDER BY a.id_aspirasi DESC
            LIMIT 5";
    $result = $conn->query($sql);
    $data   = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// ngurutin jumlah aspirasi per kategori
function countByKategori($conn) {
    $sql = "SELECT k.ket_kategori, COUNT(a.id_aspirasi) as total
            FROM aspirasi a
            JOIN input_aspirasi ia ON a.id_pelaporan = ia.id_pelaporan
            JOIN kategori k ON ia.id_kategori = k.id_kategori
            GROUP BY k.id_kategori, k.ket_kategori
            ORDER BY total DESC";
    $result = $conn->query($sql);
    $data   = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

$total     = countTotal($conn);
$menunggu  = countByStatus($conn, 'Menunggu');
$proses    = countByStatus($conn, 'Proses');
$selesai   = countByStatus($conn, 'Selesai');
$latest    = getLatestAspirasi($conn);
$per_kat   = countByKategori($conn);
$conn->close();

function badgeStatus($status) {
    $map = ['Menunggu'=>'badge-menunggu','Proses'=>'badge-proses','Selesai'=>'badge-selesai'];
    $class = $map[$status] ?? 'badge-menunggu';
    return "<span class='badge $class'>$status</span>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Pengaduan Sekolah</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>

<nav class="navbar">
                <a href="dashboard.php" class="navbar-brand">Aspirasi Pelaporan</a>
    <div class="navbar-nav">
        <a href="dashboard.php" class="nav-link active">Dashboard</a>
        <a href="list_aspirasi.php" class="nav-link">List Aspirasi</a>
        <a href="../logout.php" class="nav-link logout">Keluar</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <div>
            <h1>Dashboard Admin</h1>
            <p>Selamat datang, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>! Berikut ringkasan aspirasi hari ini.</p>
        </div>
        <a href="list_aspirasi.php" class="btn btn-primary btn-sm">Lihat Semua →</a>
    </div>

    <!--status-->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon blue">📋</div>
            <div>
                <div class="stat-value"><?= $total ?></div>
                <div class="stat-label">Total Aspirasi</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">⏳</div>
            <div>
                <div class="stat-value"><?= $menunggu ?></div>
                <div class="stat-label">Menunggu</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">🔧</div>
            <div>
                <div class="stat-value"><?= $proses ?></div>
                <div class="stat-label">Diproses</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div>
                <div class="stat-value"><?= $selesai ?></div>
                <div class="stat-label">Selesai</div>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem; align-items: start;">
        <!--pengaduan terbaru-->
        <div class="card">
            <div class="card-header">
                <h2>Aspirasi Terbaru</h2>
                <a href="list_aspirasi.php" class="btn btn-outline btn-sm">Lihat Semua</a>
            </div>
            <div class="table-wrapper">
                <?php if (empty($latest)): ?>
                    <div class="empty-state"><div class="icon">📭</div><p>Belum ada aspirasi masuk.</p></div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr><th>NISN</th><th>Kategori</th><th>Lokasi</th><th>Status</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($latest as $row): ?>
                        <tr>
                            <td data-label="NISN"><?= $row['nisn'] ?><br><small style="color:var(--text-muted)">Kelas <?= $row['kelas'] ?></small></td>
                            <td data-label="Kategori"><?= htmlspecialchars($row['ket_kategori']) ?></td>
                            <td data-label="Lokasi"><?= htmlspecialchars($row['lokasi']) ?></td>
                            <td data-label="Status"><?= badgeStatus($row['status']) ?></td>
                            <td data-label="Aksi">
                                <a href="detail_aspirasi.php?id=<?= $row['id_aspirasi'] ?>" class="btn btn-outline btn-sm">Detail</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!--per kat-->
        <div class="card">
            <div class="card-header"><h2>Per Kategori</h2></div>
            <div class="card-body">
                <?php if (empty($per_kat)): ?>
                    <p style="color:var(--text-muted); font-size:0.9rem; text-align:center;">Belum ada data.</p>
                <?php else: ?>
                    <?php foreach ($per_kat as $pk): ?>
                    <div style="margin-bottom: 14px;">
                        <div style="display:flex; justify-content:space-between; font-size:0.875rem; margin-bottom:4px;">
                            <span><?= htmlspecialchars($pk['ket_kategori']) ?></span>
                            <strong><?= $pk['total'] ?></strong>
                        </div>
                        <div style="background:var(--border); border-radius:4px; height:6px; overflow:hidden;">
                            <div style="background:var(--accent); height:100%; width:<?= $total > 0 ? round(($pk['total']/$total)*100) : 0 ?>%; border-radius:4px; transition:width 0.5s;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>