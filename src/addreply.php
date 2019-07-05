<?php

require('includes/application_top.php');

if ( !isset($_POST['id'])) {
	echo "No topic ID.";
	exit();
}

if ( !in_array('CAN_REPLY', $user_rights) ) {
    echo "No rights.";
    exit();
}


$topicid = (int)$_POST['id'];

// check if allowed to see as user
$statement = $pdo->prepare("select tu_id from elo_topic_user where topic_id=:topicid and user_id=:userid limit 1");
$statement->bindValue(':userid',$userid, PDO::PARAM_INT);
$statement->bindValue(':topicid',$topicid, PDO::PARAM_INT);
$statement->execute();

if ( !$statement->fetch(PDO::FETCH_ASSOC) ) {
	// check if allowed to see from group
	$statement = $pdo->prepare("select tg_id from elo_topic_group as tg, elo_group_user AS gu where tg.topic_id=:topicid and tg.group_id=gu.group_id and gu.user_id=:userid limit 1");
	$statement->bindValue(':userid',$userid, PDO::PARAM_INT);
	$statement->bindValue(':topicid',$topicid, PDO::PARAM_INT);
	$statement->execute();

	if ( !$statement->fetch(PDO::FETCH_ASSOC) ) {
		echo "Sorry, there is nothing.";
		exit();
	}
}

if ( strlen($_POST['text']) < 1 ) {
	echo "No text.";
	exit;	
}

$replyText = $_POST['text'];

if ( !in_array('ALLOW_HTML', $user_rights) ) 
	$replyText = htmlentities($replyText);

$statement = $pdo->prepare("insert into elo_reply (user_id, topic_id, reply_date, reply_text) values (:userid, :topicid, :time, :replyText)");
$statement->bindValue(':userid',$userid, PDO::PARAM_INT);
$statement->bindValue(':topicid',$topicid, PDO::PARAM_INT);
$statement->bindValue(':time',time());
$statement->bindValue(':replyText',$replyText);
$statement->execute();

$reply_id = $pdo->lastInsertId();

if ( in_array('CREATE_ATTACHMENTS', $user_rights) && isset($_POST['picture']) ) {
	foreach ( $_POST['picture'] as $p ) {
		$statement = $pdo->prepare("insert into elo_reply_attachment (reply_id, attachment_id) values (:reply_id, :p)");
        $statement->bindValue(':reply_id',$reply_id, PDO::PARAM_INT);
		$statement->bindValue(':p',(int)$p, PDO::PARAM_INT);
        $statement->execute();
	}
}

if ( in_array('CREATE_SHEETS', $user_rights) && isset($_POST['abc']) && strlen($_POST['abc'])) {
	processMusic();
}

if ( isset($_POST['noref']) ) {
	echo "Reply saved.";
} else {
	header("Location: topic.php?id=".$topicid."#".$reply_id);	
}
