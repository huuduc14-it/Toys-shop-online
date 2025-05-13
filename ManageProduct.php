<?php
session_start();

// Kết nối CSDL
$conn = new mysqli("localhost", "root", "", "kidstoyland"); // ⚠️ Đổi "tendatabase" thành tên thật

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thêm sản phẩm
if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;

    // ✅ Xử lý ảnh upload
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true); // Tạo thư mục nếu chưa có
    }

    $imageName = basename($_FILES["image"]["name"]);
    $targetFile = $targetDir . uniqid() . "_" . $imageName;
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Kiểm tra loại file
    if ($imageFileType != "jpg" && $imageFileType != "jpeg") {
        die("Chỉ cho phép file JPG.");
    }

    // Upload file
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        die("Lỗi khi upload ảnh.");
    }

    // Lưu đường dẫn ảnh vào DB
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, image, stock, is_featured) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdsii", $name, $description, $price, $targetFile, $stock, $is_featured);
    $stmt->execute();
    $stmt->close();
}


// Xóa sản phẩm
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM products WHERE id = $id");
}

// Lấy danh sách sản phẩm
$result = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
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
    <title>Quản lý Sản phẩm</title>
</head>
<body>
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
                            <a href="#" class="text-white me-3"><i class="fas fa-truck me-1"></i> Theo dõi đơn hàng</a>
                            <a href="#" class="text-white me-3"><i class="fas fa-map-marker-alt me-1"></i> Cửa hàng gần bạn</a>
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
        <header>
            <a href="index.php" class="btn btn-danger m-2"><i class="bi bi-arrow-left"></i> Quay lại</a>
    <div class="container my-5">
    <div class="row">
        <div class="col-md-6">
            <h3 class="mb-4">Thêm sản phẩm</h3>
            <form method="POST" enctype="multipart/form-data" class="p-4 border rounded shadow-sm bg-light">
                <div class="mb-3">
                    <label class="form-label">Tên sản phẩm</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Giá</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ảnh (JPG/JPEG)</label>
                    <input type="file" name="image" accept="image/jpeg" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Số lượng</label>
                    <input type="number" name="stock" class="form-control" required>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" name="is_featured" class="form-check-input" id="is_featured">
                    <label class="form-check-label" for="is_featured">Nổi bật</label>
                </div>
                <button type="submit" name="add" class="btn btn-primary w-100">Thêm sản phẩm</button>
            </form>
        </div>

        <div class="col-md-6">
            <h3 class="mb-4">Danh sách sản phẩm</h3>
            <div class="table-responsive">
                <table class="table table-bordered align-middle shadow-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Tên</th>
                            <th>Giá</th>
                            <th>Ảnh</th>
                            <th>Số lượng</th>
                            <th>Nổi bật</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= number_format($row['price'], 0, ',', '.') ?>₫</td>
                            <td><img src="<?= htmlspecialchars($row['image']) ?>" class="img-thumbnail" style="width: 50px; height: 50px;"></td>
                            <td><?= $row['stock'] ?></td>
                            <td><?= $row['is_featured'] ? '<span class="badge bg-success">Có</span>' : '<span class="badge bg-secondary">Không</span>' ?></td>
                            <td>
                                <a href="?delete=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa?')">
                                    <i class="fas fa-trash-alt"></i> Xóa
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
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

</body>
</html>
