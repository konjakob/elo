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
		
	$statement = $pdo->prepare("select * from elo_reply where reply_id=:replyid");
	$statement->bindValue(':replyid', $replyid, PDO::PARAM_INT);
	$statement->execute();
	
	if ( $statement->rowCount() < 1 ) {
		$msgs[] = array('state' => 'nok', 'text' => DELETE_REPLY_NO_RIGHTS);
	} else {
		$res = $statement->fetch(PDO::FETCH_ASSOC);
		$reply = $res;

		if ( ($res['user_id'] == $user_res['user_id'] && $res['reply_date'] > ($time - $conf['max_edit_time']) ) || in_array('IS_ADMIN',$user_rights)) {
			$topicid = $res['topic_id'];
	
			// music sheets
			$statement = $pdo->prepare("select a.*, ra.rm_id from elo_music as a, elo_reply_music as ra where ra.reply_id=:replyid and ra.music_id=a.music_id");
			$statement->bindValue(':replyid', $replyid, PDO::PARAM_INT);
			$statement->execute();
	
			$sheets = array();
			while ( ($r = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
				$sheets[] = $r;
			
			// submit of edited data
			if ( isset($_POST['action']) && $_POST['action'] == 'editTopic') {	

				if ( in_array('ADD_USER_TO_TOPIC',$user_rights) || in_array('ADD_GROUP_TO_TOPIC',$user_rights) ) {
					if ( in_array('ADD_USER_TO_TOPIC',$user_rights)) {	
						$statement = $pdo->prepare("delete from elo_topic_user where topic_id=:topicid");
						$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
						$statement->execute();
						if ( isset( $_POST['t_user'] )) {
							foreach ( $_POST['t_user'] as $u ) {
								$statement = $pdo->prepare("insert into elo_topic_user (user_id, topic_id) values (:u, :topicid)");
								$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
								$statement->bindValue(':u', (int)$u, PDO::PARAM_INT);
								$statement->execute();
							}
						}
					}
					if ( in_array('ADD_GROUP_TO_TOPIC',$user_rights)) {	
						$statement = $pdo->prepare("delete from elo_topic_group where topic_id=:topicid");
						$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
						$statement->execute();
						if ( isset($_POST['t_group'])) {
							foreach ( $_POST['t_group'] as $g ) {
								$statement = $pdo->prepare("insert into elo_topic_group (group_id, topic_id) values (:g, :topicid)");
								$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
								$statement->bindValue(':g', (int)$g, PDO::PARAM_INT);
								$statement->execute();
							}
						}
					}
				}
			
				foreach ($sheets as $r) {
					if ( strlen($_POST['old_abc'][$r['music_id']])) {
						if ( $_POST['old_abc'][$r['music_id']] != $r['music_text']) {
							$statement = $pdo->prepare("update elo_music set music_text=:m where music_id=:music_id");
							$statement->bindValue(':music_id', $music_id, PDO::PARAM_INT);
							$statement->bindValue(':m', $_POST['old_abc'][$r['music_id']]);
							$statement->execute();
							processMusicFiles($r['music_id'], $_POST['old_abc'][$r['music_id']]);	
						}
					} else {
						$statement = $pdo->prepare("delete from elo_music where music_id=:music_id");
						$statement->bindValue(':music_id', $music_id, PDO::PARAM_INT);
						$statement->execute();	
						
						$statement = $pdo->prepare("delete from elo_reply_music where music_id=:music_id");
						$statement->bindValue(':music_id', $music_id, PDO::PARAM_INT);
						$statement->execute();	
					}
				}
				
				$topicText = $_POST['t_topic'];
				if ( !in_array('ALLOW_HTML', $user_rights) ) 
					$topicText = htmlentities($topicText);
				
				$statement = $pdo->prepare("update elo_reply set reply_text=:topicText where reply_id=:replyid");
				$statement->bindValue(':topicText', $topicText);
				$statement->bindValue(':replyid', $replyid, PDO::PARAM_INT);
				$statement->execute();	
				
				if ( in_array('CREATE_ATTACHMENTS', $user_rights) && isset($_POST['picture']) ) {
					foreach ( $_POST['picture'] as $p ) { 
						$statement = $pdo->prepare("insert into elo_reply_attachment (reply_id, attachment_id) values (:replyid, :p)");
						$statement->bindValue(':replyid', $replyid, PDO::PARAM_INT);
						$statement->bindValue(':p', (int)$p, PDO::PARAM_INT);
						$statement->execute();
					}
				}
				
				if ( in_array('CREATE_SHEETS', $user_rights) && isset($_POST['abc']) && strlen($_POST['abc'])) {
					processMusic();
				}
		
				if ( isset($_POST['noref']) ) {
					$msgs[] = array('state' => 'ok', 'text' => "Reply saved.");
				} else {
					header("Location: topic.php?id=".$res['topic_id']."#".$replyid);	
					exit();
				}
			}
					
			// attachments
			$statement = $pdo->prepare("select ra.ra_id, a.* from elo_attachment as a, elo_reply_attachment as ra where ra.reply_id=:replyid and ra.attachment_id=a.attachment_id");
			$statement->bindValue(':replyid', $replyid, PDO::PARAM_INT);
			$statement->execute();
			
			$attachments = array();
			while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
				$attachments[] = $res;
			
			$reply['attachments'] = prepareAttachments($attachments);
			$reply['sheets'] = $sheets;
			
			if ( in_array('IS_ADMIN',$user_rights) || in_array('ADD_USER_TO_TOPIC',$user_rights) || in_array('ADD_GROUP_TO_TOPIC',$user_rights) ) {
		
				$statement = $pdo->prepare("select u.user_id, u.user_name, ut.tu_id from elo_user as u left join elo_topic_user as ut on (ut.user_id=u.user_id and ut.topic_id=:topicid) order by user_name");
				$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
				$statement->execute();
				
				$users = array();
				while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
					$users[] = array('user_id' => $res['user_id'], 'user_name' => $res['user_name'], 'selected' => $res['tu_id'] ? 1 : 0);
				$twig_data['users'] = $users;
				
				$statement = $pdo->prepare("select u.group_id, u.group_name, ut.tg_id from elo_group as u left join elo_topic_group as ut on (ut.group_id=u.group_id and ut.topic_id=:topicid )order by group_name");
				$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
				$statement->execute();

				$groups = array();
				while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
					$groups[] = array('group_id' => $res['group_id'], 'group_name' => $res['group_name'], 'selected' => $res['tg_id']);

			}

		} else {
			$msgs[] = array('state' => 'nok', 'text' => DELETE_REPLY_NO_RIGHTS);
		}
	}
}

$statement = $pdo->prepare("select topic_title from elo_topic where topic_id=:topicid");
$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
$statement->execute();
$res = $statement->fetch(PDO::FETCH_ASSOC);

$breadcrumb[] = array('href' => 'topic.php', 'text' => 'Topics');
$breadcrumb[] = array('href' => 'topic.php?id='.$topicid."#".$replyid, 'text' => $res['topic_title']);
$breadcrumb[] = array('href' => '', 'text' => TOPIC_EDIT_REPLY);
$twig_data['reply'] = $reply;
$twig_data['msgs'] = $msgs;
$twig_data['breadcrumb'] = $breadcrumb;
echo $twig->render("edit-reply.twig", $twig_data);
