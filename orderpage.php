<?php
session_start();
// 引入資料庫配置
include("connection.php");

// 檢查是否有登入管理員（例如 session 檢查），如未登入則導向至登入頁
if (!is_admin($con, $_SESSION['member_id'])) {
    header("Location: login.php");
    exit;
}

// 從資料庫獲取所有訂單
try {
    // 查詢所有訂單資料
    $stmt = $pdo->prepare("SELECT o.order_id, o.order_status, o.payment_method, o.amount, o.delivery_address, o.checkout_time, m.name AS member_name 
                           FROM orders o 
                           JOIN member m ON o.member_id = m.member_id
                           ORDER BY o.checkout_time DESC");
    $stmt->execute();

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
    <h1>訂單管理頁面</h1>
    
    <table border="1" cellpadding="10">
        <thead>
            <tr>
                <th>訂單編號</th>
                <th>會員名稱</th>
                <th>訂單狀態</th>
                <th>付款方式</th>
                <th>金額</th>
                <th>配送地址</th>
                <th>結帳時間</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($orders): ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                        <td><?php echo htmlspecialchars($order['member_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['order_status']); ?></td>
                        <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($order['amount']); ?> 元</td>
                        <td><?php echo htmlspecialchars($order['delivery_address']); ?></td>
                        <td><?php echo htmlspecialchars($order['checkout_time']); ?></td>
                        <td>
                            <!-- 可以在此放置按鈕，供管理員操作，如查看訂單詳細內容或更新狀態等 -->
                            <a href="view_order.php?order_id=<?php echo htmlspecialchars($order['order_id']); ?>">查看</a>
                            <!-- 假如要添加取消訂單的功能，則可以加上相應的按鈕或連結 -->
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8">目前沒有任何訂單。</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
