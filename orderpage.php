<?php
session_start();
// 引入資料庫配置
include("connection.php");
$member_data = check_login($con);
$is_admin = is_admin($con, $member_data['member_id']);

// 檢查是否有登入管理員（例如 session 檢查），如未登入則導向至登入頁
if (!is_admin($con, $_SESSION['member_id'])) {
    header("Location: login.php");
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
    <style>
        /* 通用樣式 */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            padding-top: 70px;
            background-color: #ffffff;
            color: #333;
        }

        /* Header 樣式 */
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

        /* Main 樣式 */
        main {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        h2 {
            color: #4976d0;
            margin-bottom: 10px;
            text-align: center;
        }

        /* 表格樣式 */
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: #ffffff;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        table th, table td {
            text-align: center;
            padding: 10px;
            border: 1px solid #ddd;
        }

        table th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        button {
            padding: 5px 10px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            background-color: #85a3e0;
            color: white;
        }

        button:hover {
            background-color: #4976d0;
        }

        /* Footer 樣式 */
        footer {
            text-align: center;
            background-color: #85a3e0;
            color: white;
            padding: 0px 0;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
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
            <?php endif; ?>
        </div>
    </header>
<main>    
    <h1>訂單管理</h1>
    
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
                            <form method="GET" action="cartorder.php" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                <button type="submit" name="view_order">查看此訂單</button>
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
</main>
<footer>
    <p>&copy; 2024 TaoBay</p>
</footer>
</body>
</html>
