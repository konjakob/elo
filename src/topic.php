<?php

require_once("dbclass.php");
$db = new db;

require("authenticate.class.php");

$auth = new Authenticate;

if(!$auth->validateAuthCookie())
	header("Location: login.php?ref=".base64_encode($_SERVER['PHP_SELF']."?".$_SERVER["QUERY_STRING"]));

require("functions.php");

$userid = $auth->getUserId();

$query = $db->query("select r.right_key from elo_right as r, elo_right_user as ru where r.right_id=ru.right_id and ru.user_id='".$userid."'");
$user_rights = array();

while ( $res = $db->fetch_array($query) )
	$user_rights[] = $res['right_key'];

$twig_data['user_rights'] = $user_rights;

$query = $db->query("select elo_user.*, elo_lang.lang_code from elo_user left join elo_lang ON (elo_user.lang_id=elo_lang.lang_id) where user_id='".$userid."' limit 1");
$user_res = $db->fetch_array($query);
$username = $twig_data['user_name'] = $user_res['user_name'];
$langcode = $user_res['lang_code'];

if ( strlen($langcode) <1 )
	$langcode = "en";

require_once('includes/languages/'.$langcode.'.php');

require_once('SBBCodeParser.php');

$time = time();



if ( isset($_GET['id']) ) {
	$topicid = intval($_GET['id']);

	
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
	
	$query = $db->query("select r.*, u.user_name from elo_reply as r, elo_user as u where r.topic_id='".$topicid."' and u.user_id=r.user_id");
	$musicsheets = array();
	
	$replies = array();
	while ( $res = $db->fetch_array($query) ) {

		?>
		<a name="<?=$res['reply_id']?>"></a><div style="border:thin;border-style:dashed;padding:10px; margin-left:20px; margin-right:20px"><div style="float:left;width:200px;"><strong><?=stripslashes($res['user_name'])?></strong><br><? 
		
		echo "<span id='small'>".date($conf['date_format'],$res['reply_date'])."</span>";
		
		if ( ($res['user_id'] == $user_res['user_id'] && $res['reply_date'] > ($time - $conf['max_edit_time'])) || in_array('IS_ADMIN',$user_rights)) {
			echo "<br><br><a href='edit_reply.php?id=".$res['reply_id']."'>".TOPIC_EDIT."</a> | <a href='delete_reply.php?id=".$res['reply_id']."'>".TOPIC_DELETE."</a>";
		}
		
		?></div><div style="float:left">
		
        <?
		$ubbParser = new SBBCodeParser_Document();
		echo $ubbParser->parse(stripslashes($res['reply_text']))->detect_links()->detect_emails()->get_html();

		// music sheets 
		if ( array_key_exists($res['reply_id'],$sheets) && is_array($sheets[$res['reply_id']]) ) {	
			
			foreach ( $sheets[$res['reply_id']] as $a) {
				echo "<hr>";
				$musicimg = "files/m-".$a['music_id']."/".$a['music_id']."-big.png";
				if ( file_exists($musicimg) ) {
					$img_data = getimagesize($musicimg);
					$musicsheets[] = $a['rm_id'];
					echo "<div><img src='".$musicimg."' ".$img_data[3]."></div>";
				} else {
					echo '<div style="display:none"><textarea name="abc'.$a['rm_id'].'" id="abc'.$a['rm_id'].'" cols="80" rows="15">'.$a['music_text'].'</textarea></div>';
					echo '<div id="midi'.$a['rm_id'].'"></div><div id="paper0'.$a['rm_id'].'"></div><div id="music'.$a['rm_id'].'"></div>';
				}
				echo TOPIC_TEXT_DOWNLOADS.': <a href="download.php?type=abc&mid='.$a['music_id'].'">ABC</a> | <a href="download.php?type=pdf&mid='.$a['music_id'].'">PDF</a> | <a href="download.php?type=midi&mid='.$a['music_id'].'">MIDI</a>';
			}
		}
		
		// attachments
		if ( array_key_exists($res['reply_id'], $attachments) && is_array($attachments[$res['reply_id']])  && sizeof($attachments[$res['reply_id']]) ) {
			echo "<hr><strong>".TOPIC_TEXT_ATTACHMENTS.":</strong><br>";	
			foreach ( $attachments[$res['reply_id']] as $a) {
				$file = $conf['file_folder'].$a['attachment_id'].base64_encode($a['attachment_filename']);
				if ( file_exists( $file ) ) {
					if ( file_exists($file.".png") ) {
						$img_data = getimagesize($file.".png");
						echo '<img src="'.$file.'.png" '.$img_data[3].'><br>';	
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
					echo $a['attachment_filename']." (<a href='download.php?aid=".$a['attachment_id']."'>".TOPIC_TEXT_DOWNLOAD."</a>, ".$filesize_s.")<br>";
				}
			}
		}
		?></div><div style="clear:both"></div>
		</div><br />
		<?
			
	}

	}
	echo $twig->render("topic_detail.twig", $twig_data);
	
} else {

	$no_topics = $db->query_one("select count(*) from ((select t.topic_id from elo_topic as t, elo_topic_group as tg, elo_group_user as gu, elo_group as g where gu.user_id='".$userid."' and gu.group_id=g.group_id and g.group_id=tg.group_id and tg.topic_id=t.topic_id)
UNION all
(select t.topic_id from elo_topic as t, elo_topic_user as tu where tu.user_id='".$userid."' and tu.topic_id=t.topic_id) ) as ta");

	include("loadtopic.php");
	echo $twig->render("topic.twig", $twig_data);
   
}
