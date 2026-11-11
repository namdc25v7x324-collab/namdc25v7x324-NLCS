<?php
require_once __DIR__.'/../config/database.php';
start_session();
$cart_count=array_sum(array_column($_SESSION['cart']??[],'quantity'));
$current_page=basename($_SERVER['SCRIPT_NAME']??'index.php');
$is_admin_page=basename(dirname($_SERVER['SCRIPT_NAME']??''))==='admin';
$page_title=$page_title??setting('site_name').' · Hành trang cho mọi chuyến đi';
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($page_title) ?></title><meta name="description" content="<?= e(setting('footer_description')) ?>">
<link rel="stylesheet" href="/vonam/assets/vendor/bootstrap.min.css"><link rel="stylesheet" href="/vonam/assets/vendor/bootstrap-icons/bootstrap-icons.css"><link rel="stylesheet" href="/vonam/assets/css/style.css?v=<?= filemtime(__DIR__.'/../assets/css/style.css') ?>"></head>
<body class="<?= $is_admin_page?'admin-page':'store-page' ?>"><a class="skip-link" href="#main-content">Đến nội dung</a>
<?php if(!$is_admin_page): ?><div class="announcement"><span><?= e(setting('announcement')) ?></span><a href="/vonam/page.php?slug=shipping">Thông tin giao hàng <i aria-hidden="true" class="bi bi-arrow-right"></i></a></div><?php endif ?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" aria-label="Điều hướng chính"><div class="container">
<a class="navbar-brand" href="/vonam/index.php"><i aria-hidden="true" class="bi bi-luggage"></i><?= e(setting('site_name')) ?><span class="brand-dot">.</span></a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Mở menu"><span class="navbar-toggler-icon"></span></button>
<div class="collapse navbar-collapse" id="navbarNav"><ul class="navbar-nav me-auto ms-lg-4">
<?php foreach(['index.php'=>'Trang chủ','catalog.php'=>'Bộ sưu tập','page.php?slug=about'=>'Về chúng tôi','faq.php'=>'FAQ','contact.php'=>'Liên hệ'] as $href=>$label): ?><li class="nav-item"><a class="nav-link <?= ($current_page===explode('?',$href)[0])?'active':'' ?>" href="/vonam/<?= e($href) ?>"><?= e($label) ?></a></li><?php endforeach ?></ul>
<ul class="navbar-nav align-items-lg-center"><li class="nav-item"><a class="nav-link" href="/vonam/catalog.php" aria-label="Tìm sản phẩm"><i aria-hidden="true" class="bi bi-search"></i><span class="d-lg-none ms-2">Tìm kiếm</span></a></li>
<li class="nav-item"><a class="nav-link" href="/vonam/cart.php"><i aria-hidden="true" class="bi bi-bag"></i> Giỏ hàng <span class="badge cart-badge"><?= (int)$cart_count ?></span></a></li>
<?php if(isset($_SESSION['user_id'])): ?><li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"><i aria-hidden="true" class="bi bi-person"></i> <?= e($_SESSION['fullname']) ?></a><ul class="dropdown-menu dropdown-menu-end">
<?php if(($_SESSION['role']??'')==='admin'): ?><li><a class="dropdown-item" href="/vonam/admin/index.php">Quản trị cửa hàng</a></li><?php endif ?>
<li><a class="dropdown-item" href="/vonam/myinfo.php">Thông tin của tôi</a></li><li><a class="dropdown-item" href="/vonam/orders.php">Đơn hàng của tôi</a></li><li><hr class="dropdown-divider"></li><li><a class="dropdown-item" href="/vonam/logout.php">Đăng xuất</a></li></ul></li>
<?php else: ?><li class="nav-item"><a class="nav-link" href="/vonam/login.php"><i aria-hidden="true" class="bi bi-person"></i> Tài khoản</a></li><?php endif ?></ul></div></div></nav>
<main id="main-content" class="site-content" tabindex="-1">
