<?php
session_start();

// 連接資料庫 
$conn = new mysqli('localhost', 'root', '', 'database');
if ($conn->connect_error) {
    die("連接失敗: " . $conn->connect_error);
}

// 假設購物車 ID 是 1（模擬用，實際可動態獲取）
$cart_id = 1;

// 檢查 cart_id 否於 cart 表
$check_cart = "SELECT COUNT(*) AS count FROM cart WHERE cart_id = ?";
$stmt = $conn->prepare($check_cart);
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    die("錯誤：指定的購物車 ID 不存在於資料庫中。");
}

// 取購物車中的商品總金額
$query = "
    SELECT SUM(p.price) AS total_amount
    FROM contains c
    JOIN product p ON c.product_id = p.product_id
    WHERE c.cart_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_amount = $row['total_amount'] ?? 0;

// 2. 提交訂單
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $delivery_address = $_POST['delivery_address'];
    $payment_method = '現金'; // 固定支付方式
    $order_status = '待出貨';

    $insert_order = "
        INSERT INTO orders (order_status, payment_method, amount, delivery_address, checkout_time, cart_id)
        VALUES (?, ?, ?, ?, NOW(), ?)
    ";
    $stmt = $conn->prepare($insert_order);
    $stmt->bind_param("ssdsi", $order_status, $payment_method, $total_amount, $delivery_address, $cart_id);
    $stmt->execute();

    echo "訂單提交成功！";
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>結帳頁面</title>
</head>
<body>
    <h1>結帳頁面</h1>
    <form method="POST" action="">
        <p>訂單金額: $<?php echo number_format($total_amount, 2); ?></p>
        <label>配送地址: <input type="text" name="delivery_address" required></label><br>
        <button type="submit">提交訂單</button>
    </form>
</body>
</html>

<!-- (必須在使用 $conn 前先定義) -->