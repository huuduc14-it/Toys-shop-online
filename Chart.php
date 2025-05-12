<?php
require_once('db_connect.php');
$conn = create_connection();

$sql_years = "SELECT DISTINCT LEFT(month, 4) AS year FROM revenue WHERE LEFT(month, 4) >= '2024' ORDER BY year";
$result_years = $conn->query($sql_years);

$years = [];
while ($row = $result_years->fetch_assoc()) {
    $years[] = $row['year'];
}

$selected_year = isset($_GET['year']) ? $_GET['year'] : $years[0];

$sql_data = "SELECT month, total_revenue FROM revenue WHERE LEFT(month, 4) = '$selected_year' ORDER BY month";
$result_data = $conn->query($sql_data);

$labels = [];
$values = [];
while ($row = $result_data->fetch_assoc()) {
    $month = (int)substr($row['month'], 5, 2); 
    $labels[] = "Tháng " . $month;
    $revenue = (float)$row['total_revenue'];
    $values[] = $revenue;
    $colors[] = $revenue < 50000000 ? '#ff4d4d' : '#4dc9f6';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Biểu đồ doanh thu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"
    />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        #chart-container {
            width: 100%;
            max-width: 900px;
            margin: 50px auto;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
 <a href="index.php" class="btn btn-danger m-2"><i class="bi bi-arrow-left"></i> Quay lại</a>
<div class="container mt-5">
    <form method="GET" class="mb-4">
        <label for="year" class="form-label">Chọn năm:</label>
        <select id="year" name="year" class="form-select w-25" onchange="this.form.submit()">
            <?php foreach ($years as $year): ?>
                <option value="<?= $year ?>" <?= $year == $selected_year ? 'selected' : '' ?>><?= $year ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <h1>Doanh thu năm <?= htmlspecialchars($selected_year) ?></h1>

    <div id="chart-container">
        <canvas id="revenueChart"></canvas>
        <div class="text-center mt-3">
    <span><strong>Chú thích:</strong></span>
    <span class="ms-2">
        <span style="display:inline-block;width:20px;height:20px;background-color:#4dc9f6;margin-right:5px;"></span>
        Doanh thu từ 50 triệu trở lên
    </span>
    <span class="ms-3">
        <span style="display:inline-block;width:20px;height:20px;background-color:#ff4d4d;margin-right:5px;"></span>
        Doanh thu dưới 50 triệu
    </span>
</div>

    </div>
</div>

<script>
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [{
                label: 'Doanh thu',
                data: <?= json_encode($values) ?>,
                backgroundColor: <?= json_encode($colors) ?>,
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('vi-VN') + ' đ';
                        }
                    }
                }
            }
        }
    });
</script>
</body>
</html>
