<?php
require_once __DIR__ . '/../app/database.php';
start_session();

// Nếu đã đăng nhập thì về trang chủ
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($username === '' || $password === '' || $fullname === '') {
        $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc.';
     } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $error = 'Tên đăng nhập gồm 3–50 chữ cái, chữ số hoặc dấu gạch dưới.';
    } elseif (mb_strlen($fullname)>100 || ($email!=='' && (!filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($email)>100)) || ($phone!=='' && !preg_match('/^[+0-9 ()-]{7,20}$/',$phone)) || mb_strlen($address)>1000) {
        $error = 'Kiểm tra họ tên, email, điện thoại và địa chỉ hợp lệ.';
    } elseif (!valid_new_password($password)) {
        $error = 'Mật khẩu phải có từ 8 đến 72 byte và không chứa ký tự không hợp lệ.';
    } elseif ($password !== $confirm) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        // Kiểm tra username đã tồn tại chưa
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Tên đăng nhập đã tồn tại.';
        } else {
            // Tạo tài khoản
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, email, phone, address) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $username, $hashed, $fullname, $email, $phone, $address);
            try {
                $stmt->execute();
                $success = 'Đăng ký thành công! Bạn có thể đăng nhập ngay.';
            } catch (mysqli_sql_exception $ex) {
                if ($ex->getCode() !== 1062) throw $ex;
                $error = 'Tên đăng nhập đã tồn tại.';
            }
        }
    }
}

include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="container">
    <div class="auth-form">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="text-center mb-4"><i aria-hidden="true" class="bi bi-person-plus"></i> Đăng ký</h3>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?> <a href="login.php">Đăng nhập</a></div>
                <?php endif; ?>

                <form method="POST"><?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập *</label>
                        <input type="text" id="username" name="username" autocomplete="username" class="form-control" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]{3,50}"
                               value="<?= htmlspecialchars($username ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="fullname" class="form-label">Họ tên *</label>
                        <input type="text" id="fullname" name="fullname" autocomplete="name" class="form-control" required maxlength="100"
                               value="<?= htmlspecialchars($fullname ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" autocomplete="email" class="form-control" maxlength="100"
                               value="<?= htmlspecialchars($email ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Số điện thoại</label>
                        <input type="tel" id="phone" name="phone" autocomplete="tel" class="form-control" maxlength="20"
                               value="<?= htmlspecialchars($phone ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Địa chỉ</label>
                        <textarea id="address" name="address" autocomplete="street-address" class="form-control" maxlength="1000" rows="2"><?= htmlspecialchars($address ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu *</label>
                        <input type="password" id="password" name="password" autocomplete="new-password" class="form-control" required minlength="8" maxlength="72">
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu *</label>
                        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" class="form-control" required minlength="8" maxlength="72">
                    </div>
                    <button type="submit" class="btn btn-dark w-100 py-2">
                        <i aria-hidden="true" class="bi bi-person-plus"></i> Đăng ký
                    </button>
                </form>

                <p class="text-center mt-3 mb-0">
                    Đã có tài khoản? <a href="login.php">Đăng nhập</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
