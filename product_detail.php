<?php
ob_start();
require_once 'db_connect.php';
session_start();

// Log function for debugging
function logError($message) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Product Detail Error: $message\n", FILE_APPEND);
}

// Get product ID from URL
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = null;

if ($product_id) {
    try {
        // Fetch product details
        $stmt = $pdo->prepare("SELECT id, name, description, price, image, stock FROM products WHERE id = ? AND stock > 0");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $_SESSION['errors'] = ["Sản phẩm không tồn tại hoặc đã hết hàng."];
        }
    } catch (PDOException $e) {
        logError("DB Error: " . $e->getMessage());
        $_SESSION['errors'] = ["Lỗi cơ sở dữ liệu. Vui lòng thử lại."];
    }
} else {
    $_SESSION['errors'] = ["ID sản phẩm không hợp lệ."];
}

// Calculate cart count for badge
$cart_count = isset($_SESSION['cart']) && !empty($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>

    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Chi Tiết Sản Phẩm - KidsToyLand</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome Icons -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <!-- Google Fonts: Nunito -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
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

            /* Product Detail Section */
            .product-detail-section {
                padding: 60px 0;
                background-color: var(--light-color);
            }

            .product-card {
                border-radius: 15px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                padding: 30px;
                background: white;
            }

            .product-image img {
                max-width: 100%;
                height: 400px;
                object-fit: cover;
                border-radius: 10px;
            }

            .product-title {
                font-weight: 800;
                font-size: 2rem;
                color: var(--text-color);
            }

            .product-price {
                font-weight: 700;
                font-size: 1.8rem;
                color: var(--primary-color);
            }

            .product-rating {
                color: #FFD700;
                margin-bottom: 1rem;
            }

            .quantity-input {
                width: 80px;
            }

            .description-section {
                margin-top: 40px;
            }

            .description-section h3 {
                font-weight: 700;
                color: var(--text-color);
                margin-bottom: 1.5rem;
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

            /* Toast Styling */
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
        <div class="toast" id="cartToast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">Thông báo</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>

    <!-- HEADER -->
    <header>
        <!-- Top Bar -->
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
                            <a href="#" class="text-white me-3"><i class="fas fa-truck me-1"></i> Theo dõi đơn hàng</a>
                            <a href="#" class="text-white me-3"><i class="fas fa-map-marker-alt me-1"></i> Cửa hàng gần bạn</a>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <span class="text-white me-3">Xin chào, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                                <a href="logout.php" class="text-white"><i class="fas fa-sign-out-alt me-1"></i> Đăng xuất</a>
                            <?php else: ?>
                                <a href="login.php" class="text-white"><i class="fas fa-user me-1"></i> Đăng nhập</a>
                                <a href="register.php" class="text-white ms-3"><i class="fas fa-user-plus me-1"></i> Đăng ký</a>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Navigation -->
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
                            <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
        <!-- Product Detail Section -->
        <section class="product-detail-section">
            <div class="container">
                <a href="index.php" class="btn btn-danger"><i class="bi bi-arrow-left"></i> Quay lại<a>
                <?php if (isset($_SESSION['errors'])): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($_SESSION['errors'] as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                    <?php unset($_SESSION['errors']); ?>
                <?php elseif ($product): ?>
                    <div class="product-card">
                        <div class="row">
                            <div class="col-md-6 product-image">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            </div>
                            <div class="col-md-6">
                                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                                <div class="product-rating">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star-half-alt"></i>
                                    <span class="ms-1 text-muted">(4.5)</span>
                                </div>
                                <p class="product-price">
                                    <?php echo number_format($product['price'], 0, ',', '.'); ?> VND
                                    <?php if ($product['id'] % 2 == 0): ?>
                                        <small class="text-decoration-line-through text-muted">
                                            <?php echo number_format($product['price'] * 1.25, 0, ',', '.'); ?> VND
                                        </small>
                                    <?php endif; ?>
                                </p>
                                <form class="add-to-cart-form d-flex align-items-center mb-3">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <label for="quantity" class="me-2">Số lượng:</label>
                                    <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="form-control quantity-input me-2">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary">
                                        <i class="fas fa-shopping-cart me-1"></i> Thêm vào giỏ
                                    </button>
                                </form>
                                <p><strong>Kho: </strong><?php echo $product['stock']; ?> sản phẩm</p>
                            </div>
                        </div>
                        <div class="description-section">
                            <h3>Mô tả sản phẩm</h3>
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

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

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle Add to Cart
        document.querySelectorAll('.add-to-cart-form').forEach(form => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const formData = new FormData(form);
                formData.append('add_to_cart', '1');

                try {
                    const response = await fetch('add_to_cart.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();

                    const toastElement = document.getElementById('cartToast');
                    const toastBody = toastElement.querySelector('.toast-body');
                    toastBody.textContent = data.message;
                    toastElement.classList.add(data.success ? 'bg-success' : 'bg-danger', 'text-white');

                    const toast = new bootstrap.Toast(toastElement);
                    toast.show();

                    if (data.success) {
                        const cartBadge = document.querySelector('.cart-badge');
                        cartBadge.textContent = data.cart_count;
                    }

                    toastElement.addEventListener('hidden.bs.toast', () => {
                        toastElement.classList.remove('bg-success', 'bg-danger', 'text-white');
                    }, { once: true });
                } catch (error) {
                    console.error('Error:', error);
                    const toastElement = document.getElementById('cartToast');
                    const toastBody = toastElement.querySelector('.toast-body');
                    toastBody.textContent = 'Đã xảy ra lỗi. Vui lòng thử lại.';
                    toastElement.classList.add('bg-danger', 'text-white');

                    const toast = new bootstrap.Toast(toastElement);
                    toast.show();

                    toastElement.addEventListener('hidden.bs.toast', () => {
                        toastElement.classList.remove('bg-danger', 'text-white');
                    }, { once: true });
                }
            });
        });
    </script>
    </body>
    </html>
<?php
ob_end_flush();
?>