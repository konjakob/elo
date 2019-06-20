<?php

require('includes/application_top.php');

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