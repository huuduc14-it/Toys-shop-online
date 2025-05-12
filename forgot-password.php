<?php
ob_start();
require_once 'db_connect.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// Log function for debugging
function logError($message) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Forgot Password Error: $message\n", FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';
    $errors = [];

    // Log POST data
    logError("POST Data: " . print_r($_POST, true));

    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ.";
        logError("Invalid email: $email");
    }

    // Check if email exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() === 0) {
                $errors[] = "Email không tồn tại.";
                logError("Email not found: $email");
            }
        } catch (PDOException $e) {
            $errors[] = "Lỗi cơ sở dữ liệu. Vui lòng thử lại.";
            logError("DB Error: " . $e->getMessage());
        }
    }

    // Generate reset token and send email
    if (empty($errors)) {
        try {
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour

            // Store token in database
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
            if ($stmt->execute([$token, $expiry, $email])) {
                // Send email
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = getenv('SMTP_USERNAME') ?: 'youractualemail@gmail.com'; // Replace with your email
                    $mail->Password = getenv('SMTP_PASSWORD') ?: 'your-16-character-app-password'; // Replace with your App Password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    // Recipients
                    $mail->setFrom($mail->Username, 'KidsToyLand');
                    $mail->addAddress($email);

                    // Content
                    $resetLink = "http://localhost/final-se/reset-password.php?token=$token";
                    $mail->isHTML(true);
                    $mail->Subject = 'Khôi phục mật khẩu KidsToyLand';
                    $mail->Body = "
                        <h2>Khôi phục mật khẩu</h2>
                        <p>Vui lòng nhấp vào liên kết sau để đặt lại mật khẩu của bạn:</p>
                        <p><a href='$resetLink'>$resetLink</a></p>
                        <p>Liên kết này sẽ hết hạn sau 1 giờ.</p>
                    ";

                    $mail->send();
                    $_SESSION['success'] = "Liên kết khôi phục đã được gửi đến email của bạn.";
                    logError("Email sent successfully to: $email");
                    header("Location: forgot-password.php");
                    exit();
                } catch (Exception $e) {
                    $errors[] = "Không thể gửi email. Vui lòng thử lại.";
                    logError("PHPMailer Error: {$mail->ErrorInfo}");
                }
            } else {
                $errors[] = "Không thể lưu token. Vui lòng thử lại.";
                logError("Failed to store reset token for email: $email");
            }
        } catch (Exception $e) {
            $errors[] = "Lỗi hệ thống. Vui lòng thử lại.";
            logError("System Error: " . $e->getMessage());
        }
    }

    // Store errors in session
    $_SESSION['errors'] = $errors;
    header("Location: forgot-password.php");
    exit();
}
?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Khôi phục mật khẩu - KidsToyLand</title>
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

            .forgot-password-section {
                padding: 100px 0;
                background-color: var(--light-color);
            }

            .forgot-password-card {
                max-width: 500px;
                margin: 0 auto;
                background: white;
                border-radius: 15px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                padding: 40px;
            }

            .forgot-password-card h2 {
                font-weight: 800;
                color: var(--text-color);
                margin-bottom: 1.5rem;
            }

            .forgot-password-card p {
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
        <!-- Forgot Password Section -->
        <section class="forgot-password-section">
            <div class="container">
                <div class="forgot-password-card">
                    <h2 class="text-center">Khôi phục mật khẩu</h2>
                    <p class="text-center">Nhập địa chỉ email của bạn để nhận liên kết khôi phục mật khẩu.</p>
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
                    <form action="forgot-password.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Nhập email của bạn" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-reset">Gửi liên kết khôi phục</button>
                    </form>
                    <div class="text-center mt-3">
                        <p class="text-muted">Đã nhớ mật khẩu? <a href="login.php" class="text-primary">Đăng nhập ngay</a></p>
                        <p class="text-muted">Chưa có tài khoản? <a href="register.php" class="text-primary">Đăng ký ngay</a></p>
                    </div>
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
    </script>
    </body>
    </html>
<?php
ob_end_flush();
?>