<?php
if (PHP_SAPI !== 'cli') { http_response_code(403);exit; }
require __DIR__.'/../app/database.php';
$rows=json_decode(file_get_contents(__DIR__.'/../public/assets/images/product-photo-sources.json'),true,512,JSON_THROW_ON_ERROR);
$conn->begin_transaction();
try {
 foreach($rows as $row) {
  $id=(int)$row['id'];$image=$row['local'];$old='vali'.$id.'.jpg';
  $stmt=db_run('UPDATE products SET image=? WHERE id=? AND image=?',[$image,$id,$old]);
  echo 'Product '.$id.': '.$stmt->affected_rows." updated\n";
 }
 $conn->commit();
} catch(Throwable $ex) {$conn->rollback();throw $ex;}
