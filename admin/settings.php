<?php
require '_init.php';
$groups=[
 'Thông tin cửa hàng'=>['site_name'=>'Tên cửa hàng','tagline'=>'Thông điệp thương hiệu','announcement'=>'Thông báo trên đầu trang','footer_description'=>'Giới thiệu ở footer'],
 'Liên hệ'=>['phone'=>'Số điện thoại','email'=>'Email liên hệ','address'=>'Địa chỉ','hours'=>'Giờ làm việc'],
 'Trang thông tin & dịch vụ'=>['collections_title'=>'Tiêu đề danh mục trang chủ','service_stock'=>'Thông điệp tồn kho','service_shipping'=>'Thông điệp giao hàng','service_support'=>'Thông điệp hỗ trợ','faq_title'=>'Tiêu đề trang FAQ','faq_intro'=>'Giới thiệu FAQ','contact_title'=>'Tiêu đề trang liên hệ','contact_intro'=>'Giới thiệu trang liên hệ'],
 'Bộ sưu tập'=>['new_title'=>'Tiêu đề hàng mới','new_subtitle'=>'Mô tả hàng mới','featured_title'=>'Tiêu đề sản phẩm nổi bật','catalog_intro'=>'Giới thiệu danh sách sản phẩm','per_page'=>'Số sản phẩm mỗi trang (2–48)'],
 'Lời mời mua hàng (CTA)'=>['cta_eyebrow'=>'Dòng dẫn','cta_title'=>'Tiêu đề CTA','cta_body'=>'Mô tả CTA','cta_button'=>'Chữ trên nút','cta_url'=>'Liên kết nút'],
 'Nhận tin'=>['newsletter_title'=>'Tiêu đề đăng ký nhận tin','newsletter_body'=>'Mô tả nhận tin']
];
$error='';$values=settings();
if($_SERVER['REQUEST_METHOD']==='POST') {
    foreach($groups as $fields) foreach($fields as $key=>$label) $values[$key]=trim($_POST[$key]??'');
    if($values['site_name']==='') $error='Tên cửa hàng không được trống.';
    elseif(!filter_var($values['email'],FILTER_VALIDATE_EMAIL))$error='Email liên hệ không hợp lệ.';
    elseif(!ctype_digit($values['per_page'])||(int)$values['per_page']<2||(int)$values['per_page']>48)$error='Số sản phẩm mỗi trang phải từ 2 đến 48.';
    elseif($values['cta_url']!==safe_link($values['cta_url']))$error='Liên kết CTA phải là trang nội bộ hoặc URL http/https hợp lệ.';
    foreach($values as $v) if(mb_strlen($v)>3000) $error='Mỗi nội dung tối đa 3.000 ký tự.';
    if(!$error) {
        $conn->begin_transaction();try{foreach($groups as $fields)foreach($fields as $key=>$label)db_run('INSERT INTO settings(setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)',[$key,$values[$key]]);$conn->commit();}catch(Throwable $ex){$conn->rollback();throw $ex;}
        admin_redirect('settings.php','Đã lưu nội dung. Thay đổi đã hiển thị trên cửa hàng.');
    }
}
admin_open('Nội dung & thông tin liên hệ','Chỉnh sửa chữ trên trang chủ, CTA, footer và thông tin cửa hàng.');
?>
<?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif ?>
<form method="post" class="cms-form"><?= csrf_field() ?>
<?php foreach($groups as $title=>$fields): ?><section class="cms-section"><h3><?= e($title) ?></h3><div class="row g-3"><?php foreach($fields as $key=>$label): ?><div class="<?= in_array($key,['footer_description','cta_title','cta_body','catalog_intro','newsletter_body'])?'col-12':'col-md-6' ?>"><label for="<?= $key ?>" class="form-label"><?= e($label) ?></label><?php if(in_array($key,['footer_description','cta_title','cta_body','catalog_intro','newsletter_body'])): ?><textarea id="<?= $key ?>" name="<?= $key ?>" class="form-control" rows="3" maxlength="3000"><?= e(str_replace('\\n',"\n",$values[$key])) ?></textarea><?php else: ?><input id="<?= $key ?>" name="<?= $key ?>" type="<?= $key==='email'?'email':($key==='per_page'?'number':'text') ?>" class="form-control" value="<?= e($values[$key]) ?>" <?= $key==='per_page'?'min="2" max="48"':'maxlength="500"' ?>><?php endif ?></div><?php endforeach ?></div></section><?php endforeach ?><button class="btn btn-dark">Lưu thay đổi</button></form>
<?php admin_close() ?>
