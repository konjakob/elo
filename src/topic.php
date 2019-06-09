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

$query = $db->query("select elo_user.*, elo_lang.lang_code from elo_user left join elo_lang ON (elo_user.lang_id=elo_lang.lang_id) where user_id='".$userid."' limit 1");
$user_res = $db->fetch_array($query);
$username = $user_res['user_name'];
$langcode = $user_res['lang_code'];

if ( strlen($langcode) <1 )
	$langcode = "en";

require_once('includes/languages/'.$langcode.'.php');

require_once('SBBCodeParser.php');

$time = time();

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<!-- CSS FILES -->
		<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/uikit/3.0.2/css/uikit.min.css">
<!--<link href="style.css" rel="stylesheet" type="text/css" />-->
<link href="css/jquery.cluetip.css" rel="stylesheet" type="text/css" />
<script src="jquery.min.js"></script>
<script src="abcjs_editor_1.7-min.js" type="text/javascript"></script>
<script src="js/jquery.cluetip.js"></script>
<script src="js/general.js"></script>
	<script type="text/javascript">

  function getIdText(itemid,dbid){
	  
	$(document).ready(function(){
    	eval($("#div"+itemid).load("getIdText.php?dbid="+dbid+"&itemid="+itemid));
		$("#div"+itemid).slideToggle("slow");
	  });
  };
  
  function getRevs(itemid,langid){
	  
	$(document).ready(function(){
   		eval($("#divrevs"+itemid).load("getRevs.php?id="+langid));
		$("#divrevs"+itemid).slideToggle("slow");
	  });
  };
  
  function addrevs(itemid){
	  
	$(document).ready(function(){
		$("#divaddrevs"+itemid).slideToggle("slow");
	  });
  };
  
  function tgldiv(divname){
	$(document).ready(function(){
		$("#"+divname).slideToggle("slow");
	  });
  };
  
  function tgldivnow(divname){
	$(document).ready(function(){
		$("#"+divname).hide();
	  });
  };
  
  function addReply(topicid){
	var t;
	$(document).ready(function(){
		t = $("#newReplyText"+topicid).val();
		if ( t.length < 1 ) {
			$("#newReplyText"+topicid).css('background-color','#F96');
			$("#newReplyText"+topicid).focus();
			return;
		}
		<? if ( in_array('CREATE_SHEETS', $user_rights) ) {	echo 't2 = $("#abc").val();'; } ?>
		
		$.post("addReply.php",
		{
		  <? if ( in_array('CREATE_SHEETS', $user_rights) ) { echo "abc:t2,"; } ?>
		  
		  id:topicid,
		  text:t,
		  noref:1
		},
		function(data,status){
			$("#newReplyRespond"+topicid).slideToggle("slow");
			$("#newReply"+topicid).slideToggle("slow");
			$("#newReplyRespond"+topicid).text(data);
		});
	  });
  };
  
  <? if (in_array('CREATE_TOPICS', $user_rights) ) { ?>
  function addTopic(){
	var t;
	var t2;
	$(document).ready(function(){
		t = $("#newTopicText").val();
		t2 = $("#t_topic_title").val();
		if ( t.length < 1 ) {
			$("#newTopicText").css('background-color','#F96');
			$("#newTopicText").focus();
			return;
		}
		if ( t2.length < 1 ) {
			$("#t_topic_title").css('background-color','#F96');
			$("#t_topic_title").focus();
			return;
		}
		<? if ( in_array('CREATE_SHEETS', $user_rights) ) {	echo 't3 = $("#abc").val();'; } ?>
		
		$.post("newTopic.php",
		{
		   <? if ( in_array('CREATE_SHEETS', $user_rights) ) { echo "abc:t3,"; } ?>
			
		  t_topic:t,
		  t_topic_title:t2,
		  noref:1
		},
		function(data,status){
			$("#brplace").after(data);
			$("#createNewTopic").show();
			$("#newTopic").hide();
		});
	  });
  };
  <? } ?>
  var start = 10;
  var stopscrol = 0;
  $(window).scroll(function()
	{
		if ( stopscrol == 0 )
		{
			if($(window).scrollTop() == $(document).height() - $(window).height())
			{
				$('div#loadmoreajaxloader').show();
				$.ajax({
				url: "loadtopic.php?start="+start,
				success: function(html)
				{
					if(html)
					{
						$("#postswrapper").append(html);
						$('div#loadmoreajaxloader').hide();
						start = start + 10;
					}else
					{
						stopscrol = 1;
						$("#postswrapper").append('<div style="border:thin;border-style:dashed;align:center;border-right:none;border-left:none;border-bottom:none"><center><?=NO_MORE_POSTS?></center></div>');
						$('div#loadmoreajaxloader').hide();
						//$('div#loadmoreajaxloader').html('<center>No more posts to show.</center>');
					}
				}
				});
			}
		}
	});
	$(document).ready(function() {
		$('a.jt').cluetip({
		  cluetipClass: 'jtip',
		  width: 350,
		  arrows: true,
		  dropShadow: false,
		  hoverIntent: false,
		  sticky: true,
		  mouseOutClose: true,
		  closePosition: 'title',
		  closeText: '<img src="includes/images/cross.png" alt="close" />'
		});
	});

  </script>
</head>

<body>
<? createRightHeader() ?>
<div id="popup" style="display:none; "><div id="popupclose" onClick="tgldiv('popup');"><strong>X</strong></div><div id="popupcontent"></div></div>
<div id="board_messages" style="display: none;"></div>
<div class="uk-container">
<?

if ( isset($_GET['id']) ) {
	$topicid = intval($_GET['id']);
	?>
    
    <div id="linksoben"><a href="<?=$_SERVER['PHP_SELF']?>"><?=TOPIC_TEXT_BACK?></a></div><br /><br />
    
    <?
	
	// check if allowed to see
	if ( !$db->query_one("select tu_id from elo_topic_user where topic_id='".$topicid."' and user_id='".$userid."' limit 1") && !in_array('IS_ADMIN',$user_rights) )
	{
		if ( !$db->query_one("select tg_id from elo_topic_group as tg, elo_group_user AS gu where tg.topic_id='".$topicid."' and tg.group_id=gu.group_id and gu.user_id='".$userid."' limit 1") )
		{
			echo "<br><br><div id='errorcomplete'>".TOPIC_TEXT_NO_RIGHT."</div></body></html>";
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
	
	echo "<h1>".$db->query_one("select topic_title from elo_topic where topic_id='".$topicid."'")."</h1>";
	
	if ( in_array('IS_ADMIN',$user_rights) ) {
		$query_see_users = $db->query("select u.user_name from elo_user as u, elo_topic_user as ut where ut.user_id=u.user_id and ut.topic_id='".$topicid."'");
		echo "<div id='topicinfo'>";
		if ( $db->num_rows($query_see_users)) {
			echo TOPIC_USER_OF_TOPIC."<ul>";
			while ( $r = $db->fetch_array($query_see_users) )
				echo "<li>".$r['user_name']."</li>";
			echo "</ul><div style='clear:both'></div>";
		}
		$query_see_groups = $db->query("select u.group_name from elo_group as u, elo_topic_group as ut where ut.group_id=u.group_id and ut.topic_id='".$topicid."'");
		if ( $db->num_rows($query_see_groups) ) {
			echo TOPIC_GROUP_OF_TOPIC."<ul>";
			while ( $r = $db->fetch_array($query_see_groups) )
				echo "<li>".$r['group_name']."</li>";
			echo "</ul><div style='clear:both'></div>";
		}
		echo "</div><br>";
	}
	
	$query = $db->query("select r.*, u.user_name from elo_reply as r, elo_user as u where r.topic_id='".$topicid."' and u.user_id=r.user_id");
	$musicsheets = array();
	
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
	// answer to a topic
	?>
    

    <div id="newReplyRespond<?=$topicid?>" style="display:none; "></div>
    <div id="newReply<?=$topicid?>" style="border:thin;border-style:dashed;padding: 10px;margin-left:20px; margin-right:20px"><form action="addreply.php" method="post" enctype="multipart/form-data"><input type="hidden" value="<?=$topicid?>" name="id"><div style="float:left"><textarea name="text" id="newReplyText<?=$topicid?>" rows="10" cols="50"></textarea></div><div style="float:left"><span class="formInfo"><a rel="faq.php?fid=1" href="faq.php?fid=1&width=475" class="jt" title="<?=TOPIC_TEXT_RULES?>">?</a></span></div><div style="clear:both"></div><?
    
	if ( in_array('CREATE_SHEETS', $user_rights) ) {
		showCreateSheet();
	}

	if ( in_array('CREATE_ATTACHMENTS', $user_rights) ) {
		echo TOPIC_TEXT_ATTACHMENTS.':<br><input type="file" name="t_file"><span class="formInfo"><a href="faq.php?fid=2&width=475" rel="faq.php?fid=2&width=475" class="jt" title="'.TOPIC_TEXT_RULES_ATTACHMENT.'">?</a></span>';	
	} else {
		//echo '<input type="button" value="'.TOPIC_TEXT_SAVE.'" onclick="javascript:addReply('.$topicid.');">';
	}
	echo '<br><div style="float:left"><input type="submit" value="'.TOPIC_TEXT_SAVE.'"></div>';
	
	?><div style="float:left"><span class="formInfo"><a rel="faq.php?fid=4" href="faq.php?fid=4" class="jt" title="<?=TOPIC_TEXT_RULES?>">?</a></span></div><div style="clear:both"></div></form></div>
    <script type="text/javascript">
	window.onload = function() {
		<?
		foreach ( $sheets as $s)
			foreach ( $s as $a)
				if ( !in_array($a['rm_id'],$musicsheets))
					echo 'abc_editor'.$a['rm_id'].' = new ABCJS.Editor("abc'.$a['rm_id'].'", { paper_id: "paper0'.$a['rm_id'].'"});';
		if ( in_array('CREATE_SHEETS', $user_rights) ) {
		?>
		
		abc_editor = new ABCJS.Editor("abc", { paper_id: "paper0", midi_id:"midi", warnings_id:"warnings" });
	}
	</script>
		<? 
	}
?>	
    <?
	
} else {

	?>
	
	<div><br><?=TOPIC_TEXT_HELLO?> <strong><?=$username?></strong><?=TOPIC_TEXT_WELCOME?></div>
	<hr>
	<div id="placeholder" style="display:none;border:thin;border-style:dashed;padding:10px"></div><br>	
<?
    	// create a topic
	if (in_array('CREATE_TOPICS', $user_rights) ) {
?>
    <div id="newTopicRespond" style="display:none; "></div>
    <div id="createNewTopic" class="uk-margin"><div><input class="uk-button uk-button-primary" type="button" onClick="javascript:tgldivnow('createNewTopic');javascript:tgldiv('newTopic2');" value="<?=TOPIC_TEXT_NEW_TOPIC?>"></div>
    <div id="newTopic2" style="display:none"><div id="panel-header"><?=TOPIC_TEXT_NEW_TOPIC?></div>
    <div id="newTopic" style="margin-left:20px; margin-right:20px;border:thin;border-style:dashed;padding: 10px;"><form action="newtopic.php" method="post" enctype="multipart/form-data"><?=TOPIC_TEXT_TOPIC_TITLE?>:<br>
<input type="text" id="t_topic_title" name="t_topic_title"><br>
<?=TOPIC_TEXT_TOPIC_TEXT?>:<br>
<div style="float:left"><textarea id="newTopicText" rows="10" name="t_topic" cols="50"></textarea></div><div style="float:left"><span class="formInfo"><a rel="faq.php?fid=1&width=475" href="faq.php?fid=1&width=475" class="jt" title="<?=TOPIC_TEXT_RULES?>">?</a></span></div><div style="clear:both"></div>

    <?
	if ( in_array('CREATE_SHEETS', $user_rights) ) {
		showCreateSheet();
	}
    if ( in_array('CREATE_ATTACHMENTS', $user_rights) ) {
		echo TOPIC_TEXT_ATTACHMENTS.':<br><input type="file" name="t_file"><span class="formInfo"><a href="faq.php?fid=2&width=475" rel="faq.php?fid=2&width=475" class="jt" title="'.TOPIC_TEXT_RULES_ATTACHMENT.'">?</a></span><br><div style="float:left"><input type="submit" value="'.TOPIC_TEXT_SAVE.'"></div>';	
	} else {
		echo '<div style="float:left"><input type="button" value="'.TOPIC_TEXT_SAVE.'" onclick="javascript:addTopic();"></div>';
	}
	?><div style="float:left"><span class="formInfo"><a rel="faq.php?fid=4" href="faq.php?fid=4" class="jt" title="<?=TOPIC_TEXT_RULES?>">?</a></span></div><div style="clear:both"></div></form><br></div></div>
	<?
    if ( in_array('CREATE_SHEETS', $user_rights) ) {
		?>
		<script type="text/javascript">
	window.onload = function() {	
		abc_editor = new ABCJS.Editor("abc", { paper_id: "paper0", midi_id:"midi", warnings_id:"warnings" });
	}
		<?
		 
	}
?>	</script><br id="brplace">


<?
	}
	/*
		$query = $db->query("select t.*, (select u.user_name from elo_user as u,elo_reply as r where topic_id=t.topic_id and r.user_id=u.user_id order by reply_id asc limit 1) as username, (select reply_date from elo_reply where topic_id=t.topic_id order by reply_id asc limit 1) as reply_date, (select reply_date from elo_reply where topic_id=t.topic_id order by reply_id desc limit 1) as last_reply_date, (select count(reply_id) from elo_reply where topic_id=t.topic_id) as no from elo_topic as t, elo_topic_group as tg, elo_group_user as gu, elo_group as g where gu.user_id='".$userid."' and gu.group_id=g.group_id and g.group_id=tg.group_id and tg.topic_id=t.topic_id order by t.topic_id DESC");

		
	$topics = array();
	while ( $res = $db->fetch_array($query) )
		$topics[$res['topic_id']] = $res;
		
	$query = $db->query("select t.*, (select user_name from elo_user as u,elo_reply as r where topic_id=t.topic_id and r.user_id=u.user_id order by reply_id asc limit 1) as username, (select reply_date from elo_reply where topic_id=t.topic_id order by reply_id asc limit 1) as reply_date, (select reply_date from elo_reply where topic_id=t.topic_id order by reply_id desc limit 1) as last_reply_date, (select count(reply_id) from elo_reply where topic_id=t.topic_id) as no from elo_topic as t, elo_topic_user as tu where tu.user_id='".$userid."' and tu.topic_id=t.topic_id order by t.topic_id DESC");
	*/

$no_topics = $db->query_one("select count(*) from ((select t.topic_id from elo_topic as t, elo_topic_group as tg, elo_group_user as gu, elo_group as g where gu.user_id='".$userid."' and gu.group_id=g.group_id and g.group_id=tg.group_id and tg.topic_id=t.topic_id)
UNION all
(select t.topic_id from elo_topic as t, elo_topic_user as tu where tu.user_id='".$userid."' and tu.topic_id=t.topic_id) ) as ta");
?>
<div class="uk-width-1-1" uk-grid>
<?php  
   include("loadtopic.php");
   
   ?>
   
<div id="postswrapper">
   <div class="item"></div>
   <div id="loadmoreajaxloader" style="display:none;"><center><img src="ajax-loader.gif" /></center></div>
</div>

</div>

<?php
}


$db->close();

?>
</div>
		<!-- JS FILES -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/uikit/3.0.2/js/uikit.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/uikit/3.0.2/js/uikit-icons.min.js"></script>
</body>
</html>