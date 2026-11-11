<?php
require __DIR__ . '/bootstrap.php';$editing=isset($product_id);$id=$product_id??0;$error='';
$product=['name'=>'','description'=>'','price'=>'','category'=>'','stock'=>0,'image'=>'','is_active'=>1,'is_new'=>1,'is_featured'=>0];
if($editing){$product=db_run('SELECT * FROM products WHERE id=?',[$id])->get_result()->fetch_assoc();if(!$product){header('Location: products.php');exit;}}
$original=$product;
if($_SERVER['REQUEST_METHOD']==='POST'){
 foreach(['name','description','category'] as $f)$product[$f]=trim($_POST[$f]??'');
 $product['price']=filter_var($_POST['price']??'',FILTER_VALIDATE_INT);$product['stock']=filter_var($_POST['stock']??'',FILTER_VALIDATE_INT);
 foreach(['is_active','is_new','is_featured'] as $f)$product[$f]=isset($_POST[$f])?1:0;
 if(!$product['name']||mb_strlen($product['name'])>200||$product['price']===false||$product['price']<1||$product['price']>999999999999||$product['stock']===false||$product['stock']<0||$product['stock']>1000000||mb_strlen($product['category'])>100||mb_strlen($product['description'])>10000)$error='Kiểm tra tên (tối đa 200 ký tự), giá dương, tồn kho hợp lệ và nội dung không quá dài.';
 if(!$error)try{
  $product['image']=uploaded_image('image',$original['image']);
  $params=[$product['name'],$product['description'],$product['price'],$product['image'],$product['category'],$product['stock'],$product['is_active'],$product['is_new'],$product['is_featured']];
  if($editing){
   $expected=filter_var($_POST['original_stock']??'',FILTER_VALIDATE_INT);
   if($expected===false || $expected!==(int)$original['stock']) throw new RuntimeException('Tồn kho vừa thay đổi. Tải lại trang và kiểm tra số lượng trước khi lưu.');
   $conn->begin_transaction();
   try {
    $locked=db_run('SELECT stock FROM products WHERE id=? FOR UPDATE',[$id])->get_result()->fetch_assoc();
    if(!$locked || (int)$locked['stock']!==$expected) throw new RuntimeException('Tồn kho vừa thay đổi. Tải lại trang trước khi lưu.');
    db_run('UPDATE products SET name=?,description=?,price=?,image=?,category=?,stock=?,is_active=?,is_new=?,is_featured=? WHERE id=?',array_merge($params,[$id]));
    $conn->commit();
   }catch(Throwable $ex){$conn->rollback();throw $ex;}
  }else db_run('INSERT INTO products(name,description,price,image,category,stock,is_active,is_new,is_featured) VALUES (?,?,?,?,?,?,?,?,?)',$params);
  admin_redirect('products.php',$editing?'Đã cập nhật sản phẩm.':'Đã thêm sản phẩm.');
 }catch(RuntimeException $ex){
  if($product['image']!==$original['image'] && preg_match('~^assets/uploads/[a-f0-9]+\.(jpg|png|webp)$~',$product['image'])) @unlink(dirname(__DIR__, 2).'/public/'.$product['image']);
  $product['image']=$original['image'];
  $error=$ex instanceof mysqli_sql_exception ? 'Không thể lưu sản phẩm lúc này. Vui lòng thử lại.' : $ex->getMessage();
 }
}
admin_open($editing?'Sửa sản phẩm':'Thêm sản phẩm','Quản lý thông tin, ảnh, tồn kho và vị trí hiển thị trên cửa hàng.');
?>
<?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif ?>
<form method="post" enctype="multipart/form-data" class="cms-section cms-form"><?= csrf_field() ?><?php if($editing): ?><input type="hidden" name="original_stock" value="<?= (int)$original['stock'] ?>"><?php endif ?><div class="row g-3"><div class="col-12"><label for="name" class="form-label">Tên sản phẩm *</label><input id="name" name="name" class="form-control" required maxlength="200" value="<?= e($product['name']) ?>"></div>
<?php foreach(['price'=>'Giá sản phẩm (VNĐ) *','stock'=>'Tồn kho *'] as $f=>$label): ?><div class="col-md-6"><label for="<?= $f ?>" class="form-label"><?= $label ?></label><input type="number" id="<?= $f ?>" name="<?= $f ?>" class="form-control" required min="<?= $f==='price'?1:0 ?>" max="<?= $f==='price'?'999999999999':'1000000' ?>" step="1" value="<?= e($product[$f]) ?>"></div><?php endforeach ?>
<div class="col-12"><label for="category" class="form-label">Danh mục</label><input id="category" name="category" class="form-control" list="categories" maxlength="100" value="<?= e($product['category']) ?>"><datalist id="categories"><?php foreach($conn->query('SELECT DISTINCT category FROM products')->fetch_all(MYSQLI_ASSOC) as $cat): ?><option value="<?= e($cat['category']) ?>"><?php endforeach ?></datalist><p class="form-help">Chọn danh mục có sẵn hoặc nhập mới. Danh mục tự xuất hiện trong bộ lọc.</p></div><div class="col-12"><label for="description" class="form-label">Mô tả</label><textarea id="description" name="description" class="form-control" rows="5" maxlength="10000"><?= e($product['description']) ?></textarea></div><div class="col-12"><label for="image" class="form-label">Ảnh sản phẩm (JPG, PNG, WebP · tối đa 5 MB)</label><input id="image" name="image" type="file" class="form-control" accept="image/jpeg,image/png,image/webp" data-preview="product-preview"><img id="product-preview" class="image-preview" src="<?= e(image_url($product['image'],$product['category'])) ?>" alt="Xem trước ảnh sản phẩm"><p class="form-help">Không chọn ảnh mới sẽ giữ ảnh hiện tại.</p></div><div class="col-12 d-flex flex-wrap gap-4"><?php foreach(['is_active'=>'Hiển thị trên cửa hàng','is_new'=>'Hàng mới đến','is_featured'=>'Sản phẩm nổi bật'] as $f=>$label): ?><label><input type="checkbox" name="<?= $f ?>" <?= $product[$f]?'checked':'' ?>> <?= $label ?></label><?php endforeach ?></div><div class="col-12 mt-4"><button class="btn btn-dark">Lưu sản phẩm</button> <a class="btn btn-outline-dark" href="products.php">Hủy</a></div></div></form>
<?php admin_close() ?>
