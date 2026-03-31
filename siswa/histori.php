<?php
require_once '../config.php';

if (!isset($_SESSION['nisn'])) {
    $cek_error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cek_nisn'])) {
        $cek_nisn  = sanitize($conn, $_POST['nisn'] ?? '');
        $cek_kelas = mysqli_real_escape_string($conn, $_POST['kelas'] ?? '');
        if ($cek_nisn && $cek_kelas) {
            $r = $conn->query("SELECT nisn, nama, kelas FROM siswa WHERE nisn = '$cek_nisn' AND kelas = '$cek_kelas'");
            if ($r && $r->num_rows > 0) {
                $s = $r->fetch_assoc();
                $_SESSION['nisn']  = $s['nisn'];
                $_SESSION['nama']  = $s['nama'];
                $_SESSION['kelas'] = $s['kelas'];
                redirect('histori.php');
            } else {
                $cek_error = 'NISN atau kelas tidak ditemukan!';
            }
        } else {
            $cek_error = 'NISN dan kelas harus diisi!';
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cek Histori - Pengaduan Sekolah</title>
        <link rel="stylesheet" href="../style/style.css">
    </head>
    <body>
    <nav class="navbar">
        <a href="../index.php" class="navbar-brand">Aspirasi Pelaporan</a>
        <div class="navbar-nav">
            <a href="../index.php" class="nav-link">Form Aspirasi</a>
            <a href="histori.php" class="nav-link active">Histori</a>
        </div>
    </nav>
    <div class="container">
        <div class="page-header" style="justify-content:center; text-align:center;">
            <div>
                <h1>Cek Histori Aspirasi</h1>
                <p>Masukkan NISN dan kelas untuk melihat histori kamu ya.</p>
            </div>
        </div>
        <div style="max-width:400px; margin: 0 auto;">
            <?php if ($cek_error): ?>
                <div class="alert alert-danger">❌ <?= htmlspecialchars($cek_error) ?></div>
            <?php endif; ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="cek_nisn" value="1">
                        <div class="form-group">
                            <label class="form-label">NISN</label>
                            <input type="text" name="nisn" class="form-control" placeholder="Masukkan NISN" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kelas</label>
                            <select name="kelas" class="form-control" required>
                                <option value="">-- Pilih Kelas --</option>
                                <option value="X">X</option>
                                <option value="XI">XI</option>
                                <option value="XII">XII</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Lihat Histori →</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit();
}

$nisn  = $_SESSION['nisn'];
$kelas = $_SESSION['kelas'];
$nama  = $_SESSION['nama'] ?? 'Siswa';

function getHistoriByNisn($nisn) {
    global $conn;
    $nisn = mysqli_real_escape_string($conn, $nisn);
    $sql  = "SELECT ia.id_pelaporan, ia.lokasi, ia.ket,
                    k.ket_kategori, a.status, a.feedback
             FROM input_aspirasi ia
             JOIN kategori k ON ia.id_kategori = k.id_kategori
             LEFT JOIN aspirasi a ON a.id_pelaporan = ia.id_pelaporan
             WHERE ia.nisn = $nisn
             ORDER BY ia.id_pelaporan DESC";
    $result = $conn->query($sql);
    $data   = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) $data[] = $row;
    }
    return $data;
}

function getProgressCount($data) {
    $counts = ['Menunggu' => 0, 'Proses' => 0, 'Selesai' => 0];
    foreach ($data as $row) {
        $status = $row['status'] ?? 'Menunggu';
        if (isset($counts[$status])) $counts[$status]++;
    }
    return $counts;
}

function badgeStatus($status) {
    $map = ['Menunggu'=>'badge-menunggu','Proses'=>'badge-proses','Selesai'=>'badge-selesai'];
    $class = $map[$status] ?? 'badge-menunggu';
    return "<span class='badge $class'>$status</span>";
}

$histori = getHistoriByNisn($nisn);
$counts  = getProgressCount($histori);
$success = isset($_GET['success']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histori Aspirasi Saya</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>

<nav class="navbar">
    <a href="../index.php" class="navbar-brand">Aspirasi Pelaporan</a>
    <div class="navbar-nav">
        <a href="../index.php" class="nav-link">Form Aspirasi</a>
        <a href="histori.php" class="nav-link active">Histori Saya</a>
        <a href="ganti.php" class="nav-link logout">Ganti Pengguna</a>
    </div>
</nav>

<div class="container">
    <div class="page-header" style="justify-content:center; text-align:center;">
        <div>
            <h1>Histori Aspirasi Saya</h1>
            <p>Halo, <strong><?= htmlspecialchars($nama) ?></strong> — Kelas <strong><?= $kelas ?></strong> | NISN: <?= $nisn ?></p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success" style="max-width:640px; margin: 0 auto 1.5rem auto;">
            ✅ Aspirasi berhasil dikirim! Tim akan segera menindaklanjuti.
        </div>
    <?php endif; ?>

    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); max-width: 480px; margin: 0 auto 1.5rem auto;">
        <div class="stat-card">
            <div class="stat-icon yellow">⏳</div>
            <div><div class="stat-value"><?= $counts['Menunggu'] ?></div><div class="stat-label">Menunggu</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">🔧</div>
            <div><div class="stat-value"><?= $counts['Proses'] ?></div><div class="stat-label">Proses</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">✅</div>
            <div><div class="stat-value"><?= $counts['Selesai'] ?></div><div class="stat-label">Selesai</div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Daftar Aspirasi (<?= count($histori) ?>)</h2>
            <a href="../index.php" class="btn btn-accent btn-sm">+ Tambah Aspirasi</a>
        </div>
        <div class="table-wrapper">
            <?php if (empty($histori)): ?>
                <div class="empty-state">
                    <div class="icon">📭</div>
                    <p>Belum ada aspirasi.</p>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th>Keterangan</th>
                        <th>Status</th>
                        <th>Umpan Balik</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($histori as $i => $row): ?>
                    <tr>
                        <td data-label="#"><?= $row['id_pelaporan'] ?></td>
                        <td data-label="Tanggal" style="white-space:nowrap;"><?= date('d M Y') ?></td>
                        <td data-label="Kategori"><?= htmlspecialchars($row['ket_kategori']) ?></td>
                        <td data-label="Lokasi"><?= htmlspecialchars($row['lokasi']) ?></td>
                        <td data-label="Keterangan"><?= htmlspecialchars($row['ket']) ?></td>
                        <td data-label="Status"><?= badgeStatus($row['status'] ?? 'Menunggu') ?></td>
                        <td data-label="Umpan Balik">
                            <?php if (!empty($row['feedback'])): ?>
                                <span style="color:var(--primary); font-size:0.875rem;"><?= htmlspecialchars($row['feedback']) ?></span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>