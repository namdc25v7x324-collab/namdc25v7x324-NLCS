<?php
if (PHP_SAPI !== 'cli' && !defined('VALI_SETUP')) { http_response_code(403); exit('CLI only.'); }
require_once __DIR__ . '/../config/database.php';
// NULL identifies historical orders without a simulated payment result.
foreach (['payment_status'=>"ENUM('success','failed') NULL DEFAULT NULL", 'note'=>'TEXT NULL'] as $column=>$definition) {
    if (!$conn->query("SHOW COLUMNS FROM orders LIKE '$column'")->num_rows) {
        $conn->query("ALTER TABLE orders ADD $column $definition");
    }
}
echo "Simulated payment migration complete. Historical orders preserved.\n";

// Update only the original demo copy, preserving custom CMS answers.
$old = 'Website tiếp nhận đơn. Cửa hàng sẽ liên hệ xác nhận phương thức thanh toán khi nhận hàng, phí vận chuyển và thời gian giao.';
$new = 'Website dùng thanh toán giả lập, không thu tiền thật. Để trống ghi chú để đơn thành công; ghi đúng “thất bại” hoặc “that bai” để thử đơn thất bại.';
$stmt = $conn->prepare('UPDATE faqs SET answer=? WHERE question=? AND answer=?');
$question = 'Tôi thanh toán bằng cách nào?';
$stmt->bind_param('sss', $new, $question, $old); $stmt->execute();
$old = 'Website hiện hỗ trợ gửi đơn và thanh toán khi nhận hàng theo thỏa thuận.';
$new = 'Website chỉ mô phỏng thanh toán, không thu tiền thật. Kết quả mặc định thành công; ghi đúng “thất bại” hoặc “that bai” trong ghi chú để thử thất bại.';
$stmt = $conn->prepare("UPDATE content_pages SET body=REPLACE(body,?,?) WHERE slug='shipping'");
$stmt->bind_param('ss', $old, $new); $stmt->execute();
