<?php 
session_start();

include("connection.php");
include("functions.php");

// 檢查是否已登入，若是則重定向
if (isset($_SESSION['member_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    // 接收輸入並進行基本清理
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    
    // 驗證輸入是否有效
    if (!empty($name) && !empty($email) && !empty($phone) && !empty($password) && !is_numeric($name)) {
        // 檢查 email 格式是否有效
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "請輸入有效的電子郵件地址！";
            exit;
        }

        // 獲取最新的 member_id 並遞增
        $query_last_id = "SELECT MAX(CAST(member_id AS UNSIGNED)) AS last_id FROM member";
        $result = $con->query($query_last_id);
        $row = $result->fetch_assoc();
        $member_id = $row['last_id'] ? $row['last_id'] + 1 : 1;

        // 密碼加密
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 插入資料到資料庫
		$check_query = "SELECT 1 FROM member WHERE email = ?";
		$stmt = $con->prepare($check_query);
		$stmt->bind_param("s", $email);
		$stmt->execute();
		$stmt->store_result();
		if ($stmt->num_rows > 0) {
			echo "該電子郵件地址已被註冊！";
		} else {
			$query = "INSERT INTO member (member_id, name, email, phone, password) VALUES (?, ?, ?, ?, ?)";
			$stmt = $con->prepare($query);
			if (!$stmt) {
				die("資料庫錯誤：" . $con->error);
			}
			$stmt->bind_param("sssss", $member_id, $name, $email, $phone, $hashed_password);

			if ($stmt->execute()) {
				// 註冊成功，重定向到登入頁面
				header("Location: login.php");
				exit;
			} else {
				echo "無法完成註冊，請稍後再試。";
				exit;
			}
		}
    } else {
        echo "請填寫所有欄位，並確保姓名是有效的文字！";
    }
}
?>



<!DOCTYPE html>
<html>
<head>
	<title>Signup</title>
</head>
<body>

	<style type="text/css">
	
	#text{

		height: 25px;
		border-radius: 5px;
		padding: 4px;
		border: solid thin #aaa;
		width: 100%;
	}

	#button{

		padding: 10px;
		width: 100px;
		color: white;
		background-color: lightblue;
		border: none;
	}

	#box{

		background-color: grey;
		margin: auto;
		width: 300px;
		padding: 20px;
	}

	</style>

	<div id="box">
		
		<form method="post">
			<div style="font-size: 20px;margin: 10px;color: white;">Signup</div>

			名字<input id="text" type="text" name="name"><br><br>
			電子郵件<input id="text" type="text" name="email"><br><br>
			電話<input id="text" type="text" name="phone"><br><br>
			密碼<input id="text" type="password" name="password"><br><br>
			<input id="button" type="submit" value="Signup"><br><br>

			<a href="login.php">Click to Login</a><br><br>
		</form>
	</div>
</body>
</html>