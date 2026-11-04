<?php
// ==============================================================================
// KONFIGURASI DATABASE SISTEM PAKAR DERMASCAN
// Mendukung Localhost (XAMPP) dan Hosting Online (InfinityFree / cPanel / Cloud)
// ==============================================================================

// 1. PENGATURAN DEFAULT (LOCALHOST XAMPP)
$host = "sql308.infinityfree.com";
$user = "if0_43356709";
$pass = "4rgon123";
$db   = "if0_43356709_db_kulit";

// 2. JIKA DI-DEPLOY DI HOSTING ONLINE (Misal: InfinityFree / cPanel)
// Cukup hilangkan tanda komentar (//) di bawah ini dan masukkan data dari cPanel hosting Anda:
/*
$host = "sqlXXX.infinityfree.com";    // MySQL Hostname dari cPanel
$user = "if0_XXXXXXXX";               // MySQL Username dari cPanel
$pass = "password_akun_anda";         // MySQL Password akun Anda
$db   = "if0_XXXXXXXX_db_kulit";      // MySQL Database Name dari cPanel
*/

// Menghubungkan ke database MySQL
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Periksa koneksi
if (!$koneksi) {
    die("<div style='font-family:sans-serif; padding:20px; background:#fee2e2; color:#991b1b; border-radius:8px; margin:20px;'>
            <h3>⚠️ Gagal Terhubung ke Database MySQL!</h3>
            <p>Pesan Error: " . mysqli_connect_error() . "</p>
            <p>Pastikan MySQL di XAMPP / Hosting sudah aktif dan nama database <b>`$db`</b> sudah dibuat.</p>
         </div>");
}
?>
