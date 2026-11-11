<?php
require_once __DIR__.'/../database.php';
require_admin();
if($_SERVER['REQUEST_METHOD']==='POST') verify_csrf();
function admin_open(string $title, string $description=''): void {
    global $page_title,$is_admin_page;$page_title=$title.' · CMS';include __DIR__.'/../views/layout/header.php';
    echo '<div class="container-fluid"><div class="row">';include __DIR__.'/../views/layout/admin_sidebar.php';
    echo '<div class="col-md-10 p-4 admin-content"><div class="d-flex justify-content-between align-items-center"><h2>'.e($title).'</h2><a class="btn btn-sm btn-outline-dark" href="/vonam/index.php" target="_blank">Xem cửa hàng <i aria-hidden="true" class="bi bi-arrow-up-right"></i></a></div><p class="cms-intro">'.e($description).'</p><hr>';show_flash();
}
function admin_close(): void { global $is_admin_page;echo '</div></div></div>';include __DIR__.'/../views/layout/footer.php'; }
function admin_redirect(string $url,string $text): void {flash($text);header('Location: '.$url);exit;}
