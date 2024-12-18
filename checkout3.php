<?php
session_start();
include("connection.php");
$member_data = check_login($con);
$is_admin = is_admin($con, $member_data['member_id']);
// 確保會員已登入
if (!isset($_SESSION['member_id'])) {
    die("錯誤：未登入會員，無法進行結帳。");
}

$member_id = $_SESSION['member_id'];

// 獲取會員近期訂單
$order_query = "
SELECT o.order_id, o.order_status, o.payment_method, o.amount, o.delivery_address, o.checkout_time, o.cart_id
FROM orders o
WHERE o.member_id = ?
ORDER BY o.checkout_time DESC
LIMIT 5
";

$order_stmt = $con->prepare($order_query);
$order_stmt->bind_param("i", $member_id);
$order_stmt->execute();
$order_result = $order_stmt->get_result();
$recent_orders = $order_result->fetch_all(MYSQLI_ASSOC);

// 獲取當前會員的購物車 ID
$cart_query = "SELECT cart_id FROM cart WHERE member_id = ? AND is_checkout = 0";
$stmt = $con->prepare($cart_query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();
$cart = $result->fetch_assoc();

if (!$cart) {
    die("錯誤：您目前沒有購物車。");
}

$cart_id = $cart['cart_id'];

// 取購物車中的商品及其數量
$query = "
    SELECT p.product_id, p.name, p.price, c.quantity
    FROM contains c
    JOIN product p ON c.product_id = p.product_id
    WHERE c.cart_id = ?
";

$stmt = $con->prepare($query);
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$total_amount = 0;

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $total_amount += $row['price'] * $row['quantity']; // 計算總金額
}

// 處理增加、減少和刪除請求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $product_id = $_POST['product_id'];
        $action = $_POST['action'];

        // 獲取商品的可用數量
        $available_query = "SELECT quantity FROM product WHERE product_id = ?";
        $available_stmt = $con->prepare($available_query);
        $available_stmt->bind_param("i", $product_id);
        $available_stmt->execute();
        $available_result = $available_stmt->get_result();
        $available_product = $available_result->fetch_assoc();
        $available_quantity = $available_product['quantity'];

        if ($action === 'increase') {
            // 增加數量
            $check_contains_stmt = $con->prepare("SELECT quantity FROM contains WHERE cart_id = ? AND product_id = ?");
            $check_contains_stmt->bind_param("ii", $cart_id, $product_id);
            $check_contains_stmt->execute();
            $existing_product = $check_contains_stmt->get_result()->fetch_assoc();

            if ($existing_product) {
                $new_quantity = $existing_product['quantity'] + 1;
                if ($new_quantity <= $available_quantity) {
                    $update_quantity_stmt = $con->prepare("UPDATE contains SET quantity = ? WHERE cart_id = ? AND product_id = ?");
                    $update_quantity_stmt->bind_param("iii", $new_quantity, $cart_id, $product_id);
                    $update_quantity_stmt->execute();
                } else {
                    echo "<script>alert('錯誤：無法增加數量，超過可用商品數量。');</script>";
                }
            }
        } elseif ($action === 'decrease') {
            // 減少數量
            $check_contains_stmt = $con->prepare("SELECT quantity FROM contains WHERE cart_id = ? AND product_id = ?");
            $check_contains_stmt->bind_param("ii", $cart_id, $product_id);
            $check_contains_stmt->execute();
            $existing_product = $check_contains_stmt->get_result()->fetch_assoc();

            if ($existing_product) {
                $new_quantity = $existing_product['quantity'] - 1;
                if ($new_quantity > 0) {
                    $update_quantity_stmt = $con->prepare("UPDATE contains SET quantity = ? WHERE cart_id = ? AND product_id = ?");
                    $update_quantity_stmt->bind_param("iii", $new_quantity, $cart_id, $product_id);
                    $update_quantity_stmt->execute();
                } else {
                    // 如果數量減少到 0，則刪除該商品
                    $delete_stmt = $con->prepare("DELETE FROM contains WHERE cart_id = ? AND product_id = ?");
                    $delete_stmt->bind_param("ii", $cart_id, $product_id);
                    $delete_stmt->execute();
                }
            }
        } elseif ($action === 'delete') {
            // 刪除商品
            $delete_stmt = $con->prepare("DELETE FROM contains WHERE cart_id = ? AND product_id = ?");
            $delete_stmt->bind_param("ii", $cart_id, $product_id);
            $delete_stmt->execute();
        }

        // 重新獲取購物車中的商品
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        $total_amount = 0;

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
            $total_amount += $row['price'] * $row['quantity']; // 計算總金額
        }
    }

    // 提交訂單
    if (isset($_POST['submit_order'])) {
        $delivery_address = $_POST['delivery_address'];
        $payment_method = $_POST['payment_method']; // 接收用戶選擇的付款方式
        $order_status = '待出貨';
    
        // 獲取目前最大訂單編號
        $max_order_id_query = "SELECT MAX(order_id) AS max_id FROM orders";
        $max_order_id_stmt = $con->prepare($max_order_id_query);
        $max_order_id_stmt->execute();
        $max_order_id_result = $max_order_id_stmt->get_result();
        $max_order_id = $max_order_id_result->fetch_assoc()['max_id'];
        $new_order_id = $max_order_id ? $max_order_id + 1 : 1; // 如果沒有訂單，則從 1 開始
    
        // 插入訂單的 SQL 語句，記錄付款方式和送出時間
        $insert_order = "
            INSERT INTO orders (order_id, order_status, payment_method, amount, delivery_address, checkout_time, member_id, cart_id)
            VALUES (?, ?, ?, ?, ?, NOW(), ?, ?)
        ";
    
        $stmt = $con->prepare($insert_order);
        $stmt->bind_param("issdsii", $new_order_id, $order_status, $payment_method, $total_amount, $delivery_address, $member_id, $cart_id);
        $stmt->execute();
    
        // 減少庫存數量
        foreach ($items as $item) {
            $update_stock_query = "UPDATE product SET quantity = quantity - ? WHERE product_id = ?";
            $update_stock_stmt = $con->prepare($update_stock_query);
            $update_stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $update_stock_stmt->execute();
        }
    
        // 提交訂單後，將當前購物車的 is_checkout 設為 1
        $update_cart_stmt = $con->prepare("UPDATE cart SET is_checkout = 1 WHERE cart_id = ?");
        $update_cart_stmt->bind_param("i", $cart_id);
        $update_cart_stmt->execute();
    
        // 獲取目前最大 cart_id
        $max_cart_id_query = "SELECT MAX(cart_id) AS max_id FROM cart";
        $max_cart_id_stmt = $con->prepare($max_cart_id_query);
        $max_cart_id_stmt->execute();
        $max_cart_id_result = $max_cart_id_stmt->get_result();
        $max_cart_id = $max_cart_id_result->fetch_assoc()['max_id'];
        $new_cart_id = $max_cart_id ? $max_cart_id + 1 : 1; // 如果沒有購物車，則從 1 開始
    
        // 提交訂單後，為會員創建一個新的購物車
        $insert_cart = "INSERT INTO cart (cart_id, member_id) VALUES (?, ?)";
        $new_cart_stmt = $con->prepare($insert_cart);
        $new_cart_stmt->bind_param("ii", $new_cart_id, $member_id);
        $new_cart_stmt->execute();
    
        // 使用 alert 通知成功送出，並跳轉回 index.php
        echo "<script>alert('訂單提交成功！已為您創建新購物車。'); window.location.href = 'index.php';</script>";
        exit();
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>結帳頁面</title>
    <style>
        html, body {
            height: 100%; /* 設置為全高 */
            margin: 0;
            overflow-y: auto; /* 確保可以垂直滾動 */
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0 auto;
            width: 80%;
            padding-top: 70px;
        }
        h2 {
            color: #333;
        }
        a {
            color: #007bff;
            text-decoration: none;
            margin-bottom: 10px;
            display: inline-block;
        }
        a:hover {
            text-decoration: underline;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
            color: #333;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        button {
            background-color: #4976d0;
            color: #fff;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 3px;
        }
        button:hover {
            background-color: #85a3e0;
        }
        form {
            margin: 0;
        }
        input[type="text"], select {
            padding: 5px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 3px;
            width: 100%;
            max-width: 300px;
        }
        p {
            font-weight: bold;
            color: #555;
        }
        /* 大按鈕樣式 */
        .large-button {
            padding: 15px 30px;
            font-size: 18px;
            font-weight: bold;
            background-color:rgb(246, 34, 34);
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .large-button:hover {
            background-color:rgb(240, 77, 77);
        }
        header {
            background-color: #85a3e0;
            color: white;
            padding: 12px 20px;
            width: 100%; /* 確保滿版 */
            position: fixed; /* 固定在頁面頂部 */
            top: 0;
            left: 0;
            z-index: 1000; /* 確保在其他元素上方 */
            display: flex; /* 使用 flexbox 布局 */
            justify-content: space-between; /* 左右分布 */
            align-items: center; /* 垂直居中 */
            box-sizing: border-box; /* 包含 padding */
        }

        .header-links {
            display: flex; /* 設定水平排列 */
            gap: 15px; /* 圖標間距 */
            max-width: 100%; /* 限制寬度以避免超出畫面 */
            overflow: hidden; /* 防止溢出 */
            flex-wrap: wrap; /* 若空間不足則換行 */
        }

        .header-links a img {
            width: 35px; /* 圖標寬度 */
            height: 35px; /* 圖標高度 */
            object-fit: contain; /* 確保圖標比例 */
            cursor: pointer; /* 鼠標樣式 */
            transition: transform 0.3s; /* 動態效果 */
        }

        .header-links a img:hover {
            transform: scale(1.1); /* 鼠标悬停放大效果 */
        }
        footer {
            background-color: #85a3e0;
            color: white;
            text-align: center;
            padding: 0px 0;
            width: 100%; /* 確保滿版 */
            position: fixed; /* 貼合底部 */
            bottom: 0;
            left: 0;
        }
        .ff{
            color: white;
        }
        header h1 {
            margin: 0;
        }
    </style>
</head>
<body>
    <header>
        <h1>TaoBay</h1>
        <div class="header-links">
            <a href="memberpage.php">
                <img src="person.png" alt="Member Page" title="Member Page">
            </a>
            <a href="logout.php">
                <img src="logout.png" alt="Logout" title="Logout">
            </a>
            <a href="index.php">
                <img src="home.png" alt="Home" title="Home">
            </a>
            <?php if ($is_admin): ?>
                <a href="management.php">
                    <img src="manage.png" alt="Manage" title="Manage">
                </a>
                <a href="orderpage.php">
                    <img src="order.png" alt="Order" title="Order">
                </a>
            <?php endif; ?>
        </div>
    </header>
    <h2>購物車商品</h2>
    <table border="1">
        <tr>
            <th>商品名稱</th>
            <th>單價</th>
            <th>數量</th>
            <th>小計</th>
            <th>操作</th>
        </tr>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td>NTD <?php echo htmlspecialchars(number_format($item['price'], 2)); ?></td>
                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                <td>NTD <?php echo htmlspecialchars(number_format($item['price'] * $item['quantity'], 2)); ?></td>
                <td>
                    <form method="POST" action="" style="display:inline;">
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($item['product_id']); ?>">
                        <button type="submit" name="action" value="increase">增加</button>
                        <button type="submit" name="action" value="decrease">減少</button>
                        <button type="submit" name="action" value="delete">刪除</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p>訂單金額: NTD <?php echo number_format($total_amount, 2); ?></p>
    <form method="POST" action="">
    <label>配送地址: <input type="text" name="delivery_address" required></label><br>
    <label>付款方式:
        <select name="payment_method" required>
            <option value="貨到付款">貨到付款</option>
            <option value="銀行轉帳">銀行轉帳</option>
        </select>
    </label><br><br>
    <button type="submit" name="submit_order"  class="large-button">結帳</button>
</form>

<h2>近期訂單</h2>
<table border="1">
    <tr>
        <th>訂單編號</th>
        <th>訂單狀態</th>
        <th>付款方式</th>
        <th>金額</th>
        <th>配送地址</th>
        <th>結帳時間</th>
        <th>操作</th>
    </tr>
    <?php if ($recent_orders): ?>
        <?php foreach ($recent_orders as $order): ?>
            <tr>
                <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                <td><?php echo htmlspecialchars($order['order_status']); ?></td>
                <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                <td>NTD <?php echo number_format($order['amount'], 2); ?></td>
                <td><?php echo htmlspecialchars($order['delivery_address']); ?></td>
                <td><?php echo htmlspecialchars($order['checkout_time']); ?></td>
                <td>
                    <form method="GET" action="cartorder.php">
                        <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                        <button type="submit">查看內容</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="7">您目前沒有任何訂單。</td>
        </tr>
    <?php endif; ?>
</table>
    <footer>
        <p class="ff">&copy; 2024 TaoBay</p>
    </footer>


</body>
</html>