# Modul 2: Dasar Sintaks SQL PostgreSQL & Dasar Bahasa PHP untuk Basis Data

Repositori ini berisi kumpulan contoh sintaks SQL PostgreSQL, potongan kode dasar PHP, dan file simulasi praktik untuk **Modul 2 Praktikum Basis Data (TA 2026/2027)**.

## 1. Aturan Leksikal PostgreSQL: Kutip Tunggal (`'...'`) vs Kutip Ganda (`"..."`)

Perbandingan penulisan kueri yang salah dan benar di PostgreSQL terkait penggunaan tanda kutip dan standar *snake_case*:

```
-- [SALAH / ERROR DI POSTGRESQL]
-- Menggunakan kutip ganda untuk nilai string 'Budi Santoso'
SELECT * FROM mahasiswa WHERE nama_lengkap = "Budi Santoso";
-- Pesan Error: ERROR: column "Budi Santoso" does not exist
-- Penjelasan: PostgreSQL mengira "Budi Santoso" adalah nama kolom, bukan nilai teks!

-- [BENAR & SESUAI STANDAR POSTGRESQL]
-- Menggunakan kutip tunggal untuk nilai string, dan opsional kutip ganda untuk nama tabel/kolom
SELECT "nim", "nama_lengkap" FROM "mahasiswa" WHERE "nama_lengkap" = 'Budi Santoso';

-- [BENAR & PALING DIREKOMENDASIKAN]
-- Tanpa kutip ganda pada identifier karena sudah menggunakan huruf kecil snake_case
SELECT nim, nama_lengkap FROM mahasiswa WHERE nama_lengkap = 'Budi Santoso';

```

## 2. Standar Identitas Otomatis & Constraint di PostgreSQL

### A. Standar Identitas Otomatis Modern (`GENERATED ALWAYS AS IDENTITY`)

```
id_produk INTEGER GENERATED ALWAYS AS IDENTITY PRIMARY KEY

```

### B. Contoh Penerapan Sintaks Constraint pada Kolom

```
-- PRIMARY KEY
id_kategori INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY

-- FOREIGN KEY
id_kategori INT NOT NULL REFERENCES kategori(id_kategori) ON DELETE RESTRICT

-- NOT NULL
nama_produk VARCHAR(150) NOT NULL

-- UNIQUE
kode_sku VARCHAR(30) NOT NULL UNIQUE

-- CHECK
harga_satuan NUMERIC(12,2) NOT NULL CHECK (harga_satuan >= 0)

-- DEFAULT
stok INT NOT NULL DEFAULT 0

```

## 3. Konsep Dasar PHP untuk Pemrograman Basis Data

### A. Associative Array: Representasi Baris Tabel Database

Ilustrasi pemetaan baris tabel dari PostgreSQL (`PDO::FETCH_ASSOC`) ke dalam struktur *Associative Array* di PHP:

```
<?php
// 1. Representasi SATU BARIS (Single Row) dari tabel 'mahasiswa' di PostgreSQL:
// Dihasilkan saat kita memanggil $stmt->fetch(PDO::FETCH_ASSOC)
$satu_mahasiswa = [
    "id_mahasiswa" => 1,
    "nim"          => "240101001",
    "nama_lengkap" => "Rina Kartika",
    "ipk"          => 3.85,
    "status_aktif" => true
];

// Cara mengakses nilai kolom: $nama_variabel['nama_kolom_postgres']
echo "NIM: " . $satu_mahasiswa['nim'] . " - Nama: " . $satu_mahasiswa['nama_lengkap'];

// 2. Representasi BANYAK BARIS (Multiple Rows / Result Set) dari PostgreSQL:
// Dihasilkan saat kita memanggil $stmt->fetchAll(PDO::FETCH_ASSOC)
$daftar_mahasiswa = [
    [
        "id_mahasiswa" => 1,
        "nim"          => "240101001",
        "nama_lengkap" => "Rina Kartika",
        "ipk"          => 3.85
    ],
    [
        "id_mahasiswa" => 2,
        "nim"          => "240101002",
        "nama_lengkap" => "Dimas Pratama",
        "ipk"          => 3.60
    ]
];

// Cara menampilkan seluruh baris menggunakan perulangan foreach:
foreach ($daftar_mahasiswa as $baris) {
    echo "<p>" . htmlspecialchars($baris['nim']) . " : " . htmlspecialchars($baris['nama_lengkap']) . "</p>";
}
?>

```

### B. Membaca Input Form Secara Aman (`$_GET` & `$_POST`) di PHP 8.x

```
<?php
// Mengecek apakah form dikirim menggunakan metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mengambil nilai dari <input name="nama_produk"> dan membersihkan spasi di ujung
    $nama_produk  = trim($_POST['nama_produk'] ?? '');
    $harga_satuan = $_POST['harga_satuan'] ?? 0;
}

// Mengambil parameter ID dari URL (misal: detail.php?id=12) dan memastikan bertipe integer
$id_terpilih = isset($_GET['id']) ? (int) $_GET['id'] : 0;
?>

```

## 4. Konsep Dasar Ekstensi PDO (`PDO_PGSQL`)

### A. Struktur DSN (Data Source Name) PostgreSQL

```
$dsn = "pgsql:host=localhost;port=5432;dbname=nama_database_anda";

```

### B. Instansiasi Objek PDO dan Blok `try-catch`

```
<?php
$host = 'localhost';
$port = '5432';
$db   = 'postgres'; // Database bawaan untuk pengujian awal
$user = 'postgres';
$pass = 'postgres';
$dsn  = "pgsql:host=$host;port=$port;dbname=$db";

try {
    // Membuat objek koneksi PDO beserta opsi konfigurasi terbaik
    $pdo = new PDO($dsn, $user, $pass, [
        // 1. Memaksa PDO melempar PDOException yang jelas jika terjadi error SQL
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        // 2. Mengatur agar hasil fetch otomatis berupa Associative Array
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        // 3. Memastikan Prepared Statement diproses asli oleh mesin PostgreSQL
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Koneksi ke PostgreSQL Gagal: " . $e->getMessage());
}
?>

```

### C. Prepared Statements (Pencegahan SQL Injection)

Contoh kode yang rentan terhadap *SQL Injection* (jangan ditiru):

```
// [CONTOH KODE BERBAHAYA / RENTAN SQL INJECTION - JANGAN PERNAH DITIRU!]
$nim = $_POST['nim'];
$pdo->query("SELECT * FROM mahasiswa WHERE nim = '$nim'");

```

Penggunaan *Prepared Statements* dalam dua tahap (`prepare()` dan `execute()`):

```
// Tahap 1: prepare()
$stmt = $pdo->prepare("SELECT nim, nama_lengkap FROM mahasiswa WHERE nim = :nim");

// Tahap 2: execute()
$stmt->execute([':nim' => $nim]);

```

## 5. Simulasi Praktik (`simulasi_dasar.php`)

Buat folder `C:\laragon\www\pertemuan2\` dan simpan kode berikut sebagai `simulasi_dasar.php`. Akses melalui browser di alamat `http://localhost/pertemuan2/simulasi_dasar.php`:

```
<?php

// 1. Konfigurasi Koneksi PDO ke Database Bawaan 'postgres'
$dsn  = "pgsql:host=localhost;port=5432;dbname=postgres";
$user = "postgres";
$pass = "postgres"; // Sesuaikan jika password postgres Anda kosong ("")

$info_server = [];
$pesan_error = "";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Mengambil informasi waktu & tipe data langsung dari mesin PostgreSQL
    $sql = "SELECT 
                current_database() AS nama_db,
                current_setting('server_version') AS versi_pg,
                CURRENT_DATE AS tanggal_hari_ini,
                CURRENT_TIMESTAMP AS waktu_timestamptz";
    $stmt = $pdo->query($sql);
    $info_server = $stmt->fetch(); // Menghasilkan 1 baris Associative Array
} catch (PDOException $e) {
    $pesan_error = $e->getMessage();
}

// 2. Simulasi Data Tabel menggunakan Multidimensional Associative Array
$katalog_buku = [
    [
        "id_buku"      => 1,
        "kode_isbn"    => "978-602-01",
        "judul_buku"   => "Arsitektur Sistem Basis Data PostgreSQL",
        "kategori"     => "Basis Data",
        "harga_satuan" => 125000.00,
        "tersedia"     => true
    ],
    [
        "id_buku"      => 2,
        "kode_isbn"    => "978-602-02",
        "judul_buku"   => "Pemrograman Web Modern dengan PHP 8 & PDO",
        "kategori"     => "Pemrograman Web",
        "harga_satuan" => 110000.00,
        "tersedia"     => true
    ],
    [
        "id_buku"      => 3,
        "kode_isbn"    => "978-602-03",
        "judul_buku"   => "Perancangan Skema Relasional & Normalisasi",
        "kategori"     => "Basis Data",
        "harga_satuan" => 95000.00,
        "tersedia"     => false
    ]
];

// 3. Menangkap Filter Kategori dari URL ($_GET['kategori'])
$filter_kategori = trim($_GET['kategori'] ?? '');

// 4. Menangkap Simulasi Input Form Tambah Data ($_POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $buku_baru = [
        "id_buku"      => count($katalog_buku) + 1,
        "kode_isbn"    => trim($_POST['kode_isbn'] ?? ''),
        "judul_buku"   => trim($_POST['judul_buku'] ?? ''),
        "kategori"     => trim($_POST['kategori'] ?? ''),
        "harga_satuan" => (float) ($_POST['harga_satuan'] ?? 0),
        "tersedia"     => isset($_POST['tersedia']) ? true : false
    ];
    // Menambahkan baris baru ke dalam Associative Array
    $katalog_buku[] = $buku_baru;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Praktikum 2 - Dasar SQL PostgreSQL & PHP</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; line-height: 1.5; color: #222; }
        .box { border: 1px solid #ccc; padding: 14px; margin-bottom: 20px; border-radius: 6px; background: #f9f9f9; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #bbb; padding: 8px 12px; text-align: left; }
        th { background-color: #eaeaea; }
        .badge-ok { color: #0a6b2d; font-weight: bold; }
        .badge-no { color: #b91c1c; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Simulasi Pertemuan 2: Komunikasi PDO PostgreSQL & Struktur Data PHP</h2>

    <!-- Bagian 1: Status Koneksi PDO PostgreSQL -->
    <div class="box">
        <h3>1. Status Koneksi PDO_PGSQL (Associative Array Satu Baris)</h3>
        <?php if ($pesan_error !== ""): ?>
            <p class="badge-no">Koneksi Gagal: <?= htmlspecialchars($pesan_error) ?></p>
        <?php else: ?>
            <ul>
                <li>Database Aktif (<code>$info_server['nama_db']</code>): <strong><?= htmlspecialchars($info_server['nama_db']) ?></strong></li>
                <li>Versi PostgreSQL (<code>$info_server['versi_pg']</code>): <strong><?= htmlspecialchars($info_server['versi_pg']) ?></strong></li>
                <li>Tipe <code>DATE</code> Server: <code><?= htmlspecialchars($info_server['tanggal_hari_ini']) ?></code></li>
                <li>Tipe <code>TIMESTAMPTZ</code> Server: <code><?= htmlspecialchars($info_server['waktu_timestamptz']) ?></code></li>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Bagian 2: Simulasi $_GET dan Menampilkan Array ke Tabel HTML -->
    <div class="box">
        <h3>2. Simulasi Filter <code>$_GET</code> & Iterasi Associative Array</h3>
        <p>
            Filter Cepat via URL (<code>$_GET['kategori']</code>): 
            <a href="simulasi_dasar.php">Semua Kategori</a> | 
            <a href="simulasi_dasar.php?kategori=Basis+Data">Basis Data</a> | 
            <a href="simulasi_dasar.php?kategori=Pemrograman+Web">Pemrograman Web</a>
        </p>
        <table>
            <thead>
                <tr>
                    <th>id_buku (IDENTITY)</th>
                    <th>kode_isbn (VARCHAR)</th>
                    <th>judul_buku (TEXT)</th>
                    <th>kategori (VARCHAR)</th>
                    <th>harga_satuan (NUMERIC)</th>
                    <th>tersedia (BOOLEAN)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($katalog_buku as $baris): ?>
                    <?php
                    // Jika ada filter $_GET['kategori'], lewati baris yang tidak cocok
                    if ($filter_kategori !== '' && $baris['kategori'] !== $filter_kategori) {
                        continue;
                    }
                    ?>
                    <tr>
                        <td><?= (int) $baris['id_buku'] ?></td>
                        <td><?= htmlspecialchars($baris['kode_isbn']) ?></td>
                        <td><?= htmlspecialchars($baris['judul_buku']) ?></td>
                        <td><?= htmlspecialchars($baris['kategori']) ?></td>
                        <td>Rp <?= number_format($baris['harga_satuan'], 2, ',', '.') ?></td>
                        <td>
                            <?php if ($baris['tersedia']): ?>
                                <span class="badge-ok">TRUE (Tersedia)</span>
                            <?php else: ?>
                                <span class="badge-no">FALSE (Habis)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Bagian 3: Simulasi Pengiriman Data via $_POST -->
    <div class="box">
        <h3>3. Simulasi Pengiriman Formulir dengan Metode <code>$_POST</code></h3>
        <form method="POST" action="simulasi_dasar.php">
            <p>
                <label>Kode ISBN (<code>VARCHAR(20)</code>):</label><br>
                <input type="text" name="kode_isbn" required placeholder="Contoh: 978-602-04">
            </p>
            <p>
                <label>Judul Buku (<code>TEXT NOT NULL</code>):</label><br>
                <input type="text" name="judul_buku" required placeholder="Masukkan judul buku" style="width: 320px;">
            </p>
            <p>
                <label>Kategori:</label><br>
                <select name="kategori">
                    <option value="Basis Data">Basis Data</option>
                    <option value="Pemrograman Web">Pemrograman Web</option>
                </select>
            </p>
            <p>
                <label>Harga Satuan (<code>NUMERIC(12,2)</code>):</label><br>
                <input type="number" name="harga_satuan" step="0.01" min="0" required placeholder="100000">
            </p>
            <p>
                <label>
                    <input type="checkbox" name="tersedia" value="1" checked> Status Tersedia (<code>BOOLEAN TRUE</code>)
                </label>
            </p>
            <button type="submit">Uji Kirim Data ($_POST)</button>
        </form>
    </div>
</body>
</html>

```