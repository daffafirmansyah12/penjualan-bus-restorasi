<?php


$hostname = "localhost";
$username = "root";
$password = "";
$database = "bus_db";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = mysqli_connect($hostname, $username, $password, $database);
    mysqli_set_charset($koneksi, "utf8mb4");
} catch (mysqli_sql_exception $ex) {
    die("Gagal tersambung ke database: " . $ex->getMessage());
}
