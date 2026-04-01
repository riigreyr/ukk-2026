<?php

require_once 'config.php';

$success = isset($_GET['success']);
$error   = '';

// Fungsi: ambil semua kategori
function getKategori() {
    global $conn;
    $result = $conn->query("SELECT * FROM kategori ORDER BY ket_kategori ASC");
    $list   = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) $list[] = $row;
    }
    return $list;
}

// Fungsi: validasi siswa
function validateSiswa($nisn, $kelas) {
    global $conn;
    $nisn  = mysqli_real_escape_string($conn, (string)$nisn);
    $kelas = mysqli_real_escape_string($conn, $kelas);
    $result = $conn->query("SELECT nisn, nama, kelas FROM siswa WHERE nisn = '$nisn' AND kelas = '$kelas'");
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Proses submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nisn        = sanitize($conn, $_POST['nisn'] ?? '');
    $kelas       = sanitize($conn, $_POST['kelas'] ?? '');
    $id_kategori = (int)($_POST['id_kategori'] ?? 0);
    $lokasi      = sanitize($conn, $_POST['lokasi'] ?? '');
    $ket         = sanitize($conn, $_POST['ket'] ?? '');

    if (!$nisn || !$kelas || !$id_kategori || !$lokasi || !$ket) {
        $error = 'Semua field harus diisi!';
    } else {
        $siswa = validateSiswa($nisn, $kelas);
        if (!$siswa) {
            $error = 'NISN atau kelas tidak ditemukan!';
        } else {
            // Simpan session
            $_SESSION['nisn']  = $siswa['nisn'];
            $_SESSION['nama']  = $siswa['nama'];
            $_SESSION['kelas'] = $siswa['kelas'];

            // Insert aspirasi
            $sql = "INSERT INTO input_aspirasi (nisn, id_kategori, lokasi, ket)
                    VALUES ('$nisn', $id_kategori, '$lokasi', '$ket')";
            if ($conn->query($sql)) {
                $id_pelaporan = $conn->insert_id;
                $conn->query("INSERT INTO aspirasi (status, id_pelaporan, feedback)
                              VALUES ('Menunggu', $id_pelaporan, '')");
                redirect('index.php?success=1');
            } else {
                $error = 'Gagal mengirim aspirasi. Coba lagi.';
            }
        }
    }
}

$kategori_list = getKategori();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Aspirasi - Pengaduan Sarana Sekolah</title>
    <link rel="stylesheet" href="./style/style.css">
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="navbar-brand">Aspirasi Pelaporan</a>
    <div class="navbar-nav">
        <a href="index.php" class="nav-link active">Form Aspirasi</a>
        <a href="siswa/histori.php" class="nav-link">Histori</a>
        <?php if (isset($_SESSION['nisn'])): ?>
            <a href="siswa/ganti.php" class="nav-link logout">Ganti Pengguna</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    <div class="page-header" style="justify-content:center; text-align:center;">
        <div>
            <h1>Form Aspirasi Siswa</h1>
            <p>Sampaikan pengaduan sarana dan prasarana sekolahmu di sini.</p>
        </div>
    </div>

    <div style="max-width: 640px; margin: 0 auto;">
        <?php if ($success): ?>
            <div class="alert alert-success">✅ Aspirasi berhasil dikirim! Tim akan segera menindaklanjuti.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h2>Data Pengaduan</h2></div>
            <div class="card-body">
                <form method="POST" action="">

                    <!-- Identitas Siswa -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label class="form-label">NISN</label>
                            <input type="text" name="nisn" class="form-control"
                                   placeholder="Masukkan NISN" required
                                   value="<?= isset($_POST['nisn']) && $error ? $_POST['nisn'] : ($_SESSION['nisn'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kelas</label>
                            <select name="kelas" class="form-control" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php $sel_kelas = isset($_POST['kelas']) && $error ? $_POST['kelas'] : ($_SESSION['kelas'] ?? ''); ?>
                                <option value="X"   <?= $sel_kelas==='X'  ?'selected':'' ?>>X</option>
                                <option value="XI"  <?= $sel_kelas==='XI' ?'selected':'' ?>>XI</option>
                                <option value="XII" <?= $sel_kelas==='XII'?'selected':'' ?>>XII</option>
                            </select>
                        </div>
                    </div>

                    <div style="border-top: 1px solid var(--border); margin: 0.75rem 0 1rem;"></div>

                    <!-- Detail Aspirasi -->
                    <div class="form-group">
                        <label class="form-label">Kategori Sarana</label>
                        <select name="id_kategori" class="form-control" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategori_list as $k): ?>
                                <option value="<?= $k['id_kategori'] ?>"><?= htmlspecialchars($k['ket_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lokasi / Ruangan</label>
                        <input type="text" name="lokasi" class="form-control"
                               placeholder="Contoh: Lab Komputer, Kelas XI-A, Toilet Lantai 2"
                               maxlength="100" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Keterangan / Deskripsi Masalah</label>
                        <textarea name="ket" class="form-control"
                                  placeholder="Jelaskan kondisi atau kerusakan yang terjadi..."
                                  maxlength="100" required></textarea>
                    </div>

                    <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:1.5rem;">
                        <button type="reset" class="btn btn-outline">Reset</button>
                        <button type="submit" class="btn btn-accent">📤 Kirim Aspirasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>