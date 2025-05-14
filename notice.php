<?php
session_start();
require_once "db_connect.php";
$conn = create_connection();

if (!isset($_SESSION['user_id'])) {
    echo "Vui lòng đăng nhập để xem thông báo.";
    exit;
}

$userId = $_SESSION['user_id'];
$sql = "
    SELECT r.id AS request_id, r.order_id, r.status, r.request_date
    FROM request r
    WHERE r.user_id = ? AND r.status = 'approved'
    ORDER BY r.request_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$approvedRequests = [];
while ($row = $result->fetch_assoc()) {
    $approvedRequests[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông báo hoàn tiền</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial; padding: 20px; background-color: #f9f9f9; }
        h2 { margin-bottom: 20px; }
        .notice {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .no-notice {
            color: #888;
        }
    </style>
</head>
<body>

<a href="index.php" class="btn btn-danger"><i class="bi bi-arrow-left"></i> Quay lại</a>
<h2>Thông báo hoàn tiền đã được chấp nhận</h2>

<?php
if (!empty($approvedRequests)) {
    foreach ($approvedRequests as $request) {
        echo '<div class="notice">';
        echo 'Đơn hàng của bạn với mã yêu cầu <strong>#' . htmlspecialchars($request['request_id']) . '</strong> đã được <strong>chấp nhận hoàn tiền</strong> vào ngày <strong>' . htmlspecialchars($request['request_date']) . '</strong>.';
        echo '</div>';
    }
} else {
    echo '<p class="no-notice">Bạn chưa có yêu cầu hoàn tiền nào được chấp nhận.</p>';
}
?>

</body>
</html>
