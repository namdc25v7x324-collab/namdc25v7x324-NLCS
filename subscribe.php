<?php
require_once 'config/database.php';start_session();
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Location: index.php');exit;}
verify_csrf();$email=trim($_POST['email']??'');
if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>150)flash('Vui lòng nhập email hợp lệ.','danger');
else {db_run('INSERT IGNORE INTO subscribers(email) VALUES (?)',[$email]);flash('Đã đăng ký email nhận tin. Cảm ơn bạn!');}
header('Location: index.php');exit;
