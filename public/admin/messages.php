<?php
require __DIR__ . '/../../app/admin/bootstrap.php';
if($_SERVER['REQUEST_METHOD']==='POST') {
 $id=(int)($_POST['id']??0);$action=$_POST['action']??'';
 if($action==='read')db_run('UPDATE contact_messages SET is_read=1 WHERE id=?',[$id]);
 if($action==='delete')db_run('DELETE FROM contact_messages WHERE id=?',[$id]);
 if($action==='unsubscribe')db_run('DELETE FROM subscribers WHERE id=?',[$id]);
 admin_redirect('messages.php','Đã cập nhật hộp thư.');
}
$page=max(1,(int)($_GET['page']??1));$total=(int)$conn->query('SELECT COUNT(*) total FROM contact_messages')->fetch_assoc()['total'];$pages=max(1,(int)ceil($total/15));$page=min($page,$pages);
$rows=db_run('SELECT * FROM contact_messages ORDER BY created_at DESC,id DESC LIMIT 15 OFFSET ?',[($page-1)*15])->get_result()->fetch_all(MYSQLI_ASSOC);
$subscriber_total=(int)$conn->query('SELECT COUNT(*) total FROM subscribers')->fetch_assoc()['total'];
$subscriber_pages=max(1,(int)ceil($subscriber_total/20));$subscriber_page=max(1,min($subscriber_pages,(int)($_GET['subscriber_page']??1)));
$subscribers=db_run('SELECT * FROM subscribers ORDER BY id DESC LIMIT 20 OFFSET ?',[($subscriber_page-1)*20])->get_result()->fetch_all(MYSQLI_ASSOC);admin_open('Hộp thư & đăng ký nhận tin','Đọc lời nhắn của khách và quản lý danh sách email nhận tin.');
?>
<h3 class="h5 mb-3">Lời nhắn (<?= $total ?>)</h3>
<?php foreach($rows as $r): ?><section class="cms-section"><div class="d-flex justify-content-between flex-wrap gap-2"><h3 class="mb-2"><?= e($r['subject']) ?></h3><span class="status-dot <?= $r['is_read']?'status-off':'' ?>"><?= $r['is_read']?'Đã đọc':'Chưa đọc' ?></span></div><p class="small text-muted"><?= e($r['fullname']) ?> · <?= e($r['email']) ?> · <?= e(date('d/m/Y H:i',strtotime($r['created_at']))) ?></p><p class="prose"><?= e($r['message']) ?></p><div class="cms-actions"><?php if(!$r['is_read']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-dark" name="action" value="read">Đánh dấu đã đọc</button></form><?php endif ?><form method="post" data-confirm="Xóa lời nhắn này?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-outline-danger" name="action" value="delete">Xóa</button></form></div></section><?php endforeach;if(!$rows): ?><p class="text-muted mb-4">Chưa có lời nhắn.</p><?php endif ?>
<?php if($pages>1): ?><nav class="shop-pagination" aria-label="Phân trang hộp thư"><?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?><a href="?page=<?= $i ?>" <?= $page===$i?'aria-current="page"':'' ?>><?= $i ?></a><?php endfor ?></nav><?php endif ?>
<h3 class="h5 mt-4 mb-3">Email đăng ký nhận tin (<?= $subscriber_total ?>)</h3><div class="table-responsive"><table class="table"><thead><tr><th>Email</th><th>Ngày đăng ký</th><th>Thao tác</th></tr></thead><tbody><?php foreach($subscribers as $s): ?><tr><td><?= e($s['email']) ?></td><td><?= e(date('d/m/Y H:i',strtotime($s['created_at']))) ?></td><td><form method="post" data-confirm="Xóa đăng ký nhận tin này?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $s['id'] ?>"><button class="btn btn-sm btn-outline-danger" name="action" value="unsubscribe">Xóa đăng ký</button></form></td></tr><?php endforeach ?></tbody></table></div>
<?php if($subscriber_pages>1): ?><nav class="shop-pagination" aria-label="Phân trang email nhận tin"><?php for($i=max(1,$subscriber_page-2);$i<=min($subscriber_pages,$subscriber_page+2);$i++): ?><a href="?<?= e(http_build_query(['page'=>$page,'subscriber_page'=>$i])) ?>" <?= $i===$subscriber_page?'aria-current="page"':'' ?>><?= $i ?></a><?php endfor ?></nav><?php endif;admin_close() ?>
