# Modul 1: Pengenalan DBMS, Arsitektur PostgreSQL, & Setup Lingkungan Kerja Laragon

Repositori ini berisi kumpulan perintah terminal, kode program PHP, dan kueri SQL untuk **Modul 1 Praktikum Basis Data (TA 2026/2027)**.

## Prasyarat Lingkungan Kerja

* **Laragon** (Apache Web Server & PHP)

* **PostgreSQL** (Port Default: `5432`, User: `postgres`)

* **Ekstensi PHP Aktif**: `pdo_pgsql` dan `pgsql`

* **Aplikasi Klien GUI**: pgAdmin 4 / DBeaver Community Edition

## 1. Pengujian Ekstensi PHP PostgreSQL

### A. Verifikasi Melalui Terminal Laragon

Buka Terminal Laragon dan jalankan perintah berikut untuk memastikan ekstensi `pdo_pgsql` dan `pgsql` sudah aktif:

```
php -m | findstr pgsql

```

Output yang diharapkan:

```
pdo_pgsql
pgsql

```

### B. Skrip Verifikasi Lingkungan Web (`cek_env.php`)

Simpan kode berikut di dalam direktori root web Laragon (`C:\laragon\www\cek_env.php`), lalu akses melalui browser pada alamat `http://localhost/cek_env.php`:

```
<?php
// Menampilkan status ketersediaan driver PDO PostgreSQL di Web Server Apache
echo "<h2>Verifikasi Lingkungan PHP & PostgreSQL di Laragon</h2>";
echo "<p>Versi PHP Aktif: <strong>" . phpversion() . "</strong></p>";

if (extension_loaded('pdo_pgsql')) {
    echo "<p style='color: green;'><strong>[BERHASIL]</strong> Ekstensi <code>pdo_pgsql</code> sudah aktif dan siap digunakan!</p>";
} else {
    echo "<p style='color: red;'><strong>[GAGAL]</strong> Ekstensi <code>pdo_pgsql</code> belum aktif. Periksa kembali php.ini dan Reload Apache.</p>";
}

echo "<p>Daftar Driver PDO yang Terdeteksi: <code>" . implode(", ", PDO::getAvailableDrivers()) . "</code></p>";
?>

```

## 2. Verifikasi Server Melalui Terminal `psql`

### A. Masuk ke Konsol `psql`

Buka Terminal Laragon dan jalankan perintah berikut untuk masuk sebagai superuser `postgres`:

```
psql -h localhost -p 5432 -U postgres

```

### B. Mengatur Kata Sandi (Password) Superuser `postgres`

Jalankan kueri SQL berikut di dalam prompt `postgres=#`:

```
ALTER USER postgres WITH PASSWORD 'postgres';

```

### C. Meta-Command & Kueri Dasar di `psql`

| Perintah di `psql` | Fungsi dan Kegunaan | 
| ----- | ----- | 
| `\conninfo` | Menampilkan detail koneksi aktif (nama database, user, host, dan port 5432). | 
| `\l` | Menampilkan daftar seluruh database yang ada di dalam server PostgreSQL. | 
| `\du` | Menampilkan daftar seluruh pengguna (roles/users) beserta hak aksesnya. | 
| `SELECT version();` | Menampilkan versi lengkap mesin PostgreSQL dan arsitektur kompilasinya. | 
| `\q` | Keluar (quit) dari antarmuka `psql` dan kembali ke terminal standar Laragon. | 

## 3. Pengujian Kueri di pgAdmin 4 (Query Tool)

Setelah mendaftarkan server PostgreSQL Laragon di pgAdmin 4, buka **Query Tool** pada database `postgres` dan jalankan kueri berikut:

```
SELECT 
    current_database() AS nama_database,
    current_user AS pengguna_aktif,
    inet_server_port() AS port_server,
    version() AS versi_postgresql;

```

## 4. Kueri Tugas Mandiri (Bagian A - Poin 4)

Jalankan kueri berikut pada **Query Tool** pgAdmin 4 atau **SQL Editor** DBeaver (sesuaikan `'Nama Lengkap Anda'` dan `'NIM Anda'` dengan identitas masing-masing):

```
SELECT 
    'Nama Lengkap Anda' AS nama_praktikan,
    'NIM Anda' AS nim_praktikan,
    current_database() AS db_aktif,
    inet_server_addr() AS ip_server,
    inet_server_port() AS port_server,
    now() AS waktu_eksekusi;

```