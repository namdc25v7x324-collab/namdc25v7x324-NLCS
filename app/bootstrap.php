<?php
// Only the cart update form accepts a flat quantity map; GET stays scalar.
$cart_update_request = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'cart.php'
    && isset($_POST['update_cart']) && !isset($_POST['action']);
foreach (['GET'=>$_GET, 'POST'=>$_POST] as $method=>$source) {
    foreach ($source as $key => $value) {
        if (is_array($value) && !($method === 'POST' && $cart_update_request && $key === 'quantity' && count(array_filter($value, 'is_array')) === 0)) {
            http_response_code(400);
            exit('Dữ liệu biểu mẫu không hợp lệ.');
        }
    }
}
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly'=>true, 'samesite'=>'Lax', 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'path'=>'/']);
}

function e($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }
function valid_new_password(string $password): bool {
    // Bcrypt on older PHP can truncate at NUL; reject it before hashing.
    return strlen($password)>=8 && strlen($password)<=72 && !str_contains($password,"\0");
}
function setting_defaults(): array {
    return about_defaults() + ['site_name'=>'ValiStore','announcement'=>'Hành trang sẵn sàng. Chuyến đi đang chờ bạn.','tagline'=>'Đi cùng bạn, trên mọi hành trình.','phone'=>'0123 456 789','email'=>'hello@valistore.example','address'=>'Khu II, Đường 3/2, Ninh Kiều, Cần Thơ','hours'=>'Thứ 2 – Chủ nhật · 08:00 – 20:00','footer_description'=>'Vali, balo và túi du lịch. Chọn hành trang phù hợp cho chuyến đi tiếp theo.','new_title'=>'Hành trang mới đến','new_subtitle'=>'Những lựa chọn mới cho hành trình tiếp theo.','featured_title'=>'Được chọn cho bạn','cta_eyebrow'=>'SẴN SÀNG CHO CHUYẾN ĐI TIẾP THEO?','cta_title'=>'Một chiếc vali.\nNhiều hành trình.','cta_body'=>'Chọn hành trang vừa vặn với cách bạn khám phá thế giới.','cta_button'=>'Tìm chiếc vali của bạn','cta_url'=>'catalog.php','newsletter_title'=>'Một chút cảm hứng cho chuyến đi','newsletter_body'=>'Đăng ký để cửa hàng lưu email nhận thông tin sản phẩm và bộ sưu tập mới.','catalog_intro'=>'Từ chuyến đi cuối tuần đến những hành trình dài. Tìm hành trang dành cho bạn.','per_page'=>'6','collections_title'=>'Hành trang theo cách của bạn.','service_stock'=>'Xem tồn kho trước khi đặt','service_shipping'=>'Xác nhận giao hàng trực tiếp','service_support'=>'Luôn sẵn sàng hỗ trợ','faq_title'=>'Giải đáp trước chuyến đi.','faq_intro'=>'Tìm câu trả lời về lựa chọn sản phẩm, đặt hàng và giao nhận.','contact_title'=>'Cùng chuẩn bị cho chuyến đi.','contact_intro'=>'Cần tư vấn sản phẩm hoặc hỗ trợ đơn hàng? Hãy để lại lời nhắn.'];
}
function about_defaults(): array {
    return [
        'about_eyebrow'=>'CÂU CHUYỆN CỦA CHÚNG TÔI',
        'about_headline'=>"Đi cùng bạn.\nĐến những nơi mới.",
        'about_intro'=>'Hành trang vừa vặn để mỗi chuyến đi bắt đầu nhẹ nhàng hơn.',
        'about_image'=>'assets/images/hero-travel.webp',
        'about_story_label'=>'TỪ HÀNH TRANG ĐẾN HÀNH TRÌNH',
        'about_story_title'=>"Chuyến đi của bạn.\nCảm hứng của chúng tôi.",
        'about_values_title'=>'Chọn hành trang. An tâm lên đường.',
        'about_value_1_title'=>'Phù hợp với chuyến đi',
        'about_value_1_body'=>'Vali, balo và túi du lịch cho những chuyến đi ngắn ngày hay hành trình dài hơn.',
        'about_value_2_title'=>'Thông tin rõ ràng',
        'about_value_2_body'=>'Xem mô tả, giá và số lượng còn hàng để cân nhắc trước khi đặt mua.',
        'about_value_3_title'=>'Kết nối trực tiếp',
        'about_value_3_body'=>'Gửi lời nhắn để được tư vấn. Cửa hàng liên hệ xác nhận đơn và thông tin giao hàng.',
        'about_cta_title'=>"Chuyến đi tiếp theo,\nbắt đầu từ đây.",
        'about_cta_body'=>'Khám phá hành trang dành cho bạn hoặc trò chuyện cùng cửa hàng để chọn sản phẩm phù hợp.',
        'about_cta_button'=>'Khám phá bộ sưu tập',
        'about_contact_button'=>'Liên hệ với chúng tôi'
    ];
}
function settings(): array {
    global $conn;
    static $values;
    if ($values===null) {
        $values=setting_defaults();
        $rows=$conn->query('SELECT setting_key,setting_value FROM settings');
        while($row=$rows->fetch_assoc()) $values[$row['setting_key']]=$row['setting_value'];
    }
    return $values;
}
function setting(string $key): string { return settings()[$key] ?? ''; }
function db_run(string $sql, array $params=[]): mysqli_stmt {
    global $conn;
    $stmt=$conn->prepare($sql);
    if($params) { $types='';foreach($params as $p) $types.=is_int($p)?'i':(is_float($p)?'d':'s');$stmt->bind_param($types,...$params); }
    $stmt->execute();return $stmt;
}
function start_session(): void { if(session_status()===PHP_SESSION_NONE) session_start(); }
function csrf_token(): string { start_session();if(empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));return $_SESSION['csrf']; }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="'.e(csrf_token()).'">'; }
function verify_csrf(): void {
    start_session();
    if(!is_string($_POST['csrf']??null)||!hash_equals(csrf_token(),$_POST['csrf'])) { http_response_code(403);exit('Phiên biểu mẫu không hợp lệ. Vui lòng tải lại trang.'); }
}
function require_admin(): void { start_session();if(empty($_SESSION['user_id'])||($_SESSION['role']??'')!=='admin') { header('Location: /vonam/login.php');exit; } }
function flash(string $text, string $type='success'): void { start_session();$_SESSION['flash']=[$text,$type]; }
function show_flash(): void { if(isset($_SESSION['flash'])) { [$text,$type]=$_SESSION['flash'];unset($_SESSION['flash']);echo '<div role="status" class="alert alert-'.e($type).'">'.e($text).'</div>'; } }
function safe_link(string $value): string {
    $value=trim($value);
    if(preg_match('~[\x00-\x20\\\\]~',$value)) return 'catalog.php';
    if(preg_match('~^https?://~i',$value)) return $value;
    if(preg_match('~^(?:[a-zA-Z0-9_-]+\.php(?:[?#].*)?|#[a-zA-Z0-9_-]+|/vonam/[a-zA-Z0-9_/?=&.#%+-]*)$~',$value)) return $value;
    return 'catalog.php';
}
function image_url(?string $image, string $category=''): string {
    if($image && preg_match('~^assets/(?:uploads|images)/[a-zA-Z0-9_.-]+\.(?:jpg|jpeg|png|webp|svg)$~i',$image) && is_file(__DIR__.'/../public/'.$image)) return '/vonam/'.$image;
    return '/vonam/assets/images/'.(str_contains(mb_strtolower($category),'balo')?'backpack.svg':(str_contains(mb_strtolower($category),'túi')?'duffel.svg':'suitcase.svg'));
}
function uploaded_image(string $field, string $existing=''): string {
    if(empty($_FILES[$field]) || $_FILES[$field]['error']===UPLOAD_ERR_NO_FILE) return $existing;
    $f=$_FILES[$field];
    if($f['error']!==UPLOAD_ERR_OK || $f['size']>5*1024*1024) throw new RuntimeException('Ảnh tối đa 5 MB. Vui lòng chọn lại ảnh.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
    if(!$ext || !getimagesize($f['tmp_name'])) throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG hoặc WebP.');
    $dir=__DIR__.'/../public/assets/uploads';if(!is_dir($dir)) mkdir($dir,0755,true);
    $path='assets/uploads/'.bin2hex(random_bytes(16)).'.'.$ext;
    if(!move_uploaded_file($f['tmp_name'],__DIR__.'/../public/'.$path)) throw new RuntimeException('Không lưu được ảnh.');
    return $path;
}
function product_image_note(?string $image): string {
    if(str_starts_with($image??'', 'assets/images/demo-reference-')) return 'Ảnh tham khảo';
    if(!$image || !preg_match('~^assets/~',$image) || str_ends_with($image,'.svg')) return 'Ảnh minh họa';
    return '';
}
function product_card(array $product): void { ?>
<article class="shop-product">
 <a class="product-image" href="/vonam/product.php?id=<?= (int)$product['id'] ?>">
  <img src="<?= e(image_url($product['image'],$product['category'])) ?>" alt="<?= e($product['name']) ?>" loading="lazy" width="480" height="520">
  <?php if($product['is_new']): ?><span class="product-label">Mới đến</span><?php endif ?>
  <?php if($image_note=product_image_note($product['image'])): ?><span class="illustration-label"><?= e($image_note) ?></span><?php endif ?>
 </a>
 <div class="product-info"><span class="product-category"><?= e($product['category']) ?></span><h3><a href="/vonam/product.php?id=<?= (int)$product['id'] ?>"><?= e($product['name']) ?></a></h3>
 <div class="product-bottom"><strong><?= number_format($product['price'],0,',','.') ?>₫</strong>
 <?php if($product['stock']>0): ?><form action="/vonam/cart.php" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add"><input type="hidden" name="id" value="<?= (int)$product['id'] ?>"><button class="quick-add" aria-label="Thêm <?= e($product['name']) ?> vào giỏ"><i aria-hidden="true" class="bi bi-plus-lg"></i></button></form><?php else: ?><span class="text-muted small">Hết hàng</span><?php endif ?></div></div>
</article>
<?php }

// Authentication forms receive the same CSRF protection as CMS and checkout.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') verify_csrf();

/** Local demo only: never connects to a payment provider or moves money. */
function demo_payment_status(string $note): string {
    $value = mb_strtolower(trim($note), 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', $value);
    return in_array($value, ['thất bại', 'that bai'], true) ? 'failed' : 'success';
}
function demo_payment_label(array $order): string {
    if (($order['payment_status'] ?? null) === 'failed') return 'Đơn thất bại — thanh toán giả lập thất bại.';
    if (($order['payment_status'] ?? null) === 'success') return 'Đơn thành công — thanh toán giả lập thành công.';
    return 'Đơn cũ — chưa ghi nhận kết quả thanh toán giả lập.';
}
