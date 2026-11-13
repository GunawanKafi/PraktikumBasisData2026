Praktikum Basis Data — Tahun Akademik 2026/2027
Implementasi Sistem Basis Data Relasional dengan PostgreSQL & PHP PDO
---
👋 Selamat Datang, Praktikan!
Selamat datang di repositori resmi Praktikum Basis Data Tahun Akademik 2026/2027. Repositori ini disusun sebagai pusat dokumentasi kode program, skrip kueri SQL, serta panduan teknis pendamping modul praktikum selama satu semester ke depan.
Melalui praktikum ini, kita akan mempelajari cara merancang dan mengimplementasikan sistem basis data relasional berstandar industri secara bertahap—mulai dari pengenalan arsitektur PostgreSQL, perancangan skema dan relasi tabel (DDL), manipulasi data (DML), penggabungan tabel (JOIN) dan agregasi, hingga membangun aplikasi web CRUD yang utuh dan aman menggunakan PHP PDO (Prepared Statements).
Silakan manfaatkan repositori ini untuk mempelajari struktur kueri, menguji skrip program di komputer masing-masing, serta menjadi referensi dalam menyelesaikan Tugas Mandiri di setiap pertemuan.
---
👨‍🏫 Profil Instruktur / Asisten Praktikum
Jika Anda mengalami kendala teknis saat melakukan konfigurasi lingkungan kerja maupun saat menuliskan kueri SQL dan kode PHP, jangan ragu untuk bertanya selama sesi laboratorium berlangsung.
Nama Lengkap : Kafi Agung Gunawan
NPM : 2414101044
Semester : 5 (Silakan sesuaikan)
Program Studi : Informatika (Silakan sesuaikan)
Dosen Pengampu : Ii Supiandi, S.T., M.Kom.
---
🌿 Struktur Branch Modul Praktikum
Agar materi dan kode program tetap rapi serta tidak saling menimpa, repositori ini dibagi ke dalam beberapa Branch sesuai urutan modul pertemuan. Branch `main` ini berfungsi sebagai halaman pengantar utama.
Berikut adalah daftar branch modul yang tersedia di repositori ini:
Nama Branch	Modul	Topik Bahasan Utama
`main`	Pengantar	Sambutan praktikan, profil instruktur, dan panduan navigasi repositori.
`modul-1`	Modul 1	Pengenalan DBMS, Arsitektur PostgreSQL, & Setup Lingkungan Kerja Laragon (`cek_env.php` & CLI `psql`).
`modul-2`	Modul 2	Dasar Sintaks SQL PostgreSQL & Dasar Bahasa PHP untuk Basis Data (`simulasi_dasar.php`).
`modul-3`	Modul 3	Perancangan DDL (`CREATE`, `ALTER`, `INDEX`) & Relasi Tabel (`db_akademik_praktikum`).
`modul-4`	Modul 4	Manipulasi Data (DML), Klausa `RETURNING`, Pencarian `ILIKE`, & Operasi CRUD Dasar SQL.
`modul-5`	Modul 5	PostgreSQL Lanjutan (`JOIN`, Agregasi, Transaksi) & Integrasi PHP Dasar — Read Data (`koneksi.php` & `index.php`).
`modul-6`	Modul 6	Integrasi Lanjutan PostgreSQL & Aplikasi CRUD PHP Utuh — Create, Update, Delete (`si_akademik`).
`modul-7`	Modul 7	Mini-Proyek Sistem Informasi Inventaris Laboratorium (`db_inventaris_lab` & `inventaris_lab`).
---
🚀 Cara Mengakses Materi pada Setiap Branch
Anda dapat mengakses kode program pada masing-masing modul melalui dua cara berikut:
Cara 1: Melalui Antarmuka Web GitHub (Tanpa Git CLI)
Klik tombol dropdown `main` (ikon cabang/branch) yang terletak di pojok kiri atas daftar file repositori ini.
Pilih nama branch modul yang ingin Anda pelajari (misalnya `modul-1`, `modul-2`, dst.).
Halaman akan otomatis memuat `README.md` beserta file kode khusus untuk modul tersebut.
Cara 2: Melalui Terminal / Git Bash di Komputer Lokal
Jika Anda ingin mengunduh repositori dan berpindah branch melalui terminal:
```bash
# 1. Clone repositori ini ke komputer Anda
git clone https://github.com/username-anda/nama-repo-praktikum.git

# 2. Masuk ke direktori repositori
cd nama-repo-praktikum

# 3. Berpindah ke branch modul tertentu (contoh: berpindah ke modul-1)
git checkout modul-1
```
---
🛠️ Perangkat Lunak & Lingkungan Kerja
Pastikan komputer atau laptop yang Anda gunakan selama praktikum telah terinstal perangkat berikut:
Laragon (Mengelola Web Server Apache & PHP 8.x)
PostgreSQL (Berjalan pada Port Default `5432`)
Ekstensi PHP: `pdo_pgsql` dan `pgsql` (Wajib diaktifkan melalui menu Laragon)
Aplikasi GUI Basis Data: pgAdmin 4 atau DBeaver Community Edition
Teks Editor: Visual Studio Code (VS Code)
---
📌 Tata Tertib Praktikum
Praktikan wajib berpakaian rapi dan mengenakan jas praktikum selama berada di laboratorium.
Keterlambatan lebih dari 20 menit tanpa alasan yang jelas akan dicatat sebagai tidak hadir (Alfa).
Ketidakhadiran ditoleransi maksimal 3 (tiga) kali pertemuan dengan syarat wajib menyertakan alasan yang jelas serta bukti yang sah (surat keterangan sakit atau izin resmi).
Praktikan wajib mengerjakan dan mengumpulkan Tugas Mandiri pada setiap akhir pertemuan sesuai batas waktu yang ditentukan.
Seluruh hasil penilaian praktikum bersifat final dan tidak ada perbaikan nilai (remedial) dalam bentuk apa pun.
---
Selamat belajar dan selamat mengikuti kegiatan Praktikum Basis Data!
