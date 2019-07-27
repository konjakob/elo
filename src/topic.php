<?php

require_once('includes/application_top.php');
require_once('includes/SBBCodeParser.php');

$breadcrumb[] = array( 'text' => _('Topics'), 'href' => 'topic.php');

	if ( isset($_GET['id']) ) {
		$twig_data['topicid'] = $topicid = (int)$_GET['id'];
		
		// check if allowed to see the topic
		$statement = $pdo->prepare("select tu_id from elo_topic_user where topic_id=:topicid and user_id=:userid limit 1");
		$statement->bindValue(':userid', $userid, PDO::PARAM_INT);
		$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
		$statement->execute();
		$res = $statement->fetch(PDO::FETCH_ASSOC);
		
		if ( !$res && !in_array('IS_ADMIN',$user_rights) )
		{
			$statement = $pdo->prepare("select tg_id from elo_topic_group as tg, elo_group_user AS gu where tg.topic_id=:topicid and tg.group_id=gu.group_id and gu.user_id=:userid limit 1");
			$statement->bindValue(':userid', $userid, PDO::PARAM_INT);
			$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
			$statement->execute();
			$res = $statement->fetch(PDO::FETCH_ASSOC);
			if ( !$res ) {
				echo $twig->render("no_access.twig", $twig_data);
				exit();
			}
		}
        
        /* Get the topic information */
		$statement = $pdo->prepare("select topic_title from elo_topic where topic_id=:topicid and elo_topic.visible_from<NOW() and elo_topic.visible_till>NOW()");
		$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
		$statement->execute();
		$res = $statement->fetch(PDO::FETCH_ASSOC);
        if ( !$res ) {
            // does not exist, or not visible
            echo $twig->render("no_access.twig", $twig_data);
            exit();
        }
        
        $twig_data['page_title'] = $res['topic_title'];
		
		// attachments
		$statement = $pdo->prepare("select r.reply_id, a.* from elo_attachment as a, elo_reply_attachment as ra, elo_reply as r where r.topic_id=:topicid and r.reply_id=ra.reply_id and ra.attachment_id=a.attachment_id");
		$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
		$statement->execute();
					
		$attachments = array();	
		while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false ) {
			if ( !array_key_exists($res['reply_id'],$attachments))
				$attachments[$res['reply_id']] = array();
			$attachments[$res['reply_id']][] = $res;
		}
		
		// music sheets
		$statement = $pdo->prepare("select r.reply_id, a.*, ra.rm_id from elo_music as a, elo_reply_music as ra, elo_reply as r where r.topic_id=:topicid and r.reply_id=ra.reply_id and ra.music_id=a.music_id");
		$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
		$statement->execute();
			
		$sheets = array();		
		while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false ) {
			if ( !array_key_exists($res['reply_id'],$sheets))
				$sheets[$res['reply_id']] = array();
			$sheets[$res['reply_id']][] = $res;
		}
		
		$twig_data['attachments'] = $attachments;
		$twig_data['sheets'] = $sheets;
				
		if ( in_array('IS_ADMIN',$user_rights) ) {
			
			/* Users who can see this topic */
			$statement = $pdo->prepare("select u.user_name from elo_user as u, elo_topic_user as ut where ut.user_id=u.user_id and ut.topic_id=:topicid");
			$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
			$statement->execute();

			$ar_user_see_topic = array();
			while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false ) {
				$ar_user_see_topic[] = $res['user_name'];
			}
			$twig_data['ar_user_see_topic'] = $ar_user_see_topic;
			
			/* Groups which can see this topic */
			$statement = $pdo->prepare("select u.group_name from elo_group as u, elo_topic_group as ut where ut.group_id=u.group_id and ut.topic_id=:topicid");
			$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
			$statement->execute();
			
			$ar_group_see_topic = array();
			while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false ) {
					$ar_group_see_topic[] = $res['group_name'];
			}
			$twig_data['ar_group_see_topic'] = $ar_group_see_topic;
		}
		
		/* Get all replies */
		$statement = $pdo->prepare("select r.*, u.user_name, u.user_picture from elo_reply as r, elo_user as u where r.topic_id=:topicid and u.user_id=r.user_id");
		$statement->bindValue(':topicid', $topicid, PDO::PARAM_INT);
		$statement->execute();

		//$musicsheets = array();	
		$replies = array();
		while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false ) {

			// music sheets 
			$sheets_reply = array();
			if ( array_key_exists($res['reply_id'],$sheets) && is_array($sheets[$res['reply_id']]) ) {	
				
				foreach ( $sheets[$res['reply_id']] as $a) {
					$sheet = array();
					$musicimg = "files/m-".$a['music_id']."/".$a['music_id']."-big.png";
					if ( file_exists($musicimg) ) {
						$img_data = getimagesize($musicimg);
						//$musicsheets[] = $a['rm_id'];
						$sheet['img'] = $musicimg;
						$sheet['img_data'] = $img_data[3];
					} else {
						$sheet['music_text'] = $a['music_text'];				
					}
					$sheet['music_id'] = $a['music_id'];
					$sheets_reply[] = $sheet;
				}
			}
		
			// attachments
			$attachments_reply = array();
			if ( array_key_exists($res['reply_id'], $attachments) && is_array($attachments[$res['reply_id']])  && sizeof($attachments[$res['reply_id']]) ) {
				$attachments_reply = prepareAttachments($attachments[$res['reply_id']]);
			}
		
			$ubbParser = new SBBCodeParser_Document(); // todo: delete old text and keep the object
			$replies[] = array(	'user_name' => $res['user_name'],
								'reply_date' => $res['reply_date'],
								'user_id' => $res['user_id'],
								'reply_id' => $res['reply_id'],
								'user_picture' => $res['user_picture'],
								'reply_text' => $ubbParser->parse(stripslashes($res['reply_text']))->detect_links()->detect_emails()->get_html(),
								'attachments' => $attachments_reply,
								'sheets' => $sheets_reply,
								'can_edit' => (($res['user_id'] == $user_res['user_id'] && $res['reply_date'] > ($time - $conf['max_edit_time'])) || in_array('IS_ADMIN',$user_rights))
							);								
		}
		
		$breadcrumb[] = array( 'text' => $twig_data['page_title'], 'href' => '');

		$twig_data['replies'] = $replies;
		$twig_data['breadcrumb'] = $breadcrumb;
		echo $twig->render("topic_detail.twig", $twig_data);
	
	} else {
		
		$query_user = $pdo->prepare("select * from elo_user order by user_name");
		$query_user->execute();

		$query_groups = $pdo->prepare("select * from elo_group order by group_name");
		$query_groups->execute();

		$groups = array();
		while ( ($res = $query_groups->fetch(PDO::FETCH_ASSOC)) !== false )
			$groups[] = $res;
		
		$users = array();
		while ( ($res = $query_user->fetch(PDO::FETCH_ASSOC)) !== false )
			$users[] = $res;
		
		$twig_data['users'] = $users;
		$twig_data['groups'] = $groups;

		$statement = $pdo->prepare("select count(*) as no from ((select t.topic_id from elo_topic as t, elo_topic_group as tg, elo_group_user as gu, elo_group as g where gu.user_id=:userid and gu.group_id=g.group_id and g.group_id=tg.group_id and tg.topic_id=t.topic_id)
	UNION all
	(select t.topic_id from elo_topic as t, elo_topic_user as tu where tu.user_id=:userid and tu.topic_id=t.topic_id) ) as ta");
		$statement->bindValue(':userid', $userid, PDO::PARAM_INT);
		$statement->execute();
		$res = $statement->fetch(PDO::FETCH_ASSOC);
		$no_topics = $res['no'];

		include("includes/loadtopic.php");
		$twig_data['breadcrumb'] = $breadcrumb;
		echo $twig->render("topic.twig", $twig_data);
   
	}
