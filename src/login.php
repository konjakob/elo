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
<html lang="en" class="uk-height-1-1">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title><?=LOGIN_INFO?></title>
		<link rel="icon" href="img/favicon.ico">
		<!-- CSS FILES -->
		<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/uikit/3.0.2/css/uikit.min.css">
	</head>
	<body class="uk-height-1-1">
		<div class="uk-flex uk-flex-center uk-flex-middle uk-background-muted uk-height-viewport">
			<div class="uk-position-bottom-center uk-position-small uk-visible@m">
				<span class="uk-text-small uk-text-muted"><a href="http://www.jakob-wankel.de">Created by Jakob Wankel</a> | Built with <a href="http://getuikit.com" title="Visit UIkit 3 site" target="_blank" data-uk-tooltip><span data-uk-icon="uikit"></span></a></span>
			</div>
			<div class="uk-width-medium uk-padding-small">
				<?php
				
	if( strlen($error)) 
		echo $error;
	
	if ( strlen($success ) )
		echo $success;
				
	if ( isset($_GET['pass']) && $_GET['pass'] == 'forgotten') {

		echo LOGIN_PASSFORGOTTEN_TEXT;
?>

<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
Email: <input type="text" name="t_email" /><br />
<input type="submit" value="<?=LOGIN_PASSFORGOTTEN_BUTTON?>" name="action_passforgotten" />
</form>
<?php		
	} else {
			 	
?>
				<form action="<?=$_SERVER['PHP_SELF']?>" method="POST">
					<input type="hidden" name="action" value="login">
					<input type="hidden" name="action_login" value="1">
					<input type="hidden" value="<?=$ref?>" name="ref" />
					<fieldset class="uk-fieldset">
						<legend class="uk-legend"><?=LOGIN_INFO?></legend>
						<div class="uk-margin">
							<div class="uk-inline uk-width-1-1">
								<span class="uk-form-icon uk-form-icon-flip" data-uk-icon="icon: user"></span>
								<input class="uk-input uk-form-large" required placeholder="Email" type="text" name="t_email">
							</div>
						</div>
						<div class="uk-margin">
							<div class="uk-inline uk-width-1-1">
								<span class="uk-form-icon uk-form-icon-flip" data-uk-icon="icon: lock"></span>
								<input class="uk-input uk-form-large" required placeholder="Passwort" type="password" name="t_pass">
							</div>
						</div>
						<div class="uk-margin">
							<button type="submit" class="uk-button uk-button-primary uk-button-primary uk-button-large uk-width-1-1"><?=LOGIN_BUTTON?></button>
						</div>
					</fieldset>
				</form>
				<a href="<?=$_SERVER['PHP_SELF']?>?pass=forgotten"><?=LOGIN_PASSWORD_FORGOTTEN?></a>
				<?php } ?>
			</div>
		</div>
		
		<!-- JS FILES -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/uikit/3.0.2/js/uikit.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/uikit/3.0.2/js/uikit-icons.min.js"></script>
	</body>
</html>