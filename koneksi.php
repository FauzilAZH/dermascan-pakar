<?php
// Koneksi ke Database MySQL XAMPP
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_sistem_pakar_kulit";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi Database Gagal: " . mysqli_connect_error());
}
?>

