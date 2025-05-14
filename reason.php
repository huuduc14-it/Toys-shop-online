<?php
session_start();
require_once "db_connect.php";
$conn = create_connection();

$sql = "
    SELECT r.id AS request_id, r.order_id, r.reason, r.status, r.request_date, o.total_amount, u.username
    FROM request r
    JOIN orders o ON r.order_id = o.id
    JOIN users u ON o.user_id = u.id
    ORDER BY r.request_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}

$stmt->close();

// Cập nhật trạng thái yêu cầu hoàn tiền
if (isset($_GET['action']) && isset($_GET['request_id'])) {
    $action = $_GET['action'];
    $requestId = $_GET['request_id'];

    if ($action == 'approve' || $action == 'reject') {
        // Cập nhật trạng thái vào database
        $newStatus = ($action == 'approve') ? 'approved' : 'rejected';
        $updateSql = "UPDATE request SET status = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("si", $newStatus, $requestId);
        $updateStmt->execute();
        $updateStmt->close();

        // Chuyển hướng lại trang để thấy thay đổi
        header("Location: reason.php");
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách yêu cầu hoàn tiền</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial; padding: 20px; background: #f9f9f9; }
        h2 { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 40px; background: #fff; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; vertical-align: top; }
        th { background-color: #f2f2f2; }
        .request-status { font-weight: bold; }
        .status-pending { color: orange; }
        .status-approved { color: green; }
        .status-rejected { color: red; }
        .action-btn {
            padding: 5px 10px;
            margin: 5px;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
        .approve-btn { background-color: #28a745; }
        .reject-btn { background-color: #dc3545; }
        .approve-btn:hover { background-color: #218838; }
        .reject-btn:hover { background-color: #c82333; }
    </style>
</head>
<body>
<a href="index.php" class="btn btn-danger"><i class="bi bi-arrow-left"></i> Quay lại<a></a>
<h2>Danh sách yêu cầu hoàn tiền</h2>

<?php
if (!empty($requests)) {
    echo '<table>';
    echo '<tr><th>Đơn hàng ID</th><th>Lý do hoàn tiền</th><th>Tình trạng</th><th>Ngày yêu cầu</th><th>Tổng tiền</th><th>Hành động</th></tr>';
    foreach ($requests as $request) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($request['order_id']) . '</td>';
        echo '<td>' . htmlspecialchars($request['reason']) . '</td>';
        echo '<td class="request-status ' . strtolower($request['status']) . '">' . htmlspecialchars($request['status']) . '</td>';
        echo '<td>' . htmlspecialchars($request['request_date']) . '</td>';
        echo '<td>' . number_format($request['total_amount'], 0, ',', '.') . ' VNĐ</td>';
        echo '<td>';
        if ($request['status'] == 'pending') {
            echo '<a href="?action=approve&request_id=' . $request['request_id'] . '" class="action-btn approve-btn">Duyệt</a>';
            echo '<a href="?action=reject&request_id=' . $request['request_id'] . '" class="action-btn reject-btn">Từ chối</a>';
        }
        echo '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p>Chưa có yêu cầu hoàn tiền nào trong hệ thống.</p>';
}
?>

</body>
</html>
