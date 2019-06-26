<?php
	require_once("dbclass.php");
	$db = new db;
	
	require("authenticate.class.php");
	
	$auth = new Authenticate;
	
	try {
		$auth->logOut();
		header("Location: login.php");
	} catch(  AuthException $a)  {
		$error = "<strong>Logout failed.</strong><br><br>";
	}
