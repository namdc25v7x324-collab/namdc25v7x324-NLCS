<?php
require __DIR__ . '/../../app/admin/bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: products.php');exit;}
$id=(int)($_POST['id']??0);
try {db_run('DELETE FROM products WHERE id=?',[$id]);flash('Đã xóa sản phẩm.');}
catch(mysqli_sql_exception $ex){if($ex->getCode()!==1451)throw $ex;flash('Sản phẩm đã có trong đơn hàng nên không thể xóa. Bạn có thể ẩn sản phẩm.','warning');}
header('Location: products.php');exit;
