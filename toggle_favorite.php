<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'is_favorited' => false, 'favorite_count' => 0];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Vui lòng đăng nhập để thêm sản phẩm vào danh sách yêu thích.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

    if (!$product_id) {
        $response['message'] = 'Sản phẩm không hợp lệ.';
        echo json_encode($response);
        exit;
    }

    try {
        // Check if product exists
        $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        if (!$stmt->fetch()) {
            $response['message'] = 'Sản phẩm không tồn tại.';
            echo json_encode($response);
            exit;
        }

        // Check if already favorited
        $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $product_id]);
        $is_favorited = $stmt->fetch();

        if ($is_favorited) {
            // Remove from favorites
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
            $response['message'] = 'Đã xóa sản phẩm khỏi danh sách yêu thích.';
            $response['is_favorited'] = false;

            // Update session
            if (isset($_SESSION['favorites'])) {
                $_SESSION['favorites'] = array_diff($_SESSION['favorites'], [$product_id]);
            }
        } else {
            // Add to favorites
            $stmt = $pdo->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
            $response['message'] = 'Đã thêm sản phẩm vào danh sách yêu thích.';
            $response['is_favorited'] = true;

            // Update session
            $_SESSION['favorites'][] = $product_id;
        }

        // Get favorite count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $response['favorite_count'] = $stmt->fetchColumn();

        $response['success'] = true;
    } catch (PDOException $e) {
        $response['message'] = 'Lỗi cơ sở dữ liệu. Vui lòng thử lại.';
        file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Toggle Favorite Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

echo json_encode($response);
?>