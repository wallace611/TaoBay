<?php
session_start();
// 引入資料庫配置
include("connection.php");
$member_data = check_login($con);
$is_admin = is_admin($con, $member_data['member_id']);

// 檢查是否有登入用戶（例如 session 檢查），如未登入則導向至登入頁
if (!isset($_SESSION['member_id'])) {
    header("Location: login.php");
    exit;
}

// 從資料庫獲取用戶的所有訂單
try {
    // 查詢用戶的所有訂單資料
    $stmt = $pdo->prepare("SELECT o.order_id, o.order_status, o.payment_method, o.amount, o.delivery_address, o.checkout_time 
                           FROM orders o 
                           WHERE o.member_id = ?
                           ORDER BY o.checkout_time DESC");
    $stmt->execute([$_SESSION['member_id']]);

    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "錯誤: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>訂單管理</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0 auto;
            width: 80%;
            padding-top: 70px;
            background-color: white; /* 背景色 */
            color: #333; /* 字體顏色 */
        }

        h2, h3 {
            color: #4976d0; /* 標題顏色 */
        }

        a {
            color: #007bff;
            text-decoration: none;
            margin-right: 15px;
        }

        a:hover {
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background-color: #fff;
            border-radius: 5px; /* 圓角效果 */
            overflow: hidden; /* 避免內容溢出 */
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1); /* 添加陰影 */
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #f9f9f9;
            color: black;
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

        img {
            max-width: 100px;
            height: auto;
        }

        p {
            font-weight: bold;
            color: #555;
        }

        footer {
            background-color: #85a3e0;
            color: white;
            text-align: center;
            padding: 0px 0;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            font-size: 14px;
        }
        .ff{
            color: white;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        h2 {
            margin-top: 30px;
            color: #4976d0;
        }

        h3 {
            margin-top: 20px;
        }
        header {
            background-color: #85a3e0;
            color: white;
            padding: 12px 20px;
            width: 100%; /* 確保滿版 */
            height: 70px;
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
            gap: 0px; /* 圖標間距 */
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

    </style>
</head>
<body>
    <header>
        <h1>TaoBay</h1>
        <div class="header-links">
            <a href="checkout3.php">
                <img src="cart.png" alt="Shopping Cart" title="Shopping Cart">
            </a>
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
    <h1>我的訂單</h1>

    <?php if ($orders): ?>
        <?php foreach ($orders as $order): ?>
            <h2>訂單編號：<?php echo htmlspecialchars($order['order_id']); ?></h2>
            <p><strong>訂單狀態：</strong><?php echo htmlspecialchars($order['order_status']); ?></p>
            <p><strong>付款方式：</strong><?php echo htmlspecialchars($order['payment_method']); ?></p>
            <p><strong>金額：</strong><?php echo htmlspecialchars($order['amount']); ?> 元</p>
            <p><strong>配送地址：</strong><?php echo htmlspecialchars($order['delivery_address']); ?></p>
            <p><strong>結帳時間：</strong><?php echo htmlspecialchars($order['checkout_time']); ?></p>

            <h3>商品清單</h3>
            <table border="1" cellpadding="10">
                <thead>
                    <tr>
                        <th>商品名稱</th>
                        <th>商品圖片</th>
                        <th>數量</th>
                        <th>價格</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // 根據訂單編號查找對應的購物車與商品
                    try {
                        $stmt2 = $pdo->prepare("
                            SELECT p.name AS product_name, p.image_path, c.quantity, p.price
                            FROM contains c
                            JOIN product p ON c.product_id = p.product_id
                            JOIN cart ca ON c.cart_id = ca.cart_id
                            WHERE ca.cart_id = (SELECT o.cart_id FROM orders o WHERE o.order_id = ?)
                        ");
                        $stmt2->execute([$order['order_id']]);
                        $products = $stmt2->fetchAll(PDO::FETCH_ASSOC);

                        if ($products): 
                            foreach ($products as $product): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                    <td>
                                        <!-- 顯示商品圖片 -->
                                        <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" width="100">
                                    </td>
                                    <td><?php echo htmlspecialchars($product['quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($product['price']); ?> 元</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">該訂單沒有商品。</td>
                            </tr>
                        <?php endif; ?>
                    <?php
                    } catch (PDOException $e) {
                        echo "錯誤: " . $e->getMessage();
                    }
                    ?>
                </tbody>
            </table>
        <?php endforeach; ?>
    <?php else: ?>
        <p>目前沒有任何訂單。</p>
    <?php endif; ?>
    <footer>
        <p class="ff">&copy; 2024 TaoBay</p>
    </footer>
</body>
</html>
