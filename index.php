<?php
ob_start();
require_once 'db_connect.php';
session_start();
$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
$role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

$conn = create_connection();

// Log function for debugging
function logError($message) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Index Error: $message\n", FILE_APPEND);
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

// Fetch featured products
try {
    $stmt = $pdo->query("SELECT id, name, description, price, image FROM products WHERE is_featured = TRUE AND stock > 0 LIMIT 8");
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    logError("Featured Products DB Error: " . $e->getMessage());
    $_SESSION['errors'] = ["Lỗi cơ sở dữ liệu khi tải sản phẩm nổi bật. Vui lòng thử lại."];
    $featured_products = [];
}

// Calculate cart and favorite counts for badges
$cart_count = isset($_SESSION['cart']) && !empty($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
$favorite_count = isset($_SESSION['favorites']) && !empty($_SESSION['favorites']) ? count(array_unique($_SESSION['favorites'])) : 0;

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Truy vấn số lượng đơn hàng với trạng thái 'pending'
    $stmt = $conn->prepare("SELECT COUNT(*) AS pending_count FROM orders WHERE status = 'pending'");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $row = $result->fetch_assoc();
    $pendingCount = $row['pending_count'];
} else {
    $pendingCount = 0; // Nếu chưa đăng nhập, không có đơn hàng pending
}
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Truy vấn số lượng yêu cầu hoàn tiền đã được chấp nhận của user hiện tại
    $stmt = $conn->prepare("SELECT COUNT(*) AS approved_count FROM request WHERE status = 'approved' AND user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $row = $result->fetch_assoc();
    $approvedCount = $row['approved_count'];
} else {
    $approvedCount = 0;
}
?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>KidsToyLand - Cửa hàng đồ chơi trẻ em</title>
        <!-- Bootstrap CSS -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
        <!-- Font Awesome Icons -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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

            .btn-secondary {
                background-color: var(--secondary-color);
                border-color: var(--secondary-color);
            }

            .btn-secondary:hover {
                background-color: #3dbdb5;
                border-color: #3dbdb5;
            }

            /* Hero Section */
            .hero-section {
                background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('/api/placeholder/1200/500') no-repeat center center;
                background-size: cover;
                padding: 100px 0;
                color: #fff;
            }

            .hero-content h1 {
                font-size: 3rem;
                font-weight: 800;
                margin-bottom: 1.5rem;
            }

            /* Category Section */
            .category-card {
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
                transition: all 0.3s ease;
            }

            .category-card:hover {
                transform: translateY(-10px);
                box-shadow: 0 15px 25px rgba(0, 0, 0, 0.1);
            }

            .category-icon {
                font-size: 2.5rem;
                color: var(--primary-color);
            }

            /* Featured Products */
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

            .favorite-btn.favorited i {
                color: var(--primary-color);
            }

            /* Product Card Footer */
            .card-footer {
                display: flex;
                gap: 10px;
                align-items: center;
                justify-content: space-between;
            }

            .card-footer .favorite-btn {
                flex: 0 0 auto;
            }

            .card-footer .add-to-cart-form {
                flex: 1;
                display: flex;
                gap: 10px;
                align-items: center;
            }

            .card-footer .quantity-input {
                width: 60px;
            }

            /* Product Slider */
            .product-slider {
                overflow-x: hidden;
                scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
            }

            .product-slider .row {
                flex-wrap: nowrap;
                margin: 0;
            }

            /* Hide scrollbar */
            .product-slider::-webkit-scrollbar {
                display: none;
            }

            .product-slider {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }

            /* Disabled button state */
            .slider-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            /* Testimonials */
            .testimonial-section {
                background-color: #f8f9fa;
            }

            .testimonial-card {
                background: white;
                border-radius: 15px;
                padding: 30px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            }

            .testimonial-text {
                font-style: italic;
                margin-bottom: 1rem;
            }

            .testimonial-rating {
                color: #FFD700;
                margin-bottom: 1rem;
            }

            .testimonial-author {
                display: flex;
                align-items: center;
            }

            .testimonial-author img {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                margin-right: 15px;
            }

            /* Newsletter */
            .newsletter-section {
                background-color: var(--secondary-color);
                color: white;
            }

            .newsletter-form .form-control {
                border-radius: 50px 0 0 50px;
                border: none;
                height: 50px;
            }

            .newsletter-form .btn {
                border-radius: 0 50px 50px 0;
                background-color: var(--primary-color);
                border-color: var(--primary-color);
                height: 50px;
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
                           
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'users'): ?>
                                 
                                 <a href="history.php" class="text-white me-3">Lịch sử mua hàng</a>
                                
                            <?php endif; ?>
                            
                           <?php if (isset($_SESSION['user_id']) && isset($_SESSION['user_name'])): ?>
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
                            <a class="nav-link active" href="index.php">Trang chủ</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link" href="products.php">
                                Sản phẩm
                            </a>
                            <!-- <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="#">Đồ chơi cho bé trai</a></li>
                                <li><a class="dropdown-item" href="#">Đồ chơi cho bé gái</a></li>
                                <li><a class="dropdown-item" href="#">Đồ chơi học tập</a></li>
                                <li><a class="dropdown-item" href="#">Đồ chơi vận động</a></li>
                                <li><a class="dropdown-item" href="#">Đồ chơi xếp hình</a></li>
                            </ul> -->
                        </li>
                        <!-- <li class="nav-item">
                            <a class="nav-link" href="#">Khuyến mãi</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Tin tức</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#">Về chúng tôi</a>
                        </li> -->
                        <li class="nav-item">
                            <a class="nav-link" href="#footer">Liên hệ</a>
                        </li>
                    </ul>
                    <div class="d-flex align-items-center">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'users'): ?>
                                <a href="notice.php" class="btn btn-primary m-2">
                                    <i class="bi bi-chat-dots"></i> <span class="badge bg-danger"><?= $approvedCount ?></span></a>
                                </a>

                            <?php endif; ?>
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
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 hero-content">
                        <h1>Khám phá thế giới đồ chơi kỳ diệu</h1>
                        <p class="lead mb-4">Chúng tôi mang đến những món đồ chơi chất lượng, an toàn và đầy sáng tạo để phát triển kỹ năng cho con bạn.</p>
                        <div class="d-flex gap-3">
                            <a href="#" class="btn btn-primary btn-lg">Mua sắm ngay</a>
                            <a href="#" class="btn btn-outline-light btn-lg">Xem thêm</a>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <img src="images/hero_image.png" alt="Kids with toys" class="img-fluid rounded">
                    </div>
                </div>
            </div>
        </section>
        <!-- Chức năng admin -->
        <section>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
          <a href="Chart.php" class="btn btn-primary p-3 font-extrabold m-2">View reveneu</a>
          <a href="ManageStaff.php" class="btn btn-primary p-3 font-extrabold m-2" style="width: 120px;">Staff list</a>
          <a href="ManageProduct.php" class="btn btn-primary p-3 font-extrabold m-2" style="width: 120px;">Store</a>
      <?php endif; ?>
        </section>
         <!-- Chức năng staff -->
        <section>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
          <a href="confirm.php" class="btn btn-primary p-3 font-extrabold m-3">Confirm payment  <span class="badge bg-danger"><?= $pendingCount ?></span></a>
          <a href="reason.php" class="btn btn-primary p-3 font-extrabold ">Verify reason</a>
      <?php endif; ?>
        </section>
        <!-- Categories Section -->
        <section class="py-5">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="fw-bold">Danh mục sản phẩm</h2>
                    <p class="text-muted">Khám phá đa dạng các loại đồ chơi phù hợp với mọi lứa tuổi và sở thích</p>
                </div>
                <div class="row g-4">
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="category-card text-center p-3">
                            <div class="mb-3">
                                <i class="fas fa-car category-icon"></i>
                            </div>
                            <h5 class="mb-0">Đồ chơi xe</h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="category-card text-center p-3">
                            <div class="mb-3">
                                <i class="fas fa-baby category-icon"></i>
                            </div>
                            <h5 class="mb-0">Đồ chơi cho bé</h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="category-card text-center p-3">
                            <div class="mb-3">
                                <i class="fas fa-brain category-icon"></i>
                            </div>
                            <h5 class="mb-0">Đồ chơi học tập</h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="category-card text-center p-3">
                            <div class="mb-3">
                                <i class="fas fa-robot category-icon"></i>
                            </div>
                            <h5 class="mb-0">Đồ chơi robot</h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="category-card text-center p-3">
                            <div class="mb-3">
                                <i class="fas fa-puzzle-piece category-icon"></i>
                            </div>
                            <h5 class="mb-0">Đồ chơi ghép hình</h5>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="category-card text-center p-3">
                            <div class="mb-3">
                                <i class="fas fa-gamepad category-icon"></i>
                            </div>
                            <h5 class="mb-0">Đồ chơi điện tử</h5>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Products -->
        <section class="py-5 bg-light">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold mb-0">Sản phẩm nổi bật</h2>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-outline-secondary slider-btn slider-prev" data-slider="productSlider"><i class="fas fa-chevron-left"></i></button>
                        <button class="btn btn-sm btn-primary slider-btn slider-next" data-slider="productSlider"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
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
                <?php if (empty($featured_products)): ?>
                    <p class="text-center">Hiện tại không có sản phẩm nổi bật nào.</p>
                <?php else: ?>
                    <div class="product-slider" id="productSlider">
                        <div class="row g-4">
                            <?php foreach ($featured_products as $product): ?>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="card product-card h-100">
                                        <?php if ($product['id'] % 2 == 0): ?>
                                            <span class="badge-sale">Giảm 20%</span>
                                        <?php endif; ?>
                                        <a href="product_detail.php?id=<?php echo $product['id']; ?>">
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        </a>
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
                                        <div class="card-footer bg-white border-top-0">
                                            <form class="add-to-cart-form">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="number" name="quantity" value="1" min="1" class="form-control quantity-input">
                                                <button type="submit" name="add_to_cart" class="btn btn-sm btn-primary"><i class="fas fa-shopping-cart me-1"></i> Thêm vào giỏ</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="text-center mt-4">
                    <a href="products.php" class="btn btn-outline-primary">Xem tất cả sản phẩm</a>
                </div>
            </div>
        </section>

        <!-- Promo Banner -->
        <!-- <section class="py-5">
            <div class="container">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card bg-primary text-white overflow-hidden">
                            <div class="row g-0">
                                <div class="col-5">
                                    <img src="/api/placeholder/300/300" class="img-fluid rounded-start h-100 object-fit-cover" alt="Đồ chơi cho bé trai">
                                </div>
                                <div class="col-7">
                                    <div class="card-body">
                                        <h4 class="card-title fw-bold">Đồ chơi cho bé trai</h4>
                                        <p class="card-text">Khám phá bộ sưu tập đồ chơi đặc biệt dành cho các bé trai.</p>
                                        <a href="#" class="btn btn-light">Mua ngay</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-secondary text-white overflow-hidden">
                            <div class="row g-0">
                                <div class="col-5">
                                    <img src="/api/placeholder/300/300" class="img-fluid rounded-start h-100 object-fit-cover" alt="Đồ chơi cho bé gái">
                                </div>
                                <div class="col-7">
                                    <div class="card-body">
                                        <h4 class="card-title fw-bold">Đồ chơi cho bé gái</h4>
                                        <p class="card-text">Những món đồ chơi xinh xắn và thú vị dành cho các công chúa nhỏ.</p>
                                        <a href="#" class="btn btn-light">Mua ngay</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section> -->

        <!-- Testimonials Section -->
        <section class="testimonial-section py-5">
            <div class="container">
                <div class="text-center mb-5">
                    <h2 class="fw-bold">Khách hàng nói gì về chúng tôi</h2>
                    <p class="text-muted">Niềm tin của khách hàng là động lực để chúng tôi phát triển</p>
                </div>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="testimonial-card h-100">
                            <div class="testimonial-rating mb-2">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <p class="testimonial-text">"Đồ chơi ở đây rất chất lượng và an toàn. Con tôi rất thích những món đồ chơi giáo dục mà tôi đã mua. Nhân viên cửa hàng cũng rất thân thiện và tư vấn nhiệt tình."</p>
                            <div class="testimonial-author">
                                <!-- <img src="/api/placeholder/50/50" alt="Avatar"> -->
                                <div>
                                    <h6 class="mb-0">Nguyễn Thị Hương</h6>
                                    <small class="text-muted">Khách hàng thân thiết</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="testimonial-card h-100">
                            <div class="testimonial-rating mb-2">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star-half-alt"></i>
                            </div>
                            <p class="testimonial-text">"Rất hài lòng với dịch vụ giao hàng nhanh chóng. Đồ chơi được đóng gói cẩn thận và đúng như mô tả trên website. Sẽ tiếp tục mua hàng ở đây trong tương lai."</p>
                            <div class="testimonial-author">
                                <!-- <img src="/api/placeholder/50/50" alt="Avatar"> -->
                                <div>
                                    <h6 class="mb-0">Trần Văn Minh</h6>
                                    <small class="text-muted">Khách hàng mới</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="testimonial-card h-100">
                            <div class="testimonial-rating mb-2">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                            <p class="testimonial-text">"Cửa hàng có nhiều sản phẩm đa dạng, phù hợp với nhiều lứa tuổi khác nhau. Tôi đặc biệt thích những món đồ chơi STEM giúp bé phát triển tư duy logic và sáng tạo."</p>
                            <div class="testimonial-author">
                                <!-- <img src="/api/placeholder/50/50" alt="Avatar"> -->
                                <div>
                                    <h6 class="mb-0">Lê Thị Thanh</h6>
                                    <small class="text-muted">Giáo viên mầm non</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Newsletter Section -->
        <section class="newsletter-section py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 text-center">
                        <h3 class="text-white mb-3">Đăng ký nhận thông tin khuyến mãi</h3>
                        <p class="text-white-50 mb-4">Nhận ngay voucher giảm giá 10% cho lần mua hàng đầu tiên khi đăng ký nhận bản tin!</p>
                        <form class="newsletter-form">
                            <div class="input-group mb-3">
                                <input type="email" class="form-control" placeholder="Nhập email của bạn">
                                <button class="btn btn-primary px-4" type="submit">Đăng ký</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer id="footer "class="footer bg-dark">
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

        // Handle Product Slider
        document.querySelectorAll('.slider-btn').forEach(button => {
            button.addEventListener('click', () => {
                const sliderId = button.getAttribute('data-slider');
                const slider = document.getElementById(sliderId);
                const card = slider.querySelector('.product-card');
                if (!card) return;

                const cardWidth = card.offsetWidth + parseInt(getComputedStyle(card).marginRight);
                const scrollAmount = cardWidth * 2; // Scroll by 2 cards
                const maxScroll = slider.scrollWidth - slider.clientWidth;

                if (button.classList.contains('slider-prev')) {
                    slider.scrollLeft -= scrollAmount;
                } else {
                    slider.scrollLeft += scrollAmount;
                }

                // Update button states
                const prevButton = slider.parentElement.querySelector('.slider-prev');
                const nextButton = slider.parentElement.querySelector('.slider-next');
                prevButton.disabled = slider.scrollLeft <= 0;
                nextButton.disabled = slider.scrollLeft >= maxScroll - 1;
            });
        });

        // Initialize button states on page load
        document.querySelectorAll('.product-slider').forEach(slider => {
            const prevButton = slider.parentElement.querySelector('.slider-prev');
            const nextButton = slider.parentElement.querySelector('.slider-next');
            const maxScroll = slider.scrollWidth - slider.clientWidth;
            prevButton.disabled = true;
            nextButton.disabled = maxScroll <= 0;

            // Update button states on scroll
            slider.addEventListener('scroll', () => {
                prevButton.disabled = slider.scrollLeft <= 0;
                nextButton.disabled = slider.scrollLeft >= maxScroll - 1;
            });
        });
    </script>
    </body>
    </html>
<?php
ob_end_flush();
?>