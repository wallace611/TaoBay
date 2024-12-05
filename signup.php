<?php 
session_start();

	include("connection.php");
	include("functions.php");
	if(isset($_SESSION['member_id'])) {
		// Redirect to another page, e.g., the homepage
		header("Location: index.php");
		die;
	}

	if($_SERVER['REQUEST_METHOD'] == "POST")
	{
		//something was posted
		$name = $_POST['name'];
		$email = $_POST['email'];
		$phone = $_POST['phone'];
		$password = $_POST['password'];

		if(!empty($name) && !empty($email) && !empty($phone) && !empty($password) && !is_numeric($name))
		{
			$member_id = random_num(20);
			//hash the password
			$hashed_password = password_hash($password, PASSWORD_DEFAULT);

			//save to database
			$query = "INSERT INTO member (member_id, name, email, phone, password) VALUES (?, ?, ?, ?, ?)";
			$stmt = $con->prepare($query);
			$stmt->bind_param("sssss", $member_id, $name, $email, $phone, $hashed_password);
			$stmt->execute();

			header("Location: login.php");
			die;
		}else
		{
			echo "Please enter some valid information!";
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