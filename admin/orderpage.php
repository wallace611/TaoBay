<?php
session_start();
// 引入資料庫配置
include("../connection.php");

// 檢查是否有登入管理員（例如 session 檢查），如未登入則導向至登入頁
if (!is_admin($con, $_SESSION['member_id'])) {
    header("Location: ../account/login.php");
    exit;
}

// 從資料庫獲取所有訂單，並且包含商品信息
try {
    // 查詢所有訂單資料
    $stmt = $pdo->prepare("
        SELECT o.order_id, o.order_status, o.payment_method, o.amount, o.delivery_address, o.checkout_time, m.name AS member_name 
        FROM orders o 
        JOIN member m ON o.member_id = m.member_id
        ORDER BY o.checkout_time DESC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 查詢訂單中的商品
    $order_items = [];
    foreach ($orders as $order) {
        $order_id = $order['order_id'];
        $item_stmt = $pdo->prepare("
            SELECT p.name 
            FROM contains c
            JOIN product p ON c.product_id = p.product_id
            WHERE c.order_id = ?
        ");
        $item_stmt->execute([$order_id]);
        $order_items[$order_id] = $item_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo "錯誤: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 處理更新狀態請求
    if (isset($_POST['update_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['order_status'];

        try {
            $update_status_query = "UPDATE orders SET order_status = ? WHERE order_id = ?";
            $stmt = $pdo->prepare($update_status_query);
            $stmt->execute([$new_status, $order_id]);

            echo "<script>alert('訂單狀態更新成功！'); window.location.href='orderpage.php';</script>";
            exit;
        } catch (PDOException $e) {
            echo "錯誤: " . $e->getMessage();
        }
    }

    // 處理取消訂單請求
    if (isset($_POST['cancel_order'])) {
        $order_id = $_POST['order_id'];

        try {
            // 假設取消訂單即刪除該訂單
            $delete_order_query = "DELETE FROM orders WHERE order_id = ?";
            $stmt = $pdo->prepare($delete_order_query);
            $stmt->execute([$order_id]);

            echo "<script>alert('訂單已取消！'); window.location.href='orderpage.php';</script>";
            exit;
        } catch (PDOException $e) {
            echo "錯誤: " . $e->getMessage();
        }
    }
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
    
    <a href="../index.php">返回首頁</a>
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
                <th>商品內容</th>
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
                            <!-- 顯示一個按鈕，跳轉到 cartorder.php -->
                            <form method="GET" action="../product/cartorder.php" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                <button type="submit" name="view_order">查看此訂單商品</button>
                            </form>
                        </td>
                        <td>
                            <!-- 更改狀態 -->
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                <select name="order_status">
                                    <option value="已接收" <?php echo $order['order_status'] == '已接收' ? 'selected' : ''; ?>>已接收</option>
                                    <option value="已出貨" <?php echo $order['order_status'] == '已出貨' ? 'selected' : ''; ?>>已出貨</option>
                                    <option value="已送達" <?php echo $order['order_status'] == '已送達' ? 'selected' : ''; ?>>已送達</option>
                                    <option value="已取貨" <?php echo $order['order_status'] == '已取貨' ? 'selected' : ''; ?>>已取貨</option>
                                </select>
                                <button type="submit" name="update_status">更新</button>
                            </form>

                            <!-- 取消訂單 -->
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                <button type="submit" name="cancel_order" onclick="return confirm('確定要取消此訂單嗎？');">取消訂單</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9">目前沒有任何訂單。</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
