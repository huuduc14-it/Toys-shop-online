<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;

    if (!$product_id) {
        $response['message'] = 'Sản phẩm không hợp lệ.';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if product exists and has stock
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ? AND stock > 0");
        $stmt->execute([$product_id]);
        if (!$stmt->fetch()) {
            $response['message'] = 'Sản phẩm không tồn tại hoặc đã hết hàng.';
            echo json_encode($response);
            exit;
        }

        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Update database
        $stmt = $pdo->prepare("
            INSERT INTO cart_items (user_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + ?
        ");
        $stmt->execute([$_SESSION['user_id'], $product_id, $quantity, $quantity]);

        // Update session
        $_SESSION['cart'][$product_id] = ($_SESSION['cart'][$product_id] ?? 0) + $quantity;

        $response['success'] = true;
        $response['message'] = 'Đã thêm sản phẩm vào giỏ hàng.';
        $response['cart_count'] = array_sum($_SESSION['cart']);
    } catch (PDOException $e) {
        $response['message'] = 'Lỗi cơ sở dữ liệu. Vui lòng thử lại.';
        file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Add to Cart Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

echo json_encode($response);
?>