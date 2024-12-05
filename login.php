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
			alert("wrong email or password!");
		}else
		{
			alert("wrong email or password!");
		}
	}

?>


<!DOCTYPE html>
<html>
<head>
	<title>Login</title>
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
			<div style="font-size: 20px;margin: 10px;color: white;">Login</div>

			電子郵件<input id="text" type="text" name="email"><br><br>
			密碼<input id="text" type="password" name="password"><br><br>

			<input id="button" type="submit" value="Login"><br><br>

			<a href="signup.php">Click to Signup</a><br><br>
		</form>
	</div>
</body>
</html>