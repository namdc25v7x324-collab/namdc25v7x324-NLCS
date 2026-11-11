<?php
require_once __DIR__ . '/../app/database.php';
start_session();

// Nếu đã đăng nhập thì về trang chủ
if (isset($_SESSION['user_id'])) {
    header("Location: " . (($_GET['next'] ?? '') === 'myinfo' ? 'myinfo.php' : 'index.php'));
    exit;
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ thông tin.';
    } elseif (str_contains($password,"\0")) {
        $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true); // Ngăn chặn Session Fixation
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['phone'] = $user['phone'] ?? '';
            $_SESSION['address'] = $user['address'] ?? '';

            if ($user['role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: " . (($_GET['next'] ?? '') === 'myinfo' ? 'myinfo.php' : 'index.php'));
            }
            exit;
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }
    }
}

include __DIR__ . '/../app/views/layout/header.php';
?>

<div class="container">
    <div class="auth-form">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h3 class="text-center mb-4"><i aria-hidden="true" class="bi bi-box-arrow-in-right"></i> Đăng nhập</h3>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST"><?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập</label>
                        <input type="text" id="username" name="username" autocomplete="username" class="form-control" required
                               value="<?= htmlspecialchars($username) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="password" id="password" name="password" autocomplete="current-password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 py-2">
                        <i aria-hidden="true" class="bi bi-box-arrow-in-right"></i> Đăng nhập
                    </button>
                </form>

                <p class="text-center mt-3 mb-0">
                    Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../app/views/layout/footer.php'; ?>
