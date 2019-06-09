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

if ( strlen($langcode) <1 )
	$langcode = "en";

require_once('includes/languages/'.$langcode.'.php');


$time = time();

$error = "";

if ( isset( $_GET['id'] ) ) {
	
	$replyid = intval($_GET['id']);
	
	$query = $db->query("select * from elo_reply where reply_id='".$replyid."'");
	
	if ( $db->num_rows($query) ) {
		$res = $db->fetch_array($query);
	
		if ( ($res['user_id'] == $user_res['user_id'] && $res['reply_date'] > ($time - $conf['max_edit_time']) ) || in_array('IS_ADMIN',$user_rights) ) {
			
			
			if ( isset($_GET['aid']) ) {
				$db->query("delete from elo_reply_attachment where reply_id='".$replyid."' and ra_id='".intval($_GET['aid'])."'");				
				header("Location: edit_reply.php?id=".$replyid);
			} else {
			
				$db->query("delete from elo_reply where reply_id='".$replyid."'");
				$db->query("delete from elo_reply_attachment where reply_id='".$replyid."'");
				$db->query("delete from elo_reply_music where reply_id='".$replyid."'");
				
				header("Location: topic.php?id=".$res['topic_id']);
			}
			exit();
		} else {
			$error = DELETE_REPLY_NO_RIGHTS;
		}
	} else {
		$error = DELETE_REPLY_NO_RIGHTS;
	}
} else {
	$error = DELETE_REPLY_NO_ID;	
}
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link href="style.css" rel="stylesheet" type="text/css" />
</head>
<body>
<div id="errorcomplete"><?=$error?></div>
</body></html>