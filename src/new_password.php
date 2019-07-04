<?php

$ref = "";

$error = "";
$success = "";

require_once("dbclass.php");

$langcode = "en";
	
if ( isset($_GET['id'])) {

	require("functions.php");

	
	require_once( "PasswordHash.php" );
    $hasher = new PasswordHash( 8, TRUE );

	$user_id = $db->query_one("select user_id from elo_pass_request where pr_code='".addslashes($_GET['id'])."' and pr_time>'".(time()-3600)."' limit 1");
	
	if ( $user_id ) {
		
		$db->query("delete from elo_pass_request where pr_code='".addslashes($_GET['id'])."'");
		
		$code = createCode(8);
	
		$db->query("update elo_user set user_password='".addslashes($hasher->HashPassword($code))."' where user_id='".$user_id."'");


		$query = $db->query("select elo_user.*, elo_lang.lang_code from elo_user left join elo_lang ON (elo_user.lang_id=elo_lang.lang_id) where user_id='".$user_id."' limit 1");
		 
		$success = "";
	
		$user_res = $db->fetch_array($query);
	 
		$email_text = str_replace(array("{user_name}", "{new_password}", "{url}"), array($user_res['user_name'], $code, $conf['url']."login.php"), file_get_contents("includes/languages/template_email_reset_".$user_res['lang_code'].".html"));
		 
		$email_text_text = preg_replace('/(\<style)(.*)(style>)/s','',$email_text);
		$email_text_text = str_replace(array("<!DOCTYPE html>","<br>"),array("","\n"),$email_text_text);
		$email_text_text = preg_replace('/(<\/?)(\w+)([^>]*>)/e','',$email_text_text);
		
		$res = prepareEmailAndSend($email_text, $user_res['user_email'], $user_res['user_name'],'Password reseted',$email_text_text);
		$success = $res[0];
		if ( strlen($res[1])) {
			$error = "<div id='warning'>".$res[1]."</div><br>";
		}
		if ( strlen($res[0])) {
			$success = 	"<div id='correct'>".$res[0]."</div>";
		}
		
		$langcode = $user_res['lang_code'];
	
	} else {
		$error = "<div id='warning'>No password request found.</div><br>";
	}

} else {
	$error = "<div id='warning'>No password request found.</div><br>";	
}
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
<div id="panel-header"><?=PASSWORT_RESET?></div>
<div id="panel-box"><?=PASSWORT_RESET_TEXT?>
</div></body></html>