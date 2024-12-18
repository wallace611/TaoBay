<?php

session_start();

if(isset($_SESSION['member_id']))
{
	unset($_SESSION['member_id']);

}

header("Location: login.php");
die;