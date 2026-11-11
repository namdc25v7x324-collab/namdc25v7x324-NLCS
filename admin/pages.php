<?php
require '_init.php';
$about_fields=[
 'about_eyebrow'=>'Dòng dẫn trên ảnh', 'about_headline'=>'Thông điệp trên ảnh', 'about_intro'=>'Giới thiệu ngắn trên ảnh',
 'about_story_label'=>'Dòng dẫn câu chuyện', 'about_story_title'=>'Tiêu đề câu chuyện',
 'about_values_title'=>'Tiêu đề phần giá trị',
 'about_value_1_title'=>'Giá trị 1 — tiêu đề', 'about_value_1_body'=>'Giá trị 1 — mô tả',
 'about_value_2_title'=>'Giá trị 2 — tiêu đề', 'about_value_2_body'=>'Giá trị 2 — mô tả',
 'about_value_3_title'=>'Giá trị 3 — tiêu đề', 'about_value_3_body'=>'Giá trị 3 — mô tả',
 'about_cta_title'=>'Tiêu đề cuối trang', 'about_cta_body'=>'Mô tả cuối trang',
 'about_cta_button'=>'Nút đến bộ sưu tập', 'about_contact_button'=>'Nút liên hệ'
];
$about_values=settings();
$error='';$id=(int)($_GET['edit']??0);$item=['id'=>0,'slug'=>'','title'=>'','body'=>'','sort_order'=>0,'is_published'=>1];
if($id){$item=db_run('SELECT * FROM content_pages WHERE id=?',[$id])->get_result()->fetch_assoc();if(!$item){http_response_code(404);exit('Không tìm thấy trang.');}}
if($_SERVER['REQUEST_METHOD']==='POST') {
 $pid=(int)($_POST['id']??0);
 if(($_POST['action']??'')==='delete'){db_run('DELETE FROM content_pages WHERE id=?',[$pid]);admin_redirect('pages.php','Đã xóa trang.');}
 $item=['id'=>$pid,'slug'=>trim($_POST['slug']??''),'title'=>trim($_POST['title']??''),'body'=>trim($_POST['body']??''),'sort_order'=>(int)($_POST['sort_order']??0),'is_published'=>isset($_POST['is_published'])?1:0];
 if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$item['slug'])||strlen($item['slug'])>100)$error='Đường dẫn chỉ dùng chữ thường không dấu, số và dấu gạch nối.';
 elseif(!$item['title']||!$item['body']||mb_strlen($item['title'])>200||mb_strlen($item['body'])>50000)$error='Nhập tiêu đề (tối đa 200 ký tự) và nội dung (tối đa 50.000 ký tự).';
 elseif(db_run('SELECT id FROM content_pages WHERE slug=? AND id<>?',[$item['slug'],$pid])->get_result()->num_rows)$error='Đường dẫn này đã được dùng.';
 if($item['slug']==='about') {
  foreach($about_fields as $key=>$label) {
   $about_values[$key]=trim($_POST[$key]??$about_values[$key]);
   if(!$about_values[$key] || mb_strlen($about_values[$key])>3000)$error='Nội dung giới thiệu không được trống và tối đa 3.000 ký tự mỗi mục.';
  }
  if(!$error)try{$about_values['about_image']=uploaded_image('about_image_upload',$about_values['about_image']);}catch(RuntimeException $ex){$error=$ex->getMessage();}
 }
 if(!$error){
  $conn->begin_transaction();
  try{
   $params=[$item['slug'],$item['title'],$item['body'],$item['is_published'],$item['sort_order']];
   if($pid)db_run('UPDATE content_pages SET slug=?,title=?,body=?,is_published=?,sort_order=? WHERE id=?',array_merge($params,[$pid]));
   else db_run('INSERT INTO content_pages(slug,title,body,is_published,sort_order) VALUES (?,?,?,?,?)',$params);
   if($item['slug']==='about')foreach(array_merge(array_keys($about_fields),['about_image']) as $key)db_run('INSERT INTO settings(setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',[$key,$about_values[$key]]);
   $conn->commit();
  }catch(Throwable $ex){$conn->rollback();throw $ex;}
  admin_redirect('pages.php','Đã lưu trang thông tin.');
 }
}
$rows=$conn->query('SELECT * FROM content_pages ORDER BY sort_order,id')->fetch_all(MYSQLI_ASSOC);admin_open('Trang thông tin','Quản lý giới thiệu, chính sách và các trang tùy chỉnh. Trang đã xuất bản tự xuất hiện ở footer.');
?>
<?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif ?>
<div class="table-responsive"><table class="table"><thead><tr><th>Trang</th><th>Đường dẫn</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody><?php foreach($rows as $row): ?><tr><td><?= e($row['title']) ?></td><td><?= e($row['slug']) ?></td><td><?= $row['is_published']?'Đã xuất bản':'Bản nháp' ?></td><td><div class="cms-actions"><a class="btn btn-sm btn-outline-dark" href="?edit=<?= $row['id'] ?>#edit-form">Sửa</a><a class="btn btn-sm btn-outline-dark" href="../page.php?slug=<?= e($row['slug']) ?>" target="_blank">Xem</a><form method="post" data-confirm="Xóa trang này?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $row['id'] ?>"><button class="btn btn-sm btn-outline-danger" name="action" value="delete">Xóa</button></form></div></td></tr><?php endforeach ?></tbody></table></div>
<form method="post" enctype="multipart/form-data" class="cms-section cms-form" id="edit-form"><h3><?= $item['id']?'Sửa trang':'Thêm trang' ?></h3><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><div class="row g-3"><div class="col-md-8"><label for="title" class="form-label">Tiêu đề</label><input id="title" name="title" class="form-control" required maxlength="200" value="<?= e($item['title']) ?>"></div><div class="col-md-4"><label for="slug" class="form-label">Đường dẫn</label><input id="slug" name="slug" class="form-control" required maxlength="100" pattern="[a-z0-9]+(-[a-z0-9]+)*" placeholder="vi-du: shipping" value="<?= e($item['slug']) ?>"></div><div class="col-12"><label for="body" class="form-label">Nội dung</label><textarea id="body" name="body" class="form-control" rows="15" required maxlength="50000"><?= e($item['body']) ?></textarea><p class="form-help">Viết văn bản, xuống dòng để chia đoạn. Nội dung không chạy mã HTML.</p></div><?php if($item['slug']==='about'): ?>
<div class="col-12"><hr><h4>Giao diện trang giới thiệu</h4><p class="form-help">Nội dung phía trên là câu chuyện ở giữa trang. Các mục bên dưới điều khiển ảnh bìa, giá trị và lời mời cuối trang. Tên thương hiệu lấy từ Nội dung & liên hệ.</p></div>
<div class="col-12"><label for="about_image_upload" class="form-label">Ảnh bìa (JPG, PNG, WebP · tối đa 5 MB)</label><input id="about_image_upload" name="about_image_upload" type="file" accept="image/jpeg,image/png,image/webp" class="form-control" data-preview="about-image-preview"><p class="form-help">Nên dùng ảnh ngang, chủ thể ở bên phải và vùng trống bên trái để chữ dễ đọc. Không chọn ảnh mới sẽ giữ ảnh hiện tại.</p><img id="about-image-preview" src="<?= e(image_url($about_values['about_image'])) ?>" alt="Xem trước ảnh bìa" style="max-width:100%;width:420px;height:220px;object-fit:cover"></div>
<?php foreach($about_fields as $key=>$label): ?><div class="col-md-6"><label for="<?= e($key) ?>" class="form-label"><?= e($label) ?></label><textarea id="<?= e($key) ?>" name="<?= e($key) ?>" class="form-control" rows="<?= str_ends_with($key,'body')?3:2 ?>" maxlength="3000" required><?= e($about_values[$key]) ?></textarea></div><?php endforeach ?>
<?php endif ?>
<div class="col-md-4"><label for="sort_order" class="form-label">Thứ tự ở footer</label><input type="number" id="sort_order" name="sort_order" class="form-control" value="<?= (int)$item['sort_order'] ?>"></div><div class="col-md-8 align-self-center"><label><input type="checkbox" name="is_published" <?= $item['is_published']?'checked':'' ?>> Xuất bản trên website</label></div><div class="col-12"><button class="btn btn-dark">Lưu trang</button> <a class="btn btn-outline-dark" href="pages.php#edit-form">Thêm mới</a></div></div></form>
<?php admin_close() ?>
