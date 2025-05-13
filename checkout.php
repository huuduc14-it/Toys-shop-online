<?php
include 'db_connect.php';
session_start();
$conn = create_connection();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT p.name, p.price, p.image, ci.quantity 
                        FROM cart_items ci 
                        JOIN products p ON ci.product_id = p.id 
                        WHERE ci.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$items = [];

while ($row = $result->fetch_assoc()) {
    $subtotal = $row['price'] * $row['quantity'];
    $total += $subtotal;
    $items[] = $row;
}
$order_successful = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // 1. Thêm vào bảng orders
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, recipient_name, phone, address) 
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idsss", $user_id, $total, $recipient_name, $phone, $address);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // 2. Thêm từng sản phẩm vào order_items
    foreach ($items as $item) {
        $product_name = $item['name'];
        $price = $item['price'];
        $quantity = $item['quantity'];

        // Lấy product_id theo name (hoặc dùng ngay trong vòng while ở bước trên)
        $stmt = $conn->prepare("SELECT id FROM products WHERE name = ?");
        $stmt->bind_param("s", $product_name);
        $stmt->execute();
        $pid_result = $stmt->get_result();
        $pid = $pid_result->fetch_assoc()['id'];

        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price)
                                VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $order_id, $pid, $quantity, $price);
        $stmt->execute();

        // 3. Cập nhật stock
        $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity, $pid);
        $stmt->execute();
    }

    // 4. Xóa giỏ hàng
    unset($_SESSION['cart']);

    $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();


    $order_successful = true;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
     <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css"
    />
    <style>
        body{
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgb(0, 0, 34);
    font-size: 0.8rem;
}
.card{
    max-width: 1000px;
    margin: 2vh;
}
.card-top{
    padding: 0.7rem 5rem;
}
.card-top a{
    float: left;
    margin-top: 0.7rem;
}
#logo{
    font-family: 'Dancing Script';
    font-weight: bold;
    font-size: 1.6rem;
}
.card-body{
    padding: 0 5rem 5rem 5rem;
    background-image: url("https://i.imgur.com/4bg1e6u.jpg");
    background-size: cover;
    background-repeat: no-repeat;
}
@media(max-width:768px){
    .card-body{
        padding: 0 1rem 1rem 1rem;
        background-image: url("https://i.imgur.com/4bg1e6u.jpg");
        background-size: cover;
        background-repeat: no-repeat;
    }  
    .card-top{
        padding: 0.7rem 1rem;
    }
}
.row{
    margin: 0;
}
.upper{
    padding: 1rem 0;
    justify-content: space-evenly;
}
#three{
    border-radius: 1rem;
        width: 22px;
    height: 22px;
    margin-right:3px;
    border: 1px solid blue;
    text-align: center;
    display: inline-block;
}
#payment{
    margin:0;
    color: blue;
}
.icons{
    margin-left: auto;
}
form span{
    color: rgb(179, 179, 179);
}
form{
    padding: 2vh 0;
}
input{
    border: 1px solid rgba(0, 0, 0, 0.137);
    padding: 1vh;
    margin-bottom: 4vh;
    outline: none;
    width: 100%;
    background-color: rgb(247, 247, 247);
}
input:focus::-webkit-input-placeholder
{
      color:transparent;
}
.header{
    font-size: 1.5rem;
}
.left{
    background-color: #ffffff;
    padding: 2vh;   
}
.left img{
    width: 2rem;
}
.left .col-4{
    padding-left: 0;
}
.right .item{
    padding: 0.3rem 0;
}
.right{
    background-color: #ffffff;
    padding: 2vh;
}
.col-8{
    padding: 0 1vh;
}
.lower{
    line-height: 2;
}
.btn{
    background-color: rgb(23, 4, 189);
    border-color: rgb(23, 4, 189);
    color: white;
    width: 100%;
    font-size: 0.7rem;
    margin: 4vh 0 1.5vh 0;
    padding: 1.5vh;
    border-radius: 0;
}
.btn:focus{
    box-shadow: none;
    outline: none;
    box-shadow: none;
    color: white;
    -webkit-box-shadow: none;
    -webkit-user-select: none;
    transition: none; 
}
.btn:hover{
    color: white;
}
a{
    color: black;
}
a:hover{
    color: black;
    text-decoration: none;
}
input[type=checkbox]{
    width: unset;
    margin-bottom: unset;
}
#cvv{
    background-image: linear-gradient(to left, rgba(255, 255, 255, 0.575) , rgba(255, 255, 255, 0.541)), url("https://img.icons8.com/material-outlined/24/000000/help.png");
    background-repeat: no-repeat;
    background-position-x: 95%;
    background-position-y: center;
} 
#cvv:hover{

}
    </style>
</head>
<body>
    <div class="card">
            <div class="card-top border-bottom text-center">
                 <a href="index.php"> Back to shop</a>
                <h2>Payment</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-7">
                        <div class="left border">
                            <div class="row">
                                <span class="header">Payment</span>
                                <div class="icons">
                                    <img src="https://img.icons8.com/color/48/000000/visa.png"/>
                                    <img src="https://img.icons8.com/color/48/000000/mastercard-logo.png"/>
                                    <img src="https://img.icons8.com/color/48/000000/maestro.png"/>
                                </div>
                            </div>
                            <form method="post">
                                <span>Recipient's name:</span>
                                <input name="name" placeholder="Linda Williams" required>

                                <span>Phone:</span>
                                <input name="phone" placeholder="0123456789" required>

                                <span>Address:</span>
                                <input name="address" placeholder="123 Hồ Chí Minh" required>

                                <span>Card Number:</span>
                                <input name="card_number" placeholder="0123 4567 7890">

                                <input type="checkbox" id="save_card" class="align-left">
                                <label for="save_card">Save card details to wallet</label>  

                                <button class="btn btn-primary" type="submit">Place Order</button>
                                <a href="cart.php"><i class="bi bi-arrow-left"></i> Quay lại giỏ hàng</a>
                            </form>

                        </div>                        
                    </div>
                    <div class="col-md-5">
                        <div class="right border">
                            <div class="header">Order Summary</div>
                            <p><?= count($items) ?> items</p>

                            <?php foreach ($items as $item): ?>
                            <div class="row item">
                                <div class="col-4 align-self-center">
                                    <img class="img-fluid" src="<?= htmlspecialchars($item['image']) ?>">
                                </div>
                                <div class="col-8">
                                    <div class="row"><b><?= number_format($item['price'], 0, ',', '.') ?> đ</b></div>
                                    <div class="row text-muted"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="row">số lượng: <?= $item['quantity'] ?></div>
                                    ------------------------------
                                </div>
                                
                            </div>
                        <?php endforeach; ?>

                        </div>
                    </div>
                </div>
                <?php if ($order_successful): ?>
    <div class="alert alert-success" role="alert">
        🎉 Đặt hàng thành công! Cảm ơn bạn đã mua sắm tại KidsToyLand.
    </div>
<?php endif; ?>

            </div>
            
         <div>
        </div>
        </div>
</body>
</html>