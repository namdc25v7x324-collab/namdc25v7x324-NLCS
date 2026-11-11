<?php
require_once __DIR__ . '/../../app/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: orders.php");
    exit;
}

// Lấy chi tiết đơn hàng
$stmt = $conn->prepare("SELECT od.*, od.product_name AS name, od.product_category AS category FROM order_details od WHERE od.order_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$details = $stmt->get_result();

$status_text = [
    'pending' => 'Chờ xử lý',
    'confirmed' => 'Đã xác nhận',
    'delivered' => 'Đã giao',
    'cancelled' => 'Đã hủy'
];
$status_class = [
    'pending' => 'warning',
    'confirmed' => 'info',
    'delivered' => 'success',
    'cancelled' => 'danger'
];

include __DIR__ . '/../../app/views/layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../app/views/layout/admin_sidebar.php'; ?>

        <div class="col-md-10 p-4 admin-content">
            <div class="d-flex justify-content-between align-items-center">
                <h2>Chi tiết đơn hàng #<?= $order['id'] ?></h2>
                <a href="orders.php" class="btn btn-outline-dark"><i aria-hidden="true" class="bi bi-arrow-left"></i> Quay lại</a>
            </div>
            <hr>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-dark text-white">Thông tin khách hàng</div>
                        <div class="card-body">
                            <p><strong>Họ tên:</strong> <?= htmlspecialchars($order['fullname']) ?></p>
                            <p><strong>SĐT:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                            <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['address']) ?></p>
                            <p><strong>Tài khoản:</strong> <?= $order['username'] ? htmlspecialchars($order['username']) : '<em>Khách vãng lai</em>' ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header bg-dark text-white">Thông tin đơn hàng</div>
                        <div class="card-body">
                    <p class="small"><strong>Thanh toán giả lập:</strong> <?= e(demo_payment_label($order)) ?></p>
                    <p class="small"><strong>Ghi chú:</strong> <?= !empty($order['note'])?nl2br(e($order['note'])):'Không có' ?></p>
                            <p><strong>Mã đơn:</strong> #<?= $order['id'] ?></p>
                            <p><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                            <p><strong>Tổng tiền:</strong> <span class="text-danger fw-bold"><?= number_format($order['total'], 0, ',', '.') ?>₫</span></p>
                            <p><strong>Trạng thái:</strong> 
                                <span class="badge bg-<?= $status_class[$order['status']] ?>"><?= $status_text[$order['status']] ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <h4>Sản phẩm trong đơn</h4>
            <div class="table-responsive" role="region" aria-label="Bảng sản phẩm trong đơn hàng" tabindex="0">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">Sản phẩm</th>
                            <th scope="col">Danh mục</th>
                            <th scope="col">Giá</th>
                            <th scope="col">Số lượng</th>
                            <th scope="col">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($d = $details->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['name']) ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($d['category']) ?></span></td>
                                <td><?= number_format($d['price'], 0, ',', '.') ?>₫</td>
                                <td><?= $d['quantity'] ?></td>
                                <td class="fw-bold"><?= number_format($d['price'] * $d['quantity'], 0, ',', '.') ?>₫</td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark">
                            <td colspan="4" class="text-end fw-bold">Tổng cộng:</td>
                            <td class="fw-bold"><?= number_format($order['total'], 0, ',', '.') ?>₫</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout/footer.php'; ?>
