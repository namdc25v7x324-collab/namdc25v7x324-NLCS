<?php
require_once __DIR__ . '/../app/database.php';start_session();$_SESSION['cart']=$_SESSION['cart']??[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$action=$_POST['action']??'';$id=0;$updates=[];
 $reject=static function(): void {http_response_code(400);exit('Dữ liệu số lượng hoặc thao tác giỏ hàng không hợp lệ.');};
 $integer=static function($raw,int $min,int $max) use($reject): int {
  if(!is_string($raw)&&!is_int($raw))$reject();
  $value=filter_var($raw,FILTER_VALIDATE_INT,['options'=>['min_range'=>$min,'max_range'=>$max]]);
  if($value===false)$reject();
  return $value;
 };
 if(isset($_POST['update_cart'])){
  if($action!==''||!is_array($_POST['quantity']??null))$reject();
  // Validate the entire update first so a bad line cannot partially edit the cart.
  foreach($_POST['quantity'] as $pid=>$qty)$updates[$integer($pid,1,2147483647)]=$integer($qty,0,1000000);
 }else{
  if(!in_array($action,['add','remove','clear'],true))$reject();
  if($action!=='clear')$id=$integer($_POST['id']??'',1,2147483647);
  if($action==='add')$quantity=$integer($_POST['quantity']??1,1,1000000);
 }
 if($action==='add'){
  $p=db_run('SELECT * FROM products WHERE id=? AND is_active=1 AND stock>0',[$id])->get_result()->fetch_assoc();
  if($p){$qty=min((int)$p['stock'],(int)($_SESSION['cart'][$id]['quantity']??0)+$quantity);$_SESSION['cart'][$id]=['name'=>$p['name'],'price'=>$p['price'],'image'=>$p['image'],'category'=>$p['category'],'quantity'=>$qty];flash('Đã thêm sản phẩm vào giỏ.');}
  else flash('Sản phẩm hiện không còn bán hoặc đã hết hàng.','warning');
 }
 if($action==='remove')unset($_SESSION['cart'][$id]);if($action==='clear')$_SESSION['cart']=[];
 foreach($updates as $pid=>$qty)if(isset($_SESSION['cart'][$pid])){if($qty===0)unset($_SESSION['cart'][$pid]);else $_SESSION['cart'][$pid]['quantity']=$qty;}
 header('Location: cart.php');exit;
}
$total=0;$changed=false;$available=[];
$ids=array_map('intval',array_keys($_SESSION['cart']));
if($ids){
 $marks=implode(',',array_fill(0,count($ids),'?'));
 $rows=db_run('SELECT * FROM products WHERE is_active=1 AND id IN ('.$marks.')',$ids)->get_result()->fetch_all(MYSQLI_ASSOC);
 foreach($rows as $p)$available[(int)$p['id']]=$p;
}
foreach($_SESSION['cart'] as $id=>$item){
 $p=$available[(int)$id]??null;
 if(!$p||$p['stock']<1){unset($_SESSION['cart'][$id]);$changed=true;continue;}
 $qty=max(1,min((int)$item['quantity'],(int)$p['stock']));
 if($qty!==(int)$item['quantity']||(float)$item['price']!==(float)$p['price'])$changed=true;
 $_SESSION['cart'][$id]=['name'=>$p['name'],'price'=>$p['price'],'image'=>$p['image'],'category'=>$p['category'],'quantity'=>$qty,'stock'=>$p['stock']];$total+=$p['price']*$qty;
}
if($changed)flash('Giỏ hàng đã được cập nhật theo giá và tồn kho hiện tại.','info');
$page_title='Giỏ hàng · '.setting('site_name');include __DIR__ . '/../app/views/layout/header.php';
?>
<section class="page-heading"><div class="container"><p class="eyebrow">VALISTORE / GIỎ HÀNG</p><h1>Hành trang bạn đã chọn.</h1></div></section><div class="container mt-4"><?php show_flash();if(!$_SESSION['cart']): ?><div class="empty-state"><i aria-hidden="true" class="bi bi-bag"></i><h2 class="mt-3">Giỏ hàng đang trống.</h2><p>Khám phá bộ sưu tập và chọn hành trang cho chuyến đi.</p><a href="catalog.php" class="btn btn-dark">Khám phá sản phẩm</a></div><?php else: ?>
<form method="post" id="update-cart"><?= csrf_field() ?><input type="hidden" name="update_cart" value="1"></form>
<div class="table-responsive"><table class="table cart-table"><thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th><th></th></tr></thead><tbody><?php foreach($_SESSION['cart'] as $id=>$item): ?><tr><td><a href="product.php?id=<?= (int)$id ?>" class="d-flex gap-3 align-items-center text-decoration-none"><img src="<?= e(image_url($item['image'],$item['category']??'')) ?>" alt="" width="60" height="65" style="object-fit:contain"><span><?= e($item['name']) ?></span></a></td><td><?= number_format($item['price'],0,',','.') ?>₫</td><td><label class="visually-hidden" for="qty-<?= (int)$id ?>">Số lượng <?= e($item['name']) ?></label><input form="update-cart" id="qty-<?= (int)$id ?>" type="number" class="form-control" name="quantity[<?= (int)$id ?>]" value="<?= (int)$item['quantity'] ?>" min="0" max="<?= (int)$item['stock'] ?>" style="width:90px"></td><td><?= number_format($item['price']*$item['quantity'],0,',','.') ?>₫</td><td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= (int)$id ?>"><button class="btn btn-sm btn-outline-danger" aria-label="Xóa <?= e($item['name']) ?>"><i aria-hidden="true" class="bi bi-x-lg"></i></button></form></td></tr><?php endforeach ?></tbody></table></div>
<div class="d-flex justify-content-between cart-actions"><div class="d-flex flex-wrap gap-2"><button form="update-cart" class="btn btn-outline-dark">Cập nhật giỏ hàng</button><a href="catalog.php" class="btn btn-outline-dark">Tiếp tục mua</a><form method="post" data-confirm="Xóa toàn bộ giỏ hàng?"><?= csrf_field() ?><input type="hidden" name="action" value="clear"><button class="btn btn-outline-danger">Xóa toàn bộ</button></form></div><div class="cart-total text-end"><h3 class="h5">Tổng tiền sản phẩm: <?= number_format($total,0,',','.') ?>₫</h3><p class="small text-muted">Phí giao hàng được xác nhận sau khi tiếp nhận đơn.</p><a href="checkout.php" class="btn btn-dark">Tiến hành đặt hàng <i aria-hidden="true" class="bi bi-arrow-right ms-2"></i></a></div></div><?php endif ?></div>
<?php include __DIR__ . '/../app/views/layout/footer.php' ?>
