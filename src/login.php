<?php

$ref = "";

$error = "";
$success = "";

require_once("dbclass.php");
$db = new db;
	
if ( isset($_POST['action_login'])) {

	require("authenticate.class.php");
	
	$auth = new Authenticate;
	
	$ref = $_POST['ref'];
	
	try {
		
		$auth->authenticate($_POST['t_email'], $_POST['t_pass'], 0);
				
		if ( strlen($ref) ) {
			header("Location: ".base64_decode($ref));
		} else {
			header("Location: topic.php");
		}
		exit;
	} catch(  AuthException $a)  {
		//echo $a;
		$error = "<div id='warning'>Login failed.</div><br>";
	}
} else if (isset($_POST['action_passforgotten'])) {
	 $query = $db->query("select elo_user.*, elo_lang.lang_code from elo_user left join elo_lang ON (elo_user.lang_id=elo_lang.lang_id) where user_email='".addslashes($_POST['t_email'])."' limit 1");
	 if ( $db->num_rows($query) ) {
		 $user_res = $db->fetch_array($query);

		 require("functions.php");
		 
		 $code = createCode(8);
		 
		 $db->query("insert into elo_pass_request (user_id, pr_code, pr_time) values ('".$user_res['user_id']."', '".$code."', '".time()."')");
		 
		 $email_text = str_replace(array("{user_name}", "{url}"), array($user_res['user_name'], $conf['url']."new_password.php?id=".$code), file_get_contents("includes/languages/template_email_forgotten_".$user_res['lang_code'].".html"));
		 
		$email_text_text = preg_replace('/(\<style)(.*)(style>)/s','',$email_text);
		$email_text_text = str_replace(array("<!DOCTYPE html>","<br>"),array("","\n"),$email_text_text);
		$email_text_text = preg_replace('/(<\/?)(\w+)([^>]*>)/e','',$email_text_text);
		
		$res = prepareEmailAndSend($email_text, $user_res['user_email'], $user_res['user_name'],'Password reset requested',$email_text_text);
		$success = $res[0];
		if ( strlen($res[1])) {
			$error = "<div id='warning'>".$res[1]."</div><br>";
		}
		if ( strlen($res[0])) {
			$success = 	"<div id='correct'>".$res[0]."</div>";
		}
	 } else {
		 $error = "<div id='warning'>Email is not known.</div><br>";
	 }
	 $_GET['pass'] = 'forgotten';
} else {
	if ( isset($_GET['ref']))
		$ref = $_GET['ref'];
}

$langcode = "en";
require_once('includes/languages/'.$langcode.'.php');
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link href="style.css" rel="stylesheet" type="text/css" />

</head>

<body><br />
<?
	if( strlen($error)) 
		echo $error;
	
	if ( strlen($success ) )
		echo $success;
		
		?>
<div id="panel-header"><?=LOGIN_INFO?></div>
<div id="panel-box">
<?
	if ( isset($_GET['pass']) && $_GET['pass'] == 'forgotten') {

		echo LOGIN_PASSFORGOTTEN_TEXT;
?>
<br>
<br>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
Email: <input type="text" name="t_email" /><br />
<input type="submit" value="<?=LOGIN_PASSFORGOTTEN_BUTTON?>" name="action_passforgotten" />
</form>
<?		
	} else {
			 	
?>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
Email: <input type="text" name="t_email" /><br />
Password: <input type="password" name="t_pass" /><br />
<input type="submit" value="<?=LOGIN_BUTTON?>" name="action_login" />
<input type="hidden" value="<?=$ref?>" name="ref" />

</form><br>
<a href="<?=$_SERVER['PHP_SELF']?>?pass=forgotten"><?=LOGIN_PASSWORD_FORGOTTEN?></a><? } ?>
</div></body></html>