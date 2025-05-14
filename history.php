<?php
session_start();
require_once "db_connect.php";
$conn = create_connection();

if (!isset($_SESSION['user_id'])) {
    echo "Vui lòng đăng nhập để xem lịch sử mua hàng.";
    exit;
}

$userId = $_SESSION['user_id'];

// Xử lý yêu cầu hoàn tiền
$refundSuccess = null;
$refundError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['reason'])) {
    $orderId = intval($_POST['order_id']);
    $reason = trim($_POST['reason']);

    $check = $conn->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ? AND status = 'completed'");
    $check->bind_param("ii", $orderId, $userId);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $checkRequest = $conn->prepare("SELECT id FROM request WHERE order_id = ? AND user_id = ?");
        $checkRequest->bind_param("ii", $orderId, $userId);
        $checkRequest->execute();
        $res = $checkRequest->get_result();

        if ($res->num_rows > 0) {
            $refundError = "Bạn đã gửi yêu cầu hoàn tiền cho đơn hàng này rồi.";
        } else {
            $insert = $conn->prepare("INSERT INTO request (user_id, order_id, reason) VALUES (?, ?, ?)");
            $insert->bind_param("iis", $userId, $orderId, $reason);
           if ($insert->execute()) {
                
                header("Location: history.php?success=1");
                exit;
            } else {
                $refundError = "Lỗi khi gửi yêu cầu. Vui lòng thử lại.";
            }

        }
    } else {
        $refundError = "Đơn hàng không hợp lệ hoặc không thuộc quyền sở hữu.";
    }
}

$showRefundFormFor = isset($_GET['refund']) ? intval($_GET['refund']) : null;

$sql = "
    SELECT 
        o.id AS order_id,
        o.order_date,
        o.total_amount,
        o.recipient_name,
        o.phone,
        o.address,
        p.name AS product_name,
        p.image,
        oi.quantity,
        oi.price
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ? AND o.status = 'completed'
    ORDER BY o.order_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$orderHistory = [];
while ($row = $result->fetch_assoc()) {
    $orderId = $row['order_id'];
    if (!isset($orderHistory[$orderId])) {
        $orderHistory[$orderId] = [
            'order_date' => $row['order_date'],
            'total_amount' => $row['total_amount'],
            'recipient_name' => $row['recipient_name'],
            'phone' => $row['phone'],
            'address' => $row['address'],
            'items' => []
        ];
    }
    $orderHistory[$orderId]['items'][] = [
        'product_name' => $row['product_name'],
        'image' => $row['image'],
        'quantity' => $row['quantity'],
        'price' => $row['price']
    ];
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch sử mua hàng</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial; padding: 20px; background: #f9f9f9; }
        h2 { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; background: #fff; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; }
        img { width: 60px; height: 60px; object-fit: cover; border: 1px solid #ccc; }
        .order-header { background-color: #e9ecef; font-weight: bold; }
        .refund-btn, .submit-btn {
            margin-top: 10px;
            padding: 8px 12px;
            background-color: #dc3545;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .refund-btn:hover, .submit-btn:hover {
            background-color: #c82333;
        }
        .refund-form {
            margin-top: 10px;
            background-color: #fff3f3;
            padding: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }
        textarea {
            width: 100%;
            height: 80px;
            padding: 8px;
            margin-top: 8px;
        }
        .message {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
<a href="index.php" class="btn btn-danger"><i class="bi bi-arrow-left"></i> Quay lại<a></a>
<h2>Lịch sử mua hàng đã hoàn tất</h2>

<?php if ($refundSuccess !== null) : ?>
    <div class="message success"><?= htmlspecialchars($refundSuccess) ?></div>
<?php elseif ($refundError !== null) : ?>
    <div class="message error"><?= htmlspecialchars($refundError) ?></div>
<?php endif; ?>

<?php if (!empty($orderHistory)) : ?>
    <?php foreach ($orderHistory as $orderId => $order) : ?>
        <table>
            <tr class="order-header">
                <td colspan="6">
                    <strong>Đơn hàng ID:</strong> <?= htmlspecialchars($orderId) ?> |
                    <strong>Ngày đặt:</strong> <?= htmlspecialchars($order['order_date']) ?> |
                    <strong>Người nhận:</strong> <?= htmlspecialchars($order['recipient_name']) ?> |
                    <strong>SĐT:</strong> <?= htmlspecialchars($order['phone']) ?> |
                    <strong>Địa chỉ:</strong> <?= htmlspecialchars($order['address']) ?> |
                    <strong>Tổng tiền:</strong> <?= number_format($order['total_amount'], 0, ',', '.') ?> VNĐ
                    <br>
                    <br>
                    
                    <a class="refund-btn" href="?refund=<?= urlencode($orderId) ?>">Yêu cầu hoàn tiền</a>
                </td>
            </tr>
            <tr>
                <th>Hình ảnh</th>
                <th>Sản phẩm</th>
                <th>Số lượng</th>
                <th>Đơn giá</th>
                <th>Thành tiền</th>
            </tr>
            <?php foreach ($order['items'] as $item) : ?>
                <tr>
                    <td><img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>"></td>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                    <td><?= number_format($item['price'], 0, ',', '.') ?> VNĐ</td>
                    <td><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> VNĐ</td>
                </tr>
            <?php endforeach; ?>
        </table>

        <?php if ($showRefundFormFor === $orderId) : ?>
            <div class="refund-form">
                <form action="history.php" method="post">
                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($orderId) ?>">
                    <label for="reason">Lý do hoàn tiền:</label>
                    <textarea name="reason" id="reason" required></textarea>
                    <br>
                    <button type="submit" class="submit-btn">Gửi yêu cầu</button>
                </form>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
<?php else : ?>
    <p>Không có đơn hàng nào đã hoàn tất.</p>
<?php endif; ?>

</body>
</html>
