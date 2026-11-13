# Modul 4: Manipulasi Data (DML) & Operasi CRUD Dasar SQL

Repositori ini berisi kumpulan kueri SQL Data Manipulation Language (`INSERT INTO`, `SELECT`, `UPDATE`, `DELETE`) dan pengujian integritas referensial untuk **Modul 4 Praktikum Basis Data (TA 2026/2027)** pada basis data **`db_akademik_praktikum`**.

## Persiapan Awal

1. Pastikan servis **PostgreSQL** di Laragon berstatus **Running**.

2. Buka **pgAdmin 4**.

3. Pada panel kiri, klik kanan pada database `db_akademik_praktikum` → pilih **Query Tool**.

## Bagian 4A: Operasi CREATE (`INSERT INTO` & Klausa `RETURNING`)

Jalankan blok kueri berikut secara bertahap pada **Query Tool** database `db_akademik_praktikum`:

### 1. Single-Row Insert pada Tabel Induk `program_studi`

```
-- INSERT Satu Baris (Single-Row Insert) pada Tabel Induk 'program_studi'
-- Dilengkapi klausa RETURNING untuk langsung menangkap id_prodi otomatis
INSERT INTO program_studi (kode_prodi, nama_prodi, jenjang, akreditasi)
VALUES ('IF-S1', 'Informatika', 'S1', 'Unggul')
RETURNING id_prodi, kode_prodi, nama_prodi, jenjang, akreditasi, created_at;

```

### 2. Multi-Row Insert pada Tabel Induk `program_studi`

```
-- INSERT Multi-Baris (Multi-Row Insert) pada Tabel Induk 'program_studi'
INSERT INTO program_studi (kode_prodi, nama_prodi, jenjang, akreditasi)
VALUES 
    ('SI-S1', 'Sistem Informasi', 'S1', 'Baik Sekali'),
    ('TI-D3', 'Teknologi Informasi', 'D3', 'Baik'),
    ('RPL-D4', 'Rekayasa Perangkat Lunak', 'D4', 'Baik Sekali')
RETURNING id_prodi, kode_prodi, nama_prodi, jenjang;

```

### 3. Multi-Row Insert pada Tabel Anak `mahasiswa` dan `kontak_darurat`

```
-- INSERT Multi-Baris pada Tabel Anak 'mahasiswa'
INSERT INTO mahasiswa (
    id_prodi, nim, nama_lengkap, jenis_kelamin, tanggal_lahir, 
    angkatan, ipk, status_aktif, email_kampus, alamat_domisili
)
VALUES
    (1, '240101001', 'Andi Pratama', 'L', '2005-03-14', 2024, 3.85, TRUE, 'andi.pratama@kampus.ac.id', 'Jl. Raya Kertajati No. 12, Majalengka'),
    (1, '240101002', 'Siti Aisyah Putri', 'P', '2005-07-22', 2024, 3.92, TRUE, 'siti.putri@kampus.ac.id', 'Jl. KH. Abdul Halim No. 45, Majalengka'),
    (1, '230101003', 'Budi Santoso', 'L', '2004-11-05', 2023, 3.15, TRUE, NULL, 'Kec. Kadipaten, Majalengka'),
    (2, '240201001', 'Dewi Anggraini Putri', 'P', '2005-01-19', 2024, 3.68, TRUE, 'dewi.putri@kampus.ac.id', 'Kec. Jatiwangi, Majalengka'),
    (2, '220201002', 'Rizky Ramadhan', 'L', '2003-08-30', 2022, 2.75, FALSE, NULL, 'Kec. Cigasong, Majalengka'),
    (3, '240301001', 'Fajar Nugraha Pratama', 'L', '2005-05-10', 2024, 3.55, TRUE, 'fajar.nugraha@kampus.ac.id', 'Kec. Maja, Majalengka')
RETURNING id_mahasiswa, nim, nama_lengkap, id_prodi, ipk, status_aktif;

-- INSERT Data Pelengkap pada Tabel Anak Kedua 'kontak_darurat'
-- Merujuk pada id_mahasiswa = 1 (Andi) dan id_mahasiswa = 6 (Fajar)
INSERT INTO kontak_darurat (id_mahasiswa, nama_kerabat, hubungan, no_telepon_darurat)
VALUES
    (1, 'Hendra Wijaya', 'Ayah Kandung', '081234567890'),
    (1, 'Ratna Sari', 'Ibu Kandung', '081234567891'),
    (6, 'Dedi Nugraha', 'Ayah Kandung', '081398765432')
RETURNING id_kontak, id_mahasiswa, nama_kerabat, hubungan;

```

## Bagian 4B: Operasi READ (`SELECT`, `WHERE`, `ILIKE`, `ORDER BY`, `LIMIT`)

### 1. Proyeksi Kolom Spesifik, Alias Kolom (`"..."`), dan Pengurutan IPK Tertinggi

```
-- Proyeksi Kolom Spesifik, Alias Kolom ("..."), dan Pengurutan IPK Tertinggi
SELECT 
    nim AS "NIM Mahasiswa",
    nama_lengkap AS "Nama Lengkap",
    angkatan AS "Tahun Angkatan",
    ipk AS "Indeks Prestasi"
FROM mahasiswa
ORDER BY ipk DESC, nama_lengkap ASC;

```

### 2. Pencarian Pola `ILIKE`, Pemfilteran Kondisi Majemuk, `IS NULL`, dan `LIMIT`

```
-- Pencarian Pola Case-Insensitive dengan ILIKE (Khas PostgreSQL)
-- Mencari mahasiswa yang namanya mengandung kata 'putri' (huruf kecil semua di kueri)
SELECT id_mahasiswa, nim, nama_lengkap, alamat_domisili
FROM mahasiswa
WHERE nama_lengkap ILIKE '%putri%';

-- Filter Kondisi Kombinasi (WHERE ... AND, BETWEEN)
-- Menampilkan mahasiswa berstatus AKTIF dengan IPK Cumlaude (3.51 s.d. 4.00)
SELECT nim, nama_lengkap, angkatan, ipk, status_aktif
FROM mahasiswa
WHERE status_aktif = TRUE
  AND ipk BETWEEN 3.51 AND 4.00
ORDER BY ipk DESC;

-- Mendeteksi Data yang Belum Lengkap menggunakan IS NULL
-- Mencari mahasiswa yang belum mengisi email_kampus
SELECT id_mahasiswa, nim, nama_lengkap, email_kampus
FROM mahasiswa
WHERE email_kampus IS NULL;

-- Mengambil 3 Mahasiswa Terbaik (Top 3) menggunakan LIMIT
SELECT nim, nama_lengkap, ipk
FROM mahasiswa
WHERE status_aktif = TRUE
ORDER BY ipk DESC
LIMIT 3 OFFSET 0;

```

## Bagian 4C: Operasi UPDATE (`UPDATE ... SET ... WHERE ... RETURNING`)

```
-- Memperbarui IPK dan email_kampus milik Budi Santoso (id_mahasiswa = 3)
UPDATE mahasiswa
SET 
    ipk = 3.45,
    email_kampus = 'budi.santoso@kampus.ac.id',
    alamat_domisili = 'Jl. Ahmad Yani No. 88, Kadipaten, Majalengka'
WHERE id_mahasiswa = 3
RETURNING id_mahasiswa, nim, nama_lengkap, ipk, email_kampus, alamat_domisili;

```

## Bagian 4D: Operasi DELETE & Pengujian Integritas Referensial (`RESTRICT` vs `CASCADE`)

### 1. Skenario Uji 4A — Mencoba Menghapus Program Studi yang Masih Memiliki Mahasiswa (`ON DELETE RESTRICT`)

```
-- [UJI INTEGRITAS 4A] Mencoba menghapus prodi yang masih dirujuk oleh tabel mahasiswa
DELETE FROM program_studi
WHERE id_prodi = 1;

```

### 2. Skenario Uji 4B — Menghapus Mahasiswa yang Memiliki Kontak Darurat (`ON DELETE CASCADE`)

```
-- Menghapus mahasiswa dengan id_mahasiswa = 6
DELETE FROM mahasiswa
WHERE id_mahasiswa = 6
RETURNING id_mahasiswa, nim, nama_lengkap;

-- Periksa isi tabel kontak_darurat setelah id_mahasiswa = 6 dihapus:
SELECT * FROM kontak_darurat;

```

### 3. Skenario Uji 4C — Menghapus Program Studi yang Kosong (Belum Memiliki Mahasiswa)

```
-- Menghapus program_studi id_prodi = 4 yang tidak memiliki data anak
DELETE FROM program_studi
WHERE id_prodi = 4
RETURNING id_prodi, kode_prodi, nama_prodi;

```