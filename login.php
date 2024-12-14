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
	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		//something was posted
		$email = $_POST['email'];
		$password = $_POST['password'];

		if(!empty($email) && !empty($password))
		{
			//read from database using prepared statements
			$query = "SELECT * FROM member WHERE email = ? LIMIT 1";
			$stmt = $con->prepare($query);
			$stmt->bind_param("s", $email);
			$stmt->execute();
			$result = $stmt->get_result();

			if($result && $result->num_rows > 0)
			{
				$member_data = $result->fetch_assoc();

				//verify password
				if(password_verify($password, $member_data['password']))
				{
					$_SESSION['member_id'] = $member_data['member_id'];
					header("Location: index.php");
					die;
				}
			}
			$error_message = "電子郵件或密碼輸入錯誤！";
		}else
		{
			$error_message = "請輸入正確的資訊！";
		}
	}

?>


<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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
            background-color:#85a3e0 ; /* 深藍色按鈕 */
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
            background-color: #4976d0; /* 卡片淺藍色 */
            border: none;
            border-radius: 10px;
            font-size: 16px;
			font-weight: 900; /* 加粗按鈕文字 */
            cursor: pointer;
            transition: background-color 0.3s;
        }

        #button:hover {
            background-color: #85a3e0; /* 按鈕 hover 顏色 */
        }

        a {
            color: #013B64; /* 深藍色連結 */
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
        <div>會員登入</div>
        <form method="post">
            <label for="email" style="color: white;">電子郵件</label>
            <input id="text" type="text" name="email" placeholder="輸入您的電子郵件">

            <label for="password" style="color: white;">密碼</label>
            <input id="text" type="password" name="password" placeholder="輸入您的密碼">

            <input id="button" type="submit" value="登入">

            <a href="signup.php">還沒有帳號？點此註冊</a>
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
