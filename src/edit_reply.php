<?php

require('includes/application_top.php');

$error = "";
$replyid = 0;

$msgs = array();
$reply = array();

if ( !isset( $_GET['id'] ) && !isset($_POST['id']) ) {
	$msgs[] = array('state' => 'nok', 'text' => DELETE_REPLY_NO_ID);
} else {
	
	$replyid = isset($_GET['id']) ? (int)$_GET['id'] : (int)$_POST['id'];
		
	$query = $db->query("select * from elo_reply where reply_id='".$replyid."'");
	
	if ( $db->num_rows($query) < 1 ) {
		$msgs[] = array('state' => 'nok', 'text' => DELETE_REPLY_NO_RIGHTS);
	} else {
		$res = $db->fetch_array($query);
		$reply = $res;

		if ( ($res['user_id'] == $user_res['user_id'] && $res['reply_date'] > ($time - $conf['max_edit_time']) ) || in_array('IS_ADMIN',$user_rights)) {
			$topicid = $res['topic_id'];
	
			// music sheets
			$query_m = $db->query("select a.*, ra.rm_id from elo_music as a, elo_reply_music as ra where ra.reply_id='".$replyid."' and ra.music_id=a.music_id");
			
			// submit of edited data
			if ( isset($_POST['edit_topic_admin']) && in_array('IS_ADMIN',$user_rights)) {	
				$db->query("delete from elo_topic_user where topic_id='".$topicid."'");			
				if ( isset( $_POST['t_user'] )) {
					foreach ( $_POST['t_user'] as $u )
						$db->query("insert into elo_topic_user (user_id, topic_id) values ('".$u."', '".$topicid."')");
				}
				$db->query("delete from elo_topic_group where topic_id='".$topicid."'");
				if ( isset($_POST['t_group'])) {
					foreach ( $_POST['t_group'] as $g )
						$db->query("insert into elo_topic_group (group_id, topic_id) values ('".$g."', '".$topicid."')");
				}
				$db->query("update elo_topic set topic_title='".addslashes($_POST['t_topic_title'])."' where topic_id='".$topicid."'");
			}

			if ( isset($_POST['text']) ) {				
				while($r = $db->fetch_array($query_m)) {
					if ( strlen($_POST['old_abc'][$r['music_id']])) {
						if ( $_POST['old_abc'][$r['music_id']] != $r['music_text']) {
							$db->query("update elo_music set music_text='".addslashes( $_POST['old_abc'][$r['music_id']])."' where music_id='".$r['music_id']."'");
							processMusicFiles($r['music_id'], $_POST['old_abc'][$r['music_id']]);	
						}
					} else {
						$db->query("delete from elo_music where music_id='".$r['music_id']."'");
						$db->query("delete from elo_music_reply where music_id='".$r['music_id']."'");	
					}
				
				}
			
				if ( !in_array('ALLOW_HTML', $user_rights) ) 
					$_POST['text'] = htmlentities($_POST['text']);
							
				$db->query("update elo_reply set reply_text='".addslashes($_POST['text'])."' where reply_id='".$replyid."'");
				
				if ( in_array('CREATE_ATTACHMENTS', $user_rights) && isset($_FILES['t_file']) && strlen($_FILES['t_file']['name'])) {
					processAttachment();
				}
				
				if ( in_array('CREATE_SHEETS', $user_rights) && isset($_POST['abc']) && strlen($_POST['abc'])) {
					processMusic();
				}
		
				if ( isset($_POST['noref']) ) {
					echo "Reply saved.";
				} else {
					header("Location: topic.php?id=".$res['topic_id']."#".$replyid);	
					exit();
				}
			}
/* *************************** */
			
			
			
			// attachments
			$query_a = $db->query("select ra.ra_id, a.* from elo_attachment as a, elo_reply_attachment as ra where ra.reply_id='".$replyid."' and ra.attachment_id=a.attachment_id");
			$attachments = array();
			while($r = $db->fetch_array($query_m)) {
				$attachments[] = $r;
			}
			$reply['attachments'] = $attachments;
	
		} else {
			$msgs[] = array('state' => 'nok', 'text' => DELETE_REPLY_NO_RIGHTS);
		}
		
		if ( in_array('IS_ADMIN',$user_rights) ) {
				
			$query_user = $db->query("select u.user_id, u.user_name, ut.tu_id from elo_user as u left join elo_topic_user as ut on (ut.user_id=u.user_id and ut.topic_id='".$topicid."') order by user_name");
			
			$users = array();
			while ( $res = $db->fetch_array($query_user) )
				$users[] = array('user_id' => $res['user_id'], 'user_name' => $res['user_name'], 'selected' => $res['tu_id'] ? 1 : 0);
			$twig_data['users'] = $users;

			$query_groups = $db->query("select u.group_id, u.group_name, ut.tg_id from elo_group as u left join elo_topic_group as ut on (ut.group_id=u.group_id and ut.topic_id='".$topicid."' )order by group_name");

			$groups = array();
			while ( $res = $db->fetch_array($query_groups) )
				$groups[] = array('group_id' => $res['group_id'], 'group_name' => $res['group_name'], 'selected' => $res['tg_id']);

		}
	}
}

$breadcrumb[] = array('href' => 'topic.php', 'text' => 'Topics');
$breadcrumb[] = array('href' => 'topic.php?id='.$topicid."#".$replyid, 'text' => 'Topic');
$twig_data['reply'] = $reply;
$twig_data['msgs'] = $msgs;
$twig_data['breadcrumb'] = $breadcrumb;
echo $twig->render("edit-reply.twig", $twig_data);
