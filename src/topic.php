<?php

require('includes/application_top.php');

require_once('SBBCodeParser.php');



$breadcrumb[] = array( 'text' => 'Topics', 'href' => 'topic.php');

	if ( isset($_GET['id']) ) {
		$twig_data['topicid'] = $topicid = intval($_GET['id']);
		
		
		// check if allowed to see
		if ( !$db->query_one("select tu_id from elo_topic_user where topic_id='".$topicid."' and user_id='".$userid."' limit 1") && !in_array('IS_ADMIN',$user_rights) )
		{
			if ( !$db->query_one("select tg_id from elo_topic_group as tg, elo_group_user AS gu where tg.topic_id='".$topicid."' and tg.group_id=gu.group_id and gu.user_id='".$userid."' limit 1") )
			{
				echo $twig->render("no_access.twig", $twig_data);
				//echo "<br><br><div id='errorcomplete'>".TOPIC_TEXT_NO_RIGHT."</div></body></html>";
				exit();
			}
		}
		
		// attachments
		$query = $db->query("select r.reply_id, a.* from elo_attachment as a, elo_reply_attachment as ra, elo_reply as r where r.topic_id='".$topicid."' and r.reply_id=ra.reply_id and ra.attachment_id=a.attachment_id");
			
		$attachments = array();
		
		while ( $res = $db->fetch_array($query) ) {
			if ( !array_key_exists($res['reply_id'],$attachments)) // && !is_array($attachments[$res['reply_id']]) )
				$attachments[$res['reply_id']] = array();
			$attachments[$res['reply_id']][] = $res;
		}
		
		// music sheets
		$query = $db->query("select r.reply_id, a.*, ra.rm_id from elo_music as a, elo_reply_music as ra, elo_reply as r where r.topic_id='".$topicid."' and r.reply_id=ra.reply_id and ra.music_id=a.music_id");
			
		$sheets = array();
		
		while ( $res = $db->fetch_array($query) ) {
			if ( !array_key_exists($res['reply_id'],$sheets)) // && !is_array($sheets[$res['reply_id']]) )
				$sheets[$res['reply_id']] = array();
			$sheets[$res['reply_id']][] = $res;
		}
		
		$twig_data['attachments'] = $attachments;
		$twig_data['sheets'] = $sheets;
		$twig_data['page_title'] = $db->query_one("select topic_title from elo_topic where topic_id='".$topicid."'");
		
		
		if ( in_array('IS_ADMIN',$user_rights) ) {
			$query_see_users = $db->query("select u.user_name from elo_user as u, elo_topic_user as ut where ut.user_id=u.user_id and ut.topic_id='".$topicid."'");

			$ar_user_see_topic = array();
			if ( $db->num_rows($query_see_users)) {
				while ( $r = $db->fetch_array($query_see_users) )
					$ar_user_see_topic[] = $r['user_name'];
			}
			$twig_data['ar_user_see_topic'] = $ar_user_see_topic;
			
			$ar_group_see_topic = array();
			$query_see_groups = $db->query("select u.group_name from elo_group as u, elo_topic_group as ut where ut.group_id=u.group_id and ut.topic_id='".$topicid."'");
			if ( $db->num_rows($query_see_groups) ) {
				while ( $r = $db->fetch_array($query_see_groups) )
					$ar_group_see_topic[] = $r['group_name'];
			}
			$twig_data['ar_group_see_topic'] = $ar_group_see_topic;
		}
		
		$query = $db->query("select r.*, u.user_name, u.user_picture from elo_reply as r, elo_user as u where r.topic_id='".$topicid."' and u.user_id=r.user_id");
		$musicsheets = array();
		
		
		$replies = array();
		while ( $res = $db->fetch_array($query) ) {

			// music sheets 
			$sheets = array();
			if ( array_key_exists($res['reply_id'],$sheets) && is_array($sheets[$res['reply_id']]) ) {	
				
				foreach ( $sheets[$res['reply_id']] as $a) {
					$sheet = array();
					$musicimg = "files/m-".$a['music_id']."/".$a['music_id']."-big.png";
					if ( file_exists($musicimg) ) {
						$img_data = getimagesize($musicimg);
						$musicsheets[] = $a['rm_id'];
						$sheet['img'] = $musicimg;
						$sheet['img_data'] = $img_data[3];
					} else {
						$sheet['music_text'] = $a['music_text'];				
					}
					$sheets[] = $sheet;
				}
			}
		
			// attachments
			$attachments = array();
			if ( array_key_exists($res['reply_id'], $attachments) && is_array($attachments[$res['reply_id']])  && sizeof($attachments[$res['reply_id']]) ) {
				foreach ( $attachments[$res['reply_id']] as $a) {
					$file = $conf['file_folder'].$a['attachment_id'].base64_encode($a['attachment_filename']);
					if ( file_exists( $file ) ) {
						$att = array();
						if ( file_exists($file.".png") ) {
							$img_data = getimagesize($file.".png");
							$att['img'] = $file.'.png';
							$att['img_data'] = $img_data[3];
						}
						$filesize_s = "";
						$filesize = filesize($file);
						if ( $filesize < 1024 ) {
							$filesize_s = $filesize." Byte";
						} else if ( $filesize < 1024*1024 ) {
							$filesize_s = round($filesize/1024)." kB";
						} else if ( $filesize < 1024*1024*1024 ) {
							$filesize_s = round($filesize/(1024*1024))." MB";
						} else {
							$filesize_s = $filesize." Byte";
						}
						$att['attachment_filename'] = $a['attachment_filename'];
						$att['attachment_id'] = $a['attachment_id'];
						$att['filesize'] = $filesize_s;
						$attachments[] = $att;
					}
				}
			}
		
			$ubbParser = new SBBCodeParser_Document(); // todo: delete old text and keep the object
			$replies[] = array(	'user_name' => $res['user_name'],
								'reply_date' => $res['reply_date'],
								'user_id' => $res['user_id'],
								'reply_id' => $res['reply_id'],
								'user_picture' => $res['user_picture'],
								'reply_text' => $ubbParser->parse(stripslashes($res['reply_text']))->detect_links()->detect_emails()->get_html(),
								'attachments' => $attachments,
								'sheets' => $sheets,
								'can_edit' => (($res['user_id'] == $user_res['user_id'] && $res['reply_date'] > ($time - $conf['max_edit_time'])) || in_array('IS_ADMIN',$user_rights))
							);								
		}
		
		$breadcrumb[] = array( 'text' => $twig_data['page_title'], 'href' => '');

		$twig_data['replies'] = $replies;
		$twig_data['breadcrumb'] = $breadcrumb;
		echo $twig->render("topic_detail.twig", $twig_data);
	
	} else {
		
		$query_user = $db->query("select * from elo_user order by user_name");
		$query_groups = $db->query("select * from elo_group order by group_name");

		$groups = array();
		while ( $res = $db->fetch_array($query_groups) )
			$groups[] = $res;
		
		while ( $res = $db->fetch_array($query_user) )
			$users[] = $res;
		
		$twig_data['users'] = $users;
		$twig_data['groups'] = $groups;

		$no_topics = $db->query_one("select count(*) from ((select t.topic_id from elo_topic as t, elo_topic_group as tg, elo_group_user as gu, elo_group as g where gu.user_id='".$userid."' and gu.group_id=g.group_id and g.group_id=tg.group_id and tg.topic_id=t.topic_id)
	UNION all
	(select t.topic_id from elo_topic as t, elo_topic_user as tu where tu.user_id='".$userid."' and tu.topic_id=t.topic_id) ) as ta");

		include("loadtopic.php");
		$twig_data['breadcrumb'] = $breadcrumb;
		echo $twig->render("topic.twig", $twig_data);
   
	}
