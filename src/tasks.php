<?php

require_once("includes/dbclass.php");
$db = new db;

require("includes/functions.php");

require_once('includes/SBBCodeParser.php');

/*****************************************************************
*
* Create the email object with all the setting set.
*
*****************************************************************/

$time_last_cron = $db->query_one("select cron_time from elo_cron order by cron_time asc limit 1");
$db->query("insert into elo_cron (cron_time) values ('".time()."')");

date_default_timezone_set('Etc/UTC');
require 'includes/class.phpmailer.php';

$mail = new PHPMailer;

$mail->SMTPDebug  = 0;
$mail->IsSMTP();                                      // Set mailer to use SMTP
$mail->Host = $conf['smtp_server'];  // Specify main and backup server
$mail->Port = $conf['smtp_port'];
$mail->SMTPAuth = true;                               // Enable SMTP authentication
$mail->Username = $conf['smtp_username'];                            // SMTP username
$mail->Password = $conf['smtp_password'];                           // SMTP password
$mail->SMTPSecure = '';                            // Enable encryption, 'ssl' also accepted tls

$mail->SetFrom($conf['from_email'], $conf['from_name']);
$mail->FromName = $conf['from_name'];
$mail->WordWrap = 80;                                 // Set word wrap 
$mail->IsHTML(true); 
	
 
	// where user_lastvisit<".$lastvisit
$query_user = $db->query("select elo_user.*, elo_lang.lang_code from elo_user, elo_lang where elo_user.lang_id=elo_lang.lang_id");
while ( $res_user = $db->fetch_array($query_user) ) {
	
	$topicids = array();
	
	$langcode = $res_user['lang_code'];
	
	if ( strlen($langcode) <1 )
		$langcode = "en";
	
	// remove the old recipient
	$mail->ClearAllRecipients();
	
	$lastvisit = $res_user['user_lastvisit'];
	$userid = $res_user['user_id'];
	
	$time_to_check = $lastvisit;
	if ( $time_to_check < $time_last_cron )
		$time_to_check = $time_last_cron;
	
	$query = $db->query("select * from ((select t.*, (select u.user_name from elo_user as u,elo_reply as r where topic_id=t.topic_id and r.user_id=u.user_id order by reply_id asc limit 1) as username, (select reply_date from elo_reply where topic_id=t.topic_id order by reply_id asc limit 1) as reply_date, (select reply_date from elo_reply where topic_id=t.topic_id order by reply_id desc limit 1) as last_reply_date, (select count(reply_id) from elo_reply where topic_id=t.topic_id) as no from elo_topic as t, elo_topic_group as tg, elo_group_user as gu, elo_group as g where gu.user_id='".$userid."' and gu.group_id=g.group_id and g.group_id=tg.group_id and tg.topic_id=t.topic_id)
UNION
(select t.*, (select user_name from elo_user as u,elo_reply as r where topic_id=t.topic_id and r.user_id=u.user_id order by reply_id asc limit 1) as username, (select reply_date from elo_reply where topic_id=t.topic_id order by reply_id asc limit 1) as reply_date, (select reply_date from elo_reply where topic_id=t.topic_id order by reply_id desc limit 1) as last_reply_date, (select count(reply_id) from elo_reply where topic_id=t.topic_id) as no from elo_topic as t, elo_topic_user as tu where tu.user_id='".$userid."' and tu.topic_id=t.topic_id) order by last_reply_date DESC limit 5) as tab where last_reply_date>'".$time_to_check."'");
	
	if ( $db->num_rows($query)) {
		
		$topics = array();
		
		while ( $res = $db->fetch_array($query) ) {
			$topicids[] = $res['topic_id'];
			$topics[] = $res;
		}	
	
	// ************************************
	$attachments = array();
	$music = array();
	
	if ( sizeof($topicids) ) {
		$query_attach = $db->query("SELECT elo_user.user_name, elo_reply.reply_id, elo_reply.topic_id, elo_reply.reply_text, elo_attachment.attachment_id, elo_attachment.attachment_filename, elo_reply.reply_date
		FROM (elo_attachment INNER JOIN elo_reply_attachment ON elo_attachment.attachment_id = elo_reply_attachment.attachment_id) INNER JOIN (elo_reply INNER JOIN elo_user ON elo_reply.user_id = elo_user.user_id) ON elo_reply_attachment.reply_id = elo_reply.reply_id
		WHERE (((elo_reply.topic_id) IN (".implode(",",$topicids).")));");
		
		while ( $res = $db->fetch_array($query_attach) ) {
			if ( !array_key_exists($res['topic_id'],$attachments) )
				$attachments[$res['topic_id']] = array();
			$attachments[$res['topic_id']][] = $res;
		}
		
		$query_music = $db->query("SELECT elo_reply_music.music_id,  elo_reply.reply_id, elo_reply.topic_id, elo_user.user_name, elo_reply.reply_text, elo_reply.reply_date
		FROM (elo_reply_music INNER JOIN elo_reply ON elo_reply_music.reply_id = elo_reply.reply_id) INNER JOIN elo_user ON elo_reply.user_id = elo_user.user_id
		WHERE (((elo_reply.topic_id) IN (".implode(",",$topicids).")));");
		
		
		while ( $res = $db->fetch_array($query_music) ) {
			if ( !array_key_exists($res['topic_id'],$music) )
				$music[$res['topic_id']] = array();
			$music[$res['topic_id']][] = $res;
		}
	}

/* Create view*/

	ob_start();
	
	?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<style type="text/css">
#topic {
	border:thin;
	border-style:dashed;
	padding:10px;
	border-top:none;
}
#reply-header {
	padding: 16px;
	border:thin;
	border-style:dashed;
	border-top:hidden;
}
body {
	font-family: Arial, helvetica, sans-serif;
	background: white;
	margin: 20px;
}
A:link {
	color: blue
}
A:visited {
	color: darkblue
}
A:active {
	color: green
}

#event {
	border:thin;border-style:dashed;border-top:hidden;padding:10px;
}
#topictitle {
	border:thin;border-style:dashed;padding:10px;background-color:#FCF;
}
#small {
	font-size:12px;
}
#titlediv {
	font-size: 18px;
	color: darkblue;
	font-weight: bold;
}
</style>
   </head><body><div id="topictitle"><?=EMAIL_TEXT_HELLO?><strong><?=$res_user['user_name']?></strong><?=EMAIL_TEXT_LAST_VISIT?></div>
    <?
	foreach( $topics as $res )
		createTopic();
?><br>

<div id="topictitle"><?=EMAIL_TEXT_ATTACHMENTS?></div>
<?
foreach ( $topics as $topic ) {


	if ( array_key_exists($topic['topic_id'], $attachments) || array_key_exists($topic['topic_id'], $music ) ) {
?>
<div id="reply-header">
<?
		echo EMAIL_TEXT_IN_TOPIC."<strong>".$topic['topic_title']."</strong>";
		?>
        <br><br>
        <? 
		//</div><div id="topic">
		if ( array_key_exists($topic['topic_id'], $attachments) ) {
			$res = $attachments[$topic['topic_id']][0];
		} else {
			$res = $music[$topic['topic_id']][0];
		}

		?>
		
		<strong><?=stripslashes($res['user_name'])?></strong> (<?=date($conf['date_format'],$res['reply_date'])?>): 
		<? 
		
		$ubbParser = new SBBCodeParser_Document();
		echo $ubbParser->parse(stripslashes($res['reply_text']))->detect_links()->detect_emails()->get_html();
	
		?>
       <br>
<br>
 <a href="<?=$conf['url']?>topic.php?id=<?=$topic['topic_id']?>#<?=$res['reply_id']?>"><?=TOPIC_TEXT_TOPIC_VIEW?></a><br>

        <?
		// music
		if ( array_key_exists($topic['topic_id'], $music) ) {
			foreach( $music[$topic['topic_id']] as $m ) {
				$file = $conf['file_folder'].'m-'.$m['music_id'].'/'.$m['music_id'].'.png';
				echo "<br>";
				if ( file_exists($file) ) {
					$img_data = getimagesize($file);
					echo '<img src="'.$conf['url'].$file.'" '.$img_data[3].'>';
				} else {
					echo TOPIC_TEXT_DOWNLOADS.': <a href="'.$conf['url'].'download.php?type=abc&mid='.$m['music_id'].'">ABC</a> | <a href="'.$conf['url'].'download.php?type=pdf&mid='.$m['music_id'].'">PDF</a> | <a href="'.$conf['url'].'download.php?type=midi&mid='.$m['music_id'].'">MIDI</a>';
				}
			}
		}
		
		// attachments
		if ( array_key_exists($topic['topic_id'], $attachments) ) {
			foreach( $attachments[$topic['topic_id']] as $a ) {
				$file = $conf['file_folder'].$a['attachment_id'].base64_encode($a['attachment_filename']);
				echo "<br>";
				if ( file_exists($file.".png") ) {
					$img_data = getimagesize($file.".png");
					echo '<img src="'.$conf['url'].$file.'.png" '.$img_data[3].'>';	
				} else {
					echo "<a href='".$conf['url']."download.php?aid=".$a['attachment_id']."'>".TOPIC_TEXT_DOWNLOAD."</a>";	
				}
			}
		}
		?>
        </div>

        <?

	}
}
	echo EMAIL_TEXT_FOOTER;
?>
</body></html>
<?
		$string = ob_get_contents();
		ob_end_clean(); 
	
		//echo $string;
		//die();
		
		$mail->AddAddress($res_user['user_email'], $res_user['user_name']);  // Add a recipient
		
		$mail->Subject = EMAIL_TEXT_TITLE;
		$mail->Body    = $string; // html text
		$mail->AltBody = EMAIL_TEXT_NOSUPPORT;
		
		if(!$mail->Send()) {
		   echo 'Message could not be sent.';
		   echo 'Mailer Error: ' . $mail->ErrorInfo;
		   //exit;
		} else {
			echo $res_user['user_email']." OK\n";
		}
		
		//echo 'Message has been sent';

	}
}

