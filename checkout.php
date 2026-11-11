<?php
require_once 'config/database.php';start_session();
$success=isset($_GET['success']) && (int)($_GET['order_id']??0)===(int)($_SESSION['last_order_id']??-1);
if(empty($_SESSION['cart'])&&!$success){header('Location: cart.php');exit;}
$result_order=$success?db_run('SELECT * FROM orders WHERE id=?',[(int)$_GET['order_id']])->get_result()->fetch_assoc():null;
if($success&&!$result_order)$success=false;
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$fullname=trim($_POST['fullname']??'');$phone=trim($_POST['phone']??'');$address=trim($_POST['address']??'');$note=trim($_POST['note']??'');
 if(!$fullname||mb_strlen($fullname)>100||!preg_match('/^[+0-9 ()-]{7,20}$/',$phone)||!$address||mb_strlen($address)>1000)$error='Vui lòng nhập họ tên, điện thoại và địa chỉ hợp lệ.';
 elseif(mb_strlen($note)>1000)$error='Ghi chú tối đa 1.000 ký tự.';
 elseif(empty($_SESSION['cart']))$error='Giỏ hàng trống.';
 else {
  $conn->begin_transaction();try{
   $total=0;$lines=[];$cart=$_SESSION['cart'];ksort($cart);
   foreach($cart as $pid=>$item){$pid=(int)$pid;$qty=(int)$item['quantity'];$p=db_run('SELECT * FROM products WHERE id=? FOR UPDATE',[$pid])->get_result()->fetch_assoc();
    if(!$p||!$p['is_active']||$qty<1||$qty>(int)$p['stock'])throw new RuntimeException('Một sản phẩm đã hết hàng, bị ẩn hoặc không đủ số lượng. Vui lòng kiểm tra lại giỏ.');
    if((float)$item['price']!==(float)$p['price'])throw new RuntimeException('Giá sản phẩm vừa thay đổi. Vui lòng quay lại giỏ để xác nhận giá mới.');
    // DECIMAL(12,0) stores whole VND; validate before multiplication or SQL writes.
    $price=(int)$p['price'];
    if($price<1 || $price>999999999999 || $qty>intdiv(999999999999-$total,$price))throw new RuntimeException('Tổng tiền sản phẩm vượt giới hạn 999.999.999.999đ. Vui lòng giảm số lượng hoặc chia đơn.');
    $lines[]=[$pid,$qty,$price,$p['name'],$p['category']??''];$total+=$price*$qty;
   }
   $user_id=isset($_SESSION['user_id'])?(int)$_SESSION['user_id']:null;
   $payment_status=demo_payment_status($note);$status=$payment_status==='failed'?'cancelled':'pending';
   db_run('INSERT INTO orders(user_id,fullname,phone,address,total,status,payment_status,note) VALUES (?,?,?,?,?,?,?,?)',[$user_id,$fullname,$phone,$address,$total,$status,$payment_status,$note]);$order_id=(int)$conn->insert_id;
   foreach($lines as [$pid,$qty,$price,$product_name,$product_category]){db_run('INSERT INTO order_details(order_id,product_id,quantity,price,product_name,product_category) VALUES (?,?,?,?,?,?)',[$order_id,$pid,$qty,$price,$product_name,$product_category]);if($payment_status==='success')db_run('UPDATE products SET stock=stock-? WHERE id=?',[$qty,$pid]);}
   $conn->commit();if($payment_status==='success')$_SESSION['cart']=[];$_SESSION['last_order_id']=$order_id;header('Location: checkout.php?success=1&order_id='.$order_id);exit;
  }catch(Throwable $ex){$conn->rollback();$error=$ex instanceof RuntimeException && !($ex instanceof mysqli_sql_exception) ? 'Đặt hàng thất bại: '.$ex->getMessage() : 'Không thể lưu đơn lúc này. Vui lòng thử lại.';error_log((string)$ex);}
 }
}
$page_title='Đặt hàng · '.setting('site_name');
include 'includes/header.php';
?>

<div class="container mt-4">
    <h2><i aria-hidden="true" class="bi bi-credit-card"></i> Đặt hàng</h2>
    <hr>

    <?php if ($success): ?>
        <?php $payment_failed=($result_order['payment_status']??'')==='failed'; ?>
        <div class="text-center py-5 checkout-success">
            <i aria-hidden="true" class="bi <?= $payment_failed?'bi-x-circle-fill text-danger':'bi-check-circle-fill text-success' ?>" style="font-size: 4rem;"></i>
            <h3 class="mt-3 <?= $payment_failed?'text-danger':'text-success' ?>"><?= $payment_failed?'Đơn hàng thất bại!':'Đặt hàng thành công!' ?></h3>
            <p class="text-muted">Mã đơn hàng: <strong>#<?= (int)$_GET['order_id'] ?></strong></p>
            <p><?= e(demo_payment_label($result_order)) ?></p>
            <p class="small text-muted">Đây là thanh toán giả lập, không phát sinh giao dịch tiền thật.</p>
            <?php if(!empty($result_order['note'])): ?><p><strong>Ghi chú:</strong> <?= e($result_order['note']) ?></p><?php endif ?>
            <?php if($payment_failed): ?><p>Đơn này đã hủy, không trừ tồn kho. Giỏ hàng được giữ để bạn thử lại.</p><a class="btn btn-dark" href="checkout.php">Thử lại</a><?php endif ?>
            <a href="index.php" class="btn btn-dark mt-3">
                <i aria-hidden="true" class="bi bi-house"></i> Tiếp tục mua sắm
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="orders.php" class="btn btn-outline-dark mt-3 ms-2">
                    <i aria-hidden="true" class="bi bi-clock-history"></i> Xem đơn hàng
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php
        // Tính tổng tiền hiển thị
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        ?>
        <div class="row g-4 g-lg-5">
            <!-- Form thông tin -->
            <div class="col-md-7">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST"><?= csrf_field() ?>
                    <h5>Thông tin giao hàng</h5><p class="small text-muted">Demo dùng thanh toán giả lập, không thu tiền thật. Kết quả mặc định là thành công.</p>
                    <div class="mb-3">
                        <label for="fullname" class="form-label">Họ tên *</label>
                        <input type="text" id="fullname" name="fullname" autocomplete="name" class="form-control" required maxlength="100"
                               value="<?= htmlspecialchars($_POST['fullname'] ?? $_SESSION['fullname'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Số điện thoại *</label>
                        <input type="tel" id="phone" name="phone" autocomplete="tel" class="form-control" required maxlength="20"
                               value="<?= htmlspecialchars($_POST['phone'] ?? $_SESSION['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Địa chỉ giao hàng *</label>
                        <textarea id="address" name="address" autocomplete="street-address" class="form-control" rows="3" required maxlength="1000"><?= htmlspecialchars($_POST['address'] ?? $_SESSION['address'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="note" class="form-label">Ghi chú đơn hàng</label>
                        <textarea id="note" name="note" class="form-control" rows="2" maxlength="1000" aria-describedby="note-help"><?= e($_POST['note']??'') ?></textarea>
                        <p id="note-help" class="form-text">Để trống hoặc ghi “thành công”: đơn thành công. Ghi đúng “thất bại” hoặc “that bai”: giả lập đơn thất bại. Ghi chú khác vẫn mặc định thành công.</p>
                    </div>
                    <button type="submit" class="btn btn-cart btn-lg w-100">
                        <i aria-hidden="true" class="bi bi-check-lg"></i> Xác nhận đặt hàng
                    </button>
                </form>
            </div>

            <!-- Tóm tắt đơn hàng -->
            <div class="col-md-5">
                <div class="card checkout-summary">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Đơn hàng của bạn</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                            <div class="summary-item">
                                <span><?= htmlspecialchars($item['name']) ?> x<?= $item['quantity'] ?></span>
                                <span><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>₫</span>
                            </div>
                        <?php endforeach; ?>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5 summary-total">
                            <span>Tiền sản phẩm:</span>
                            <span class="text-danger"><?= number_format($total, 0, ',', '.') ?>₫</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
