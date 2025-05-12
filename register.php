<?php
require_once 'db_connect.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = filter_var($_POST['fullName'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $errors = [];

    // Validate input
    if (empty($fullName)) {
        $errors[] = "Họ và tên không được để trống.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ.";
    }
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Mật khẩu phải có ít nhất 6 ký tự.";
    }
    if ($password !== $confirmPassword) {
        $errors[] = "Mật khẩu xác nhận không khớp.";
    }
    if (!isset($_POST['agreeTerms'])) {
        $errors[] = "Vui lòng đồng ý với điều khoản dịch vụ.";
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email đã được sử dụng.";
        }
    }

    // Register user
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$fullName, $email, $hashedPassword])) {
            $_SESSION['success'] = "Đăng ký thành công! Vui lòng đăng nhập.";
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Đã có lỗi xảy ra. Vui lòng thử lại.";
        }
    }

    // Store errors in session
    $_SESSION['errors'] = $errors;
    header("Location: register.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - KidsToyLand</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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

        /* Header Styles */
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

        /* Register Form */
        .register-section {
            padding: 100px 0;
            background-color: var(--light-color);
        }

        .register-card {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }

        .register-card h2 {
            font-weight: 800;
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

        .btn-register {
            border-radius: 50px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
        }

        .social-register {
            margin-top: 1.5rem;
        }

        .social-register .btn {
            border-radius: 50px;
            padding: 10px;
            font-weight: 600;
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #e0e0e0;
            color: var(--text-color);
        }

        .social-register .btn i {
            margin-right: 10px;
        }

        /* Footer */
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
    <!-- Register Section -->
    <section class="register-section">
        <div class="container">
            <div class="register-card">
                <h2 class="text-center">Đăng ký tài khoản</h2>
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
                <form action="register.php" method="POST">
                    <div class="mb-3">
                        <label for="fullName" class="form-label">Họ và tên</label>
                        <input type="text" class="form-control" id="fullName" name="fullName" placeholder="Nhập họ và tên" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Nhập email của bạn" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Xác nhận mật khẩu</label>
                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="Xác nhận mật khẩu" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="agreeTerms" name="agreeTerms" required>
                        <label class="form-check-label" for="agreeTerms">Tôi đồng ý với <a href="#" class="text-primary">điều khoản dịch vụ</a></label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-register">Đăng ký</button>
                </form>
                <div class="text-center mt-3">
                    <p class="text-muted">Đã có tài khoản? <a href="login.php" class="text-primary">Đăng nhập ngay</a></p>
                </div>
                <div class="social-register text-center">
                    <p class="text-muted">Hoặc đăng ký bằng</p>
                    <button class="btn"><i class="fab fa-google"></i> Google</button>
                    <button class="btn"><i class="fab fa-facebook-f"></i> Facebook</button>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>