<?php
/**
 * SETUP - Chạy file này 1 lần để tạo database và dữ liệu mẫu
 * Mở trình duyệt: http://localhost/vonam/setup.php
 */

$host = getenv('VALI_DB_HOST') ?: 'localhost';
$dbname = getenv('VALI_DB_NAME') ?: 'vali_store';
if (!preg_match('/^[a-zA-Z0-9_]+$/',$dbname)) exit('Tên database không hợp lệ.');
$port=(int)(getenv('VALI_DB_PORT') ?: 3306);
$username = getenv('VALI_DB_USER') ?: 'root';
$password = getenv('VALI_DB_PASSWORD') ?: '';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $username, $password, null, $port);
    $conn->set_charset('utf8mb4');
    $installed = $conn->query("SELECT COUNT(*) n FROM information_schema.tables WHERE table_schema='$dbname' AND table_name='users'")->fetch_assoc()['n'];
    if ($installed && $conn->query("SELECT COUNT(*) n FROM `$dbname`.users WHERE role='admin'")->fetch_assoc()['n']) {
        http_response_code(403);
        exit('Cài đặt đã hoàn tất. Dùng scripts/migrate.php qua CLI để cập nhật, không chạy lại setup.');
    }
} catch (mysqli_sql_exception $ex) {
    http_response_code(503); exit('Không kết nối được MySQL. Vui lòng bật MySQL trong XAMPP.');
}
require_once __DIR__.'/includes/app.php';
if (PHP_SAPI!=='cli' && ($_SERVER['REQUEST_METHOD']??'GET')!=='POST') {
    echo '<!doctype html><html lang="vi"><meta charset="utf-8"><title>Cài đặt ValiStore</title><h1>Cài đặt ValiStore</h1><p>Tạo cơ sở dữ liệu và tài khoản demo trên máy cục bộ.</p><form method="post">'.csrf_field().'<button>Bắt đầu cài đặt</button></form></html>';
    exit;
}
echo "<h1>ValiStore - Cài đặt Database</h1>";

// 2. Chạy file SQL
$sql = str_replace('vali_store', '`'.$dbname.'`', file_get_contents(__DIR__ . '/database.sql'));
$conn->multi_query($sql);

// Đợi tất cả query chạy xong
do {
    if ($result = $conn->store_result()) {
        $result->free();
    }
} while ($conn->more_results() && $conn->next_result());

echo "<p>✅ Tạo database và bảng thành công</p>";
echo "<p>✅ Thêm 8 sản phẩm mẫu thành công</p>";

// 3. Kết nối lại vào database vali_store
$conn->close();
$conn = new mysqli($host, $username, $password, $dbname, $port);
$conn->set_charset("utf8mb4");

// 4. Tạo tài khoản admin (password: admin123)
$admin_user = 'admin';
$admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, fullname, email, role) VALUES (?, ?, 'Quản trị viên', 'admin@valistore.com', 'admin')");
$stmt->bind_param("ss", $admin_user, $admin_pass);
$stmt->execute();

// 5. Tạo tài khoản user mẫu (password: user123)
$user_name = 'user1';
$user_pass = password_hash('user123', PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, fullname, email, phone, address, role) VALUES (?, ?, 'Nguyễn Văn A', 'user1@gmail.com', '0901234567', '123 Đường ABC, TP. Cần Thơ', 'user')");
$stmt->bind_param("ss", $user_name, $user_pass);
$stmt->execute();

echo "<p>✅ Tạo tài khoản admin thành công</p>";

echo "<hr>";
echo "<h2>🎉 Cài đặt hoàn tất!</h2>";
echo "<h3>Tài khoản đăng nhập:</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Vai trò</th><th>Username</th><th>Password</th></tr>";
echo "<tr><td>Admin</td><td><b>admin</b></td><td><b>admin123</b></td></tr>";
echo "<tr><td>User</td><td><b>user1</b></td><td><b>user123</b></td></tr>";
echo "</table>";
echo "<br>";
echo "<p><a href='index.php' style='padding: 10px 20px; background: #2c3e50; color: white; text-decoration: none; border-radius: 5px;'>🏠 Vào trang chủ</a></p>";
echo "<p style='color: orange;'>⚠️ Hãy xóa file setup.php sau khi cài đặt xong!</p>";

$conn->close();
define('VALI_SETUP', true);
require __DIR__ . '/scripts/migrate.php';
?>
