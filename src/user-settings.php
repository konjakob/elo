<?php

require_once("dbclass.php");
$db = new db;

require("authenticate.class.php");

$auth = new Authenticate;

if(!$auth->validateAuthCookie())
	header("Location: login.php?ref=".base64_encode($_SERVER['PHP_SELF']."?".$_SERVER["QUERY_STRING"]));

require("functions.php");

$userid = $auth->getUserId();

$query = $db->query("select r.right_key from elo_right as r, elo_right_user as ru where r.right_id=ru.right_id and ru.user_id='".$userid."'");
$user_rights = array();

while ( $res = $db->fetch_array($query) )
	$user_rights[] = $res['right_key'];

$query = $db->query("select elo_user.*, elo_lang.lang_code from elo_user left join elo_lang ON (elo_user.lang_id=elo_lang.lang_id) where user_id='".$userid."' limit 1");
$user_res = $db->fetch_array($query);
$username = $user_res['user_name'];
$langcode = $user_res['lang_code'];
$twig_data['user_res'] = $user_res;

if ( strlen($langcode) <1 )
	$langcode = "en";

require_once('includes/languages/'.$langcode.'.php');

$saved_languages = array();
$query_lang = $db->query("select * from elo_lang order by lang_name desc");
while ( $res2 = $db->fetch_array($query_lang) )
	$saved_languages[] = $res2;

$twig_data['saved_languages'] = $saved_languages;

$breadcrumb[] = array('href' => 'topic.php', 'text' => 'Topics');
$breadcrumb[] = array('href' => '', 'text' => 'User settings');


   if (isset($_POST['action'])) {
		$sql_pass = "";
		
		
		if ( $db->query_one("select user_id from elo_user where user_email='".addslashes($_POST['t_email'])."' and user_id<>'".$userid."'") ) {
			echo "<div id='error'>".USER_SETTINGS_EMAIL_EXISTS."</div>";
		} else {
			
			if ( isset($_POST['t_pass']) && strlen($_POST['t_pass'])) {
				require_once( "PasswordHash.php" );
				$hasher = new PasswordHash( 8, TRUE );
				$sql_pass = ", user_password='".$hasher->HashPassword($_POST['t_pass'])."' ";
			}
			$db->query("update elo_user set user_name='".addslashes($_POST['t_name'])."', user_email='".addslashes($_POST['t_email'])."' ".$sql_pass.", lang_id='".intval($_POST['t_lang'])."' where user_id='".$userid."'");
			header("Location: user-settings.php?saved=1");
		}
	}
	if( isset($_GET['saved']))
		echo "<div id='correct'>".USER_SETTINGS_SAVED."</div>";

$db->close();

$twig_data['breadcrumb'] = $breadcrumb;
echo $twig->render("user-settings.twig", $twig_data);


