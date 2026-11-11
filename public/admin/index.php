<?php
require_once __DIR__ . '/../../app/database.php';
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Thống kê
$total_products = $conn->query("SELECT COUNT(*) as cnt FROM products")->fetch_assoc()['cnt'];
$total_orders = $conn->query("SELECT COUNT(*) as cnt FROM orders")->fetch_assoc()['cnt'];
$total_users = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'user'")->fetch_assoc()['cnt'];
$total_revenue = $conn->query("SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE status = 'delivered'")->fetch_assoc()['total'];

include __DIR__ . '/../../app/views/layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../../app/views/layout/admin_sidebar.php'; ?>

        <!-- Main content -->
        <div class="col-md-10 p-4 admin-content">
            <h2>Tổng quan cửa hàng</h2>
            <hr>

            <div class="row">
                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <i aria-hidden="true" class="bi bi-box-seam" style="font-size: 2rem;"></i>
                            <h3><?= $total_products ?></h3>
                            <p class="mb-0">Sản phẩm</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <i aria-hidden="true" class="bi bi-receipt" style="font-size: 2rem;"></i>
                            <h3><?= $total_orders ?></h3>
                            <p class="mb-0">Đơn hàng</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <i aria-hidden="true" class="bi bi-people" style="font-size: 2rem;"></i>
                            <h3><?= $total_users ?></h3>
                            <p class="mb-0">Khách hàng</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3 mb-3">
                    <div class="card stat-card">
                        <div class="card-body text-center">
                            <i aria-hidden="true" class="bi bi-cash-stack" style="font-size: 2rem;"></i>
                            <h3><?= number_format($total_revenue, 0, ',', '.') ?>₫</h3>
                            <p class="mb-0">Giá trị đơn đã giao</p>
                        </div>
                    </div>
                </div>
            </div>

            <section class="cms-section mt-4"><h3>Nội dung cửa hàng</h3><p class="cms-intro">Cập nhật nội dung ngay tại đây, rồi tải lại trang cửa hàng để xem thay đổi.</p><div class="d-flex flex-wrap gap-2"><a class="btn btn-outline-dark" href="banners.php">Banner</a><a class="btn btn-outline-dark" href="settings.php">Trang chủ & liên hệ</a><a class="btn btn-outline-dark" href="pages.php">Chính sách</a><a class="btn btn-outline-dark" href="faqs.php">FAQ</a><a class="btn btn-outline-dark" href="messages.php">Hộp thư</a></div></section><!-- Đơn hàng gần đây -->
            <h4 class="mt-4">Đơn hàng gần đây</h4>
            <div class="table-responsive" role="region" aria-label="Bảng quản lý" tabindex="0">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Khách hàng</th>
                            <th scope="col">Tổng tiền</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Ngày đặt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
                        while ($order = $recent->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['fullname']) ?></td>
                                <td><?= number_format($order['total'], 0, ',', '.') ?>₫</td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'delivered' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    $status_text = [
                                        'pending' => 'Chờ xử lý',
                                        'confirmed' => 'Đã xác nhận',
                                        'delivered' => 'Đã giao',
                                        'cancelled' => 'Đã hủy'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $status_class[$order['status']] ?>">
                                        <?= $status_text[$order['status']] ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout/footer.php'; ?>
