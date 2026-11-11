<?php
require_once __DIR__ . '/../app/database.php';
start_session();
if (($_SERVER['REQUEST_METHOD']??'GET') !== 'POST') {
    $page_title='Đăng xuất · '.setting('site_name');
    include __DIR__ . '/../app/views/layout/header.php';
    echo '<div class="container py-5"><h1>Đăng xuất tài khoản</h1><form method="post">'.csrf_field().'<button class="btn btn-dark">Xác nhận đăng xuất</button> <a class="btn btn-outline-dark" href="index.php">Quay lại</a></form></div>';
    include __DIR__ . '/../app/views/layout/footer.php';
    exit;
}
verify_csrf();
$_SESSION=[];
if (ini_get('session.use_cookies')) {
    $params=session_get_cookie_params();
    setcookie(session_name(),'', ['expires'=>time()-42000,'path'=>$params['path'],'domain'=>$params['domain'],'secure'=>$params['secure'],'httponly'=>$params['httponly'],'samesite'=>$params['samesite']??'Lax']);
}
session_destroy();
header('Location: index.php');exit;
