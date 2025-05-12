<?php
session_start();
require_once 'account.php';
require_once 'db_connect.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);

     if (login($email, $password)) {
        if ($remember_me) {
            setcookie('remember_email', $email, time() + 30 * 24 * 60 * 60, '/', '', false, true);
        }

        header('Location: index.php');
        exit;
    } else {
        $_SESSION['errors'] = ['Email hoặc mật khẩu không đúng.'];
        header('Location: login.php');
        exit;
    }

    // try {
    //     $stmt = $pdo->prepare("SELECT id, full_name, email, password FROM users WHERE email = ?");
    //     $stmt->execute([$email]);
    //     $user = $stmt->fetch(PDO::FETCH_ASSOC);

    //     if ($user && password_verify($password, $user['password'])) {
    //         $_SESSION['user_id'] = $user['id'];
    //         $_SESSION['user_name'] = $user['full_name'];
    //         $_SESSION['user_email'] = $user['email'];

    //         // Load cart from database
    //         $stmt = $pdo->prepare("SELECT product_id, quantity FROM cart_items WHERE user_id = ?");
    //         $stmt->execute([$user['id']]);
    //         $_SESSION['cart'] = [];
    //         while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    //             $_SESSION['cart'][$row['product_id']] = $row['quantity'];
    //         }

    //         // Load favorites from database
    //         $stmt = $pdo->prepare("SELECT product_id FROM favorites WHERE user_id = ?");
    //         $stmt->execute([$user['id']]);
    //         $_SESSION['favorites'] = [];
    //         while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    //             $_SESSION['favorites'][] = $row['product_id'];
    //         }

    //         if ($remember_me) {
    //             $token = bin2hex(random_bytes(16));
    //             $hashed_token = password_hash($token, PASSWORD_DEFAULT);
    //             $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
    //             $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, remember_token_expiry = ? WHERE id = ?");
    //             $stmt->execute([$hashed_token, $expiry, $user['id']]);
    //             setcookie('remember_me', $token, time() + 30 * 24 * 60 * 60, '/', '', false, true);
    //         }

    //         header('Location: index.php');
    //         exit;
    //     } else {
    //         $_SESSION['errors'] = ['Email hoặc mật khẩu không đúng.'];
    //         header('Location: login.php');
    //         exit;
    //     }
    // } catch (PDOException $e) {
    //     $_SESSION['errors'] = ['Lỗi cơ sở dữ liệu. Vui lòng thử lại.'];
    //     file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Login Error: " . $e->getMessage() . "\n", FILE_APPEND);
    //     header('Location: login.php');
    //     exit;
    // }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - KidsToyLand</title>
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

        /* Login Form */
        .login-section {
            padding: 100px 0;
            background-color: var(--light-color);
        }

        .login-card {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }

        .login-card h2 {
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

        .btn-login {
            border-radius: 50px;
            padding: 12px;
            font-weight: 600;
            width: 100%;
        }

        .social-login {
            margin-top: 1.5rem;
        }

        .social-login .btn {
            border-radius: 50px;
            padding: 10px;
            font-weight: 600;
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #e0e0e0;
            color: var(--text-color);
        }

        .social-login .btn i {
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
    <!-- Login Section -->
    <section class="login-section">
        <div class="container">
            <div class="login-card">
                <h2 class="text-center">Đăng nhập</h2>
                <?php
                if (isset($_SESSION['errors'])) {
                    echo '<div class="alert alert-danger">';
                    foreach ($_SESSION['errors'] as $error) {
                        echo "<p>$error</p>";
                    }
                    echo '</div>';
                    unset($_SESSION['errors']);
                }
                ?>
                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Nhập email của bạn" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu</label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Nhập mật khẩu" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                        <label class="form-check-label" for="rememberMe">Ghi nhớ đăng nhập</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-login">Đăng nhập</button>
                </form>
                <div class="text-center mt-3">
                    <a href="forgot-password.php" class="text-muted">Quên mật khẩu?</a>
                </div>
                <div class="text-center mt-3">
                    <p class="text-muted">Chưa có tài khoản? <a href="register.php" class="text-primary">Đăng ký ngay</a></p>
                </div>
                <!-- <div class="social-login text-center">
                    <p class="text-muted">Hoặc đăng nhập bằng</p>
                    <button class="btn"><i class="fab fa-google"></i> Google</button>
                    <button class="btn"><i class="fab fa-facebook-f"></i> Facebook</button>
                </div> -->
            </div>
        </div>
    </section>
</main>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script>
    document.querySelector('form').addEventListener('submit', function (e) {
        const email = document.getElementById('email').value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Vui lòng nhập email hợp lệ.');
        }
    });

    // Auto-fill email from cookie
    window.onload = function() {
        const emailInput = document.getElementById('email');
        const rememberMeCheckbox = document.getElementById('rememberMe');
        const cookies = document.cookie.split(';').reduce((acc, cookie) => {
            const [name, value] = cookie.trim().split('=');
            acc[name] = value;
            return acc;
        }, {});

        if (cookies.remember_email) {
            emailInput.value = cookies.remember_email;
            rememberMeCheckbox.checked = true;
        }
    };
</script>
</body>
</html>