<?php
$host = getenv('VALI_DB_HOST') ?: 'localhost';
$dbname = getenv('VALI_DB_NAME') ?: 'vali_store';
$username = getenv('VALI_DB_USER') ?: 'root';
$password = getenv('VALI_DB_PASSWORD') ?: '';
$port = (int)(getenv('VALI_DB_PORT') ?: 3306);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $username, $password, $dbname, $port);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(503);
    error_log("ValiStore database unavailable: " . $e->getMessage());
    die("Lỗi kết nối cơ sở dữ liệu. Vui lòng kiểm tra MySQL đã bật chưa.");
}
require_once __DIR__ . '/bootstrap.php';
