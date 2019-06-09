<?php

require_once("dbclass.php");
$db = new db;

require("authenticate.class.php");

$auth = new Authenticate;

if(!$auth->validateAuthCookie()) {
	echo "Please log in.";
	exit;
}

require("functions.php");

// check if user is allowed to see the topic
$topicid = intval($_POST['id']);
$userid = $auth->getUserId();

// check if allowed to see
if ( !$db->query_one("select tu_id from elo_topic_user where topic_id='".$topicid."' and user_id='".$userid."' limit 1") )
{
	if ( !$db->query_one("select tg_id from elo_topic_group as tg, elo_group_user AS gu where tg.topic_id='".$topicid."' and tg.group_id=gu.group_id and gu.user_id='".$userid."' limit 1") )
	{
		echo "Sorry, there is nothing.";
		exit();
	}
}

if ( strlen($_POST['text']) < 1 ) {
	echo "No text.";
	exit;	
}

$query = $db->query("select r.right_key from elo_right as r, elo_right_user as ru where r.right_id=ru.right_id and ru.user_id='".$userid."'");
$user_rights = array();

while ( $res = $db->fetch_array($query) )
	$user_rights[] = $res['right_key'];
	
if ( !in_array('ALLOW_HTML', $user_rights) ) 
	$_POST['text'] = htmlentities($_POST['text']);
			
$db->query("insert into elo_reply (user_id, topic_id, reply_date, reply_text) values ('".$userid."', '".$topicid."', '".time()."', '".addslashes($_POST['text'])."')");
$reply_id = $db->insert_id();

if ( in_array('CREATE_ATTACHMENTS', $user_rights) && isset($_FILES['t_file']) && strlen($_FILES['t_file']['name'])) {
	processAttachment();
}

if ( in_array('CREATE_SHEETS', $user_rights) && isset($_POST['abc']) && strlen($_POST['abc'])) {
	processMusic();
}

if ( isset($_POST['noref']) ) {
	echo "Reply saved.";
} else {
	header("Location: topic.php?id=".$topicid."#".$reply_id);	
}

?>