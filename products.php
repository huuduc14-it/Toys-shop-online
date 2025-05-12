<?php
ob_start();
require_once 'db_connect.php';
session_start();

// Log function for debugging
function logError($message) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Products Error: $message\n", FILE_APPEND);
}

// Khôi phục giỏ hàng nếu người dùng đã đăng nhập nhưng giỏ hàng chưa được khởi tạo
if (isset($_SESSION['user_id']) && !isset($_SESSION['cart'])) {
    try {
        $_SESSION['cart'] = [];
        $stmt = $pdo->prepare("SELECT product_id, quantity FROM cart_items WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $_SESSION['cart'][$row['product_id']] = $row['quantity'];
        }
    } catch (PDOException $e) {
        logError("Cart Restore DB Error: " . $e->getMessage());
    }
}

// Check for remember_me cookie
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    try {
        $token = $_COOKIE['remember_me'];
        $stmt = $pdo->prepare("SELECT id, full_name, email, remember_token, remember_token_expiry FROM users WHERE remember_token IS NOT NULL AND remember_token_expiry > NOW()");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $user = null;
        foreach ($users as $u) {
            if (password_verify($token, $u['remember_token'])) {
                $user = $u;
                break;
            }
        }

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];

            // Khôi phục giỏ hàng
            $_SESSION['cart'] = [];
            $stmt = $pdo->prepare("SELECT product_id, quantity FROM cart_items WHERE user_id = ?");
            $stmt->execute([$user['id']]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $_SESSION['cart'][$row['product_id']] = $row['quantity'];
            }

            $newExpiry = date('Y-m-d H:i:s', strtotime('+30 days'));
            $stmt = $pdo->prepare("UPDATE users SET remember_token_expiry = ? WHERE id = ?");
            $stmt->execute([$newExpiry, $user['id']]);
            setcookie('remember_me', $token, time() + 30 * 24 * 60 * 60, '/', '', false, true);
            logError("Auto-login via Remember Me: User ID {$user['id']}");
        } else {
            setcookie('remember_me', '', time() - 3600, '/');
            logError("Invalid Remember Me token: $token");
        }
    } catch (PDOException $e) {
        logError("Remember Me DB Error: " . $e->getMessage());
    }
}

// Fetch products
try {
    $stmt = $pdo->query("SELECT id, name, description, price, image FROM products WHERE stock > 0");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    logError("DB Error: " . $e->getMessage());
    $_SESSION['errors'] = ["Lỗi cơ sở dữ liệu. Vui lòng thử lại."];
    $products = [];
}

// Calculate cart and favorite counts for badges
$cart_count = isset($_SESSION['cart']) && !empty($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sản Phẩm - KidsToyLand</title>
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

            .btn-secondary {
                background-color: var(--secondary-color);
                border-color: var(--secondary-color);
            }

            .btn-secondary:hover {
                background-color: #3dbdb5;
                border-color: #3dbdb5;
            }

            /* Product Cards */
            .product-card {
                border: none;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
            }

            .product-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            }

            .product-title {
                font-weight: 700;
                font-size: 1.1rem;
                margin-bottom: 0.5rem;
            }

            .product-price {
                font-weight: 800;
                color: var(--primary-color);
                font-size: 1.2rem;
            }

            .product-rating {
                color: #FFD700;
                margin-bottom: 0.5rem;
            }

            .badge-sale {
                position: absolute;
                top: 10px;
                right: 10px;
                background-color: var(--primary-color);
                color: white;
                padding: 5px 10px;
                border-radius: 15px;
                font-weight: 600;
            }

            /* Ensure uniform image sizes */
            .product-card .card-img-top {
                width: 100%;
                height: 200px;
                object-fit: cover;
                object-position: center;
            }

            /* Favorite Button */
            .favorite-btn.favorited i {
                color: var(--primary-color);
            }

            /* Footer */
            .footer {
                background-color: var(--dark-color);
                color: #fff;
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
                text-decoration: none;
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
                        <!-- <a href="favorites.php" class="btn btn-outline-dark position-relative me-2">
                            <i class="fas fa-heart"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger favorite-badge">
                            <?php echo $favorite_count; ?>
                        </span>
                        </a> -->
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
        <!-- Products Section -->
        <section class="products-section py-5">
            <div class="container">
                <h2 class="text-center mb-5 fw-bold">Tất Cả Sản Phẩm</h2>
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
                <?php if (empty($products)): ?>
                    <p class="text-center">Hiện tại không có sản phẩm nào.</p>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        <?php foreach ($products as $product): ?>
                            <div class="col">
                                <div class="card product-card h-100">
                                    <?php if ($product['id'] % 2 == 0): ?>
                                        <span class="badge-sale">Giảm 20%</span>
                                    <?php endif; ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="card-body">
                                        <div class="product-rating">
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star"></i>
                                            <i class="fas fa-star-half-alt"></i>
                                            <span class="ms-1 text-muted">(4.5)</span>
                                        </div>
                                        <h5 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                        <p class="product-price mb-0">
                                            <?php echo number_format($product['price'], 0, ',', '.'); ?> VND
                                            <?php if ($product['id'] % 2 == 0): ?>
                                                <small class="text-decoration-line-through text-muted"><?php echo number_format($product['price'] * 1.25, 0, ',', '.'); ?> VND</small>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-white border-top-0 d-flex justify-content-between align-items-center">
                                        <!-- <button class="btn btn-sm btn-outline-dark favorite-btn <?php echo isset($_SESSION['favorites']) && in_array($product['id'], $_SESSION['favorites']) ? 'favorited' : ''; ?>" data-product-id="<?php echo $product['id']; ?>">
                                            <i class="<?php echo isset($_SESSION['favorites']) && in_array($product['id'], $_SESSION['favorites']) ? 'fas fa-heart' : 'far fa-heart'; ?>"></i>
                                        </button> -->
                                        <form class="add-to-cart-form d-flex align-items-center">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="number" name="quantity" value="1" min="1" class="form-control quantity-input me-2">
                                            <button type="submit" name="add_to_cart" class="btn btn-sm btn-primary"><i class="fas fa-shopping-cart me-1"></i> Thêm vào giỏ</button>
                                        </form>
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

        // Handle Favorite Toggle
        document.querySelectorAll('.favorite-btn').forEach(button => {
            button.addEventListener('click', async () => {
                const productId = button.getAttribute('data-product-id');
                const formData = new FormData();
                formData.append('product_id', productId);

                try {
                    const response = await fetch('toggle_favorite.php', {
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
                        if (data.is_favorited) {
                            button.classList.add('favorited');
                            button.querySelector('i').classList.replace('far', 'fas');
                        } else {
                            button.classList.remove('favorited');
                            button.querySelector('i').classList.replace('fas', 'far');
                        }

                        const favoriteBadge = document.querySelector('.favorite-badge');
                        favoriteBadge.textContent = data.favorite_count;
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