<?php
session_start();
require 'db_connect.php';
$conn = create_connection();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order_id'])) {
    $orderId = intval($_POST['confirm_order_id']);
    $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
}
$stmt = $conn->prepare("
  INSERT INTO revenue (month, total_revenue)
  SELECT DATE_FORMAT(order_date, '%Y-%m') AS month, SUM(total_amount)
  FROM orders
  WHERE status = 'completed'
  GROUP BY month
  ON DUPLICATE KEY UPDATE total_revenue = VALUES(total_revenue)
");
$stmt->execute();


$result = $conn->query("SELECT * FROM orders ORDER BY order_date DESC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý đơn hàng</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
   <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"
    />
</head>
<body>
  <div class="container mt-5">
    <a href="index.php"><i class="bi bi-arrow-left"></i> Quay lại</a>
    <h2>Danh sách đơn hàng</h2>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>ID</th>
          <th>Người nhận</th>
          <th>SĐT</th>
          <th>Địa chỉ</th>
          <th>Tổng tiền</th>
          <th>Ngày đặt</th>
          <th>Trạng thái</th>
          <th>Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php while($order = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $order['id'] ?></td>
          <td><?= htmlspecialchars($order['recipient_name']) ?></td>
          <td><?= htmlspecialchars($order['phone']) ?></td>
          <td><?= htmlspecialchars($order['address']) ?></td>
          <td><?= number_format($order['total_amount'], 0, ',', '.') ?>đ</td>
          <td><?= $order['order_date'] ?></td>
          <td>
            <span class="badge <?= $order['status'] === 'completed' ? 'bg-success' : 'bg-warning' ?>">
              <?= ucfirst($order['status']) ?>
            </span>
          </td>
          <td>
            <?php if ($order['status'] === 'pending'): ?>
              <form method="post" onsubmit="return confirm('Bạn có chắc muốn xác nhận đơn hàng này?');">
                <input type="hidden" name="confirm_order_id" value="<?= $order['id'] ?>">
                <button type="submit" class="btn btn-sm btn-primary">Xác nhận thanh toán</button>
              </form>
            <?php else: ?>
              <span class="text-muted">Đã hoàn tất</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</body>
</html>
