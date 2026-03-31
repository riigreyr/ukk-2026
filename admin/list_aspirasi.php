<?php
require_once '../config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    redirect('../login.php');
}

$conn = getConnection();

//ngambil list aspirasi pke filter dinamis
function getListAspirasi($conn, $filters = []) {
    $where = [];

    if (!empty($filters['nisn'])) {
        $nisn    = (int)$filters['nisn'];
        $where[] = "ia.nisn = $nisn";
    }
    if (!empty($filters['kelas'])) {
        $kelas   = mysqli_real_escape_string($conn, $filters['kelas']);
        $where[] = "s.kelas = '$kelas'";
    }
    if (!empty($filters['id_kategori'])) {
        $kat     = (int)$filters['id_kategori'];
        $where[] = "ia.id_kategori = $kat";
    }
    if (!empty($filters['status'])) {
        $status  = mysqli_real_escape_string($conn, $filters['status']);
        $where[] = "a.status = '$status'";
    }

    $where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

    $sql = "SELECT a.id_aspirasi, a.status, a.feedback,
                   ia.id_pelaporan, ia.lokasi, ia.ket, ia.nisn,
                   k.ket_kategori, s.kelas, s.nama
            FROM aspirasi a
            JOIN input_aspirasi ia ON a.id_pelaporan = ia.id_pelaporan
            JOIN kategori k ON ia.id_kategori = k.id_kategori
            JOIN siswa s ON ia.nisn = s.nisn
            $where_sql
            ORDER BY a.id_aspirasi DESC";

    $result = $conn->query($sql);
    $data   = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// ngambil semua kategori
function getKategoriList($conn) {
    $result = $conn->query("SELECT * FROM kategori ORDER BY ket_kategori ASC");
    $data   = [];
    if ($result) while ($row = $result->fetch_assoc()) $data[] = $row;
    return $data;
}

// ngambil filter dari GET
$filters = [
    'nisn'        => $_GET['nisn'] ?? '',
    'kelas'       => $_GET['kelas'] ?? '',
    'id_kategori' => $_GET['id_kategori'] ?? '',
    'status'      => $_GET['status'] ?? '',
];

$list    = getListAspirasi($conn, $filters);
$kat_list = getKategoriList($conn);
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
    <title>List Aspirasi - Admin</title>
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
            <h1>List Aspirasi</h1>
            <p>Total ditemukan: <strong><?= count($list) ?></strong> aspirasi</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="card" style="margin-bottom: 1rem;">
        <div class="card-body" style="padding: 1rem 1.5rem;">
            <form method="GET" action="">
                <div class="filter-bar">
                    <div class="form-group">
                        <label class="form-label">NISN Siswa</label>
                        <input type="text" name="nisn" class="form-control"
                               placeholder="Cari NISN..." value="<?= htmlspecialchars($filters['nisn']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kelas</label>
                        <select name="kelas" class="form-control">
                            <option value="">Semua Kelas</option>
                            <option value="X"   <?= $filters['kelas']==='X'  ?'selected':'' ?>>X</option>
                            <option value="XI"  <?= $filters['kelas']==='XI' ?'selected':'' ?>>XI</option>
                            <option value="XII" <?= $filters['kelas']==='XII'?'selected':'' ?>>XII</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kategori</label>
                        <select name="id_kategori" class="form-control">
                            <option value="">Semua Kategori</option>
                            <?php foreach ($kat_list as $k): ?>
                                <option value="<?= $k['id_kategori'] ?>"
                                    <?= $filters['id_kategori']==$k['id_kategori']?'selected':'' ?>>
                                    <?= htmlspecialchars($k['ket_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="Menunggu" <?= $filters['status']==='Menunggu'?'selected':'' ?>>Menunggu</option>
                            <option value="Proses"   <?= $filters['status']==='Proses'  ?'selected':'' ?>>Proses</option>
                            <option value="Selesai"  <?= $filters['status']==='Selesai' ?'selected':'' ?>>Selesai</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">🔍 Filter</button>
                    <a href="list_aspirasi.php" class="btn btn-outline">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel -->
    <div class="card">
        <div class="table-wrapper">
            <?php if (empty($list)): ?>
                <div class="empty-state">
                    <div class="icon">🔍</div>
                    <p>Tidak ada aspirasi yang sesuai filter.</p>
                </div>
            <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tanggal</th>
                        <th>Nama / Kelas</th>
                        <th>Kategori</th>
                        <th>Lokasi</th>
                        <th>Keterangan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($list as $i => $row): ?>
                    <tr>
                        <td data-label="#"><?= $row['id_pelaporan'] ?></td>
                        <td data-label="Tanggal" style="white-space:nowrap;"><?= date('d M Y') ?></td>
                        <td data-label="Nama">
                            <strong><?= htmlspecialchars($row['nama']) ?></strong><br>
                            <small style="color:var(--text-muted)">Kelas <?= $row['kelas'] ?> | <?= $row['nisn'] ?></small>
                        </td>
                        <td data-label="Kategori"><?= htmlspecialchars($row['ket_kategori']) ?></td>
                        <td data-label="Lokasi"><?= htmlspecialchars($row['lokasi']) ?></td>
                        <td data-label="Keterangan" style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            <?= htmlspecialchars($row['ket']) ?>
                        </td>
                        <td data-label="Status"><?= badgeStatus($row['status']) ?></td>
                        <td data-label="Aksi">
                            <a href="detail_aspirasi.php?id=<?= $row['id_aspirasi'] ?>"
                               class="btn btn-outline btn-sm">Detail</a>
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