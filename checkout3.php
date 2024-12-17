<?php
session_start();
include("connection.php");

// 確保會員已登入
if (!isset($_SESSION['member_id'])) {
    die("錯誤：未登入會員，無法進行結帳。");
}

$member_id = $_SESSION['member_id'];

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
        $payment_method = '現金'; // 固定支付方式
        $order_status = '待出貨';

        // 獲取目前最大訂單編號
        $max_order_id_query = "SELECT MAX(order_id) AS max_id FROM orders";
        $max_order_id_stmt = $con->prepare($max_order_id_query);
        $max_order_id_stmt->execute();
        $max_order_id_result = $max_order_id_stmt->get_result();
        $max_order_id = $max_order_id_result->fetch_assoc()['max_id'];
        $new_order_id = $max_order_id ? $max_order_id + 1 : 1; // 如果沒有訂單，則從 1 開始

        // 插入訂單的 SQL 語句
        $insert_order = "
            INSERT INTO orders (order_id, order_status, payment_method, amount, delivery_address, checkout_time, member_id)
            VALUES (?, ?, ?, ?, ?, NOW(), ?)
        ";

        $stmt = $con->prepare($insert_order);
        $stmt->bind_param("issdii", $new_order_id, $order_status, $payment_method, $total_amount, $delivery_address, $member_id);
        $stmt->execute();

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
        echo "<script>alert('訂單提交成功！已為您創建新的購物車。'); window.location.href='index.php';</script>";
        exit; // 確保後續代碼不再執行
    }
}

$stmt->close();
$con->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>結帳頁面</title>
</head>
<body>
    <h1>結帳頁面</h1>
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
        <button type="submit" name="submit_order">提交訂單</button>
    </form>
</body>
</html>