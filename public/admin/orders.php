<?php
require_once __DIR__ . '/../../app/database.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Transactional status update; cancellation restores reserved stock once.
if ($_SERVER['REQUEST_METHOD']==='POST') {
 verify_csrf();$status=$_POST['status']??'';$order_id=(int)($_POST['order_id']??0);
 $conn->begin_transaction();
 try {
  $order=db_run('SELECT * FROM orders WHERE id=? FOR UPDATE',[$order_id])->get_result()->fetch_assoc();
  $allowed=['pending'=>['pending','confirmed','cancelled'],'confirmed'=>['confirmed','delivered','cancelled'],'delivered'=>['delivered'],'cancelled'=>['cancelled']];
  if(!$order||!in_array($status,$allowed[$order['status']]??[],true))throw new RuntimeException('Trạng thái không hợp lệ. Đơn đã giao hoặc đã hủy không được chuyển ngược.');
  if($status==='cancelled'&&$order['status']!=='cancelled') {
   $lines=db_run('SELECT product_id,quantity FROM order_details WHERE order_id=? ORDER BY product_id',[$order_id])->get_result()->fetch_all(MYSQLI_ASSOC);
   foreach($lines as $line)db_run('UPDATE products SET stock=stock+? WHERE id=?',[(int)$line['quantity'],(int)$line['product_id']]);
  }
  db_run('UPDATE orders SET status=? WHERE id=?',[$status,$order_id]);$conn->commit();flash('Đã cập nhật trạng thái đơn hàng.');
 }catch(Throwable $ex){$conn->rollback();error_log((string)$ex);flash($ex instanceof RuntimeException && !($ex instanceof mysqli_sql_exception) ? $ex->getMessage() : 'Không thể cập nhật đơn lúc này. Vui lòng thử lại.','danger');}
 header('Location: orders.php');exit;
}

// Lấy danh sách đơn hàng
$filter=trim($_GET['status']??'');
if(!in_array($filter,['pending','confirmed','delivered','cancelled'],true))$filter='';
$search=mb_substr(trim($_GET['search']??''),0,100);
$where='(fullname LIKE ? OR phone LIKE ?)';$params=['%'.$search.'%','%'.$search.'%'];
if($filter!==''){$where.=' AND status=?';$params[]=$filter;}
$total=(int)db_run('SELECT COUNT(*) total FROM orders WHERE '.$where,$params)->get_result()->fetch_assoc()['total'];
$pages=max(1,(int)ceil($total/15));$page=max(1,min($pages,(int)($_GET['page']??1)));
$result=db_run('SELECT * FROM orders WHERE '.$where.' ORDER BY created_at DESC,id DESC LIMIT 15 OFFSET ?',array_merge($params,[($page-1)*15]))->get_result();

include __DIR__ . '/../../app/views/layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../../app/views/layout/admin_sidebar.php'; ?>

        <div class="col-md-10 p-4 admin-content">
            <h2>Quản lý đơn hàng</h2>
<form method="get" class="d-flex flex-wrap gap-2 my-3"><label class="visually-hidden" for="search">Tên khách hoặc điện thoại</label><input id="search" class="form-control" name="search" value="<?= e($search) ?>" placeholder="Tên khách hoặc điện thoại" style="max-width:300px"><label class="visually-hidden" for="filter-status">Lọc trạng thái</label><select id="filter-status" class="form-select" name="status" style="max-width:210px"><option value="">Tất cả trạng thái</option><?php foreach(['pending'=>'Chờ xử lý','confirmed'=>'Đã xác nhận','delivered'=>'Đã giao','cancelled'=>'Đã hủy'] as $v=>$label): ?><option value="<?= $v ?>" <?= $filter===$v?'selected':'' ?>><?= $label ?></option><?php endforeach ?></select><button class="btn btn-dark">Lọc đơn hàng</button><a class="btn btn-outline-dark" href="orders.php">Xóa bộ lọc</a></form><p class="small text-muted"><?= $total ?> đơn hàng · Trang <?= $page ?>/<?= $pages ?></p>
            <hr><?php show_flash(); ?>

            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
            <?php endif; ?>

            <div class="table-responsive" role="region" aria-label="Bảng quản lý" tabindex="0">
                <table class="table table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Khách hàng</th>
                            <th scope="col">SĐT</th>
                            <th scope="col">Địa chỉ</th>
                            <th scope="col">Tổng tiền</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Ngày đặt</th>
                            <th scope="col">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
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
                        <?php while ($order = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['fullname']) ?><div class="small text-muted"><?= e(demo_payment_label($order)) ?></div><div class="small">Ghi chú: <?= e($order['note']??'') ?></div></td>
                                <td><?= htmlspecialchars($order['phone']) ?></td>
                                <td><?= htmlspecialchars(mb_substr($order['address'], 0, 30)) ?>...</td>
                                <td class="fw-bold"><?= number_format($order['total'], 0, ',', '.') ?>₫</td>
                                <td>
                                    <span class="badge bg-<?= $status_class[$order['status']] ?>">
                                        <?= $status_text[$order['status']] ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <form method="POST" class="d-flex gap-1 admin-table-actions"><?= csrf_field() ?>
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <select name="status" class="form-select form-select-sm" style="width: auto;" aria-label="Trạng thái đơn hàng #<?= $order['id'] ?>">
                                            <option value="pending" <?= !in_array('pending',['pending'=>['pending','confirmed','cancelled'],'confirmed'=>['confirmed','delivered','cancelled'],'delivered'=>['delivered'],'cancelled'=>['cancelled']][$order['status']],true)?'disabled':'' ?> <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                                            <option value="confirmed" <?= !in_array('confirmed',['pending'=>['pending','confirmed','cancelled'],'confirmed'=>['confirmed','delivered','cancelled'],'delivered'=>['delivered'],'cancelled'=>['cancelled']][$order['status']],true)?'disabled':'' ?> <?= $order['status'] === 'confirmed' ? 'selected' : '' ?>>Đã xác nhận</option>
                                            <option value="delivered" <?= !in_array('delivered',['pending'=>['pending','confirmed','cancelled'],'confirmed'=>['confirmed','delivered','cancelled'],'delivered'=>['delivered'],'cancelled'=>['cancelled']][$order['status']],true)?'disabled':'' ?> <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Đã giao</option>
                                            <option value="cancelled" <?= !in_array('cancelled',['pending'=>['pending','confirmed','cancelled'],'confirmed'=>['confirmed','delivered','cancelled'],'delivered'=>['delivered'],'cancelled'=>['cancelled']][$order['status']],true)?'disabled':'' ?> <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-dark" aria-label="Lưu trạng thái đơn hàng #<?= $order['id'] ?>">
                                            <i aria-hidden="true" class="bi bi-check"></i>
                                        </button>
                                        <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-dark" aria-label="Xem đơn hàng #<?= $order['id'] ?>">
                                            <i aria-hidden="true" class="bi bi-eye"></i>
                                        </a>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
<?php if($pages>1): ?><nav class="shop-pagination" aria-label="Phân trang đơn hàng"><?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?><a href="?<?= e(http_build_query(['search'=>$search,'status'=>$filter,'page'=>$i])) ?>" <?= $i===$page?'aria-current="page"':'' ?>><?= $i ?></a><?php endfor ?></nav><?php endif ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../app/views/layout/footer.php'; ?>
