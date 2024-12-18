<?php
session_start();
// 引入資料庫配置
include("../connection.php");

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
</head>
<body>
    <h1>我的訂單</h1>
    
    <a href="../index.php">返回首頁</a>
    <a href="checkout3.php">回購物車</a>

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
                                        <img src="<?php echo htmlspecialchars('../' . $product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" width="100">
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
</body>
</html>
