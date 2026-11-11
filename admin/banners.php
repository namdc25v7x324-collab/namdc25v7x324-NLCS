<?php
require '_init.php';
$id=max(0,(int)($_GET['edit']??0));$error='';
$item=['id'=>0,'eyebrow'=>'','title'=>'','body'=>'','image'=>'assets/images/hero-travel.webp','button_text'=>'Khám phá ngay','button_url'=>'catalog.php','sort_order'=>0,'is_active'=>1];
if($id) { $item=db_run('SELECT * FROM banners WHERE id=?',[$id])->get_result()->fetch_assoc();if(!$item){http_response_code(404);exit('Không tìm thấy banner.');} }
if($_SERVER['REQUEST_METHOD']==='POST') {
    $pid=(int)($_POST['id']??0);
    if(($_POST['action']??'')==='delete'){db_run('DELETE FROM banners WHERE id=?',[$pid]);admin_redirect('banners.php','Đã xóa banner.');}
    $existing=$pid?db_run('SELECT * FROM banners WHERE id=?',[$pid])->get_result()->fetch_assoc():null;
    if($pid&&!$existing){http_response_code(404);exit('Không tìm thấy banner.');}
    foreach(['eyebrow','title','body','button_text','button_url'] as $f)$item[$f]=trim($_POST[$f]??'');
    $item['id']=$pid;$item['sort_order']=(int)($_POST['sort_order']??0);$item['is_active']=isset($_POST['is_active'])?1:0;
    if(!$item['title']||mb_strlen($item['title'])>255)$error='Tiêu đề bắt buộc, tối đa 255 ký tự.';
    elseif($item['button_url']!==safe_link($item['button_url']))$error='Liên kết phải là trang nội bộ hoặc URL http/https.';
    elseif(mb_strlen($item['eyebrow'])>150||mb_strlen($item['button_text'])>100||mb_strlen($item['body'])>5000||strlen($item['button_url'])>500)$error='Nội dung banner quá dài.';
    if(!$error)try{
        $item['image']=uploaded_image('image',$existing['image']??'assets/images/hero-travel.webp');
        $params=[$item['eyebrow'],$item['title'],$item['body'],$item['image'],$item['button_text'],$item['button_url'],$item['sort_order'],$item['is_active']];
        if($pid)db_run('UPDATE banners SET eyebrow=?,title=?,body=?,image=?,button_text=?,button_url=?,sort_order=?,is_active=? WHERE id=?',array_merge($params,[$pid]));else db_run('INSERT INTO banners(eyebrow,title,body,image,button_text,button_url,sort_order,is_active) VALUES (?,?,?,?,?,?,?,?)',$params);
        admin_redirect('banners.php','Đã lưu banner.');
    }catch(RuntimeException $ex){$error=$ex->getMessage();}
}
$rows=$conn->query('SELECT * FROM banners ORDER BY sort_order,id')->fetch_all(MYSQLI_ASSOC);
admin_open('Banner trang chủ','Thêm banner, tải ảnh, sửa CTA, sắp xếp và bật/tắt từng banner.');
?>
<?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif ?>
<div class="table-responsive"><table class="table"><thead><tr><th>Ảnh</th><th>Tiêu đề</th><th>Thứ tự</th><th>Hiển thị</th><th>Thao tác</th></tr></thead><tbody><?php foreach($rows as $row): ?><tr><td><img src="<?= e(image_url($row['image'])) ?>" alt="" width="110" height="65" style="object-fit:cover"></td><td><?= e($row['title']) ?></td><td><?= (int)$row['sort_order'] ?></td><td><?= $row['is_active']?'Đang bật':'Đang tắt' ?></td><td><div class="cms-actions"><a class="btn btn-sm btn-outline-dark" href="?edit=<?= $row['id'] ?>#edit-form">Sửa</a><form method="post" data-confirm="Xóa banner này?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $row['id'] ?>"><button name="action" value="delete" class="btn btn-sm btn-outline-danger">Xóa</button></form></div></td></tr><?php endforeach ?></tbody></table></div>
<form method="post" enctype="multipart/form-data" class="cms-section cms-form" id="edit-form"><h3><?= $item['id']?'Sửa banner':'Thêm banner mới' ?></h3><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><div class="row g-3">
<?php foreach(['eyebrow'=>'Dòng dẫn','title'=>'Tiêu đề (có thể xuống dòng)','body'=>'Mô tả','button_text'=>'Chữ trên nút','button_url'=>'Liên kết nút'] as $key=>$label): ?><div class="<?= in_array($key,['title','body'])?'col-12':'col-md-6' ?>"><label class="form-label" for="<?= $key ?>"><?= $label ?></label><?php if(in_array($key,['title','body'])): ?><textarea class="form-control" id="<?= $key ?>" name="<?= $key ?>" rows="2" <?= $key==='title'?'required maxlength="255"':'maxlength="5000"' ?>><?= e($item[$key]) ?></textarea><?php else: ?><input class="form-control" id="<?= $key ?>" name="<?= $key ?>" maxlength="<?= $key==='button_text'?100:($key==='eyebrow'?150:500) ?>" value="<?= e($item[$key]) ?>"><?php endif ?></div><?php endforeach ?>
<div class="col-md-8"><label class="form-label" for="image">Ảnh banner (JPG, PNG, WebP · tối đa 5 MB)</label><input class="form-control" type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" data-preview="banner-preview"><p class="form-help">Ảnh ngang; chừa khoảng trống bên trái để đặt chữ.</p><img class="image-preview" id="banner-preview" src="<?= e(image_url($item['image'])) ?>" alt="Xem trước banner"></div><div class="col-md-4"><label for="sort_order" class="form-label">Thứ tự</label><input type="number" class="form-control" id="sort_order" name="sort_order" value="<?= (int)$item['sort_order'] ?>"><label class="mt-3"><input type="checkbox" name="is_active" <?= $item['is_active']?'checked':'' ?>> Hiển thị banner</label></div><div class="col-12"><button class="btn btn-dark">Lưu banner</button> <a href="banners.php#edit-form" class="btn btn-outline-dark">Thêm mới</a></div></div></form>
<?php admin_close() ?>
