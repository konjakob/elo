<?php

require('includes/application_top.php');
	
$returnData = array();    
    
if(isset($_GET['action']) || isset($_POST['action'])) {
	
	$action = isset($_GET['action'])  ?  $_GET['action'] : $_POST['action'];
	
	if ( $action == 'getUser' ) {
	    
        if ( !in_array('IS_ADMIN',$user_rights) ) {
			echo json_encode(toastFeedback('nok', 'No rights.', 'Error'));
			exit();
		}
        
        if ( !isset($_GET['userid']) ) {
            $returnData['state'] = 'nok';
            $returnData['text'] = 'No user given.';
            json_encode($returnData);
            exit();
        }
        
		$statement = $pdo->prepare("select * from elo_group order by group_name");
		$statement->execute();
		$groups = array();
		while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
			$groups[] = $res;
		
        $statement = $pdo->prepare("select * from elo_right order by right_name");
		$statement->execute();
		$rights = array();
		while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
			$rights[] = $res;	
		
        $statement = $pdo->prepare("select right_id from elo_right_user where user_id=:userid");
        $statement->bindValue(':userid', filter_input(INPUT_GET, 'userid', FILTER_SANITIZE_NUMBER_INT), PDO::PARAM_INT);
        $statement->execute();
		$saved_rights = array();
		while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
			$saved_rights[] = $res['right_id'];
		
		$statement = $pdo->prepare("select group_id from elo_group_user where user_id=:userid");
		$statement->bindValue(':userid', filter_input(INPUT_GET, 'userid', FILTER_SANITIZE_NUMBER_INT), PDO::PARAM_INT);
		$statement->execute();
		$saved_groups = array();
		while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
			$saved_groups[] = $res['group_id'];
		
		$statement = $pdo->prepare("select user_id, user_name, user_email, lang_id, user_lastvisit from elo_user where user_id=:userid");
		$statement->bindValue(':userid', filter_input(INPUT_GET, 'userid', FILTER_SANITIZE_NUMBER_INT), PDO::PARAM_INT);
		$statement->execute();
		$res = $statement->fetch(PDO::FETCH_ASSOC);
		
		$returnData['user_data'] = $res;
		
		$saved_languages = array();
		$statement = $pdo->prepare("select * from elo_lang order by lang_name desc");
		$statement->execute();
		while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
			$saved_languages[] = $res;
		
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
		
        $x1 = (int)filter_input(INPUT_POST,'x1',FILTER_SANITIZE_NUMBER_INT);
        $x2 = (int)filter_input(INPUT_POST,'x2',FILTER_SANITIZE_NUMBER_INT);
        $y1 = (int)filter_input(INPUT_POST,'y1',FILTER_SANITIZE_NUMBER_INT);
        $y2 = (int)filter_input(INPUT_POST,'y2',FILTER_SANITIZE_NUMBER_INT);

		$filepath = "images/profile/" . $user_res['user_picture'];
		list($width, $height, $type, $attr) = getimagesize($filepath);
		
        if ( $width > IMAGE_CROP_MAX_WIDTH_HEIGHT || $height > IMAGE_CROP_MAX_WIDTH_HEIGHT ) {
			$factor = 1;
			if ( $height > IMAGE_CROP_MAX_WIDTH_HEIGHT ) {
				$factor = $height/IMAGE_CROP_MAX_WIDTH_HEIGHT;
			} else {
				$factor = $width/IMAGE_CROP_MAX_WIDTH_HEIGHT;
			}
			$x1 *= $factor;
			$x2 *= $factor;
			$y1 *= $factor;
			$y2 *= $factor;			
		}		
		
        exec($conf['convert']." ".$filepath." -crop ".($x2-$x1)."x".($y2-$y1)."+".$x1."+".$y1." ".$filepath);

        $returnData['filePath'] = $filepath;
        $returnData['state'] = 'ok';
        echo json_encode($returnData);
        exit();
    }
	else if ($action == 'deleteTopic') {
        
        if ( !in_array('IS_ADMIN',$user_rights) || !in_array('CAN_DELETE_TOPICS',$user_rights)) {
			echo json_encode(toastFeedback('nok', 'No rights.', 'Error'));
			exit();
		}
        
		if ( !isset($_POST['topicid'])) {
			echo json_encode(toastFeedback('nok', 'No topic given.', 'Error'));
			exit();
		}
		
		$topicid = (int)$_POST['topicid'];
		
		$statement = $pdo->prepare("delete from elo_topic_user where topic_id=:topicid");
		$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
		$statement->execute();
                
		$statement = $pdo->prepare("delete from elo_topic_group where topic_id=:topicid");
		$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
		$statement->execute();
                
		$statement = $pdo->prepare("delete from elo_reply where topic_id=:topicid");
		$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
		$statement->execute();
                
		$statement = $pdo->prepare("delete from elo_topic where topic_id=:topicid");
		$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
		$statement->execute();
	
		echo json_encode(toastFeedback('ok', 'Topic deleted.', 'Success'));
		exit();
	}
	else if ($action == 'changeUser') {
        
        if ( !in_array('IS_ADMIN',$user_rights) || !in_array('CAN_MODIFY_USERS',$user_rights)) {
			echo json_encode(toastFeedback('nok', 'No rights.', 'Error'));
			exit();
		}
        
		if ( isset($_POST['userid']) && isset($_POST['t_name']) && isset($_POST['t_email']) && isset($_POST['t_l']) ) {
            
			if ( strlen($_POST['t_name']) < $conf['min_length_username'] && strlen($_POST['t_email'])) {
				$sql_pass = "";
				
				require_once( "PasswordHash.php" );
				$hasher = new PasswordHash( 8, TRUE );
					
				if ( isset($_POST['t_pass']) && strlen($_POST['t_pass'])) {
					$sql_pass = ", user_password=:user_password ";
				}
				
				$statement = $pdo->prepare("update elo_user set user_name=:t_name, user_email=:t_email ".$sql_pass.", lang_id=:t_lang where user_id=:userid");
				$statement->bindValue(':userid', $userid, PDO::PARAM_INT);
				$statement->bindValue(':t_email', $_POST['t_email']);
				$statement->bindValue(':t_lang', (int)$_POST['t_lang'], PDO::PARAM_INT);
				$statement->bindValue(':t_name', $_POST['t_name']);
				if ( isset($_POST['t_pass']) && strlen($_POST['t_pass']))
					$statement->bindValue(':user_password', $hasher->HashPassword($_POST['t_pass']));
				$statement->execute();
		
			} else {
				$returnData = toastFeedback('nok', 'Please enter an email address and a name with '.$conf['min_length_username'].' characters.', 'Error');				
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
        if ( !in_array('IS_ADMIN',$user_rights)  || !in_array('CAN_DELETE_GROUPS',$user_rights)) {
			echo json_encode(toastFeedback('nok', 'No rights.', 'Error'));
			exit();
		}
		if ( isset($_POST['userid']) && isset($_POST['t_r']) && is_array($_POST['t_r']) ) {		
			$user = intval($_POST['userid']);
			$returnData['state'] = 'ok';
			for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ ) {
                $statement = $pdo->prepare("delete from elo_group_user where user_id=:user and group_id=:group");
                $statement->bindValue(':user', $user, PDO::PARAM_INT);
                $statement->bindValue(':group',(int)$_POST['t_r'][$i], PDO::PARAM_INT);
                $statement->execute();
            }
		} else {
			$returnData = toastFeedback('nok', 'Please select a user.', 'Error');				
		}
		echo json_encode($returnData);
		exit();
	}
	
	else if ($action == 'addGroup') {
        if ( !in_array('IS_ADMIN',$user_rights)  || !in_array('CAN_ADD_USERS_TO_GROUPS',$user_rights)) {
			echo json_encode(toastFeedback('nok', 'No rights.', 'Error'));
			exit();
		}
		if ( isset($_POST['userid']) && isset($_POST['t_r']) && is_array($_POST['t_r']) ) {
			$user = (int)$_POST['userid'];
			$returnData['state'] = 'ok';
			for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ ) {
                $statement = $pdo->prepare("insert into elo_group_user (user_id, group_id) values (:user, :group)");
                $statement->bindValue(':user', $user, PDO::PARAM_INT);
                $statement->bindValue(':group',(int)$_POST['t_r'][$i], PDO::PARAM_INT);
                $statement->execute();
            }
		} else {
			$returnData = toastFeedback('nok', 'Please select a user.', 'Error');			
		}
		echo json_encode($returnData);
		exit();
	}
	
	else if ($action == 'removeRight') {
        if ( !in_array('IS_ADMIN',$user_rights)  || !in_array('CAN_DELETE_USER_RIGHTS',$user_rights)) {
			echo json_encode(toastFeedback('nok', 'No rights.', 'Error'));
			exit();
		}
		if ( isset($_POST['userid']) && isset($_POST['t_r']) && is_array($_POST['t_r']) ) {
			$returnData['state'] = 'ok';
			$user = intval($_POST['userid']);
			for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ ) {	
                $statement = $pdo->prepare("delete from elo_right_user where user_id=:user and right_id=:right");
                $statement->bindValue(':user', $user, PDO::PARAM_INT);
                $statement->bindValue(':right',(int)$_POST['t_r'][$i], PDO::PARAM_INT);
                $statement->execute();
            }
		} else {
			$returnData = toastFeedback('nok', 'Please select a user.', 'Error');
		}
		echo json_encode($returnData);
		exit();
	}
	else if ( $action == 'generatePreview' ) {
		if ( isset($_GET['fileid']) ) {
		
			$statement = $pdo->prepare("select * from elo_attachment where attachment_id=:aid");
			$statement->bindValue(':aid', (int)$_GET['fileid'], PDO::PARAM_INT);
			$statement->execute();
			
			$res = $statement->fetch(PDO::FETCH_ASSOC);
			
			$filename = $res['attachment_id'].base64_encode($res['attachment_filename']);
			
			if (  preg_match('/[(pdf)|(gif)|(png)|(jpeg)|(jpg)]$/',$res['attachment_filename']) ) {
				exec($conf['convert']." \"".$conf['file_folder']."{".$filename."}[0]\" -colorspace RGB -geometry 200 \"".$conf['file_folder'].$filename.".png\"");
				
				$returnData['preview'] = $conf['file_folder'].$filename.".png";
				$returnData['state'] = 'ok';
				$returnData['text'] = 'Preview generated.';
				$returnData['title'] = 'Success';
				echo json_encode($returnData);
			} else {
				echo json_encode(toastFeedback('nok', 'Not a file, which can be previewed.', 'Error'));
			}
		} else {
			echo json_encode(toastFeedback('nok', 'No attachment given.', 'Error'));
		}	
		exit();
	}	
	else if ($action == 'removeUserFromGoup') {
        if ( !in_array('IS_ADMIN',$user_rights)  || !in_array('CAN_DELETE_USER_FROM_GROUP',$user_rights)) {
			echo json_encode(toastFeedback('nok', 'No rights.', 'Error'));
			exit();
		}
		if ( isset($_POST['guid']) ) {
			$returnData['state'] = 'ok';
            $statement = $pdo->prepare("delete from elo_group_user where gu_id=:group_user");
            $statement->bindValue(':group_user', (int)$_POST['guid'], PDO::PARAM_INT);
            $statement->execute();
		} else {
			$returnData = toastFeedback('nok', 'Please select a user.', 'Error');
		}
		echo json_encode($returnData);
		exit();
	}
	else if ($action == 'deleteGroup') {
        if ( !in_array('IS_ADMIN',$user_rights)  || !in_array('CAN_DELETE_GROUPS',$user_rights)) {
			echo json_encode(toastFeedback('nok', 'No rights.', 'Error'));
			exit();
		}
		if ( isset($_POST['delete_group']) ) {
			
			$group_id = (int) $_POST['delete_group'];
            
			// delete all user group relations
            $statement = $pdo->prepare("delete from elo_group_user where group_id=:group");
            $statement->bindValue(':group',$group_id, PDO::PARAM_INT);
            $statement->execute();

			// delete the group
            $statement = $pdo->prepare("delete from elo_group where group_id=:group");
            $statement->bindValue(':group',$group_id, PDO::PARAM_INT);
            $statement->execute();
            
			if (  $statement->rowCount() ) {
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
	else if ($action == 'validate') {
		$response = array(
		  'valid' => false,
		  'message' => 'No argument given.'
		);

		if( isset($_POST['t_email']) ) {
			
			if ( !filter_var($_POST['t_email'], FILTER_VALIDATE_EMAIL) ) {
				$response = array('valid' => false, 'message' => 'Please provide a valid email address.');
			} else {
				$statement = $pdo->prepare("select user_id from elo_user where user_email=:t_email");
				$statement->bindValue(':t_email', filter_input(INPUT_POST, 't_email', FILTER_SANITIZE_EMAIL));
				$statement->execute();	
				
				if ( $statement->rowCount() ) {
					$response = array('valid' => false, 'message' => 'This email is already registered.');
				} else {
					// email not yet registered
					$response = array('valid' => true);
				}
			}
		} else if ($_POST['t_email_c']) {
			if ( !filter_var($_POST['t_email_c'], FILTER_VALIDATE_EMAIL) ) {
				$response = array('valid' => false, 'message' => 'Please provide a valid email address.');
			} else {
				$statement = $pdo->prepare("select user_id from elo_user where user_email=:t_email and user_id<>:user_id");
				$statement->bindValue(':t_email', filter_input(INPUT_POST, 't_email_c', FILTER_SANITIZE_EMAIL));
				$statement->bindValue(':user_id', (int)filter_input(INPUT_POST, 'userid'), PDO::PARAM_INT);
				$statement->execute();	
				
				if ( $statement->rowCount() ) {
					$response = array('valid' => false, 'message' => 'This email is already registered.');
				} else {
					// email not yet registered
					$response = array('valid' => true);
				}
			}
		}
		echo json_encode($response);
		exit();
	}
	else if ($action == 'addRight') {
		if ( isset($_POST['userid']) && isset($_POST['t_r']) && is_array($_POST['t_r']) ) {
			$returnData['state'] = 'ok';
			$user = intval($_POST['userid']);
			for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ ) {
                $statement = $pdo->prepare("insert into elo_right_user (user_id, right_id) values (:user, :right)");
                $statement->bindValue(':user',$user, PDO::PARAM_INT);
                $statement->bindValue(':right',(int)$_POST['t_r'][$i], PDO::PARAM_INT);
                $statement->execute();
            }
		} else {
			$returnData['state'] = 'nok';
			$returnData['text'] = 'Please select a user.';
			$returnData['title'] = 'Error';
			
		}	
		echo json_encode($returnData);
		exit();
	}
	else if ($action == 'newGroup' ) {
        if ( !in_array('IS_ADMIN',$user_rights) && !in_array('CREATE_GROUPS',$user_rights)) {
			echo json_encode(toastFeedback('nok', 'No rights.', 'Error'));
			exit();
		}
		if ( isset($_POST['t_group']) ) {
			$statement = $pdo->prepare("insert into elo_group (group_name) values (:t_group)");
			$statement->bindValue(':t_group', filter_input(INPUT_POST, 't_group'));
			$statement->execute();
            $returnData['groupId'] = $pdo->lastInsertId();
            $returnData['state'] = 'ok';
			$returnData['text'] = 'Successfully created.';
			$returnData['title'] = 'Success';
            echo json_encode($returnData);
		} else {
			echo json_encode(toastFeedback('nok', 'No group name given.', 'Error'));	
		}
        exit();
	}
	else if ( $action == 'getGroupUser') {
		if ( isset($_GET['group_id']) ) {
			$returnData['state'] = 'ok';
			
            $statement = $pdo->prepare("SELECT elo_user.user_id, elo_user.user_name, elo_group_user.gu_id
	FROM elo_group INNER JOIN (elo_group_user INNER JOIN elo_user ON elo_group_user.user_id = elo_user.user_id) ON elo_group.group_id = elo_group_user.group_id
	GROUP BY elo_group.group_id, elo_user.user_id, elo_user.user_name
	HAVING (((elo_group.group_id)=:group));");
            $statement->bindValue(':group',(int)$_GET['group_id'], PDO::PARAM_INT);
            $statement->execute();

			$returnData['users'] = array();
			while (($res = $statement->fetch(PDO::FETCH_ASSOC)) !== null) {
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
            $statement = $pdo->prepare("update elo_group set group_name=:name where group_id=:group");
            $statement->bindValue(':name',$_POST['t_name']);
            $statement->bindValue(':group',(int)$_POST['guid'], PDO::PARAM_INT);
            $statement->execute();
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
        $statement = $pdo->prepare("SELECT elo_reply.reply_id, elo_reply.user_id, elo_reply.topic_id, elo_reply.reply_date FROM elo_topic INNER JOIN elo_reply ON elo_topic.topic_id = elo_reply.topic_id WHERE (((elo_topic.topic_id)=:topicid)) ORDER BY elo_reply.reply_date desc limit 1");
        $statement->bindValue(':topicid',$topicid, PDO::PARAM_INT);
        $statement->execute();
		if ( $statement->rowCount() < 1 ) {
			echo json_encode(toastFeedback('nok', 'No topic found.', 'Error'));
			exit();
		}
        $res = $statement->fetch(PDO::FETCH_ASSOC);
		if ( ($res['user_id'] == $user_res['user_id'] && $res['reply_date'] > ($time - $conf['max_edit_time']) ) || in_array('IS_ADMIN',$user_rights)) {
            
            $statement = $pdo->prepare("update elo_topic set topic_title=:t_topic_title where topic_id=:topicid");
            $statement->bindValue(':topicid',$topicid, PDO::PARAM_INT);
            $statement->bindValue(':t_topic_title', $_POST['t_topic_title']);
            $statement->execute();

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
			
            $statement = $pdo->prepare("select * from elo_reply where reply_id=:replyid");
            $statement->bindValue(':replyid',$replyid, PDO::PARAM_INT);
            $statement->execute();
			
			if ( $statement->rowCount() ) {
                $res = $statement->fetch(PDO::FETCH_ASSOC);
			
				if ( ($res['user_id'] == $user_res['user_id'] && $res['reply_date'] > ($time - $conf['max_edit_time']) ) || in_array('IS_ADMIN',$user_rights) ) {
					
					
					if ( isset($_GET['aid']) ) {
                        $statement = $pdo->prepare("delete from elo_reply_attachment where reply_id=:replyid and attachment_id=:aid");
                        $statement->bindValue(':replyid',$replyid, PDO::PARAM_INT);
                        $statement->bindValue(':aid',(int)$_GET['aid'], PDO::PARAM_INT);
                        $statement->execute();

						if (  $statement->rowCount()) {						
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
					
                        $statement = $pdo->prepare("delete from elo_reply where reply_id=:replyid");
                        $statement->bindValue(':replyid',$replyid, PDO::PARAM_INT);
                        $statement->execute();
                        
                        $statement = $pdo->prepare("delete from elo_reply_attachment where reply_id=:replyid");
                        $statement->bindValue(':replyid',$replyid, PDO::PARAM_INT);
                        $statement->execute();
                        
                        $statement = $pdo->prepare("delete from elo_reply_music where reply_id=:replyid");
                        $statement->bindValue(':replyid',$replyid, PDO::PARAM_INT);
                        $statement->execute();
						
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
            
            $statement = $pdo->prepare("select group_id, group_name from elo_group where group_id=:group_id");
            $statement->bindValue(':group_id',(int)$_GET['group_id'], PDO::PARAM_INT);
            $statement->execute();
            
			$returnData['data'] = $statement->fetch(PDO::FETCH_ASSOC);
		} else {
			$returnData = toastFeedback('nok', 'Please select a group.', 'Error');
		}
		echo json_encode($returnData);
		exit();
	}
	else if ( $action == 'deleteFileAttachment') {
		if ( !in_array('IS_ADMIN',$user_rights) ) {
			echo json_encode(toastFeedback('nok', 'No rights.', 'Error'));
			exit();
		}
		if ( isset($_GET['aid']) ) {
			$statement = $pdo->prepare("delete from elo_reply_attachment where attachment_id=:aid");
			$statement->bindValue(':aid',(int)$_GET['aid'], PDO::PARAM_INT);
			$statement->execute();
			
			$statement = $pdo->prepare("delete from elo_attachment where attachment_id=:aid");
			$statement->bindValue(':aid',(int)$_GET['aid'], PDO::PARAM_INT);
			$statement->execute();

			if (  $statement->rowCount()) {						
				$returnData['state'] = 'ok';
				$returnData['text'] = "Attachment deleted";
				$returnData['title'] = "Deleted";
			} else {
				$returnData = toastFeedback('nok', 'No attachment could be deleted.', 'Error');
			}	
			echo json_encode($returnData);
			exit();
		}
		
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

		$startTime = DateTime::createFromFormat("d.m.Y H:i", $_POST['date_start']);
		$endTime = DateTime::createFromFormat("d.m.Y H:i", $_POST['date_end']);
		
		$statement = $pdo->prepare("insert into elo_topic (topic_title, visible_from, visible_till) values (:t_topic_title, :from, :till)");
		$statement->bindValue(':t_topic_title', $_POST['t_topic_title']);
		$statement->bindValue(':from', $startTime->format('Y-m-d H:i:s'));
		$statement->bindValue(':till', $endTime->format('Y-m-d H:i:s'));
		$statement->execute();

        $topicid = $pdo->lastInsertId();
		
		$statement = $pdo->prepare("insert into elo_reply (user_id, topic_id, reply_date, reply_text) values (:userid, :topicid, :time, :t_topic)");
		$statement->bindValue(':userid', $userid, PDO::PARAM_INT);
		$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
		$statement->bindValue(':time', time(), PDO::PARAM_INT);
		$statement->bindValue(':t_topic', $_POST['t_topic']);
		$statement->execute();
		
        $reply_id = $pdo->lastInsertId();
		
		$statement = $pdo->prepare("insert into elo_topic_user (user_id, topic_id) values (:userid, :topicid)");
		$statement->bindValue(':userid', $userid, PDO::PARAM_INT);
		$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
		$statement->execute();

        if ( in_array('CREATE_ATTACHMENTS', $user_rights) && isset($_POST['picture']) ) {
            foreach ( $_POST['picture'] as $p ) {
				$statement = $pdo->prepare("insert into elo_reply_attachment (reply_id, attachment_id) values (:reply_id, :p)");
				$statement->bindValue(':reply_id', $reply_id, PDO::PARAM_INT);
				$statement->bindValue(':p', $p, PDO::PARAM_INT);
				$statement->execute();
			}
        }

        if ( in_array('CREATE_SHEETS', $user_rights) && isset($_POST['abc']) && strlen($_POST['abc'])) {
            processMusic();
        }

        if ( in_array('ADD_USER_TO_TOPIC', $user_rights) && isset( $_POST['t_user'] )) {
			foreach ( $_POST['t_user'] as $u )
                if ( $u != $userid ) {
					$statement = $pdo->prepare("insert into elo_topic_user (user_id, topic_id) values (:u, :topicid)");
					$statement->bindValue(':u', $u, PDO::PARAM_INT);
					$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
					$statement->execute();					
				}
		}
		if ( in_array('ADD_GROUP_TO_TOPIC', $user_rights) && isset($_POST['t_group'])) {
			foreach ( $_POST['t_group'] as $g ) {
				$statement = $pdo->prepare("insert into elo_topic_group (group_id, topic_id) values (:g, :topicid)");
				$statement->bindValue(':g', $g, PDO::PARAM_INT);
				$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
				$statement->execute();	
            }
		}
        
        $dateNow = new DateTime();
        if (  $dateNow->getTimestamp() > $startTime->getTimestamp() && $dateNow->getTimestamp() < $endTime->getTimestamp() ) {

            $topic = array('topic' => array('topic_title' => $_POST['t_topic_title'],
                                            'no_replies' => 0,
                                            'reply_date' => $time,
                                            'reply_text' => $_POST['t_topic'],
                                            'username' => $username,
                                            'last_reply_date' => '',
                                            'topic_id' => $topicid,
                                            'href' => $conf['url']."topic.php?id=".$topicid,
                                            'user_picture' => $user_res['user_picture']
                        ));        
                    
            $returnData['html'] = $twig->render("partials/topicblock.twig", $topic);
            
        } else {
            $returnData['addComment'] = "Your topic is not visible, due to the start and end time you entered.";
        }
              
		$returnData['state'] = 'ok';

		echo json_encode($returnData);
		exit();
        /*
        // Email to admin
		$statement = $pdo->prepare("select emailtext_text from elo_emailtext where emailtext_key='NEW_TOPIC_ADMIN' and lang_id=1");
		$statement->execute();
		$res = $statement->fetch(PDO::FETCH_ASSOC);
		$email_text = $res['emailtext_text'];

        $search_array = array("{ID}", "{USER}");
        $replace_array = array($topicid, $userid);

        $email_text = str_replace($search_array, $replace_array, $email_text);
        */
	}
}
	
