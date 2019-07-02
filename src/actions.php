<?php

require('includes/application_top.php');
	
$returnData = array();    
    
if(isset($_GET['action']) || isset($_POST['action'])) {
	
	$action = isset($_GET['action'])  ?  $_GET['action'] : $_POST['action'];
	
	if ( $action == 'getUser' ) {
	    
        if ( !isset($_GET['userid']) ) {
            $returnData['state'] = 'nok';
            $returnData['text'] = 'No user given.';
            json_encode($returnData);
            exit();
        }

		$query_groups = $db->query("select * from elo_group order by group_name");
		$groups = array();
		while ( $res = $db->fetch_array($query_groups) )
			$groups[] = $res;
			
		$query_right = $db->query("select * from elo_right order by right_name");
		$rights = array();
		while ( $res = $db->fetch_array($query_right) )
			$rights[] = $res;	
			
		$query_user_rights = $db->query("select right_id from elo_right_user where user_id=".intval($_GET['userid']));
		$saved_rights = array();
		while ( $res2 = $db->fetch_array($query_user_rights) )
			$saved_rights[] = $res2['right_id'];
			
		$query_user_groups = $db->query("select group_id from elo_group_user where user_id=".intval($_GET['userid']));
		$saved_groups = array();
		while ( $res2 = $db->fetch_array($query_user_groups) )
			$saved_groups[] = $res2['group_id'];
		
		$query = $db->query("select user_id, user_name, user_email, lang_id, user_lastvisit from elo_user where user_id='".intval($_GET['userid'])."'");
		$res = $db->fetch_array($query);
		
		$returnData['user_data'] = $res;
		
		$saved_languages = array();
		$query_lang = $db->query("select * from elo_lang order by lang_name desc");
		while ( $res2 = $db->fetch_array($query_lang) )
			$saved_languages[] = $res2;
		
		$returnData['state'] = 'ok';
		
		$option_right_yes = array();
		$option_right_no = array();
		foreach ( $rights as $r ) {
			if ( in_array($r['right_id'],$saved_rights)) {
				$option_right_yes[$r['right_id']] = $r['right_name'];
			} else {
				$option_right_no[$r['right_id']] = $r['right_name'];
			}
		}
		$returnData['option_right_yes'] = $option_right_yes;
		$returnData['option_right_no'] = $option_right_no;
		
		$option_group_yes = array();
		$option_group_no = array();

		foreach ( $groups as $g ) {
			if ( in_array($g['group_id'],$saved_groups)) {
				$option_group_yes[$g['group_id']] = $g['group_name'];
			} else {
				$option_group_no[$g['group_id']] = $g['group_name'];
			}
		}
		$returnData['option_group_yes'] = $option_group_yes;
		$returnData['option_group_no'] = $option_group_no;
		
		$returnData['exampleCode'] = createCode(8);

		echo json_encode($returnData);
		exit();
			
	}
    else if ($action == 'cropImage') {
        if ( !isset($_POST['x1']) || !isset($_POST['x2']) || !isset($_POST['y1']) || !isset($_POST['y2']) ) {
            $returnData['state'] = 'nok';
            $returnData['text'] = 'Missing value for cropping.';
            $returnData['title'] = 'Error';	
            echo json_encode($returnData);
            exit();
        }
		
		$x1 = (int)$_POST['x1'];
		$x2 = (int)$_POST['x2'];
		$y1 = (int)$_POST['y1'];
		$y2 = (int)$_POST['y2'];
		
		$filepath = "images/profile/" . $user_res['user_picture'];
		exec($conf['convert']." ".$filepath." -crop ".($x2-$x1)."x".($y2-$y1)."+".$x1."+".$y1." ".$filepath);
		// todo: wait and check results of crop action
        $returnData['filePath'] = $filepath;
        $returnData['state'] = 'ok';
        echo json_encode($returnData);
        exit();
    }
	else if ($action == 'deleteTopic') {
		if ( !isset($_POST['topicid'])) {
			echo json_encode(toastFeedback('nok', 'No topic given.', 'Error'));
			exit();
		}
		if ( !in_array('IS_ADMIN',$user_rights) ) {
			echo json_encode(toastFeedback('nok', 'No rights.', 'Error'));
			exit();
		}
		$topicid = (int)$_POST['topicid'];
		
		$db->query("delete from elo_topic_user where topic_id='".$topicid."'");
		$db->query("delete from elo_topic_group where topic_id='".$topicid."'");
		$db->query("delete from elo_reply where topic_id='".$topicid."'");
		$db->query("delete from elo_topic where topic_id='".$topicid."'");
		
        echo json_encode(toastFeedback('ok', 'Topic deleted.', 'Success'));
        exit();
	}
	else if ($action == 'changeUser') {
		if ( isset($_POST['userid']) && isset($_POST['t_name']) && isset($_POST['t_email']) && isset($_POST['t_l']) ) {
			if (strlen($_POST['t_name']) && strlen($_POST['t_email'])) {
				$sql_pass = "";
				if ( isset($_POST['t_pass']) && strlen($_POST['t_pass'])) {
					require_once( "PasswordHash.php" );
					$hasher = new PasswordHash( 8, TRUE );
					$sql_pass = ", user_password='".$hasher->HashPassword($_POST['t_pass'])."' ";
				}
				$db->query("update elo_user set user_name='".addslashes($_POST['t_name'])."', user_email='".addslashes($_POST['t_email'])."' ".$sql_pass.", lang_id='".intval($_POST['t_l'])."' where user_id='".intval($_POST['userid'])."'");
			} else {
				$returnData = toastFeedback('nok', 'Please enter an email address and a name.', 'Error');				
			}
		} else {
			$returnData = toastFeedback('nok', 'Please enter all the data', 'Error');				
		}
		echo json_encode($returnData);
		exit();
	}
	
	else if ($action == 'loadTopic') {
		include("loadtopic.php");
		$html = "";
		foreach ( $topics as $topic ) 
			$html .= $twig->render("partials/topicblock.twig", array('topic' => $topic));
		$returnData['state'] = 'ok';
		$returnData['rows'] = sizeof($topics);
		$returnData['html'] = $html;
		echo json_encode($returnData);
		exit();
	}
	
	else if ($action == 'removeGroup') {
		if ( isset($_POST['userid']) && isset($_POST['t_r']) && is_array($_POST['t_r']) ) {		
			$user = intval($_POST['userid']);
			$returnData['state'] = 'ok';
			for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ )
				$db->query("delete from elo_group_user where user_id='".$user."' and group_id='".intval($_POST['t_r'][$i])."'");
		} else {
			$returnData = toastFeedback('nok', 'Please select a user.', 'Error');				
		}
		echo json_encode($returnData);
		exit();
	}
	
	else if ($action == 'addGroup') {
		if ( isset($_POST['userid']) && isset($_POST['t_r']) && is_array($_POST['t_r']) ) {
			$user = intval($_POST['userid']);
			$returnData['state'] = 'ok';
			for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ )
				$db->query("insert into elo_group_user (user_id, group_id) values ('".$user."', '".intval($_POST['t_r'][$i])."')");	
		} else {
			$returnData = toastFeedback('nok', 'Please select a user.', 'Error');			
		}
		echo json_encode($returnData);
		exit();
	}
	
	else if ($action == 'removeRight') {
		if ( isset($_POST['userid']) && isset($_POST['t_r']) && is_array($_POST['t_r']) ) {
			$returnData['state'] = 'ok';
			$user = intval($_POST['userid']);
			for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ )
				$db->query("delete from elo_right_user where user_id='".$user."' and right_id='".intval($_POST['t_r'][$i])."'");		
		} else {
			$returnData = toastFeedback('nok', 'Please select a user.', 'Error');
		}
		echo json_encode($returnData);
		exit();
	}
	
	else if ($action == 'removeUserFromGoup') {
		if ( isset($_POST['guid']) ) {
			$returnData['state'] = 'ok';
			$db->query("delete from elo_group_user where gu_id='".intval($_POST['guid'])."'");	
		} else {
			$returnData = toastFeedback('nok', 'Please select a user.', 'Error');
		}
		echo json_encode($returnData);
		exit();
	}
	else if ($action == 'deleteGroup') {
		if ( isset($_POST['delete_group']) ) {
			
			$group_id = (int) $_POST['delete_group'];
			// delete all user group relations
			$db->query("delete from elo_group_user where group_id='".$group_id."'");
			// delete the group
			$db->query("delete from elo_group where group_id='".$group_id."'");
			if ( $db->affected_rows() ) {
				$returnData = toastFeedback('ok', 'The group was successfully deleted.', 'Success');
			} else {
				$returnData = toastFeedback('nok', 'No group was deleted.', 'Error');
			}
		} else {
			$returnData = toastFeedback('nok', 'Please select a group.', 'Error');
		}
		echo json_encode($returnData);
		exit();
	}
	else if ($action == 'addRight') {
		if ( isset($_POST['userid']) && isset($_POST['t_r']) && is_array($_POST['t_r']) ) {
			$returnData['state'] = 'ok';
			$user = intval($_POST['userid']);
			for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ )
				$db->query("insert into elo_right_user (user_id, right_id) values ('".$user."', '".intval($_POST['t_r'][$i])."')");
		} else {
			$returnData['state'] = 'nok';
			$returnData['text'] = 'Please select a user.';
			$returnData['title'] = 'Error';
			
		}	
		echo json_encode($returnData);
		exit();
	}
	
	else if ( $action == 'getGroupUser') {
		if ( isset($_GET['group_id']) ) {
			$returnData['state'] = 'ok';
			
			$query = $db->query("SELECT elo_user.user_id, elo_user.user_name, elo_group_user.gu_id
	FROM elo_group INNER JOIN (elo_group_user INNER JOIN elo_user ON elo_group_user.user_id = elo_user.user_id) ON elo_group.group_id = elo_group_user.group_id
	GROUP BY elo_group.group_id, elo_user.user_id, elo_user.user_name
	HAVING (((elo_group.group_id)=".intval($_GET['group_id'])."));");
			$returnData['users'] = array();
			while ($res = $db->fetch_array($query)) {
				$returnData['users'][$res['gu_id']] = $res['user_name'];
			}
		} else {
			$returnData = toastFeedback('nok', 'Please select a group', 'Error');
		}
		echo json_encode($returnData);
		exit();
	}
	else if ( $action == 'changeGoup' ) {
		if ( isset($_POST['guid']) && isset($_POST['t_name']) && strlen($_POST['t_name']) > 0) {
			$returnData['state'] = 'ok';
			$db->query("update elo_group set group_name='".addslashes($_POST['t_name'])."' where group_id='".intval($_POST['guid'])."'");
		} else {
			$returnData = toastFeedback('nok', 'Please select a group and enter a name.', 'Error');
		}
		echo json_encode($returnData);
		exit();
	}
	else if ( $action == 'updateTopicTitle') {
		if ( !isset($_POST['topicid'])) {
			echo json_encode(toastFeedback('nok', 'No topic given.', 'Error'));
			exit();
		}
		$topicid = (int)$_POST['topicid'];
		$query = $db->query("SELECT elo_reply.reply_id, elo_reply.user_id, elo_reply.topic_id, elo_reply.reply_date FROM elo_topic INNER JOIN elo_reply ON elo_topic.topic_id = elo_reply.topic_id WHERE (((elo_topic.topic_id)='".$topicid."')) ORDER BY elo_reply.reply_date desc limit 1 ");
		if ( $db->num_rows($query) < 1 ) {
			echo json_encode(toastFeedback('nok', 'No topic found.', 'Error'));
			exit();
		}
		$res = $db->fetch_array($query);
		if ( ($res['user_id'] == $user_res['user_id'] && $res['reply_date'] > ($time - $conf['max_edit_time']) ) || in_array('IS_ADMIN',$user_rights)) {
			$db->query("update elo_topic set topic_title='".addslashes($_POST['t_topic_title'])."' where topic_id='".$topicid."'");
			echo json_encode(toastFeedback('ok', 'Successfully modified.', 'Success'));			
		} else {
			echo json_encode(toastFeedback('nok', 'Too late to edit.', 'Error'));
		}
		exit();
	}
	else if ( $action == 'delete_reply') {
		$returnData = array();
		if ( isset($_GET['id'])) {
	
			$replyid = intval($_GET['id']);
			
			$query = $db->query("select * from elo_reply where reply_id='".$replyid."'");
			
			if ( $db->num_rows($query) ) {
				$res = $db->fetch_array($query);
			
				if ( ($res['user_id'] == $user_res['user_id'] && $res['reply_date'] > ($time - $conf['max_edit_time']) ) || in_array('IS_ADMIN',$user_rights) ) {
					
					
					if ( isset($_GET['aid']) ) {
						$db->query("delete from elo_reply_attachment where reply_id='".$replyid."' and attachment_id='".intval($_GET['aid'])."'");
						if ( $db->affected_rows() ) {						
							$returnData['state'] = 'ok';
							$returnData['text'] = "Attachment deleted";
							$returnData['title'] = "Deleted";
							if ( isset($_GET['ref']) ) {
								header("Location: edit_reply.php?id=".$replyid);
								exit();
							}
						} else {
							$returnData = toastFeedback('nok', 'No attachment could be deleted.', 'Error');
						}
					} else {
					
						$db->query("delete from elo_reply where reply_id='".$replyid."'");
						$db->query("delete from elo_reply_attachment where reply_id='".$replyid."'");
						$db->query("delete from elo_reply_music where reply_id='".$replyid."'");
						
						$returnData = toastFeedback('ok', "Reply deleted", 'Deleted');
						
						if ( isset($_GET['ref']) ) {
							header("Location: topic.php?id=".$res['topic_id']);
							exit();
						}
					}
					
				} else {
					$returnData = toastFeedback('nok', DELETE_REPLY_NO_RIGHTS, 'Error');	
				}
			} else {
				$returnData = toastFeedback('nok', "Reply not found", 'Error');
			}
			
		} else {
			$returnData = toastFeedback('nok', DELETE_REPLY_NO_ID, 'Error');
		}
		echo json_encode($returnData);
		exit();
	}
	else if ( $action == 'getGroup') {
		$returnData = array();
		if ( isset($_GET['group_id'])) {
			$returnData['state'] = 'ok';
			$query = $db->query("select group_id, group_name from elo_group where group_id='".intval($_GET['group_id'])."'");
			$returnData['data'] = $db->fetch_array($query);
		} else {
			$returnData = toastFeedback('nok', 'Please select a group.', 'Error');
		}
		echo json_encode($returnData);
		exit();
	}
	else if ( $action == 'newTopic') {
        $returnData = array();
        // check the rights
        if ( !in_array('CREATE_TOPICS', $user_rights) ) {
			$returnData = toastFeedback('nok', 'No rights to create topics.', 'Error');
            echo json_encode($returnData);
            exit();
        }
        // check if the title and text is given
        if ( strlen($_POST['t_topic_title']) <1 || strlen($_POST['t_topic']) < 1 ) {
			$returnData = toastFeedback('nok', 'No title and/or text.', 'Error');
            echo json_encode($returnData);
            exit();
        }
        
        if ( !in_array('ALLOW_HTML', $user_rights) )  {
            $_POST['t_topic_title'] = htmlentities($_POST['t_topic_title']);
            $_POST['t_topic'] = htmlentities($_POST['t_topic']);
        }
        
        $db->query("insert into elo_topic (topic_title) values ('".addslashes($_POST['t_topic_title'])."')");

        $topicid = $db->insert_id();
        $db->query("insert into elo_reply (user_id, topic_id, reply_date, reply_text) values ('".$userid."', '".$topicid."', '".time()."', '".addslashes($_POST['t_topic'])."')");
        $reply_id = $db->insert_id();
        $db->query("insert into elo_topic_user (user_id, topic_id) values ('".$userid."', '".$topicid."')");

        if ( in_array('CREATE_ATTACHMENTS', $user_rights) && isset($_POST['picture']) ) {
            foreach ( $_POST['picture'] as $p ) 
                $db->query("insert into elo_reply_attachment (reply_id, attachment_id) values ('".$reply_id."', '".(int)$p."')");
        }

        if ( in_array('CREATE_SHEETS', $user_rights) && isset($_POST['abc']) && strlen($_POST['abc'])) {
            processMusic();
        }

        if ( in_array('ADD_USER_TO_TOPIC', $user_rights) && isset( $_POST['t_user'] )) {
			foreach ( $_POST['t_user'] as $u )
                if ( $u != $userid )
                    $db->query("insert into elo_topic_user (user_id, topic_id) values ('".(int)$u."', '".$topicid."')");
		}
		if ( in_array('ADD_GROUP_TO_TOPIC', $user_rights) && isset($_POST['t_group'])) {
			foreach ( $_POST['t_group'] as $g )
				$db->query("insert into elo_topic_group (group_id, topic_id) values ('".(int)$g."', '".$topicid."')");
		}   

        $topic = array('topic' => array('topic_title' => $_POST['t_topic_title'],
										'no_replies' => 0,
										'reply_date' => $time,
										'reply_text' => $_POST['t_topic'],
										'username' => $username,
										'last_reply_date' => '',
										'topic_id' => $topicid,
										'href' => $conf['url']."topic.php?id=".$topicid
					));        
        
		
		$returnData['html'] = $twig->render("partials/topicblock.twig", $topic);
		$returnData['state'] = 'ok';

		echo json_encode($returnData);
		exit();
        /*
        // Email to admin
        $email_text = $db->query_one("select emailtext_text from elo_emailtext where emailtext_key='NEW_TOPIC_ADMIN' and lang_id=1");

        $search_array = array("{ID}", "{USER}");
        $replace_array = array($topicid, $userid);

        $email_text = str_replace($search_array, $replace_array, $email_text);
        */
	}
}
	
