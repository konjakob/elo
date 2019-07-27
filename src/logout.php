<?php
	require_once("includes/dbclass.php");
	require_once("includes/authenticate.class.php");
	
	$auth = new Authenticate;
	
	try {
		$auth->logOut();
		header("Location: login.php");
	} catch(  AuthException $a)  {
		// todo: handle error
		$error = "<strong>Logout failed.</strong><br><br>";
	}
