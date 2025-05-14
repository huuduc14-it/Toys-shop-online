<?php
ob_start();
require_once 'db_connect.php';
session_start();

// Log function for debugging
function logError($message) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Cart Error: $message\n", FILE_APPEND);
}

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
    // Nếu người dùng đã đăng nhập, khôi phục giỏ hàng từ cơ sở dữ liệu
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT product_id, quantity FROM cart_items WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $_SESSION['cart'][$row['product_id']] = $row['quantity'];
            }
        } catch (PDOException $e) {
            logError("Cart Restore DB Error: " . $e->getMessage());
        }
    }
}

// Handle remove item
if (isset($_GET['remove'])) {
    $product_id = filter_var($_GET['remove'], FILTER_SANITIZE_NUMBER_INT);
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        // Xóa sản phẩm khỏi cart_items
        if (isset($_SESSION['user_id'])) {
            try {
                $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$_SESSION['user_id'], $product_id]);
            } catch (PDOException $e) {
                logError("DB Error on Remove Item: " . $e->getMessage());
            }
        }
    }
    header("Location: cart.php");
    exit();
}

// Handle update quantities
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        $quantity = filter_var($quantity, FILTER_SANITIZE_NUMBER_INT);
        $product_id = filter_var($product_id, FILTER_SANITIZE_NUMBER_INT);
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
            // Xóa sản phẩm khỏi cart_items
            if (isset($_SESSION['user_id'])) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
                    $stmt->execute([$_SESSION['user_id'], $product_id]);
                } catch (PDOException $e) {
                    logError("DB Error on Remove Item: " . $e->getMessage());
                }
            }
        } else {
            $_SESSION['cart'][$product_id] = $quantity;
            // Cập nhật số lượng trong cart_items
            if (isset($_SESSION['user_id'])) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO cart_items (user_id, product_id, quantity)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE quantity = ?
                    ");
                    $stmt->execute([$_SESSION['user_id'], $product_id, $quantity, $quantity]);
                } catch (PDOException $e) {
                    logError("DB Error on Update Quantity: " . $e->getMessage());
                }
            }
        }
    }
    $_SESSION['success'] = "Giỏ hàng đã được cập nhật.";
    header("Location: cart.php");
    exit();
}

// Fetch product details from database
$cart_items = [];
$total = 0;
if (!empty($_SESSION['cart'])) {
    try {
        $placeholders = implode(',', array_fill(0, count($_SESSION['cart']), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price, image FROM products WHERE id IN ($placeholders)");
        $stmt->execute(array_keys($_SESSION['cart']));
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as $product) {
            $quantity = $_SESSION['cart'][$product['id']];
            $subtotal = $product['price'] * $quantity;
            $total += $subtotal;
            $cart_items[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $quantity,
                'subtotal' => $subtotal
            ];
        }
    } catch (PDOException $e) {
        logError("DB Error: " . $e->getMessage());
        $_SESSION['errors'] = ["Lỗi cơ sở dữ liệu. Vui lòng thử lại."];
    }
}
?>
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Giỏ hàng - KidsToyLand</title>
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

            .cart-section {
                padding: 100px 0;
                background-color: var(--light-color);
            }

            .cart-card {
                max-width: 1000px;
                margin: 0 auto;
                background: white;
                border-radius: 15px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                padding: 40px;
            }

            .cart-card h2 {
                font-weight: 800;
                color: var(--text-color);
                margin-bottom: 1.5rem;
            }

            .table {
                margin-bottom: 2rem;
            }

            .table img {
                width: 80px;
                height: 80px;
                object-fit: cover;
                border-radius: 5px;
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
                background-color: #3db8b0;
                border-color: #3db8b0;
            }

            .btn-danger {
                background-color: #dc3545;
                border-color: #dc3545;
            }

            .btn-danger:hover {
                background-color: #c82333;
                border-color: #c82333;
            }

            .form-control.quantity-input {
                width: 80px;
                display: inline-block;
            }

            .total-section {
                font-size: 1.2rem;
                font-weight: 700;
                text-align: right;
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
        <!-- Cart Section -->
        <section class="cart-section">
            
            <div class="container">
                <a href="index.php" class="btn btn-danger"><i class="bi bi-arrow-left"></i> Quay lại<a></a>
                <div class="cart-card">
                    <h2 class="text-center">Giỏ hàng của bạn</h2>
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
                    <?php if (empty($cart_items)): ?>
                        <p class="text-center">Giỏ hàng của bạn đang trống.</p>
                        <div class="text-center mt-4">
                            <a href="products.php" class="btn btn-secondary">Tiếp tục mua hàng</a>
                        </div>
                    <?php else: ?>
                        <form action="cart.php" method="POST">
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Giá</th>
                                    <th>Số lượng</th>
                                    <th>Tổng</th>
                                    <th>Hành động</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </td>
                                        <td><?php echo number_format($item['price'], 0, ',', '.'); ?> VND</td>
                                        <td>
                                            <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="form-control quantity-input">
                                        </td>
                                        <td><?php echo number_format($item['subtotal'], 0, ',', '.'); ?> VND</td>
                                        <td>
                                            <a href="cart.php?remove=<?php echo $item['id']; ?>" class="btn btn-danger btn-sm">
                                                <i class="fas fa-trash"></i> Xóa
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <div class="total-section">
                                Tổng cộng: <?php echo number_format($total, 0, ',', '.'); ?> VND
                            </div>
                            <div class="text-end mt-4">
                                <button type="submit" name="update_cart" class="btn btn-primary">Cập nhật giỏ hàng</button>
                                <a href="checkout.php" class="btn btn-primary">Thanh toán</a>
                            </div>
                        </form>
                        <div class="text-center mt-4">
                            <a href="products.php" class="btn btn-secondary">Tiếp tục mua hàng</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
<?php
ob_end_flush();
?>