# Modul 5: PostgreSQL Lanjutan (JOIN, Agregasi, Transaksi) & Praktik Integrasi PHP Dasar (Read Data)

Repositori ini berisi kumpulan kueri SQL tingkat lanjut (`INNER JOIN`, `LEFT JOIN`, fungsi agregasi, `GROUP BY`, serta pengendalian transaksi `BEGIN`, `COMMIT`, `ROLLBACK`) dan kode program dasar integrasi web PHP PDO (`koneksi.php` dan `index.php`) untuk **Modul 5 Praktikum Basis Data (TA 2026/2027)**.

## Bagian 1: Kueri PostgreSQL Lanjutan (`db_akademik_praktikum`)

Pastikan servis PostgreSQL di Laragon berstatus **Running**, lalu buka **Query Tool** (pgAdmin 4) pada database `db_akademik_praktikum` dan jalankan skrip berikut secara bertahap:

### Langkah 1: Menyiapkan 1 Program Studi Tanpa Mahasiswa (Penguji `JOIN`)

```
-- PERSIAPAN DATA PENGUJI JOIN: Menambahkan 1 Prodi Baru Tanpa Mahasiswa
INSERT INTO program_studi (kode_prodi, nama_prodi, jenjang, akreditasi)
VALUES ('BD-S1', 'Bisnis Digital', 'S1', 'Baik') 
RETURNING id_prodi, kode_prodi, nama_prodi, jenjang, akreditasi;

```

### Langkah 2: Penggabungan Tabel dengan `INNER JOIN` dan `LEFT JOIN`

#### A. Kueri `INNER JOIN`

```
-- KUERI INNER JOIN: Menampilkan Daftar Mahasiswa Beserta Nama Prodinya
-- Hanya menampilkan baris yang cocok di tabel mahasiswa (m) & program_studi (p)
SELECT 
    m.id_mahasiswa,
    m.nim,
    m.nama_lengkap,
    m.angkatan,
    m.ipk,
    m.status_aktif,
    p.kode_prodi,
    p.nama_prodi,
    p.jenjang
FROM mahasiswa m
INNER JOIN program_studi p ON m.id_prodi = p.id_prodi
ORDER BY m.nim ASC;

```

#### B. Kueri `LEFT JOIN`

```
-- KUERI LEFT JOIN: Menampilkan SELURUH Program Studi Beserta Mahasiswanya
-- Prodi yang belum punya mahasiswa tetap tampil dengan nilai kolom mahasiswa NULL
SELECT 
    p.id_prodi,
    p.kode_prodi,
    p.nama_prodi,
    m.nim,
    m.nama_lengkap,
    m.ipk
FROM program_studi p
LEFT JOIN mahasiswa m ON p.id_prodi = m.id_prodi
ORDER BY p.id_prodi ASC, m.nim ASC;

```

### Langkah 3: Fungsi Agregasi (`COUNT`, `AVG`, `MAX`, `MIN`) dan `GROUP BY`

```
-- KUERI AGREGASI & GROUP BY: Statistik Akademik per Program Studi
SELECT 
    p.id_prodi,
    p.kode_prodi,
    p.nama_prodi,
    p.jenjang,
    p.akreditasi,
    COUNT(m.id_mahasiswa) AS total_mahasiswa,
    COALESCE(ROUND(AVG(m.ipk), 2), 0.00) AS rata_rata_ipk,
    COALESCE(MAX(m.ipk), 0.00) AS ipk_tertinggi,
    COALESCE(MIN(m.ipk), 0.00) AS ipk_terendah
FROM program_studi p
LEFT JOIN mahasiswa m ON p.id_prodi = m.id_prodi
GROUP BY 
    p.id_prodi, 
    p.kode_prodi, 
    p.nama_prodi, 
    p.jenjang, 
    p.akreditasi
ORDER BY total_mahasiswa DESC, p.kode_prodi ASC;

```

### Langkah 4: Praktik Pengendalian Transaksi (`BEGIN`, `ROLLBACK`, `COMMIT`)

#### A. Simulasi Transaksi 1: Membatalkan Perubahan dengan `ROLLBACK`

```
-- SIMULASI TRANSAKSI 1: Membatalkan Perubahan dengan ROLLBACK
-- 1. Memulai blok transaksi
BEGIN;

-- 2. Melakukan perubahan di dalam transaksi (mengubah seluruh IPK menjadi 4.00!)
UPDATE mahasiswa SET ipk = 4.00;

-- 3. Mengecek isi tabel di dalam sesi transaksi saat ini (terlihat IPK menjadi 4.00)
SELECT nim, nama_lengkap, ipk FROM mahasiswa;

-- 4. Membatalkan seluruh perubahan karena terjadi kesalahan logika
ROLLBACK;

-- 5. Mengecek kembali isi tabel setelah ROLLBACK (seluruh IPK kembali aman seperti semula!)
SELECT nim, nama_lengkap, ipk FROM mahasiswa;

```

#### B. Simulasi Transaksi 2: Menyimpan Perubahan Permanen dengan `COMMIT`

```
BEGIN;

-- 1. Menambahkan 1 mahasiswa baru ke prodi Teknologi Informasi (id_prodi = 3)
INSERT INTO mahasiswa (
    id_prodi, nim, nama_lengkap, jenis_kelamin, tanggal_lahir, 
    angkatan, ipk, status_aktif, email_kampus, alamat_domisili
)
VALUES (
    3, '240301002', 'Gilang Dirga Pratama', 'L', '2005-09-12', 
    2024, 3.60, TRUE, 'gilang.pratama@kampus.ac.id', 'Kec. Sindangwangi, Majalengka'
);

-- 2. Mengaktifkan kembali status mahasiswa Rizky Ramadhan
UPDATE mahasiswa 
SET status_aktif = TRUE 
WHERE nim = '220201002';

-- 3. Menyimpan kedua perubahan di atas secara permanen ke dalam database
COMMIT;

```

## Bagian 2: Integrasi Web PHP & PDO (`C:\laragon\www\si_akademik\`)

Buat sebuah folder proyek baru bernama `si_akademik` di dalam direktori `C:\laragon\www\`:

```
C:\laragon\www\si_akademik\
├── index.php
└── koneksi.php

```

### 1. File Koneksi Database (`koneksi.php`)

Simpan kode berikut di dalam `C:\laragon\www\si_akademik\koneksi.php`:

```
<?php
// 1. Parameter Konfigurasi Server PostgreSQL Laragon
$db_host = 'localhost';
$db_port = '5432';
$db_name = 'db_akademik_praktikum';
$db_user = 'postgres';
$db_pass = 'postgres'; // Sesuaikan menjadi '' (string kosong) jika postgres Anda tanpa password

// 2. Menyusun Data Source Name (DSN) khusus driver PostgreSQL
$dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name}";

// 3. Opsi Konfigurasi Standar Keamanan & Performa PDO
$options = [
    // Memaksa PDO melempar objek PDOException saat terjadi error SQL/koneksi
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Mengatur format pengambilan baris data default menjadi Associative Array
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Menonaktifkan emulasi agar Prepared Statement dikompilasi asli oleh PostgreSQL
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// 4. Membuka Koneksi di dalam Blok Penanganan Eksepsi (try-catch)
try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Menghentikan eksekusi halaman & menampilkan pesan error yang informatif jika gagal
    die("
        <div style='font-family: Arial, sans-serif; padding: 20px; border: 2px solid #dc2626; background: #fef2f2; color: #991b1b; border-radius: 8px; max-width: 700px; margin: 40px auto;'>
            <h3 style='margin-top: 0;'>Koneksi ke Basis Data PostgreSQL Gagal!</h3>
            <p>Pastikan servis <strong>PostgreSQL</strong> di Laragon sedang berjalan pada port <strong>5432</strong> dan database <code>db_akademik_praktikum</code> sudah dibuat.</p>
            <p><strong>Detail Teknis Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        </div>
    ");
}
?>

```

### 2. Halaman Utama Menampilkan Data JOIN (`index.php`)

Simpan kode berikut di dalam `C:\laragon\www\si_akademik\index.php`, lalu akses melalui browser pada alamat `http://localhost/si_akademik/`:

```
<?php
// 1. Memanggil file koneksi database (menghasilkan variabel objek $pdo)
require_once 'koneksi.php';

// 2. Mengambil Data Statistik Rekapitulasi per Program Studi (LEFT JOIN + GROUP BY)
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
$stmt_stat = $pdo->query($sql_statistik);
$daftar_prodi = $stmt_stat->fetchAll();

// 3. Menangkap Parameter Filter Pencarian (keyword) & Filter Prodi dari URL ($_GET)
$keyword_cari = trim($_GET['keyword'] ?? '');
$filter_prodi = isset($_GET['id_prodi']) && $_GET['id_prodi'] !== '' ? (int) $_GET['id_prodi'] : 0;

// 4. Menyusun Kueri Utama Daftar Mahasiswa dengan INNER JOIN secara Dinamis & Aman
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

// Jika pengguna mengetik kata kunci pencarian, gunakan ILIKE khas PostgreSQL
if ($keyword_cari !== '') {
    $sql_mahasiswa .= " AND (m.nama_lengkap ILIKE :keyword OR m.nim ILIKE :keyword)";
    $params[':keyword'] = '%' . $keyword_cari . '%';
}

// Jika pengguna memilih filter program studi tertentu
if ($filter_prodi > 0) {
    $sql_mahasiswa .= " AND m.id_prodi = :id_prodi";
    $params[':id_prodi'] = $filter_prodi;
}

$sql_mahasiswa .= " ORDER BY m.nim ASC";

// 5. Mengeksekusi Kueri Daftar Mahasiswa menggunakan PDO Prepared Statement
$stmt_mhs = $pdo->prepare($sql_mahasiswa);
$stmt_mhs->execute($params);
$daftar_mahasiswa = $stmt_mhs->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Akademik - PostgreSQL & PHP PDO</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; color: #1e293b; margin: 0; padding: 24px; }
        .container { max-width: 1150px; margin: 0 auto; background: #ffffff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h1 { margin-top: 0; color: #0f172a; font-size: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
        h2 { color: #334155; font-size: 18px; margin-top: 24px; margin-bottom: 12px; }
        
        /* Tabel Styling */
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px; }
        th, td { border: 1px solid #cbd5e1; padding: 10px 12px; text-align: left; }
        th { background-color: #1e3a8a; color: #ffffff; font-weight: 600; }
        tr:nth-child(even) { background-color: #f8fafc; }
        tr:hover { background-color: #eff6ff; }
        
        /* Form Filter Styling */
        .filter-bar { display: flex; gap: 12px; align-items: center; background: #f1f5f9; padding: 14px; border-radius: 6px; margin-bottom: 16px; flex-wrap: wrap; }
        .filter-bar input[type="text"], .filter-bar select { padding: 8px 12px; border: 1px solid #94a3b8; border-radius: 4px; font-size: 14px; }
        .btn { padding: 8px 14px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #2563eb; color: #ffffff; }
        .btn-reset { background-color: #64748b; color: #ffffff; }
        .btn-success { background-color: #16a34a; color: #ffffff; margin-left: auto; }
        
        /* Badge Status */
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-aktif { background-color: #dcfce7; color: #166534; }
        .badge-nonaktif { background-color: #fee2e2; color: #991b1b; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>

<div class="container">
    <h1>Dasbor Akademik Fakultas (PostgreSQL + PHP PDO)</h1>

    <!-- BAGIAN 1: Tabel Rekapitulasi Statistik Program Studi (Hasil LEFT JOIN & GROUP BY) -->
    <h2>1. Rekapitulasi Statistik per Program Studi (<code>LEFT JOIN</code> &amp; <code>GROUP BY</code>)</h2>
    <table>
        <thead>
            <tr>
                <th>Kode Prodi</th>
                <th>Nama Program Studi</th>
                <th class="text-center">Jenjang</th>
                <th class="text-center">Akreditasi</th>
                <th class="text-center">Total Mahasiswa (<code>COUNT</code>)</th>
                <th class="text-right">Rata-Rata IPK (<code>AVG</code>)</th>
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
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- BAGIAN 2: Daftar Mahasiswa & Filter Pencarian (Hasil INNER JOIN & ILIKE) -->
    <h2>2. Data Mahasiswa Terdaftar (<code>INNER JOIN</code> &amp; Pencarian <code>ILIKE</code>)</h2>

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

        <!-- Tombol Persiapan untuk Pertemuan 6 -->
        <a href="tambah.php" class="btn btn-success">+ Tambah Mahasiswa Baru</a>
    </form>

    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>NIM</th>
                <th>Nama Lengkap</th>
                <th class="text-center">L/P</th>
                <th>Program Studi (Hasil <code>JOIN</code>)</th>
                <th class="text-center">Angkatan</th>
                <th class="text-right">IPK</th>
                <th class="text-center">Status</th>
                <th>Email Kampus</th>
                <th class="text-center">Aksi (Pertemuan 6)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($daftar_mahasiswa) === 0): ?>
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px; color: #64748b;">
                        Data mahasiswa tidak ditemukan untuk kriteria pencarian tersebut.
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
                            <a href="edit.php?id=<?= (int) $mhs['id_mahasiswa'] ?>">Edit</a> | 
                            <a href="hapus.php?id=<?= (int) $mhs['id_mahasiswa'] ?>">Hapus</a>
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