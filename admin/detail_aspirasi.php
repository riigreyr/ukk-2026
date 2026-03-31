<?php
require_once '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$conn = getConnection();
$id   = (int)($_GET['id'] ?? 0);

if (!$id) {
    redirect('list_aspirasi.php');
}

$success = '';
$error   = '';

// ngambil detail aspirasi berdasarkan ID
function getDetailAspirasi($conn, $id_aspirasi) {
    $sql = "SELECT a.id_aspirasi, a.status, a.feedback,
                   ia.id_pelaporan, ia.lokasi, ia.ket, ia.nisn,
                   k.ket_kategori, s.kelas, s.nama
            FROM aspirasi a
            JOIN input_aspirasi ia ON a.id_pelaporan = ia.id_pelaporan
            JOIN kategori k ON ia.id_kategori = k.id_kategori
            JOIN siswa s ON ia.nisn = s.nisn
            WHERE a.id_aspirasi = $id_aspirasi";
    $result = $conn->query($sql);
    return $result ? $result->fetch_assoc() : null;
}

// ngupdate status dan feedback aspirasi
function updateAspirasi($conn, $id_aspirasi, $status, $feedback) {
    $status   = mysqli_real_escape_string($conn, $status);
    $feedback = mysqli_real_escape_string($conn, htmlspecialchars(trim($feedback)));
    $sql      = "UPDATE aspirasi SET status = '$status', feedback = '$feedback'
                 WHERE id_aspirasi = $id_aspirasi";
    return $conn->query($sql);
}

// proses submit feedback
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status   = $_POST['status']   ?? '';
    $feedback = $_POST['feedback'] ?? '';

    $allowed_status = ['Menunggu', 'Proses', 'Selesai'];

    if (in_array($status, $allowed_status) && $feedback !== '') {
        if (updateAspirasi($conn, $id, $status, $feedback)) {
            $success = 'Umpan balik berhasil disimpan!';
        } else {
            $error = 'Gagal menyimpan. Coba lagi.';
        }
    } else {
        $error = 'Status dan umpan balik tidak boleh kosong!';
    }
}

$data = getDetailAspirasi($conn, $id);
$conn->close();

if (!$data) {
    redirect('list_aspirasi.php');
}

function badgeStatus($status) {
    $map = ['Menunggu'=>'badge-menunggu','Proses'=>'badge-proses','Selesai'=>'badge-selesai'];
    $class = $map[$status] ?? 'badge-menunggu';
    return "<span class='badge $class' style='font-size:0.9rem; padding:6px 16px;'>$status</span>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Aspirasi #<?= $id ?> - Admin</title>
    <link rel="stylesheet" href="../style/style.css">
</head>
<body>

<nav class="navbar">
    <a href="dashboard.php" class="navbar-brand">Aspirasi Pelaporan</a>
    <div class="navbar-nav">
        <a href="dashboard.php" class="nav-link">Dashboard</a>
        <a href="list_aspirasi.php" class="nav-link active">List Aspirasi</a>
        <a href="../logout.php" class="nav-link logout">Keluar</a>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <div>
            <h1>Detail Aspirasi #<?= $id ?></h1>
            <p><a href="list_aspirasi.php" style="color:var(--accent); text-decoration:none;">← Kembali ke List</a></p>
        </div>
        <?= badgeStatus($data['status']) ?>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?= $error ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: start;">

        <!--info aspirasi-->
        <div class="card">
            <div class="card-header"><h2>📋 Informasi Aspirasi</h2></div>
            <div class="card-body">
                <div class="detail-section">
                    <div class="detail-label">Nama Siswa</div>
                    <div class="detail-value" style="font-size:1.1rem; font-weight:600;"><?= htmlspecialchars($data['nama']) ?></div>
                </div>
                <div class="detail-section">
                    <div class="detail-label">NISN</div>
                    <div class="detail-value"><?= $data['nisn'] ?></div>
                </div>
                <div class="detail-section">
                    <div class="detail-label">Kelas</div>
                    <div class="detail-value"><?= $data['kelas'] ?></div>
                </div>
                <div class="detail-section">
                    <div class="detail-label">Tanggal Pengaduan</div>
                    <div class="detail-value"><?= date('d F Y') ?></div>
                </div>
                <div class="detail-section">
                    <div class="detail-label">Kategori Sarana</div>
                    <div class="detail-value"><?= htmlspecialchars($data['ket_kategori']) ?></div>
                </div>
                <div class="detail-section">
                    <div class="detail-label">Lokasi / Ruangan</div>
                    <div class="detail-value"><?= htmlspecialchars($data['lokasi']) ?></div>
                </div>
                <div class="detail-section">
                    <div class="detail-label">Keterangan Masalah</div>
                    <div class="detail-value" style="background:var(--bg); padding:12px; border-radius:8px; border:1px solid var(--border);">
                        <?= htmlspecialchars($data['ket']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!--umpan balik-->
        <div class="card">
            <div class="card-header"><h2>💬 Umpan Balik Admin</h2></div>
            <div class="card-body">
                <?php if (!empty($data['feedback'])): ?>
                <div class="alert alert-info" style="margin-bottom:1.25rem;">
                    <div style="font-size:0.78rem; color:var(--text-muted); margin-bottom:4px; text-transform:uppercase; letter-spacing:.05em;">Umpan balik saat ini</div>
                    <?= htmlspecialchars(html_entity_decode($data['feedback'])) ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Status Penyelesaian</label>
                        <select name="status" class="form-control" required>
                            <option value="Menunggu" <?= $data['status']==='Menunggu'?'selected':'' ?>>⏳ Menunggu</option>
                            <option value="Proses"   <?= $data['status']==='Proses'  ?'selected':'' ?>>🔧 Proses</option>
                            <option value="Selesai"  <?= $data['status']==='Selesai' ?'selected':'' ?>>✅ Selesai</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Umpan Balik / Keterangan Admin</label>
                        <textarea name="feedback" class="form-control" rows="5"
                                  placeholder="Tuliskan respons atau tindakan yang sudah/akan dilakukan..."
                                  required><?= htmlspecialchars(html_entity_decode($data['feedback'])) ?></textarea>
                    </div>

                    <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:1rem;">
                        <a href="list_aspirasi.php" class="btn btn-outline">Batal</a>
                        <button type="submit" class="btn btn-accent">💾 Simpan Umpan Balik</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

</body>
</html>