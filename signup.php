<?php 
session_start();

	include("connection.php");
	include("functions.php");
	$error_message = ""; // 初始化錯誤訊息
	if(isset($_SESSION['member_id'])) {
		// Redirect to another page, e.g., the homepage
		header("Location: index.php");
		die;
	}

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

<<<<<<< HEAD
			if ($stmt->execute()) {
				// 註冊成功，重定向到登入頁面
				header("Location: login.php");
				exit;
			} else {
				echo "無法完成註冊，請稍後再試。";
				exit;
			}
=======
			header("Location: login.php");
			die;
		}else
		{
			$error_message = "請輸入正確的資訊！";
>>>>>>> origin/main
		}
    } else {
        echo "請填寫所有欄位，並確保姓名是有效的文字！";
    }
}
?>



<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: white; /* 背景淡藍色 */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        #box {
            background-color: #85a3e0; /* 卡片淺藍色 */
            margin: auto;
            width: 320px;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        #box div {
            font-size: 24px;
            margin-bottom: 20px;
            color: #FFFFFF; /* 白色文字 */
            text-align: center;
            font-weight: bold;
        }

        #text {
            height: 30px;
            border-radius: 10px;
            padding: 5px;
            border: solid 2px #85a3e0; /* 淺藍邊框 */
            width: calc(100% - 14px);
            margin-bottom: 15px;
            font-size: 16px;
        }

        #button {
            padding: 10px;
            width: 100%;
            color: white;
            background-color: #4976d0; /* 深藍色按鈕 */
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 900; /* 更粗的按鈕文字 */
            cursor: pointer;
            transition: background-color 0.3s;
        }

        #button:hover {
            background-color: #85a3e0; /* 按鈕 hover 顏色 */
        }

        a {
            color: #013B64; /* 更深的深藍色連結 */
            text-decoration: none;
            font-size: 14px;
            display: block;
            text-align: center;
            margin-top: 10px;
        }

        a:hover {
            text-decoration: underline;
        }

        label {
            font-weight: bold;
            margin-bottom: 5px;
            display: inline-block; /* 確保 margin-bottom 生效 */
        }
    </style>
</head>
<body>
    <div id="box">
        <div>會員註冊</div>
        <form method="post">
            <label for="name" style="color: white;">名字</label>
            <input id="text" type="text" name="name" placeholder="輸入您的名字">

            <label for="email" style="color: white;">電子郵件</label>
            <input id="text" type="text" name="email" placeholder="輸入您的電子郵件">

            <label for="phone" style="color: white;">電話</label>
            <input id="text" type="text" name="phone" placeholder="輸入您的電話">

            <label for="password" style="color: white;">密碼</label>
            <input id="text" type="password" name="password" placeholder="輸入您的密碼">

            <input id="button" type="submit" value="註冊">

            <a href="login.php">已有帳號？點此登入</a>
        </form>
    </div>
	<script>
        // 使用 PHP 傳遞的錯誤訊息
        const errorMessage = "<?php echo $error_message; ?>";
        if (errorMessage) {
            window.alert(errorMessage);
        }
    </script>
</body>
</html>
