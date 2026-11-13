# Modul 7: Mini-Proyek Sistem Informasi Inventaris Laboratorium (PostgreSQL & PHP PDO)

Repositori ini berisi kumpulan skrip SQL (DDL, DML, TCL, dan Agregasi) serta kode program lengkap aplikasi web **Sistem Informasi Inventaris Alat Laboratorium (`db_inventaris_lab`)** untuk **Modul 7 Praktikum Basis Data (TA 2026/2027)**.

---

## Tahap 1: Penyelesaian SQL DDL — Pembuatan Database & Tabel Berelasi

Pastikan layanan **Apache** dan **PostgreSQL (Port 5432)** di Laragon telah berstatus **Running**. Buat basis data baru bernama `db_inventaris_lab` melalui pgAdmin 4, lalu buka **Query Tool** pada basis data tersebut dan jalankan skrip DDL berikut secara berurutan:

### 1. Membuat Tabel Induk (Parent Table): `kategori_alat`

```sql
CREATE TABLE kategori_alat (
    id_kategori INTEGER GENERATED ALWAYS AS IDENTITY,
    kode_kategori VARCHAR(10) NOT NULL,
    nama_kategori VARCHAR(100) NOT NULL,
    lokasi_ruang VARCHAR(80) NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_kategori_alat PRIMARY KEY (id_kategori),
    CONSTRAINT uq_kategori_kode UNIQUE (kode_kategori)
);
```

### 2. Membuat Tabel Anak Utama (Child Table 1): `alat_laboratorium`

```sql
CREATE TABLE alat_laboratorium (
    id_alat INTEGER GENERATED ALWAYS AS IDENTITY,
    id_kategori INTEGER NOT NULL,
    kode_inventaris VARCHAR(25) NOT NULL,
    nama_alat VARCHAR(150) NOT NULL,
    merk_spesifikasi TEXT,
    tanggal_pengadaan DATE NOT NULL,
    jumlah_unit INTEGER NOT NULL DEFAULT 1,
    harga_satuan NUMERIC(14, 2) NOT NULL DEFAULT 0.00,
    kondisi_baik BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_alat_laboratorium PRIMARY KEY (id_alat),
    CONSTRAINT uq_alat_kode_inventaris UNIQUE (kode_inventaris),
    CONSTRAINT chk_alat_jumlah_unit CHECK (jumlah_unit >= 0),
    CONSTRAINT chk_alat_harga_satuan CHECK (harga_satuan >= 0.00),
    -- Relasi ke Tabel Induk dengan ON DELETE RESTRICT
    -- (Kategori tidak boleh dihapus jika masih ada alat di dalamnya)
    CONSTRAINT fk_alat_kategori FOREIGN KEY (id_kategori)
        REFERENCES kategori_alat (id_kategori)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);
```

### 3. Membuat Tabel Anak Pendukung (Child Table 2): `riwayat_perawatan`

```sql
CREATE TABLE riwayat_perawatan (
    id_perawatan INTEGER GENERATED ALWAYS AS IDENTITY,
    id_alat INTEGER NOT NULL,
    tanggal_servis DATE NOT NULL DEFAULT CURRENT_DATE,
    deskripsi_tindakan TEXT NOT NULL,
    biaya_servis NUMERIC(12, 2) NOT NULL DEFAULT 0.00,
    CONSTRAINT pk_riwayat_perawatan PRIMARY KEY (id_perawatan),
    CONSTRAINT chk_perawatan_biaya CHECK (biaya_servis >= 0.00),
    -- Relasi ke Tabel alat_laboratorium dengan ON DELETE CASCADE
    -- (Jika data alat dihapus, riwayat servisnya ikut terhapus otomatis)
    CONSTRAINT fk_perawatan_alat FOREIGN KEY (id_alat)
        REFERENCES alat_laboratorium (id_alat)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);
```

### 4. Modifikasi Struktur Tabel (`ALTER TABLE`) & Pembuatan `INDEX`

```sql
-- Menambahkan kolom email penanggung jawab alat (opsional namun unik jika diisi)
ALTER TABLE alat_laboratorium
    ADD COLUMN email_pic VARCHAR(120);

ALTER TABLE alat_laboratorium
    ADD CONSTRAINT uq_alat_email_pic UNIQUE (email_pic);

-- Membuat indeks pencarian cepat pada kolom nama_alat
CREATE INDEX idx_alat_nama ON alat_laboratorium (nama_alat);
```

---

## Tahap 2: Penyelesaian SQL DML — Pengisian Data Awal, Transaksi, & Kueri Laporan

Masih di dalam **Query Tool** database `db_inventaris_lab`, jalankan skrip SQL DML serta kueri laporan analitik berikut:

### 1. Mengisi Data Tabel Induk `kategori_alat` menggunakan Multi-Row `INSERT` + `RETURNING`

```sql
INSERT INTO kategori_alat (kode_kategori, nama_kategori, lokasi_ruang)
VALUES
    ('JRG-01', 'Perangkat Jaringan & Server', 'Lab Jaringan Lt. 2'),
    ('KOM-02', 'Komputer & Workstation', 'Lab Pemrograman Lt. 1'),
    ('MM-03',  'Perangkat Multimedia & IoT', 'Lab Multimedia Lt. 3'),
    ('RBT-04', 'Perangkat Robotika & Sensor', 'Lab Riset Terpadu Lt. 3')
RETURNING id_kategori, kode_kategori, nama_kategori, lokasi_ruang;
```

### 2. Mengisi Data Tabel Anak `alat_laboratorium` & `riwayat_perawatan` dalam Blok Transaksi (TCL)

```sql
BEGIN;

INSERT INTO alat_laboratorium (
    id_kategori, kode_inventaris, nama_alat, merk_spesifikasi,
    tanggal_pengadaan, jumlah_unit, harga_satuan, kondisi_baik, email_pic
)
VALUES
    (1, 'INV-JRG-2025-001', 'Router Core Mikrotik Cloud', 'Mikrotik CCR2004-16G-2S+ / 16 Port Gigabit', '2025-02-15', 2, 8500000.00, TRUE, 'admin.jaringan@kampus.ac.id'),
    (1, 'INV-JRG-2025-002', 'Switch Managed 24 Port PoE', 'Cisco Catalyst 1000-24P / Layer 2 Managed', '2025-03-10', 4, 6200000.00, TRUE, NULL),
    (2, 'INV-KOM-2025-001', 'PC Workstation Praktikum', 'Intel Core i7 Gen-13, RAM 32GB, SSD NVMe 1TB', '2025-05-20', 25, 14500000.00, TRUE, 'koordinator.lab1@kampus.ac.id'),
    (2, 'INV-KOM-2024-002', 'UPS Server Line Interactive', 'APC Smart-UPS 1500VA LCD 230V', '2024-08-12', 3, 5400000.00, FALSE, NULL),
    (3, 'INV-MM-2026-001', 'Kamera Mirrorless Dokumentasi', 'Sony Alpha a6400 Kit 16-50mm OLED', '2026-01-18', 2, 13800000.00, TRUE, 'divisi.multimedia@kampus.ac.id'),
    (3, 'INV-MM-2026-002', 'Proyektor Laser Interaktif', 'Epson EB-L200SW WXGA 3800 Lumens', '2026-02-05', 2, 16000000.00, TRUE, NULL)
RETURNING id_alat, kode_inventaris, nama_alat, jumlah_unit, harga_satuan, kondisi_baik;

INSERT INTO riwayat_perawatan (id_alat, tanggal_servis, deskripsi_tindakan, biaya_servis)
VALUES
    (3, '2026-06-10', 'Pembersihan debu heatsink dan pembaruan thermal paste rutin', 250000.00),
    (4, '2026-09-15', 'Penggantian baterai sel timbal-asam UPS yang mulai drop', 1200000.00);

COMMIT;
```

### 3. Kueri Laporan Gabungan (`LEFT JOIN` + Agregasi Nilai Aset + `GROUP BY`)

```sql
SELECT 
    k.id_kategori,
    k.kode_kategori,
    k.nama_kategori,
    k.lokasi_ruang,
    COUNT(a.id_alat) AS jenis_alat,
    COALESCE(SUM(a.jumlah_unit), 0) AS total_unit_fisik,
    COALESCE(SUM(a.jumlah_unit * a.harga_satuan), 0.00) AS total_nilai_aset,
    COALESCE(ROUND(AVG(a.harga_satuan), 2), 0.00) AS rata_harga_satuan
FROM kategori_alat k
LEFT JOIN alat_laboratorium a ON k.id_kategori = a.id_kategori
GROUP BY k.id_kategori, k.kode_kategori, k.nama_kategori, k.lokasi_ruang
ORDER BY k.id_kategori ASC;
```

---

## Tahap 3: Proyek Aplikasi Web PHP PDO (`C:\laragon\www\inventaris_lab\`)

Buat sebuah folder proyek baru di dalam direktori web Laragon dengan nama `inventaris_lab` sehingga strukturnya adalah sebagai berikut:

```text
C:\laragon\www\inventaris_lab\
├── edit.php
├── hapus.php
├── index.php
├── koneksi.php
└── tambah.php
```

### 1. Kode Lengkap File `koneksi.php`

Buat file `C:\laragon\www\inventaris_lab\koneksi.php`:

```php
<?php
$db_host = 'localhost';
$db_port = '5432';
$db_name = 'db_inventaris_lab';
$db_user = 'postgres';
$db_pass = 'postgres'; // Ubah menjadi '' jika akun postgres Laragon Anda tanpa password

$dsn = "pgsql:host={$db_host};port={$db_port};dbname={$db_name}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    die("
        <div style='font-family: sans-serif; padding: 20px; border: 2px solid #dc2626; background: #fef2f2; color: #991b1b; border-radius: 8px; max-width: 680px; margin: 40px auto;'>
            <h3 style='margin-top:0;'>Koneksi Basis Data Gagal!</h3>
            <p>Pastikan servis PostgreSQL di Laragon berjalan pada port 5432 dan database <code>db_inventaris_lab</code> telah dibuat.</p>
            <p><strong>Pesan Teknis:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        </div>
    ");
}
?>
```

### 2. Kode Lengkap File `index.php`

Buat file `C:\laragon\www\inventaris_lab\index.php`:

```php
<?php
require_once 'koneksi.php';

// Menangkap notifikasi sukses atau pesan peringatan error dari operasi CRUD
$pesan_sukses = trim($_GET['pesan'] ?? '');
$pesan_error  = trim($_GET['error'] ?? '');

// 1. Kueri Laporan Rekapitulasi Aset per Kategori (LEFT JOIN + Fungsi Agregasi)
$sql_rekap = "
    SELECT 
        k.id_kategori,
        k.kode_kategori,
        k.nama_kategori,
        k.lokasi_ruang,
        COUNT(a.id_alat) AS jenis_alat,
        COALESCE(SUM(a.jumlah_unit), 0) AS total_unit_fisik,
        COALESCE(SUM(a.jumlah_unit * a.harga_satuan), 0.00) AS total_nilai_aset
    FROM kategori_alat k
    LEFT JOIN alat_laboratorium a ON k.id_kategori = a.id_kategori
    GROUP BY k.id_kategori, k.kode_kategori, k.nama_kategori, k.lokasi_ruang
    ORDER BY k.id_kategori ASC
";
$rekap_kategori = $pdo->query($sql_rekap)->fetchAll();

// 2. Menangkap Parameter Pencarian & Filter Kategori dari URL ($_GET)
$keyword     = trim($_GET['keyword'] ?? '');
$id_kategori = isset($_GET['id_kategori']) && $_GET['id_kategori'] !== '' ? (int) $_GET['id_kategori'] : 0;

// 3. Menyusun Kueri Daftar Alat Laboratorium dengan INNER JOIN & Kolom Kalkulasi
$sql_alat = "
    SELECT 
        a.id_alat,
        a.kode_inventaris,
        a.nama_alat,
        a.merk_spesifikasi,
        a.tanggal_pengadaan,
        a.jumlah_unit,
        a.harga_satuan,
        (a.jumlah_unit * a.harga_satuan) AS subtotal_aset,
        a.kondisi_baik,
        a.email_pic,
        k.kode_kategori,
        k.nama_kategori,
        k.lokasi_ruang
    FROM alat_laboratorium a
    INNER JOIN kategori_alat k ON a.id_kategori = k.id_kategori
    WHERE 1=1
";

$params = [];

if ($keyword !== '') {
    $sql_alat .= " AND (a.nama_alat ILIKE :kw OR a.kode_inventaris ILIKE :kw OR a.merk_spesifikasi ILIKE :kw)";
    $params[':kw'] = '%' . $keyword . '%';
}

if ($id_kategori > 0) {
    $sql_alat .= " AND a.id_kategori = :id_kat";
    $params[':id_kat'] = $id_kategori;
}

$sql_alat .= " ORDER BY a.id_alat ASC";

$stmt_alat = $pdo->prepare($sql_alat);
$stmt_alat->execute($params);
$daftar_alat = $stmt_alat->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SI Inventaris Laboratorium - Studi Kasus Terpadu</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; color: #1e293b; margin: 0; padding: 24px; }
        .container { max-width: 1220px; margin: 0 auto; background: #ffffff; padding: 24px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        h1 { margin-top: 0; color: #0f172a; font-size: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; }
        h2 { color: #334155; font-size: 18px; margin-top: 24px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13.5px; }
        th, td { border: 1px solid #cbd5e1; padding: 9px 11px; text-align: left; vertical-align: middle; }
        th { background-color: #0f172a; color: #ffffff; font-weight: 600; }
        tr:nth-child(even) { background-color: #f8fafc; }
        tr:hover { background-color: #eff6ff; }
        .filter-bar { display: flex; gap: 12px; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; padding: 14px; border-radius: 6px; margin-bottom: 16px; flex-wrap: wrap; }
        .filter-bar input[type="text"], .filter-bar select { padding: 8px 12px; border: 1px solid #94a3b8; border-radius: 4px; font-size: 14px; }
        .btn { padding: 8px 14px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #2563eb; color: #ffffff; }
        .btn-reset { background-color: #64748b; color: #ffffff; }
        .btn-success { background-color: #16a34a; color: #ffffff; margin-left: auto; }
        .btn-edit { background-color: #0284c7; color: #ffffff; padding: 5px 10px; }
        .btn-delete { background-color: #dc2626; color: #ffffff; padding: 5px 10px; }
        .alert-success { background-color: #dcfce7; border: 1px solid #4ade80; color: #166534; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .alert-error { background-color: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-baik { background-color: #dcfce7; color: #166534; }
        .badge-rusak { background-color: #fee2e2; color: #991b1b; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>

<div class="container">
    <h1>Sistem Informasi Inventaris Alat Laboratorium Kampus</h1>

    <?php if ($pesan_sukses !== ''): ?>
        <div class="alert-success"><strong>Berhasil:</strong> <?= htmlspecialchars($pesan_sukses) ?></div>
    <?php endif; ?>

    <?php if ($pesan_error !== ''): ?>
        <div class="alert-error"><strong>Peringatan Integritas Data:</strong> <?= htmlspecialchars($pesan_error) ?></div>
    <?php endif; ?>

    <!-- TABEL 1: Rekapitulasi Aset per Kategori (LEFT JOIN + GROUP BY) -->
    <h2>1. Rekapitulasi Nilai Aset per Kategori (<code>LEFT JOIN</code>, <code>COUNT</code>, &amp; <code>SUM</code>)</h2>
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Kategori Alat</th>
                <th>Lokasi Ruang</th>
                <th class="text-center">Jumlah Jenis Alat</th>
                <th class="text-center">Total Unit Fisik</th>
                <th class="text-right">Total Nilai Aset (Rp)</th>
                <th class="text-center">Uji Hapus Induk</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $grand_total_unit = 0;
            $grand_total_aset = 0.0;
            foreach ($rekap_kategori as $kat): 
                $grand_total_unit += (int) $kat['total_unit_fisik'];
                $grand_total_aset += (float) $kat['total_nilai_aset'];
            ?>
                <tr>
                    <td><strong><?= htmlspecialchars($kat['kode_kategori']) ?></strong></td>
                    <td><?= htmlspecialchars($kat['nama_kategori']) ?></td>
                    <td><?= htmlspecialchars($kat['lokasi_ruang']) ?></td>
                    <td class="text-center"><?= (int) $kat['jenis_alat'] ?> Jenis</td>
                    <td class="text-center"><strong><?= (int) $kat['total_unit_fisik'] ?></strong> Unit</td>
                    <td class="text-right"><strong>Rp <?= number_format((float) $kat['total_nilai_aset'], 2, ',', '.') ?></strong></td>
                    <td class="text-center">
                        <a href="hapus.php?tipe=kategori&amp;id=<?= (int) $kat['id_kategori'] ?>" 
                           class="btn btn-delete"
                           onclick="return confirm('Uji hapus Kategori <?= htmlspecialchars($kat['nama_kategori']) ?>?');">
                           Hapus Kategori
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr style="background-color: #e2e8f0; font-weight: bold;">
                <td colspan="4" class="text-right">TOTAL KESELURUHAN ASET LABORATORIUM:</td>
                <td class="text-center"><?= $grand_total_unit ?> Unit</td>
                <td class="text-right">Rp <?= number_format($grand_total_aset, 2, ',', '.') ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- TABEL 2: Daftar Inventaris Alat Laboratorium (INNER JOIN + CRUD) -->
    <h2>2. Daftar Inventaris Perangkat Laboratorium (<code>INNER JOIN</code> &amp; Pencarian <code>ILIKE</code>)</h2>

    <form method="GET" action="index.php" class="filter-bar">
        <div>
            <label for="keyword"><strong>Cari Alat / Kode / Spesifikasi:</strong></label>
            <input type="text" id="keyword" name="keyword" style="width: 240px;"
                   value="<?= htmlspecialchars($keyword) ?>" placeholder="Ketik nama alat atau kode...">
        </div>

        <div>
            <label for="id_kategori"><strong>Filter Kategori:</strong></label>
            <select id="id_kategori" name="id_kategori">
                <option value="">-- Semua Kategori --</option>
                <?php foreach ($rekap_kategori as $kat): ?>
                    <option value="<?= (int) $kat['id_kategori'] ?>" 
                        <?= ($id_kategori === (int) $kat['id_kategori']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kat['kode_kategori'] . ' - ' . $kat['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Terapkan Filter</button>
        <a href="index.php" class="btn btn-reset">Reset</a>
        <a href="tambah.php" class="btn btn-success">+ Tambah Alat Baru</a>
    </form>

    <table>
        <thead>
            <tr>
                <th class="text-center">No</th>
                <th>Kode Inventaris</th>
                <th>Nama Alat &amp; Spesifikasi</th>
                <th>Kategori &amp; Lokasi</th>
                <th class="text-center">Tgl Pengadaan</th>
                <th class="text-center">Unit</th>
                <th class="text-right">Harga Satuan</th>
                <th class="text-right">Subtotal Aset</th>
                <th class="text-center">Kondisi</th>
                <th class="text-center" style="width: 125px;">Aksi CRUD</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($daftar_alat) === 0): ?>
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px; color: #64748b;">
                        Data alat laboratorium tidak ditemukan.
                    </td>
                </tr>
            <?php else: ?>
                <?php $no = 1; ?>
                <?php foreach ($daftar_alat as $alat): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($alat['kode_inventaris']) ?></strong><br>
                            <small style="color: #64748b;">
                                PIC: <?= $alat['email_pic'] !== null ? htmlspecialchars($alat['email_pic']) : '<em>Belum diatur</em>' ?>
                            </small>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($alat['nama_alat']) ?></strong><br>
                            <small style="color: #475569;"><?= htmlspecialchars((string) ($alat['merk_spesifikasi'] ?? '-')) ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($alat['nama_kategori']) ?><br>
                            <small style="color: #0369a1;"><strong><?= htmlspecialchars($alat['lokasi_ruang']) ?></strong></small>
                        </td>
                        <td class="text-center"><?= htmlspecialchars($alat['tanggal_pengadaan']) ?></td>
                        <td class="text-center"><strong><?= (int) $alat['jumlah_unit'] ?></strong></td>
                        <td class="text-right">Rp <?= number_format((float) $alat['harga_satuan'], 2, ',', '.') ?></td>
                        <td class="text-right"><strong>Rp <?= number_format((float) $alat['subtotal_aset'], 2, ',', '.') ?></strong></td>
                        <td class="text-center">
                            <?php if ($alat['kondisi_baik']): ?>
                                <span class="badge badge-baik">Layak Pakai</span>
                            <?php else: ?>
                                <span class="badge badge-rusak">Perlu Servis</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="edit.php?id=<?= (int) $alat['id_alat'] ?>" class="btn btn-edit">Edit</a>
                            <a href="hapus.php?tipe=alat&amp;id=<?= (int) $alat['id_alat'] ?>" 
                               class="btn btn-delete"
                               onclick="return confirm('Hapus perangkat <?= htmlspecialchars($alat['nama_alat']) ?> (<?= htmlspecialchars($alat['kode_inventaris']) ?>)?');">
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

### 3. Kode Lengkap File `tambah.php`

Buat file `C:\laragon\www\inventaris_lab\tambah.php`:

```php
<?php
require_once 'koneksi.php';

// Mengambil daftar kategori dari Tabel Induk untuk mengisi Dropdown <select>
$sql_kat = "SELECT id_kategori, kode_kategori, nama_kategori, lokasi_ruang 
            FROM kategori_alat 
            ORDER BY nama_kategori ASC";
$daftar_kategori = $pdo->query($sql_kat)->fetchAll();

$pesan_error = '';
$input = [
    'id_kategori'       => '',
    'kode_inventaris'   => '',
    'nama_alat'         => '',
    'merk_spesifikasi'  => '',
    'tanggal_pengadaan' => date('Y-m-d'),
    'jumlah_unit'       => 1,
    'harga_satuan'      => '0.00',
    'email_pic'         => '',
    'kondisi_baik'      => true
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input['id_kategori']       = isset($_POST['id_kategori']) ? (int) $_POST['id_kategori'] : 0;
    $input['kode_inventaris']   = trim($_POST['kode_inventaris'] ?? '');
    $input['nama_alat']         = trim($_POST['nama_alat'] ?? '');
    $input['merk_spesifikasi']  = trim($_POST['merk_spesifikasi'] ?? '');
    $input['tanggal_pengadaan'] = trim($_POST['tanggal_pengadaan'] ?? '');
    $input['jumlah_unit']       = isset($_POST['jumlah_unit']) ? (int) $_POST['jumlah_unit'] : 0;
    $input['harga_satuan']      = isset($_POST['harga_satuan']) ? (float) $_POST['harga_satuan'] : 0.00;
    $input['email_pic']         = trim($_POST['email_pic'] ?? '');
    $input['kondisi_baik']      = isset($_POST['kondisi_baik']) ? true : false;

    $spek_db  = ($input['merk_spesifikasi'] !== '') ? $input['merk_spesifikasi'] : null;
    $email_db = ($input['email_pic'] !== '') ? $input['email_pic'] : null;

    if ($input['id_kategori'] <= 0 || $input['kode_inventaris'] === '' || $input['nama_alat'] === '' || $input['tanggal_pengadaan'] === '') {
        $pesan_error = 'Kategori, Kode Inventaris, Nama Alat, dan Tanggal Pengadaan wajib diisi!';
    } else {
        try {
            $sql_insert = "
                INSERT INTO alat_laboratorium (
                    id_kategori, kode_inventaris, nama_alat, merk_spesifikasi,
                    tanggal_pengadaan, jumlah_unit, harga_satuan, kondisi_baik, email_pic
                ) VALUES (
                    :id_kategori, :kode_inventaris, :nama_alat, :merk_spesifikasi,
                    :tanggal_pengadaan, :jumlah_unit, :harga_satuan, :kondisi_baik, :email_pic
                )
                RETURNING id_alat, kode_inventaris, nama_alat
            ";

            $stmt = $pdo->prepare($sql_insert);
            $stmt->bindValue(':id_kategori', $input['id_kategori'], PDO::PARAM_INT);
            $stmt->bindValue(':kode_inventaris', $input['kode_inventaris'], PDO::PARAM_STR);
            $stmt->bindValue(':nama_alat', $input['nama_alat'], PDO::PARAM_STR);
            $stmt->bindValue(':merk_spesifikasi', $spek_db, $spek_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':tanggal_pengadaan', $input['tanggal_pengadaan'], PDO::PARAM_STR);
            $stmt->bindValue(':jumlah_unit', $input['jumlah_unit'], PDO::PARAM_INT);
            $stmt->bindValue(':harga_satuan', $input['harga_satuan']);
            $stmt->bindValue(':kondisi_baik', $input['kondisi_baik'], PDO::PARAM_BOOL);
            $stmt->bindValue(':email_pic', $email_db, $email_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

            $stmt->execute();
            $baru = $stmt->fetch();

            $info = "Perangkat {$baru['nama_alat']} ({$baru['kode_inventaris']}) berhasil disimpan dengan ID #{$baru['id_alat']}.";
            header("Location: index.php?pesan=" . urlencode($info));
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $pesan_error = 'Gagal Menyimpan: Kode Inventaris atau Email PIC sudah terdaftar pada perangkat lain (UNIQUE Violation).';
            } elseif ($e->getCode() === '23503') {
                $pesan_error = 'Gagal Menyimpan: Kategori yang dipilih tidak valid di basis data (FOREIGN KEY Violation).';
            } elseif ($e->getCode() === '23514') {
                $pesan_error = 'Gagal Menyimpan: Jumlah Unit atau Harga Satuan tidak boleh bernilai negatif (CHECK Violation).';
            } else {
                $pesan_error = 'Kesalahan basis data: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Alat Laboratorium - SI Inventaris</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; color: #1e293b; margin: 0; padding: 24px; }
        .card { max-width: 680px; margin: 0 auto; background: #ffffff; padding: 24px 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h2 { margin-top: 0; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .form-group { margin-bottom: 14px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; }
        input[type="text"], input[type="email"], input[type="date"], input[type="number"], select, textarea {
            width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; box-sizing: border-box;
        }
        textarea { resize: vertical; min-height: 65px; }
        .row-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .alert-error { background-color: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
        .btn { padding: 10px 18px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #16a34a; color: #ffffff; }
        .btn-secondary { background-color: #64748b; color: #ffffff; margin-left: 8px; }
    </style>
</head>
<body>

<div class="card">
    <h2>Tambah Inventaris Alat Laboratorium</h2>

    <?php if ($pesan_error !== ''): ?>
        <div class="alert-error"><strong>Peringatan:</strong> <?= htmlspecialchars($pesan_error) ?></div>
    <?php endif; ?>

    <form method="POST" action="tambah.php">
        <div class="form-group">
            <label for="id_kategori">Kategori Alat &amp; Lokasi Laboratorium *</label>
            <select id="id_kategori" name="id_kategori" required>
                <option value="">-- Pilih Kategori Alat --</option>
                <?php foreach ($daftar_kategori as $kat): ?>
                    <option value="<?= (int) $kat['id_kategori'] ?>" 
                        <?= ((int) $input['id_kategori'] === (int) $kat['id_kategori']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kat['kode_kategori'] . ' - ' . $kat['nama_kategori'] . ' (' . $kat['lokasi_ruang'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row-2col">
            <div class="form-group">
                <label for="kode_inventaris">Kode Inventaris Unik *</label>
                <input type="text" id="kode_inventaris" name="kode_inventaris" maxlength="25" required 
                       value="<?= htmlspecialchars($input['kode_inventaris']) ?>" placeholder="Contoh: INV-JRG-2026-003">
            </div>

            <div class="form-group">
                <label for="tanggal_pengadaan">Tanggal Pengadaan *</label>
                <input type="date" id="tanggal_pengadaan" name="tanggal_pengadaan" required 
                       value="<?= htmlspecialchars($input['tanggal_pengadaan']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="nama_alat">Nama Perangkat / Alat Laboratorium *</label>
            <input type="text" id="nama_alat" name="nama_alat" maxlength="150" required 
                   value="<?= htmlspecialchars($input['nama_alat']) ?>" placeholder="Masukkan nama perangkat...">
        </div>

        <div class="form-group">
            <label for="merk_spesifikasi">Merk &amp; Spesifikasi Teknis</label>
            <textarea id="merk_spesifikasi" name="merk_spesifikasi" placeholder="Tuliskan detail spesifikasi perangkat..."><?= htmlspecialchars($input['merk_spesifikasi']) ?></textarea>
        </div>

        <div class="row-2col">
            <div class="form-group">
                <label for="jumlah_unit">Jumlah Unit Fisik *</label>
                <input type="number" id="jumlah_unit" name="jumlah_unit" min="0" required 
                       value="<?= (int) $input['jumlah_unit'] ?>">
            </div>

            <div class="form-group">
                <label for="harga_satuan">Harga Perolehan per Unit (Rp) *</label>
                <input type="number" id="harga_satuan" name="harga_satuan" step="0.01" min="0" required 
                       value="<?= htmlspecialchars((string) $input['harga_satuan']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="email_pic">Email Penanggung Jawab / PIC (Opsional &amp; Unik)</label>
            <input type="email" id="email_pic" name="email_pic" maxlength="120" 
                   value="<?= htmlspecialchars($input['email_pic']) ?>" placeholder="teknisi.lab@kampus.ac.id">
        </div>

        <div class="form-group">
            <label style="font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="kondisi_baik" value="1" <?= $input['kondisi_baik'] ? 'checked' : '' ?>>
                <strong>Kondisi Alat Layak Pakai / Berfungsi Normal</strong> (<code>BOOLEAN TRUE</code>)
            </label>
        </div>

        <div style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">Simpan Inventaris</button>
            <a href="index.php" class="btn btn-secondary">Batal &amp; Kembali</a>
        </div>
    </form>
</div>

</body>
</html>
```

### 4. Kode Lengkap File `edit.php`

Buat file `C:\laragon\www\inventaris_lab\edit.php`:

```php
<?php
require_once 'koneksi.php';

$id_alat = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['id_alat']) ? (int) $_POST['id_alat'] : 0);

if ($id_alat <= 0) {
    header("Location: index.php?error=" . urlencode("ID Alat tidak valid!"));
    exit;
}

// Mengambil daftar kategori untuk Dropdown
$sql_kat = "SELECT id_kategori, kode_kategori, nama_kategori, lokasi_ruang 
            FROM kategori_alat 
            ORDER BY nama_kategori ASC";
$daftar_kategori = $pdo->query($sql_kat)->fetchAll();

// Mengambil data lama alat berdasarkan id_alat
$stmt_lama = $pdo->prepare("SELECT * FROM alat_laboratorium WHERE id_alat = :id");
$stmt_lama->execute([':id' => $id_alat]);
$data_alat = $stmt_lama->fetch();

if (!$data_alat) {
    header("Location: index.php?error=" . urlencode("Data perangkat dengan ID #{$id_alat} tidak ditemukan!"));
    exit;
}

$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_alat['id_kategori']       = isset($_POST['id_kategori']) ? (int) $_POST['id_kategori'] : 0;
    $data_alat['kode_inventaris']   = trim($_POST['kode_inventaris'] ?? '');
    $data_alat['nama_alat']         = trim($_POST['nama_alat'] ?? '');
    $data_alat['merk_spesifikasi']  = trim($_POST['merk_spesifikasi'] ?? '');
    $data_alat['tanggal_pengadaan'] = trim($_POST['tanggal_pengadaan'] ?? '');
    $data_alat['jumlah_unit']       = isset($_POST['jumlah_unit']) ? (int) $_POST['jumlah_unit'] : 0;
    $data_alat['harga_satuan']      = isset($_POST['harga_satuan']) ? (float) $_POST['harga_satuan'] : 0.00;
    $data_alat['email_pic']         = trim($_POST['email_pic'] ?? '');
    $data_alat['kondisi_baik']      = isset($_POST['kondisi_baik']) ? true : false;

    $spek_db  = ($data_alat['merk_spesifikasi'] !== '') ? $data_alat['merk_spesifikasi'] : null;
    $email_db = ($data_alat['email_pic'] !== '') ? $data_alat['email_pic'] : null;

    if ($data_alat['id_kategori'] <= 0 || $data_alat['kode_inventaris'] === '' || $data_alat['nama_alat'] === '' || $data_alat['tanggal_pengadaan'] === '') {
        $pesan_error = 'Kategori, Kode Inventaris, Nama Alat, dan Tanggal Pengadaan wajib diisi!';
    } else {
        try {
            $sql_update = "
                UPDATE alat_laboratorium
                SET 
                    id_kategori       = :id_kategori,
                    kode_inventaris   = :kode_inventaris,
                    nama_alat         = :nama_alat,
                    merk_spesifikasi  = :merk_spesifikasi,
                    tanggal_pengadaan = :tanggal_pengadaan,
                    jumlah_unit       = :jumlah_unit,
                    harga_satuan      = :harga_satuan,
                    kondisi_baik      = :kondisi_baik,
                    email_pic         = :email_pic
                WHERE id_alat = :id_alat
                RETURNING id_alat, kode_inventaris, nama_alat
            ";

            $stmt_up = $pdo->prepare($sql_update);
            $stmt_up->bindValue(':id_kategori', $data_alat['id_kategori'], PDO::PARAM_INT);
            $stmt_up->bindValue(':kode_inventaris', $data_alat['kode_inventaris'], PDO::PARAM_STR);
            $stmt_up->bindValue(':nama_alat', $data_alat['nama_alat'], PDO::PARAM_STR);
            $stmt_up->bindValue(':merk_spesifikasi', $spek_db, $spek_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt_up->bindValue(':tanggal_pengadaan', $data_alat['tanggal_pengadaan'], PDO::PARAM_STR);
            $stmt_up->bindValue(':jumlah_unit', $data_alat['jumlah_unit'], PDO::PARAM_INT);
            $stmt_up->bindValue(':harga_satuan', $data_alat['harga_satuan']);
            $stmt_up->bindValue(':kondisi_baik', $data_alat['kondisi_baik'], PDO::PARAM_BOOL);
            $stmt_up->bindValue(':email_pic', $email_db, $email_db === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt_up->bindValue(':id_alat', $id_alat, PDO::PARAM_INT);

            $stmt_up->execute();
            $hasil = $stmt_up->fetch();

            $info = "Perangkat {$hasil['nama_alat']} ({$hasil['kode_inventaris']}) berhasil diperbarui.";
            header("Location: index.php?pesan=" . urlencode($info));
            exit;

        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $pesan_error = 'Gagal Memperbarui: Kode Inventaris atau Email PIC sudah digunakan pada baris lain.';
            } elseif ($e->getCode() === '23514') {
                $pesan_error = 'Gagal Memperbarui: Jumlah Unit atau Harga Satuan melanggar batas CHECK Constraint.';
            } else {
                $pesan_error = 'Kesalahan basis data: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Alat Laboratorium - SI Inventaris</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; color: #1e293b; margin: 0; padding: 24px; }
        .card { max-width: 680px; margin: 0 auto; background: #ffffff; padding: 24px 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        h2 { margin-top: 0; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .form-group { margin-bottom: 14px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; }
        input[type="text"], input[type="email"], input[type="date"], input[type="number"], select, textarea {
            width: 100%; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 14px; box-sizing: border-box;
        }
        textarea { resize: vertical; min-height: 65px; }
        .row-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .alert-error { background-color: #fef2f2; border: 1px solid #f87171; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 16px; font-size: 14px; }
        .btn { padding: 10px 18px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: 600; text-decoration: none; display: inline-block; }
        .btn-primary { background-color: #2563eb; color: #ffffff; }
        .btn-secondary { background-color: #64748b; color: #ffffff; margin-left: 8px; }
    </style>
</head>
<body>

<div class="card">
    <h2>Edit Inventaris Alat (ID #<?= (int) $id_alat ?>)</h2>

    <?php if ($pesan_error !== ''): ?>
        <div class="alert-error"><strong>Peringatan:</strong> <?= htmlspecialchars($pesan_error) ?></div>
    <?php endif; ?>

    <form method="POST" action="edit.php?id=<?= (int) $id_alat ?>">
        <input type="hidden" name="id_alat" value="<?= (int) $id_alat ?>">

        <div class="form-group">
            <label for="id_kategori">Kategori Alat &amp; Lokasi Laboratorium *</label>
            <select id="id_kategori" name="id_kategori" required>
                <?php foreach ($daftar_kategori as $kat): ?>
                    <option value="<?= (int) $kat['id_kategori'] ?>" 
                        <?= ((int) $data_alat['id_kategori'] === (int) $kat['id_kategori']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kat['kode_kategori'] . ' - ' . $kat['nama_kategori'] . ' (' . $kat['lokasi_ruang'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row-2col">
            <div class="form-group">
                <label for="kode_inventaris">Kode Inventaris Unik *</label>
                <input type="text" id="kode_inventaris" name="kode_inventaris" maxlength="25" required 
                       value="<?= htmlspecialchars($data_alat['kode_inventaris']) ?>">
            </div>

            <div class="form-group">
                <label for="tanggal_pengadaan">Tanggal Pengadaan *</label>
                <input type="date" id="tanggal_pengadaan" name="tanggal_pengadaan" required 
                       value="<?= htmlspecialchars($data_alat['tanggal_pengadaan']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="nama_alat">Nama Perangkat / Alat Laboratorium *</label>
            <input type="text" id="nama_alat" name="nama_alat" maxlength="150" required 
                   value="<?= htmlspecialchars($data_alat['nama_alat']) ?>">
        </div>

        <div class="form-group">
            <label for="merk_spesifikasi">Merk &amp; Spesifikasi Teknis</label>
            <textarea id="merk_spesifikasi" name="merk_spesifikasi"><?= htmlspecialchars((string) ($data_alat['merk_spesifikasi'] ?? '')) ?></textarea>
        </div>

        <div class="row-2col">
            <div class="form-group">
                <label for="jumlah_unit">Jumlah Unit Fisik *</label>
                <input type="number" id="jumlah_unit" name="jumlah_unit" min="0" required 
                       value="<?= (int) $data_alat['jumlah_unit'] ?>">
            </div>

            <div class="form-group">
                <label for="harga_satuan">Harga Perolehan per Unit (Rp) *</label>
                <input type="number" id="harga_satuan" name="harga_satuan" step="0.01" min="0" required 
                       value="<?= htmlspecialchars((string) $data_alat['harga_satuan']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="email_pic">Email Penanggung Jawab / PIC</label>
            <input type="email" id="email_pic" name="email_pic" maxlength="120" 
                   value="<?= htmlspecialchars((string) ($data_alat['email_pic'] ?? '')) ?>">
        </div>

        <div class="form-group">
            <label style="font-weight: normal; cursor: pointer;">
                <input type="checkbox" name="kondisi_baik" value="1" <?= $data_alat['kondisi_baik'] ? 'checked' : '' ?>>
                <strong>Kondisi Alat Layak Pakai / Berfungsi Normal</strong>
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

### 5. Kode Lengkap File `hapus.php`

Buat file `C:\laragon\www\inventaris_lab\hapus.php`:

```php
<?php
require_once 'koneksi.php';

$tipe = trim($_GET['tipe'] ?? 'alat');
$id   = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id <= 0) {
    header("Location: index.php?error=" . urlencode("Parameter ID penghapusan tidak valid!"));
    exit;
}

try {
    if ($tipe === 'kategori') {
        // Menghapus Kategori Alat (Tabel Induk - Dilindungi ON DELETE RESTRICT)
        $stmt = $pdo->prepare("
            DELETE FROM kategori_alat 
            WHERE id_kategori = :id 
            RETURNING kode_kategori, nama_kategori
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $terhapus = $stmt->fetch();
        if ($terhapus) {
            $info = "Kategori {$terhapus['nama_kategori']} ({$terhapus['kode_kategori']}) berhasil dihapus.";
            header("Location: index.php?pesan=" . urlencode($info));
        } else {
            header("Location: index.php?error=" . urlencode("Kategori tidak ditemukan."));
        }
        exit;

    } else {
        // Menghapus Alat Laboratorium (Tabel Anak - Riwayat servis otomatis CASCADE)
        $stmt = $pdo->prepare("
            DELETE FROM alat_laboratorium 
            WHERE id_alat = :id 
            RETURNING kode_inventaris, nama_alat
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $terhapus = $stmt->fetch();
        if ($terhapus) {
            $info = "Perangkat {$terhapus['nama_alat']} ({$terhapus['kode_inventaris']}) beserta riwayat perawatannya berhasil dihapus.";
            header("Location: index.php?pesan=" . urlencode($info));
        } else {
            header("Location: index.php?error=" . urlencode("Data perangkat tidak ditemukan."));
        }
        exit;
    }

} catch (PDOException $e) {
    if ($e->getCode() === '23503') {
        $peringatan = "Penghapusan Ditolak oleh PostgreSQL (SQLSTATE 23503 - ON DELETE RESTRICT): Kategori ini tidak dapat dihapus karena masih memiliki perangkat laboratorium aktif di dalamnya!";
        header("Location: index.php?error=" . urlencode($peringatan));
        exit;
    }

    header("Location: index.php?error=" . urlencode("Gagal menghapus data: " . $e->getMessage()));
    exit;
}
?>
```