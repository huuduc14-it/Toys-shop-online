<?php
ob_start();
require_once 'db_connect.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = filter_var($_POST['token'], FILTER_SANITIZE_STRING);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $errors = [];

    // Validate input
    if (empty($token)) {
        $errors[] = "Token không hợp lệ.";
    }
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Mật khẩu phải có ít nhất 6 ký tự.";
    }
    if ($password !== $confirmPassword) {
        $errors[] = "Mật khẩu xác nhận không khớp.";
    }

    // Verify token
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if (!$user) {
            $errors[] = "Token không hợp lệ hoặc đã hết hạn.";
        }
    }

    // Update password
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
        if ($stmt->execute([$hashedPassword, $user['id']])) {
            $_SESSION['success'] = "Mật khẩu đã được đặt lại thành công! Vui lòng đăng nhập.";
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Đã có lỗi xảy ra. Vui lòng thử lại.";
        }
    }

    // Store errors in session
    $_SESSION['errors'] = $errors;
    header("Location: reset-password.php?token=" . urlencode($token));
    exit();
}
?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Đặt lại mật khẩu - KidsToyLand</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome Icons -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <!-- Google Fonts: Nunito -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary-color: #FF6B6B;
                --secondary-color: #4ECDC4;
                --accent-color: #FFE66D;
                --text-color: #2D3748;
                --light-color: #F7FFF7;
                --dark-color: #1A202C;
            }

            body {
                font-family: 'Nunito', sans-serif;
                color: var(--text-color);
            }

            .navbar-brand {
                font-weight: 800;
                font-size: 1.8rem;
            }

            .navbar-brand span {
                color: var(--primary-color);
            }

            .nav-link {
                font-weight: 600;
                color: var(--text-color) !important;
                transition: all 0.3s ease;
            }

            .nav-link:hover {
                color: var(--primary-color) !important;
            }

            .nav-link.active {
                color: var(--primary-color) !important;
            }

            .search-form {
                position: relative;
            }

            .search-form .form-control {
                border-radius: 50px;
                padding-right: 40px;
            }

            .search-form .btn {
                position: absolute;
                right: 5px;
                top: 5px;
                border-radius: 50%;
                width: 30px;
                height: 30px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .btn-primary {
                background-color: var(--primary-color);
                border-color: var(--primary-color);
            }

            .btn-primary:hover {
                background-color: #ff5252;
                border-color: #ff5252;
            }

            .reset-password-section {
                padding: 100px 0;
                background-color: var(--light-color);
            }

            .reset-password-card {
                max-width: 500px;
                margin: 0 auto;
                background: white;
                border-radius: 15px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                padding: 40px;
            }

            .reset-password-card h2 {
                font-weight: 800;
                color: var(--text-color);
                margin-bottom: 1.5rem;
            }

            .reset-password-card p {
                color: var(--text-color);
                margin-bottom: 1.5rem;
            }

            .form-control {
                border-radius: 10px;
                padding: 12px;
                border: 1px solid #e0e0e0;
            }

            .form-control:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 5px rgba(255, 107, 107, 0.3);
            }

            .btn-reset {
                border-radius: 50px;
                padding: 12px;
                font-weight: 600;
                width: 100%;
            }

            .footer {
                background-color: var(--dark-color);
                color: #fff;
                padding: 60px 0 20px;
            }

            .footer h5 {
                color: var(--accent-color);
                font-weight: 700;
                margin-bottom: 1.5rem;
            }

            .footer-links {
                list-style: none;
                padding-left: 0;
            }

            .footer-links li {
                margin-bottom: 10px;
            }

            .footer-links a {
                color: #fff;
                text-decoration: none;
                transition: all 0.3s ease;
            }

            .footer-links a:hover {
                color: var(--accent-color);
                padding-left: 5px;
            }

            .social-icons {
                margin-top: 20px;
            }

            .social-icons a {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background-color: rgba(255, 255, 255, 0.1);
                color: #fff;
                margin-right: 10px;
                transition: all 0.3s ease;
            }

            .social-icons a:hover {
                background-color: var(--primary-color);
                transform: translateY(-3px);
            }

            .copyright {
                background-color: rgba(0, 0, 0, 0.2);
                padding: 15px 0;
                margin-top: 40px;
            }
        </style>
    </head>
    <body>
    <!-- MAIN CONTENT -->
    <main>
        <!-- Reset Password Section -->
        <section class="reset-password-section">
            <div class="container">
                <div class="reset-password-card">
                    <h2 class="text-center">Đặt lại mật khẩu</h2>
                    <p class="text-center">Nhập mật khẩu mới để hoàn tất quá trình khôi phục.</p>
                    <?php
                    if (isset($_SESSION['errors'])) {
                        echo '<div class="alert alert-danger">';
                        foreach ($_SESSION['errors'] as $error) {
                            echo "<p>$error</p>";
                        }
                        echo '</div>';
                        unset($_SESSION['errors']);
                    }
                    if (isset($_SESSION['success'])) {
                        echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
                        unset($_SESSION['success']);
                    }
                    ?>
                    <form id="resetPasswordForm" action="reset-password.php" method="POST">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu mới</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu mới" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Xác nhận mật khẩu</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Xác nhận mật khẩu" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-reset">Đặt lại mật khẩu</button>
                    </form>
                    <div class="text-center mt-3">
                        <p class="text-muted">Quay lại <a href="login.php" class="text-primary">Đăng nhập</a></p>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('resetPasswordForm').addEventListener('submit', function (e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            // Client-side validation
            if (password.length < 6) {
                e.preventDefault();
                alert('Mật khẩu phải có ít nhất 6 ký tự.');
                return;
            }
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Mật khẩu xác nhận không khớp.');
                return;
            }
            // Form will submit to server if validation passes
        });
    </script>
    </body>
    </html>
<?php
ob_end_flush();
?>