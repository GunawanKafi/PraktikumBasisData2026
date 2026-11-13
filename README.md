# Modul 6: Integrasi Lanjutan PostgreSQL & Aplikasi CRUD PHP Utuh (Create, Update, Delete)

Repositori ini berisi potongan kode konsep PDO lanjutan serta kode program lengkap aplikasi CRUD **Sistem Informasi Akademik (`C:\laragon\www\si_akademik\`)** untuk **Modul 6 Praktikum Basis Data (TA 2026/2027)**.

## 1. Konsep Pemanfaatan Klausa `RETURNING` di PHP PDO

Contoh pengambilan nilai kolom dari baris yang baru saja disisipkan menggunakan klausa `RETURNING` tanpa memerlukan kueri `SELECT` tambahan:

```
$sql = "INSERT INTO mahasiswa (id_prodi, nim, nama_lengkap, ...) 
        VALUES (:id_prodi, :nim, :nama_lengkap, ...) 
        RETURNING id_mahasiswa, nim, nama_lengkap";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

// Mengambil hasil baris yang dikembalikan oleh klausa RETURNING
$baris_baru = $stmt->fetch(); 
// Sekarang $baris_baru['id_mahasiswa'] dan $baris_baru['nim'] langsung tersedia!

```

## 2. Kode Program Aplikasi CRUD Utuh (`C:\laragon\www\si_akademik\`)

Pastikan layanan **Apache** dan **PostgreSQL (Port 5432)** pada Laragon sudah berstatus **Running**. Struktur direktori proyek pada `C:\laragon\www\si_akademik\` adalah sebagai berikut:

```
C:\laragon\www\si_akademik\
├── edit.php
├── hapus.php
├── index.php
├── koneksi.php
└── tambah.php

```

> **Catatan:** File `koneksi.php` menggunakan kode yang sama dengan yang telah dibuat pada **Modul 5**.

### Bagian 1: Fitur Create (`tambah.php`)

Buat file baru bernama `tambah.php` di dalam folder `C:\laragon\www\si_akademik\tambah.php`:

```
<?php
require_once 'koneksi.php';

// 1. Mengambil daftar Program Studi dari Tabel Induk untuk mengisi Dropdown <select>
$sql_prodi = "SELECT id_prodi, kode_prodi, nama_prodi, jenjang 
              FROM program_studi 
              ORDER BY nama_prodi ASC";
$daftar_prodi = $pdo->query($sql_prodi)->fetchAll();

// Variabel penampung pesan kesalahan dan nilai input lama (sticky form)
$pesan_error = '';
$input = [
    'id_prodi'        => '',
    'nim'             => '',
    'nama_lengkap'    => '',
    'jenis_kelamin'   => 'L',
    'tanggal_lahir'   => '',
    'angkatan'        => date('Y'),
    'ipk'             => '0.00',
    'email_kampus'    => '',
    'alamat_domisili' => '',
    'status_aktif'    => true
];

// 2. Memproses Data Ketika Formulir Dikirim (Metode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Menangkap dan membersihkan input dari pengguna
    $input['id_prodi']        = isset($_POST['id_prodi']) ? (int) $_POST['id_prodi'] : 0;
    $input['nim']             = trim($_POST['nim'] ?? '');
    $input['nama_lengkap']    = trim($_POST['nama_lengkap'] ?? '');
    $input['jenis_kelamin']   = trim($_POST['jenis_kelamin'] ?? 'L');
    $input['tanggal_lahir']   = trim($_POST['tanggal_lahir'] ?? '');
    $input['angkatan']        = isset($_POST['angkatan']) ? (int) $_POST['angkatan'] : 0;
    $input['ipk']             = isset($_POST['ipk']) ? (float) $_POST['ipk'] : 0.00;
    $input['email_kampus']    = trim($_POST['email_kampus'] ?? '');
    $input['alamat_domisili'] = trim($_POST['alamat_domisili'] ?? '');
    $input['status_aktif']    = isset($_POST['status_aktif']) ? true : false;

    // Mengubah string kosong menjadi NULL murni untuk kolom opsional
    $email_db  = ($input['email_kampus'] !== '') ? $input['email_kampus'] : null;
    $alamat_db = ($input['alamat_domisili'] !== '') ? $input['alamat_domisili'] : null;

    // Validasi dasar di sisi server (PHP)
    if ($input['id_prodi'] <= 0 || $input['nim'] === '' || $input['nama_lengkap'] === '' || $input['tanggal_lahir'] === '') {
        $pesan_error = 'Program Studi, NIM, Nama Lengkap, dan Tanggal Lahir wajib diisi!';
    } else {
        try {
            // 3. Menyusun Kueri INSERT dengan Named Placeholders & Klausa RETURNING
            $sql_insert = "
                INSERT INTO mahasiswa (
                    id_prodi, nim, nama_lengkap, jenis_kelamin, tanggal_lahir,
                    angkatan, ipk, status_aktif, email_kampus, alamat_domisili
                ) VALUES (
                    :id_prodi, :nim, :nama_lengkap, :jenis_kelamin, :tanggal_lahir,
                    :angkatan, :ipk, :status_aktif, :email_kampus, :alamat_domisili
                )
                RETURNING id_mahasiswa, nim, nama_lengkap
            ";

            $stmt = $pdo->prepare($sql_insert);

            // 4. Melakukan Parameter Binding secara Eksplisit & Aman
            $stmt->bindValue(':id_prodi', $input['id_prodi'], PDO::PARAM_INT);
            $stmt->bindValue(':nim', $input['nim'], PDO::PARAM_STR);
            $stmt->bindValue(':nama_lengkap', $input['nama_lengkap'], PDO::PARAM_STR);
            $stmt->bindValue(':jenis_kelamin', $input['jenis_kelamin'], PDO::PARAM_STR);
            $stmt->bindValue(':tanggal_lahir', $input['tanggal_lahir'], PDO::PARAM_STR);
            $stmt->bindValue(':angkatan', $input['angkatan'], PDO::PARAM_INT);
            $stmt->bindValue(':ipk', $input['ipk']); // NUMERIC dikirim sebagai string/float
            $stmt->bindValue(':status_aktif', $input['status_aktif'], PDO::PARAM_BOOL);
            $stmt->bindValue(':email_kampus', $email_db, $email_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':alamat_domisili', $alamat_db, $alamat_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

            $stmt->execute();

            // 5. Menangkap hasil klausa RETURNING dari PostgreSQL
            $hasil_baru = $stmt->fetch();
            $info_sukses = "Mahasiswa {$hasil_baru['nama_lengkap']} (NIM: {$hasil_baru['nim']}) berhasil ditambahkan dengan ID #{$hasil_baru['id_mahasiswa']}.";

            // Redirect ke index.php (Pola Post/Redirect/Get)
            header("Location: index.php?pesan=" . urlencode($info_sukses));
            exit;

        } catch (PDOException $e) {
            // 6. Menangkap Kode Error SQLSTATE PostgreSQL
            if ($e->getCode() === '23505') {
                $pesan_error = 'Gagal Menyimpan: NIM atau Email Kampus yang Anda masukkan sudah terdaftar pada mahasiswa lain (Pelanggaran UNIQUE Constraint).';
            } elseif ($e->getCode() === '23503') {
                $pesan_error = 'Gagal Menyimpan: Program Studi yang dipilih tidak valid di dalam basis data (Pelanggaran FOREIGN KEY).';
            } elseif ($e->getCode() === '23514') {
                $pesan_error = 'Gagal Menyimpan: Nilai IPK (0.00 - 4.00) atau Tahun Angkatan tidak sesuai aturan validasi tabel (Pelanggaran CHECK Constraint).';
            } else {
                $pesan_error = 'Terjadi kesalahan basis data: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Mahasiswa Baru - SI Akademik</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #1e293b; margin: 0; padding: 24px; }
        .card { max-width: 680px; margin: 0 auto; background: #ffffff; padding: 24px 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h2 { margin-top: 0; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .form-group { margin-bottom: 14px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; }
        input[type="text"], input[type="email"], input[type="date"], input[type="number"], select, textarea {
            width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; box-sizing: border-box;
        }
        textarea { resize: vertical; min-height: 70px; }
        .row-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .alert-error { background-color: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
        .btn { padding: 10px 18px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #16a34a; color: #ffffff; }
        .btn-secondary { background-color: #64748b; color: #ffffff; margin-left: 8px; }
    </style>
</head>
<body>

<div class="card">
    <h2>Tambah Data Mahasiswa Baru</h2>

    <?php if ($pesan_error !== ''): ?>
        <div class="alert-error">
            <strong>Peringatan:</strong> <?= htmlspecialchars($pesan_error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="tambah.php">
        <div class="form-group">
            <label for="id_prodi">Program Studi (Tabel Relasi Induk) *</label>
            <select id="id_prodi" name="id_prodi" required>
                <option value="">-- Pilih Program Studi --</option>
                <?php foreach ($daftar_prodi as $prodi): ?>
                    <option value="<?= (int) $prodi['id_prodi'] ?>" 
                        <?= ((int) $input['id_prodi'] === (int) $prodi['id_prodi']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($prodi['jenjang'] . ' - ' . $prodi['nama_prodi'] . ' (' . $prodi['kode_prodi'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row-2col">
            <div class="form-group">
                <label for="nim">NIM (Maks. 15 Karakter) *</label>
                <input type="text" id="nim" name="nim" maxlength="15" required 
                       value="<?= htmlspecialchars($input['nim']) ?>" placeholder="Contoh: 240101009">
            </div>

            <div class="form-group">
                <label for="jenis_kelamin">Jenis Kelamin *</label>
                <select id="jenis_kelamin" name="jenis_kelamin" required>
                    <option value="L" <?= ($input['jenis_kelamin'] === 'L') ? 'selected' : '' ?>>Laki-laki (L)</option>
                    <option value="P" <?= ($input['jenis_kelamin'] === 'P') ? 'selected' : '' ?>>Perempuan (P)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="nama_lengkap">Nama Lengkap Mahasiswa *</label>
            <input type="text" id="nama_lengkap" name="nama_lengkap" maxlength="120" required 
                   value="<?= htmlspecialchars($input['nama_lengkap']) ?>" placeholder="Masukkan nama lengkap sesuai KTM">
        </div>

        <div class="row-2col">
            <div class="form-group">
                <label for="tanggal_lahir">Tanggal Lahir *</label>
                <input type="date" id="tanggal_lahir" name="tanggal_lahir" required 
                       value="<?= htmlspecialchars($input['tanggal_lahir']) ?>">
            </div>

            <div class="form-group">
                <label for="angkatan">Tahun Angkatan (2000 - 2100) *</label>
                <input type="number" id="angkatan" name="angkatan" min="2000" max="2100" required 
                       value="<?= (int) $input['angkatan'] ?>">
            </div>
        </div>

        <div class="row-2col">
            <div class="form-group">
                <label for="ipk">Indeks Prestasi Kumulatif (0.00 - 4.00) *</label>
                <input type="number" id="ipk" name="ipk" step="0.01" min="0.00" max="4.00" required 
                       value="<?= htmlspecialchars((string) $input['ipk']) ?>">
            </div>

            <div class="form-group">
                <label for="email_kampus">Email Kampus (Opsional / Unik)</label>
                <input type="email" id="email_kampus" name="email_kampus" maxlength="120" 
                       value="<?= htmlspecialchars($input['email_kampus']) ?>" placeholder="nama@kampus.ac.id">
            </div>
        </div>

        <div class="form-group">
            <label for="alamat_domisili">Alamat Domisili</label>
            <textarea id="alamat_domisili" name="alamat_domisili" placeholder="Masukkan alamat lengkap..."><?= htmlspecialchars($input['alamat_domisili']) ?></textarea>
        </div>

        <div class="form-group">
            <label style="font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="status_aktif" value="1" <?= $input['status_aktif'] ? 'checked' : '' ?>>
                <strong>Mahasiswa Berstatus Aktif Kuliah</strong> (<code>BOOLEAN TRUE</code>)
            </label>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Simpan Data Mahasiswa</button>
            <a href="index.php" class="btn btn-secondary">Batal &amp; Kembali</a>
        </div>
    </form>
</div>

</body>
</html>

```

### Bagian 2: Fitur Update (`edit.php`)

Buat file baru bernama `edit.php` di dalam folder `C:\laragon\www\si_akademik\edit.php`:

```
<?php
require_once 'koneksi.php';

// 1. Menangkap ID Mahasiswa dari URL ($_GET['id']) atau dari Hidden Input Form ($_POST['id_mahasiswa'])
$id_mahasiswa = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id_mahasiswa']) ? (int) $_POST['id_mahasiswa'] : 0);

if ($id_mahasiswa <= 0) {
    header("Location: index.php?error=" . urlencode("ID Mahasiswa tidak valid!"));
    exit;
}

// 2. Mengambil Daftar Program Studi untuk Dropdown <select>
$sql_prodi = "SELECT id_prodi, kode_prodi, nama_prodi, jenjang 
              FROM program_studi 
              ORDER BY nama_prodi ASC";
$daftar_prodi = $pdo->query($sql_prodi)->fetchAll();

// 3. Mengambil Data Lama Mahasiswa Berdasarkan id_mahasiswa menggunakan Prepared Statement
$stmt_lama = $pdo->prepare("SELECT * FROM mahasiswa WHERE id_mahasiswa = :id");
$stmt_lama->execute([':id' => $id_mahasiswa]);
$data_mhs = $stmt_lama->fetch();

// Jika ID tidak ditemukan di dalam tabel PostgreSQL, kembalikan ke index.php
if (!$data_mhs) {
    header("Location: index.php?error=" . urlencode("Data mahasiswa dengan ID #{$id_mahasiswa} tidak ditemukan!"));
    exit;
}

$pesan_error = '';

// 4. Memproses Pembaruan Data Ketika Formulir Edit Dikirim (Metode POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_mhs['id_prodi']        = isset($_POST['id_prodi']) ? (int) $_POST['id_prodi'] : 0;
    $data_mhs['nim']             = trim($_POST['nim'] ?? '');
    $data_mhs['nama_lengkap']    = trim($_POST['nama_lengkap'] ?? '');
    $data_mhs['jenis_kelamin']   = trim($_POST['jenis_kelamin'] ?? 'L');
    $data_mhs['tanggal_lahir']   = trim($_POST['tanggal_lahir'] ?? '');
    $data_mhs['angkatan']        = isset($_POST['angkatan']) ? (int) $_POST['angkatan'] : 0;
    $data_mhs['ipk']             = isset($_POST['ipk']) ? (float) $_POST['ipk'] : 0.00;
    $data_mhs['email_kampus']    = trim($_POST['email_kampus'] ?? '');
    $data_mhs['alamat_domisili'] = trim($_POST['alamat_domisili'] ?? '');
    $data_mhs['status_aktif']    = isset($_POST['status_aktif']) ? true : false;

    $email_db  = ($data_mhs['email_kampus'] !== '') ? $data_mhs['email_kampus'] : null;
    $alamat_db = ($data_mhs['alamat_domisili'] !== '') ? $data_mhs['alamat_domisili'] : null;

    if ($data_mhs['id_prodi'] <= 0 || $data_mhs['nim'] === '' || $data_mhs['nama_lengkap'] === '' || $data_mhs['tanggal_lahir'] === '') {
        $pesan_error = 'Program Studi, NIM, Nama Lengkap, dan Tanggal Lahir tidak boleh dikosongkan!';
    } else {
        try {
            // 5. Menyusun Kueri UPDATE Berbasis Primary Key dengan Klausa RETURNING
            $sql_update = "
                UPDATE mahasiswa
                SET 
                    id_prodi        = :id_prodi,
                    nim             = :nim,
                    nama_lengkap    = :nama_lengkap,
                    jenis_kelamin   = :jenis_kelamin,
                    tanggal_lahir   = :tanggal_lahir,
                    angkatan        = :angkatan,
                    ipk             = :ipk,
                    status_aktif    = :status_aktif,
                    email_kampus    = :email_kampus,
                    alamat_domisili = :alamat_domisili
                WHERE id_mahasiswa = :id_mahasiswa
                RETURNING id_mahasiswa, nim, nama_lengkap
            ";

            $stmt_up = $pdo->prepare($sql_update);
            $stmt_up->bindValue(':id_prodi', $data_mhs['id_prodi'], PDO::PARAM_INT);
            $stmt_up->bindValue(':nim', $data_mhs['nim'], PDO::PARAM_STR);
            $stmt_up->bindValue(':nama_lengkap', $data_mhs['nama_lengkap'], PDO::PARAM_STR);
            $stmt_up->bindValue(':jenis_kelamin', $data_mhs['jenis_kelamin'], PDO::PARAM_STR);
            $stmt_up->bindValue(':tanggal_lahir', $data_mhs['tanggal_lahir'], PDO::PARAM_STR);
            $stmt_up->bindValue(':angkatan', $data_mhs['angkatan'], PDO::PARAM_INT);
            $stmt_up->bindValue(':ipk', $data_mhs['ipk']);
            $stmt_up->bindValue(':status_aktif', $data_mhs['status_aktif'], PDO::PARAM_BOOL);
            $stmt_up->bindValue(':email_kampus', $email_db, $email_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt_up->bindValue(':alamat_domisili', $alamat_db, $alamat_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt_up->bindValue(':id_mahasiswa', $id_mahasiswa, PDO::PARAM_INT);

            $stmt_up->execute();
            $hasil_edit = $stmt_up->fetch();

            $info_sukses = "Data mahasiswa {$hasil_edit['nama_lengkap']} (NIM: {$hasil_edit['nim']}) berhasil diperbarui.";
            header("Location: index.php?pesan=" . urlencode($info_sukses));
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $pesan_error = 'Gagal Memperbarui: NIM atau Email Kampus tersebut sudah digunakan oleh mahasiswa lain.';
            } elseif ($e->getCode() === '23514') {
                $pesan_error = 'Gagal Memperbarui: Nilai IPK atau Tahun Angkatan melanggar batas validasi CHECK Constraint.';
            } else {
                $pesan_error = 'Terjadi kesalahan basis data: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Data Mahasiswa - SI Akademik</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #1e293b; margin: 0; padding: 24px; }
        .card { max-width: 680px; margin: 0 auto; background: #ffffff; padding: 24px 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h2 { margin-top: 0; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .form-group { margin-bottom: 14px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; }
        input[type="text"], input[type="email"], input[type="date"], input[type="number"], select, textarea {
            width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; box-sizing: border-box;
        }
        textarea { resize: vertical; min-height: 70px; }
        .row-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .alert-error { background-color: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
        .btn { padding: 10px 18px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #2563eb; color: #ffffff; }
        .btn-secondary { background-color: #64748b; color: #ffffff; margin-left: 8px; }
    </style>
</head>
<body>

<div class="card">
    <h2>Edit Data Mahasiswa (ID #<?= (int) $id_mahasiswa ?>)</h2>

    <?php if ($pesan_error !== ''): ?>
        <div class="alert-error">
            <strong>Peringatan:</strong> <?= htmlspecialchars($pesan_error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="edit.php?id=<?= (int) $id_mahasiswa ?>">
        <!-- Menyimpan Primary Key ID secara tersembunyi (Hidden Input) -->
        <input type="hidden" name="id_mahasiswa" value="<?= (int) $id_mahasiswa ?>">

        <div class="form-group">
            <label for="id_prodi">Program Studi *</label>
            <select id="id_prodi" name="id_prodi" required>
                <?php foreach ($daftar_prodi as $prodi): ?>
                    <option value="<?= (int) $prodi['id_prodi'] ?>" 
                        <?= ((int) $data_mhs['id_prodi'] === (int) $prodi['id_prodi']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($prodi['jenjang'] . ' - ' . $prodi['nama_prodi'] . ' (' . $prodi['kode_prodi'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row-2col">
            <div class="form-group">
                <label for="nim">NIM *</label>
                <input type="text" id="nim" name="nim" maxlength="15" required 
                       value="<?= htmlspecialchars($data_mhs['nim']) ?>">
            </div>

            <div class="form-group">
                <label for="jenis_kelamin">Jenis Kelamin *</label>
                <select id="jenis_kelamin" name="jenis_kelamin" required>
                    <option value="L" <?= ($data_mhs['jenis_kelamin'] === 'L') ? 'selected' : '' ?>>Laki-laki (L)</option>
                    <option value="P" <?= ($data_mhs['jenis_kelamin'] === 'P') ? 'selected' : '' ?>>Perempuan (P)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="nama_lengkap">Nama Lengkap Mahasiswa *</label>
            <input type="text" id="nama_lengkap" name="nama_lengkap" maxlength="120" required 
                   value="<?= htmlspecialchars($data_mhs['nama_lengkap']) ?>">
        </div>

        <div class="row-2col">
            <div class="form-group">
                <label for="tanggal_lahir">Tanggal Lahir *</label>
                <input type="date" id="tanggal_lahir" name="tanggal_lahir" required 
                       value="<?= htmlspecialchars($data_mhs['tanggal_lahir']) ?>">
            </div>

            <div class="form-group">
                <label for="angkatan">Tahun Angkatan *</label>
                <input type="number" id="angkatan" name="angkatan" min="2000" max="2100" required 
                       value="<?= (int) $data_mhs['angkatan'] ?>">
            </div>
        </div>

        <div class="row-2col">
            <div class="form-group">
                <label for="ipk">Indeks Prestasi Kumulatif (0.00 - 4.00) *</label>
                <input type="number" id="ipk" name="ipk" step="0.01" min="0.00" max="4.00" required 
                       value="<?= htmlspecialchars((string) $data_mhs['ipk']) ?>">
            </div>

            <div class="form-group">
                <label for="email_kampus">Email Kampus</label>
                <input type="email" id="email_kampus" name="email_kampus" maxlength="120" 
                       value="<?= htmlspecialchars((string) ($data_mhs['email_kampus'] ?? '')) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="alamat_domisili">Alamat Domisili</label>
            <textarea id="alamat_domisili" name="alamat_domisili"><?= htmlspecialchars((string) ($data_mhs['alamat_domisili'] ?? '')) ?></textarea>
        </div>

        <div class="form-group">
            <label style="font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="status_aktif" value="1" <?= $data_mhs['status_aktif'] ? 'checked' : '' ?>>
                <strong>Mahasiswa Berstatus Aktif Kuliah</strong>
            </label>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            <a href="index.php" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>

</body>
</html>

```

### Bagian 3: Fitur Delete & Penanganan Foreign Key (`hapus.php`)

Buat file baru bernama `hapus.php` di dalam folder `C:\laragon\www\si_akademik\hapus.php`:

```
<?php
require_once 'koneksi.php';

// Menangkap tipe entitas yang akan dihapus ('mahasiswa' atau 'prodi') dan ID-nya
$tipe = trim($_GET['tipe'] ?? 'mahasiswa');
$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=" . urlencode("Parameter ID penghapusan tidak valid!"));
    exit;
}

try {
    if ($tipe === 'prodi') {
        // SKENARIO A: Menghapus Data Tabel Induk (program_studi)
        // Dilindungi oleh aturan ON DELETE RESTRICT pada fk_mahasiswa_prodi
        $sql = "DELETE FROM program_studi 
                WHERE id_prodi = :id 
                RETURNING kode_prodi, nama_prodi";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $terhapus = $stmt->fetch();
        if ($terhapus) {
            $pesan = "Program Studi {$terhapus['nama_prodi']} ({$terhapus['kode_prodi']}) berhasil dihapus.";
            header("Location: index.php?pesan=" . urlencode($pesan));
        } else {
            header("Location: index.php?error=" . urlencode("Data Program Studi tidak ditemukan."));
        }
        exit;

    } else {
        // SKENARIO B: Menghapus Data Tabel Anak (mahasiswa)
        // Relasi ke kontak_darurat menggunakan ON DELETE CASCADE (otomatis bersih)
        $sql = "DELETE FROM mahasiswa 
                WHERE id_mahasiswa = :id 
                RETURNING nim, nama_lengkap";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $terhapus = $stmt->fetch();
        if ($terhapus) {
            $pesan = "Data mahasiswa {$terhapus['nama_lengkap']} (NIM: {$terhapus['nim']}) berhasil dihapus dari basis data.";
            header("Location: index.php?pesan=" . urlencode($pesan));
        } else {
            header("Location: index.php?error=" . urlencode("Data mahasiswa tidak ditemukan."));
        }
        exit;
    }

} catch (PDOException $e) {
    // Menangkap Error Pelanggaran FOREIGN KEY CONSTRAINT (SQLSTATE 23503)
    if ($e->getCode() === '23503') {
        $peringatan_fk = "Penghapusan Ditolak oleh PostgreSQL (Foreign Key Restrict): Data Program Studi ini tidak dapat dihapus karena masih memiliki Mahasiswa yang terdaftar di dalamnya!";
        header("Location: index.php?error=" . urlencode($peringatan_fk));
        exit;
    }

    // Error basis data lainnya
    header("Location: index.php?error=" . urlencode("Gagal menghapus data: " . $e->getMessage()));
    exit;
}
?>

```

### Bagian 4: Penyempurnaan `index.php` (Versi Final dengan Notifikasi & Tombol Uji Hapus Prodi)

Perbarui file `C:\laragon\www\si_akademik\index.php` yang telah dibuat pada Pertemuan 5 menjadi versi final berikut:

```
<?php
require_once 'koneksi.php';

// Menangkap pesan notifikasi sukses atau peringatan error dari URL ($_GET)
$pesan_sukses = trim($_GET['pesan'] ?? '');
$pesan_error  = trim($_GET['error'] ?? '');

// 1. Kueri Statistik Rekapitulasi per Program Studi (LEFT JOIN + GROUP BY)
$sql_statistik = "
    SELECT 
        p.id_prodi,
        p.kode_prodi,
        p.nama_prodi,
        p.jenjang,
        p.akreditasi,
        COUNT(m.id_mahasiswa) AS total_mahasiswa,
        COALESCE(ROUND(AVG(m.ipk), 2), 0.00) AS rata_rata_ipk
    FROM program_studi p
    LEFT JOIN mahasiswa m ON p.id_prodi = m.id_prodi
    GROUP BY p.id_prodi, p.kode_prodi, p.nama_prodi, p.jenjang, p.akreditasi
    ORDER BY p.id_prodi ASC
";
$daftar_prodi = $pdo->query($sql_statistik)->fetchAll();

// 2. Menangkap Parameter Filter Pencarian (keyword) & Filter Prodi
$keyword_cari = trim($_GET['keyword'] ?? '');
$filter_prodi = isset($_GET['id_prodi']) && $_GET['id_prodi'] !== '' ? (int) $_GET['id_prodi'] : 0;

// 3. Kueri Utama Daftar Mahasiswa dengan INNER JOIN
$sql_mahasiswa = "
    SELECT 
        m.id_mahasiswa,
        m.nim,
        m.nama_lengkap,
        m.jenis_kelamin,
        m.tanggal_lahir,
        m.angkatan,
        m.ipk,
        m.status_aktif,
        m.email_kampus,
        p.kode_prodi,
        p.nama_prodi,
        p.jenjang
    FROM mahasiswa m
    INNER JOIN program_studi p ON m.id_prodi = p.id_prodi
    WHERE 1=1
";

$params = [];

if ($keyword_cari !== '') {
    $sql_mahasiswa .= " AND (m.nama_lengkap ILIKE :keyword OR m.nim ILIKE :keyword)";
    $params[':keyword'] = '%' . $keyword_cari . '%';
}

if ($filter_prodi > 0) {
    $sql_mahasiswa .= " AND m.id_prodi = :id_prodi";
    $params[':id_prodi'] = $filter_prodi;
}

$sql_mahasiswa .= " ORDER BY m.nim ASC";

$stmt_mhs = $pdo->prepare($sql_mahasiswa);
$stmt_mhs->execute($params);
$daftar_mahasiswa = $stmt_mhs->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Akademik - CRUD Utuh PostgreSQL & PHP PDO</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #1e293b; margin: 0; padding: 24px; }
        .container { max-width: 1150px; margin: 0 auto; background: #ffffff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h1 { margin-top: 0; color: #0f172a; font-size: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
        h2 { color: #334155; font-size: 18px; margin-top: 24px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px 12px; text-align: left; }
        th { background-color: #1e3a8a; color: #ffffff; font-weight: 600; }
        tr:nth-child(even) { background-color: #f8fafc; }
        tr:hover { background-color: #eff6ff; }
        .filter-bar { display: flex; gap: 12px; align-items: center; background: #f1f5f9; padding: 14px; border-radius: 6px; margin-bottom: 16px; flex-wrap: wrap; }
        .filter-bar input[type="text"], .filter-bar select { padding: 8px 12px; border: 1px solid #94a3b8; border-radius: 4px; font-size: 14px; }
        .btn { padding: 8px 14px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #2563eb; color: #ffffff; }
        .btn-reset { background-color: #64748b; color: #ffffff; }
        .btn-success { background-color: #16a34a; color: #ffffff; margin-left: auto; }
        .btn-edit { background-color: #0284c7; color: #ffffff; padding: 5px 10px; }
        .btn-delete { background-color: #dc2626; color: #ffffff; padding: 5px 10px; }
        .alert-success { background-color: #dcfce7; border: 1px solid #4ade80; color: #166534; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .alert-error { background-color: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-aktif { background-color: #dcfce7; color: #166534; }
        .badge-nonaktif { background-color: #fee2e2; color: #991b1b; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>

<div class="container">
    <h1>Sistem Informasi Akademik Fakultas (CRUD PostgreSQL &amp; PHP PDO)</h1>

    <!-- Menampilkan Pesan Notifikasi Sukses / Peringatan Error Foreign Key -->
    <?php if ($pesan_sukses !== ''): ?>
        <div class="alert-success">
            <strong>Berhasil:</strong> <?= htmlspecialchars($pesan_sukses) ?>
        </div>
    <?php endif; ?>

    <?php if ($pesan_error !== ''): ?>
        <div class="alert-error">
            <strong>Peringatan Sistem:</strong> <?= htmlspecialchars($pesan_error) ?>
        </div>
    <?php endif; ?>

    <!-- BAGIAN 1: Tabel Rekapitulasi Statistik Program Studi -->
    <h2>1. Data Master Program Studi &amp; Statistik (Uji Proteksi <code>ON DELETE RESTRICT</code>)</h2>
    <table>
        <thead>
            <tr>
                <th>Kode Prodi</th>
                <th>Nama Program Studi</th>
                <th class="text-center">Jenjang</th>
                <th class="text-center">Akreditasi</th>
                <th class="text-center">Total Mahasiswa</th>
                <th class="text-right">Rata-Rata IPK</th>
                <th class="text-center">Uji Hapus Induk</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($daftar_prodi as $prodi): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($prodi['kode_prodi']) ?></strong></td>
                    <td><?= htmlspecialchars($prodi['nama_prodi']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($prodi['jenjang']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($prodi['akreditasi']) ?></td>
                    <td class="text-center"><strong><?= (int) $prodi['total_mahasiswa'] ?></strong> Orang</td>
                    <td class="text-right"><?= number_format((float) $prodi['rata_rata_ipk'], 2, ',', '.') ?></td>
                    <td class="text-center">
                        <a href="hapus.php?tipe=prodi&amp;id=<?= (int) $prodi['id_prodi'] ?>" 
                           class="btn btn-delete"
                           onclick="return confirm('Uji hapus Program Studi <?= htmlspecialchars($prodi['nama_prodi']) ?>?');">
                           Hapus Prodi
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- BAGIAN 2: Tabel Daftar Mahasiswa & Operasi CRUD -->
    <h2>2. Daftar Mahasiswa Terdaftar (Operasi CRUD Penuh)</h2>

    <form method="GET" action="index.php" class="filter-bar">
        <div>
            <label for="keyword"><strong>Cari Nama / NIM:</strong></label>
            <input type="text" id="keyword" name="keyword" 
                   value="<?= htmlspecialchars($keyword_cari) ?>" 
                   placeholder="Ketik nama atau NIM...">
        </div>

        <div>
            <label for="id_prodi"><strong>Program Studi:</strong></label>
            <select id="id_prodi" name="id_prodi">
                <option value="">-- Semua Program Studi --</option>
                <?php foreach ($daftar_prodi as $prodi): ?>
                    <option value="<?= (int) $prodi['id_prodi'] ?>" 
                        <?= ($filter_prodi === (int) $prodi['id_prodi']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($prodi['jenjang'] . ' - ' . $prodi['nama_prodi']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Filter Data</button>
        <a href="index.php" class="btn btn-reset">Reset</a>
        <a href="tambah.php" class="btn btn-success">+ Tambah Mahasiswa Baru</a>
    </form>

    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>NIM</th>
                <th>Nama Lengkap</th>
                <th class="text-center">L/P</th>
                <th>Program Studi</th>
                <th class="text-center">Angkatan</th>
                <th class="text-right">IPK</th>
                <th class="text-center">Status</th>
                <th>Email Kampus</th>
                <th class="text-center" style="width: 130px;">Aksi CRUD</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($daftar_mahasiswa) === 0): ?>
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px; color: #64748b;">
                        Data mahasiswa tidak ditemukan.
                    </td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($daftar_mahasiswa as $mhs): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($mhs['nim']) ?></strong></td>
                        <td><?= htmlspecialchars($mhs['nama_lengkap']) ?></td>
                        <td class="text-center"><?= htmlspecialchars($mhs['jenis_kelamin']) ?></td>
                        <td>
                            <?= htmlspecialchars($mhs['nama_prodi']) ?> 
                            (<strong><?= htmlspecialchars($mhs['kode_prodi']) ?></strong>)
                        </td>
                        <td class="text-center"><?= (int) $mhs['angkatan'] ?></td>
                        <td class="text-right"><strong><?= number_format((float) $mhs['ipk'], 2, ',', '.') ?></strong></td>
                        <td class="text-center">
                            <?php if ($mhs['status_aktif']): ?>
                                <span class="badge badge-aktif">Aktif</span>
                            <?php else: ?>
                                <span class="badge badge-nonaktif">Non-Aktif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $mhs['email_kampus'] !== null 
                                ? htmlspecialchars($mhs['email_kampus']) 
                                : '<em style="color: #94a3b8;">Belum diisi</em>' ?>
                        </td>
                        <td class="text-center">
                            <a href="edit.php?id=<?= (int) $mhs['id_mahasiswa'] ?>" class="btn btn-edit">Edit</a>
                            <a href="hapus.php?id=<?= (int) $mhs['id_mahasiswa'] ?>" 
                               class="btn btn-delete"
                               onclick="return confirm('Apakah Anda yakin ingin menghapus mahasiswa <?= htmlspecialchars($mhs['nama_lengkap']) ?> (NIM: <?= htmlspecialchars($mhs['nim']) ?>)?');">
                               Hapus
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>

```
