# Modul 3: Perancangan DDL (Data Definition Language) & Relasi Tabel

Repositori ini berisi kumpulan skrip SQL Data Definition Language (`CREATE`, `ALTER`, `INDEX`) serta perintah inspeksi terminal `psql` untuk **Modul 3 Praktikum Basis Data (TA 2026/2027)** menggunakan studi kasus **Sistem Informasi Akademik Program Studi (`db_akademik_praktikum`)**.

## Standar Penamaan Constraint Eksplisit

Pada modul ini, penamaan constraint dilakukan secara eksplisit menggunakan kata kunci `CONSTRAINT nama_constraint` dengan pola berikut:

* `pk_nama_tabel` untuk **Primary Key**

* `fk_tabel_anak_tabel_induk` untuk **Foreign Key**

* `uq_nama_tabel_kolom` untuk **Unique**

* `chk_nama_tabel_kolom` untuk **Check**

## Langkah 1: Membuat Basis Data Baru (`db_akademik_praktikum`)

Buat database baru melalui menu GUI pgAdmin 4 (**Databases** → **Create** → **Database...**) atau jalankan perintah DDL berikut:

```
CREATE DATABASE db_akademik_praktikum
    WITH 
    OWNER = postgres
    ENCODING = 'UTF8';

```

> **Catatan Penting:** Pastikan Anda membuka **Query Tool** langsung pada database `db_akademik_praktikum` (bukan pada database bawaan `postgres`) sebelum menjalankan skrip pembuatan tabel di bawah ini.

## Langkah 2: Skrip DDL Pembuatan Tabel Berelasi (`CREATE TABLE`)

Jalankan ketiga skrip `CREATE TABLE` berikut secara berurutan (mulai dari Tabel Induk ke Tabel Anak) pada **Query Tool** database `db_akademik_praktikum`:

### 1. Tabel Induk: `program_studi`

```
CREATE TABLE program_studi (
    id_prodi INTEGER GENERATED ALWAYS AS IDENTITY,
    kode_prodi VARCHAR(10) NOT NULL,
    nama_prodi VARCHAR(100) NOT NULL,
    jenjang VARCHAR(5) NOT NULL,
    akreditasi VARCHAR(20) NOT NULL DEFAULT 'Baik',
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Definisi Constraint Eksplisit pada Tabel Induk
    CONSTRAINT pk_program_studi PRIMARY KEY (id_prodi),
    CONSTRAINT uq_program_studi_kode UNIQUE (kode_prodi),
    CONSTRAINT chk_program_studi_jenjang CHECK (jenjang IN ('D3', 'D4', 'S1', 'S2'))
);

```

### 2. Tabel Anak Utama: `mahasiswa`

```
CREATE TABLE mahasiswa (
    id_mahasiswa INTEGER GENERATED ALWAYS AS IDENTITY,
    id_prodi INTEGER NOT NULL,
    nim VARCHAR(15) NOT NULL,
    nama_lengkap VARCHAR(120) NOT NULL,
    jenis_kelamin CHAR(1) NOT NULL,
    tanggal_lahir DATE NOT NULL,
    angkatan SMALLINT NOT NULL,
    ipk NUMERIC(3, 2) NOT NULL DEFAULT 0.00,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Definisi Constraint Primary Key, Unique, dan Check
    CONSTRAINT pk_mahasiswa PRIMARY KEY (id_mahasiswa),
    CONSTRAINT uq_mahasiswa_nim UNIQUE (nim),
    CONSTRAINT chk_mahasiswa_jk CHECK (jenis_kelamin IN ('L', 'P')),
    CONSTRAINT chk_mahasiswa_angkatan CHECK (angkatan >= 2000 AND angkatan <= 2100),
    CONSTRAINT chk_mahasiswa_ipk CHECK (ipk >= 0.00 AND ipk <= 4.00),
    -- Definisi Constraint FOREIGN KEY ke Tabel Induk program_studi
    CONSTRAINT fk_mahasiswa_prodi FOREIGN KEY (id_prodi)
        REFERENCES program_studi (id_prodi)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);

```

### 3. Tabel Anak Pendukung: `kontak_darurat`

```
CREATE TABLE kontak_darurat (
    id_kontak INTEGER GENERATED ALWAYS AS IDENTITY,
    id_mahasiswa INTEGER NOT NULL,
    nama_kerabat VARCHAR(100) NOT NULL,
    hubungan VARCHAR(30) NOT NULL,
    no_telepon_darurat VARCHAR(20) NOT NULL,
    CONSTRAINT pk_kontak_darurat PRIMARY KEY (id_kontak),
    -- Definisi Constraint FOREIGN KEY ke Tabel mahasiswa dengan CASCADE
    CONSTRAINT fk_kontak_mahasiswa FOREIGN KEY (id_mahasiswa)
        REFERENCES mahasiswa (id_mahasiswa)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

```

## Langkah 3: Memodifikasi Struktur Tabel (`ALTER TABLE` & `CREATE INDEX`)

Jalankan skrip berikut pada **Query Tool** yang sama untuk menyesuaikan struktur tabel sesuai perubahan kebutuhan sistem:

### 1. Menambahkan kolom baru `email_kampus` dan `alamat_domisili` ke tabel `mahasiswa`

```
ALTER TABLE mahasiswa
    ADD COLUMN email_kampus VARCHAR(120),
    ADD COLUMN alamat_domisili TEXT;

```

### 2. Menambahkan constraint `UNIQUE` pada kolom `email_kampus`

```
ALTER TABLE mahasiswa
    ADD CONSTRAINT uq_mahasiswa_email UNIQUE (email_kampus);

```

### 3. Mengubah kapasitas panjang karakter kolom `nama_prodi` dari `VARCHAR(100)` menjadi `VARCHAR(150)`

```
ALTER TABLE program_studi
    ALTER COLUMN nama_prodi TYPE VARCHAR(150);

```

### 4. Membuat `INDEX` pencarian cepat pada kolom `nama_lengkap` di tabel `mahasiswa`

```
CREATE INDEX idx_mahasiswa_nama ON mahasiswa (nama_lengkap);

```

## Langkah 4: Inspeksi Struktur Tabel Melalui Terminal `psql` Laragon

Buka **Terminal** dari jendela utama Laragon, lalu jalankan perintah berikut secara berurutan untuk memverifikasi struktur tabel dan relasinya:

### 1. Masuk ke konsol `psql`

```
psql -h localhost -p 5432 -U postgres

```

### 2. Berpindah ke dalam database `db_akademik_praktikum`

```
\c db_akademik_praktikum

```

### 3. Menampilkan daftar tabel di dalam skema `public`

```
\dt

```

### 4. Menampilkan rincian struktur kolom, tipe data, indeks, dan Foreign Key milik tabel `mahasiswa`

```
\d mahasiswa

```
