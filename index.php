<?php

function check_login($con)
{

	if(isset($_SESSION['member_id']))
	{

		$id = $_SESSION['member_id'];
		$query = "select * from member where member_id = '$id' limit 1";

		$result = mysqli_query($con,$query);
		if($result && mysqli_num_rows($result) > 0)
		{

			$member_data = mysqli_fetch_assoc($result);
			return $member_data;
		}
	}

	//redirect to login
	header("Location: login.php");
	die;

}
?>

<?php 
session_start();

	include("connection.php");

	$member_data = check_login($con);

try {
    $stmt = $pdo->query("SELECT * FROM product");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("讀取資料失敗：" . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品列表</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>

	<a href="logout.php">Logout</a>
	<a href="memberpage.php">Member Page</a>
	<h1>This is the index page</h1>
	

	<br>
	Hello, <?php echo $member_data['name']; ?>
    <h1>商品列表</h1>
    <div class="container">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $product): ?>
                <div class="card">
                    <img src="https://via.placeholder.com/250x150.png?text=Product+Image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <div class="card-body">
                        <div class="card-title"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="card-description"><?php echo htmlspecialchars($product['description']); ?></div>
                        <div class="card-price">NTD <?php echo htmlspecialchars(number_format($product['price'], 2)); ?></div>
                        <a href="product.php?product_id=<?php echo htmlspecialchars($product['product_id']); ?>">查看商品</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align: center;">目前沒有商品。</p>
        <?php endif; ?>
    </div>
</body>
</html>