<?php
require_once 'config/database.php';
session_start();

// Phải đăng nhập mới xem được
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$count=(int)db_run('SELECT COUNT(*) total FROM orders WHERE user_id=?',[(int)$user_id])->get_result()->fetch_assoc()['total'];
$pages=max(1,(int)ceil($count/10));$page=max(1,min($pages,(int)($_GET['page']??1)));
$orders=db_run('SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC,id DESC LIMIT 10 OFFSET ?',[(int)$user_id,($page-1)*10])->get_result()->fetch_all(MYSQLI_ASSOC);

$detail_map=[];
if($orders){
 $ids=array_map('intval',array_column($orders,'id'));
 $marks=implode(',',array_fill(0,count($ids),'?'));
 $rows=db_run('SELECT od.*, od.product_name FROM order_details od JOIN orders o ON o.id=od.order_id WHERE o.user_id=? AND od.order_id IN ('.$marks.') ORDER BY od.id',array_merge([(int)$user_id],$ids))->get_result()->fetch_all(MYSQLI_ASSOC);
 foreach($rows as $line)$detail_map[(int)$line['order_id']][]=$line;
}

include 'includes/header.php';

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

<div class="container mt-4">
    <h2><i aria-hidden="true" class="bi bi-clock-history"></i> Lịch sử đơn hàng</h2>
    <hr>

    <?php if (!$orders): ?>
        <div class="empty-state">
            <i class="bi bi-receipt" aria-hidden="true"></i>
            <p class="mt-3 text-muted">Bạn chưa có đơn hàng nào.</p>
            <a href="index.php" class="btn btn-dark">Mua sắm ngay</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div class="card order-card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <strong>Đơn hàng #<?= $order['id'] ?></strong>
                        <small class="text-muted ms-2"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></small>
                    </span>
                    <span class="badge bg-<?= $status_class[$order['status']] ?? 'secondary' ?>">
                        <?= $status_text[$order['status']] ?? $order['status'] ?>
                    </span>
                </div>
                <div class="card-body">
                    <p class="small"><strong>Thanh toán giả lập:</strong> <?= e(demo_payment_label($order)) ?></p>
                    <p class="small"><strong>Ghi chú:</strong> <?= !empty($order['note'])?nl2br(e($order['note'])):'Không có' ?></p>
                    <div class="table-responsive" role="region" aria-label="Bảng sản phẩm trong đơn hàng" tabindex="0">
                        <table class="table table-sm mb-2">
                            <thead>
                                <tr>
                                    <th scope="col">Sản phẩm</th>
                                    <th scope="col">Giá</th>
                                    <th scope="col">SL</th>
                                    <th scope="col">Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detail_map[(int)$order['id']]??[] as $d): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d['product_name']) ?></td>
                                        <td><?= number_format($d['price'], 0, ',', '.') ?>₫</td>
                                        <td><?= $d['quantity'] ?></td>
                                        <td><?= number_format($d['price'] * $d['quantity'], 0, ',', '.') ?>₫</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end">
                        <strong>Tổng cộng: <span class="text-danger"><?= number_format($order['total'], 0, ',', '.') ?>₫</span></strong>
                    </div>
                    <small class="text-muted">
                        <i aria-hidden="true" class="bi bi-geo-alt"></i> <?= htmlspecialchars($order['address']) ?> |
                        <i aria-hidden="true" class="bi bi-telephone"></i> <?= htmlspecialchars($order['phone']) ?>
                    </small>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if($pages>1): ?><nav class="shop-pagination" aria-label="Phân trang đơn hàng của tôi"><?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?><a href="?page=<?= $i ?>" <?= $i===$page?'aria-current="page"':'' ?>><?= $i ?></a><?php endfor ?></nav><?php endif ?>
<?php include 'includes/footer.php'; ?>
