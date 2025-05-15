<?php
ob_start();
require_once 'db_connect.php';
session_start();

// Redirect if not logged in or not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Log function for debugging
function logError($message) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Admin Orders Error: $message\n", FILE_APPEND);
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];

    if (!in_array($status, ['approved', 'rejected'])) {
        $_SESSION['errors'] = ["Trạng thái không hợp lệ."];
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            $_SESSION['success'] = "Cập nhật trạng thái đơn hàng thành công.";
        } catch (PDOException $e) {
            logError("Update Status DB Error: " . $e->getMessage());
            $_SESSION['errors'] = ["Lỗi cơ sở dữ liệu khi cập nhật trạng thái."];
        }
    }
    header('Location: admin_orders.php');
    exit;
}

// Fetch all orders
try {
    $stmt = $pdo->query("
        SELECT o.id, o.user_id, o.total_amount, o.status, o.order_date, o.recipient_name, o.phone, o.address, u.full_name
        FROM orders o
        JOIN users u ON o.user_id = u.id
        ORDER BY o.order_date DESC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch order items for each order
    $order_items = [];
    foreach ($orders as $order) {
        $stmt = $pdo->prepare("
            SELECT oi.product_id, oi.quantity, oi.price, p.name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order['id']]);
        $order_items[$order['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    logError("DB Error: " . $e->getMessage());
    $_SESSION['errors'] = ["Lỗi cơ sở dữ liệu. Vui lòng thử lại."];
    $orders = [];
    $order_items = [];
}

// Calculate cart count for badge
$cart_count = isset($_SESSION['cart']) && !empty($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>

    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Quản Lý Đơn Hàng - KidsToyLand</title>
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
            }

            .nav-link:hover, .nav-link.active {
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

            .order-card {
                border-radius: 15px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            }

            .status-pending { color: #FFA500; }
            .status-approved { color: #28A745; }
            .status-rejected { color: #DC3545; }
            .status-completed { color: #17A2B8; }

            .footer {
                background-color: var(--dark-color);
                color: #fff;
            }

            .footer h5 {
                color: var(--accent-color);
                font-weight: 700;
            }

            .footer-links {
                list-style: none;
                padding-left: 0;
            }

            .footer-links a {
                color: #fff;
                text-decoration: none;
            }

            .footer-links a:hover {
                color: var(--accent-color);
                padding-left: 5px;
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
            }

            .social-icons a:hover {
                background-color: var(--primary-color);
                transform: translateY(-3px);
            }

            .copyright {
                background-color: rgba(0, 0, 0, 0.2);
            }

            .toast-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1050;
            }
        </style>
    </head>
    <body>
    <!-- Toast Container -->
    <div class="toast-container">
        <div class="toast" id="statusToast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">Thông báo</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>

    <!-- HEADER -->
    <header>
        <div class="bg-dark text-white py-2">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <small>
                            <i class="fas fa-phone-alt me-2"></i> Hotline: 1900 1234
                            <i class="fas fa-envelope ms-3 me-2"></i> Email: info@kidstoy.vn
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <small>
                            <a href="admin_orders.php" class="text-white me-3"><i class="fas fa-tasks me-1"></i> Quản lý đơn hàng</a>
                            <span class="text-white me-3">Xin chào, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            <a href="logout.php" class="text-white"><i class="fas fa-sign-out-alt me-1"></i> Đăng xuất</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <nav class="navbar navbar-expand-lg navbar-light bg-white py-3 shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="index.php">Kids<span>ToyLand</span></a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Trang chủ</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Sản phẩm
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="#">Đồ chơi cho bé trai</a></li>
                                <li><a class="dropdown-item" href="#">Đồ chơi cho bé gái</a></li>
                                <li><a class="dropdown-item" href="#">Đồ chơi học tập</a></li>
                                <li><a class="dropdown-item" href="#">Đồ chơi vận động</a></li>
                                <li><a class="dropdown-item" href="#">Đồ chơi xếp hình</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Khuyến mãi</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Tin tức</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Về chúng tôi</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Liên hệ</a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center">
                        <form class="search-form me-3" action="search.php" method="GET">
                            <input class="form-control" type="search" name="query" placeholder="Tìm kiếm đồ chơi..." aria-label="Search">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                        </form>
                        <a href="cart.php" class="btn btn-outline-dark position-relative">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge">
                                <?php echo $cart_count; ?>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <!-- MAIN CONTENT -->
    <main>
        <section class="py-5">
            <div class="container">
                <h2 class="text-center mb-5 fw-bold">Quản Lý Đơn Hàng</h2>
                <?php if (isset($_SESSION['errors'])): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($_SESSION['errors'] as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                    <?php unset($_SESSION['errors']); ?>
                <?php endif; ?>
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['success']; ?>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>
                <?php if (empty($orders)): ?>
                    <p class="text-center">Hiện tại không có đơn hàng nào.</p>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($orders as $order): ?>
                            <div class="col-12">
                                <div class="card order-card">
                                    <div class="card-header">
                                        <h5 class="mb-0">Đơn hàng #<?php echo $order['id']; ?> -
                                            <span class="status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>Khách hàng:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                                        <p><strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
                                        <p><strong>Tổng tiền:</strong> <?php echo number_format($order['total_amount'], 0, ',', '.'); ?> VND</p>
                                        <p><strong>Người nhận:</strong> <?php echo htmlspecialchars($order['recipient_name']); ?></p>
                                        <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                                        <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order['address']); ?></p>
                                        <h6>Sản phẩm:</h6>
                                        <ul>
                                            <?php foreach ($order_items[$order['id']] as $item): ?>
                                                <li>
                                                    <?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?>
                                                    (<?php echo number_format($item['price'], 0, ',', '.'); ?> VND)
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php if ($order['status'] === 'pending'): ?>
                                            <form method="POST" class="d-flex gap-2">
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                <button type="submit" name="status" value="approved" class="btn btn-success">Duyệt</button>
                                                <button type="submit" name="status" value="rejected" class="btn btn-danger">Từ chối</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer bg-dark">
            <div class="container">
                <div class="row pt-5">
                    <div class="col-md-4 col-12 mb-4">
                        <h5>KidsToyLand</h5>
                        <p>Địa chỉ: 123 Đường Vui Chơi, Quận 1, TP. Hồ Chí Minh</p>
                        <p>Email: info@kidstoy.vn</p>
                        <p>Điện thoại: 1900 1234</p>
                    </div>
                    <div class="col-md-4 col-12 mb-4">
                        <h5>Liên kết nhanh</h5>
                        <ul class="footer-links">
                            <li><a href="#">Trang chủ</a></li>
                            <li><a href="#">Sản phẩm</a></li>
                            <li><a href="#">Khuyến mãi</a></li>
                            <li><a href="#">Về chúng tôi</a></li>
                            <li><a href="#">Liên hệ</a></li>
                        </ul>
                    </div>
                    <div class="col-md-4 col-12 mb-4">
                        <h5>Liên hệ</h5>
                        <p>Email: support@kidstoyland.com</p>
                        <p>Hotline: 0123 456 789</p>
                        <div class="social-icons">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
                <div class="copyright text-center">
                    <p>© 2025 KidsToyLand. Tất cả quyền được bảo lưu.</p>
                </div>
            </div>
        </footer>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
<?php
ob_end_flush();
?>