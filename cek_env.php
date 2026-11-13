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
