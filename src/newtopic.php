<?php

require('includes/application_top.php');

// check if allowed to see
if ( !$db->query_one("select ru.ru_id from elo_right as r, elo_right_user as ru where user_id='".$userid."' and right_key='CREATE_TOPICS' and r.right_id=ru.right_id") ) {		
	//echo "Sorry, there is nothing.";
	echo json_encode(array('state' => 'nok', 'key' => 'no_rights', 'text' => 'You do not have sufficient rights for this action.', 'title' => 'Error', 'type' => 'error'));
	exit();
}
	
if ( strlen($_POST['t_topic_title']) <1 || strlen($_POST['t_topic']) < 1 ) {
	echo "Text or title are empty.";
	exit();	
}
	
$query = $db->query("select r.right_key from elo_right as r, elo_right_user as ru where r.right_id=ru.right_id and ru.user_id='".$userid."'");
$user_rights = array();

while ( $res = $db->fetch_array($query) )
	$user_rights[] = $res['right_key'];
	
if ( !in_array('ALLOW_HTML', $user_rights) )  {
	$_POST['t_topic_title'] = htmlentities($_POST['t_topic_title']);
	$_POST['t_topic'] = htmlentities($_POST['t_topic']);
}
	
$db->query("insert into elo_topic (topic_title) values ('".addslashes($_POST['t_topic_title'])."')");

$topicid = $db->insert_id();
$db->query("insert into elo_reply (user_id, topic_id, reply_date, reply_text) values ('".$userid."', '".$topicid."', '".time()."', '".addslashes($_POST['t_topic'])."')");
$reply_id = $db->insert_id();
$db->query("insert into elo_topic_user (user_id, topic_id) values ('".$userid."', '".$topicid."')");


if ( in_array('CREATE_ATTACHMENTS', $user_rights) && isset($_FILES['t_file']) ) {
	processAttachment();
}

if ( in_array('CREATE_SHEETS', $user_rights) && isset($_POST['abc']) && strlen($_POST['abc'])) {
	processMusic();
}

// Email to admin
$email_text = $db->query_one("select emailtext_text from elo_emailtext where emailtext_key='NEW_TOPIC_ADMIN' and lang_id=1");

$search_array = array("{ID}", "{USER}");
$replace_array = array($topicid, $userid);

$email_text = str_replace($search_array, $replace_array, $email_text);

// mail();

if ( isset($_POST['noref']) ) {
	$res = array();
	$res['topic_title'] = $_POST['t_topic_title'];
	$res['no'] = 1;
	$res['last_reply_date'] = $res['reply_date'] = time();
	$res['topic_id'] = $topicid;
	$res['username'] = $db->query_one("select user_name from elo_user where user_id='".$userid."'");
	createTopic();
} else {
	header("Location: topic.php");	
}
	