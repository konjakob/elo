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


$time = time();

$error = "";

if ( isset( $_GET['id'] ) || isset($_POST['id']) ) {
	
	$replyid = 0;
	if ( isset($_GET['id']) )
		$replyid = intval($_GET['id']);
	else if ( isset($_POST['id']) )
		$replyid = intval($_POST['id']);
		
	$query = $db->query("select * from elo_reply where reply_id='".$replyid."'");
	
	if ( $db->num_rows($query) ) {
		$res = $db->fetch_array($query);

		if ( ($res['user_id'] == $user_res['user_id'] && $res['reply_date'] > ($time - $conf['max_edit_time']) ) || in_array('IS_ADMIN',$user_rights)) {
			$topicid = $res['topic_id'];
	
			// music sheets
			$query_m = $db->query("select a.*, ra.rm_id from elo_music as a, elo_reply_music as ra where ra.reply_id='".$replyid."' and ra.music_id=a.music_id");
					
/* *************************** */
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
				$reply_id = $replyid;
				
				if ( in_array('CREATE_ATTACHMENTS', $user_rights) && isset($_FILES['t_file']) && strlen($_FILES['t_file']['name'])) {
					processAttachment();
				}
				
				if ( in_array('CREATE_SHEETS', $user_rights) && isset($_POST['abc']) && strlen($_POST['abc'])) {
					processMusic();
				}
		
				if ( isset($_POST['noref']) ) {
					echo "Reply saved.";
				} else {
					header("Location: topic.php?id=".$res['topic_id']."#".$reply_id);	
					exit();
				}
			}
/* *************************** */
			
			
			
			// attachments
			$query_a = $db->query("select ra.ra_id, a.* from elo_attachment as a, elo_reply_attachment as ra where ra.reply_id='".$replyid."' and ra.attachment_id=a.attachment_id");
	
			
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link href="style.css" rel="stylesheet" type="text/css" />
<link href="css/jquery.cluetip.css" rel="stylesheet" type="text/css" />
<script src="jquery.min.js"></script>
<script src="abcjs_editor_1.7-min.js" type="text/javascript"></script>
<script src="js/jquery.cluetip.js"></script>
<script src="js/general.js"></script>
<script>
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
	
  function tgldiv(divname){
	$(document).ready(function(){
		$("#"+divname).slideToggle("slow");
	  });
  };
  
  <? 
		if ( in_array('IS_ADMIN',$user_rights) ) {
			?>
     function removeUserTopic() {
		$(document).ready(function(){
			$('#t_user option:selected').each(function(){
				$("<option/>").val($(this).val()).text($(this).text()).prependTo("#ex_users");
				$("#t_user option[value="+$(this).val()+"]").remove();
			});
		});
	}
	function addUserTopic() {
		$(document).ready(function(){
			$('#ex_users option:selected').each(function(){
				$("<option/>").val($(this).val()).text($(this).text()).prependTo("#t_user");
				$("#ex_users option[value="+$(this).val()+"]").remove();
			});
		});
	}
	function removeGroupTopic() {
		$(document).ready(function(){
			$('#t_group option:selected').each(function(){
				$("<option/>").val($(this).val()).text($(this).text()).prependTo("#ex_groups");
				$("#t_group option[value="+$(this).val()+"]").remove();
			});
		});
	}
	function addGroupTopic() {
		$(document).ready(function(){
			$('#ex_groups option:selected').each(function(){
				$("<option/>").val($(this).val()).text($(this).text()).prependTo("#t_group");
				$("#ex_groups option[value="+$(this).val()+"]").remove();
			});
	  });
	}
	function selectAllOptions(selStr)
	{
	  var selObj = document.getElementById(selStr);
	  for (var i=0; i<selObj.options.length; i++) {
		selObj.options[i].selected = true;
	  }
	}
	<? } ?>
  </script>
</head>
<body>
<div id="linksoben"><a href="topic.php?id=<?=$topicid?>#<?=$replyid?>"><?=TOPIC_TEXT_BACK?></a></div><? createRightHeader() ?><br>
<br><br>

<div id="panel-header"><?=TOPIC_EDIT_REPLY?></div>
    <div id="editReply<?=$replyid?>" style="margin-left:20px; margin-right:20px;border:thin;border-style:dashed;padding: 10px;"><form action="<?=$_SERVER['PHP_SELF']?>" method="post" enctype="multipart/form-data"><input type="hidden" value="<?=$replyid?>" name="id"><div style="float:left"><textarea name="text" id="newReplyText<?=$replyid?>" rows="10" cols="50"><?=$res['reply_text']?></textarea></div><div style="float:left"><span class="formInfo"><a rel="faq.php?fid=1" href="faq.php?fid=1&width=475" class="jt" title="<?=TOPIC_TEXT_RULES?>">?</a></span></div><div style="clear:both"></div><?
    
	if ( in_array('CREATE_SHEETS', $user_rights) ) {
		if ( $db->num_rows($query_m))
			$db->data_seek($query_m,0);
		while ( $r = $db->fetch_array($query_m)) {
		?>
        <div id="abc<?=$r['rm_id']?>Check" style="display:none"><pre id="abc<?=$r['rm_id']?>CheckText" class="abcCheckText"></pre></div>
      <div style="float:left">  <textarea name="old_abc[<?=$r['music_id']?>]" id="abc<?=$r['rm_id']?>" cols="80" rows="15"><?=$r['music_text']?></textarea></div><div style="float:left"><span class="formInfo"><a rel="faq.php?fid=3" href="faq.php?fid=3" class="jt" title="<?=TOPIC_SHEET_INFO?>">?</a></span></div><div style="clear:both"></div>
<input type="button" value="Check syntax" onclick="checkAbcSyntax('abc<?=$r['rm_id']?>')" /><div id="midi<?=$r['rm_id']?>"></div>
<div id="warnings<?=$r['rm_id']?>"></div>
<div id="music<?=$r['rm_id']?>"></div>
<div id="paper0<?=$r['rm_id']?>"></div>

        <?
		}
		showCreateSheet();
	}
	
	if ( in_array('CREATE_ATTACHMENTS', $user_rights) ) {
		echo TOPIC_TEXT_ATTACHMENTS.":";
		if ( $db->num_rows($query_a) ) {
			echo "<ul>";
			while ( $r = $db->fetch_array($query_a) ) {
				echo "<li>".$r['attachment_filename']." (<a href='delete_reply.php?aid=".$r['ra_id']."&id=".$replyid."'>".TOPIC_DELETE."</a>)</li>"; 	
			}
			echo "</ul><br>";
		}
		echo '<br><input type="file" name="t_file"><span class="formInfo"><a href="faq.php?fid=2&width=475" rel="faq.php?fid=2&width=475" class="jt" title="'.TOPIC_TEXT_RULES_ATTACHMENT.'">?</a></span>';	
	}
	echo '<br><div style="float:left"><input type="submit" value="'.TOPIC_TEXT_SAVE.'"></div>';
	
	?><div style="float:left"><span class="formInfo"><a rel="faq.php?fid=4" href="faq.php?fid=4" class="jt" title="<?=TOPIC_TEXT_RULES?>">?</a></span></div><div style="clear:both"></div></form></div>
    <script type="text/javascript">
	window.onload = function() {
		<?
		if ( in_array('CREATE_SHEETS', $user_rights) ) {
			if ( $db->num_rows($query_m))
				$db->data_seek($query_m,0);
			while ( $r = $db->fetch_array($query_m))
				echo 'abc_editor'.$r['rm_id'].' = new ABCJS.Editor("abc'.$r['rm_id'].'", { paper_id: "paper0'.$r['rm_id'].'", midi_id:"midi'.$r['rm_id'].'", warnings_id:"warnings'.$r['rm_id'].'"});';
		?>
		
		abc_editor = new ABCJS.Editor("abc", { paper_id: "paper0", midi_id:"midi", warnings_id:"warnings" });
	}
	</script>
    
		<? 
		if ( in_array('IS_ADMIN',$user_rights) ) {
			?>
       <br>
     <div id="panel-header"><?=TOPIC_EDIT_TOPIC_SETTINGS?></div>
	<div style="border:thin;border-style:dashed;padding:10px;margin-left:20px; margin-right:20px;">
<form action="<?=$_SERVER['PHP_SELF']?>" method="post" enctype="multipart/form-data"  onsubmit="selectAllOptions('t_user');selectAllOptions('t_group');">
Users: 
<? 
	$query_user = $db->query("select * from elo_user order by user_name");
		
	$users = array();
	while ( $res = $db->fetch_array($query_user) )
		$users[] = $res;
		
	$query_groups = $db->query("select * from elo_group order by group_name");

	$groups = array();
	while ( $res = $db->fetch_array($query_groups) )
		$groups[] = $res;
	
	$query_see_users = $db->query("select u.user_id from elo_user as u, elo_topic_user as ut where ut.user_id=u.user_id and ut.topic_id='".$topicid."'");
	$query_see_groups = $db->query("select u.group_id from elo_group as u, elo_topic_group as ut where ut.group_id=u.group_id and ut.topic_id='".$topicid."'");

	$user_topic = array();
	while ( $r = $db->fetch_array($query_see_users) )
		$user_topic[] = $r['user_id'];
		
	$group_topic = array();
	while ( $r = $db->fetch_array($query_see_groups) )
		$group_topic[] = $r['group_id'];
	
	$user_select = "";
	$user_select_in = "";
	foreach ( $users as $u ) {
		if ( !in_array($u['user_id'],$user_topic) ) { 
			$user_select .= "<option value='".$u['user_id']."'>".$u['user_name']."</option>";	
		} else {
			$user_select_in .= "<option value='".$u['user_id']."'>".$u['user_name']."</option>";	
		}
	}
	echo "<table><tr><td>Existing user:</td><td></td><td>User for this topic:</td></tr><tr><td><select id='ex_users' size='5' multiple='multiple'>".$user_select."</select></td>";
	echo '<td><input type="button" value=">>" onClick="javascript:addUserTopic();"><br><input type="button" value="<<" onClick="javascript:removeUserTopic();"></td>';
	echo "<td><select id='t_user' size='5' multiple='multiple' name='t_user[]'>".$user_select_in."</select></td></tr></table>";
	

?><br />
Groups:
<? 
	$select_group = "";
	$select_group_in = "";
	foreach ( $groups as $g ) {
		if ( !in_array( $g['group_id'],$group_topic ) ) {
			$select_group .= "<option value='".$g['group_id']."'>".$g['group_name']."</option>";	
		} else {
			$select_group_in .= "<option value='".$g['group_id']."'>".$g['group_name']."</option>";	
		}
	}
	
	echo "<table><tr><td>Existing groups:</td><td></td><td>Groups for this topic:</td></tr><tr><td><select id='ex_groups' size='5' multiple='multiple'>".$select_group."</select></td>";
	echo '<td><input type="button" value=">>" onClick="javascript:addGroupTopic();"><br><input type="button" value="<<" onClick="javascript:removeGroupTopic();"></td>';
	echo "<td><select id='t_group' size='5' multiple='multiple' name='t_group[]'>".$select_group_in."</select></td></tr></table>";
	
?>
<br />
Title: <input type="text" name="t_topic_title" value="<?=$db->query_one("select topic_title from elo_topic where topic_id='".$topicid."'")?>"/><br>
<input type="hidden" value="<?=$replyid?>" name="id">
    <input type="submit" value="<?=TOPIC_TEXT_SAVE?>" name="edit_topic_admin" />
</form>
</div>
	<?
		}
	}

		} else {
			$error = DELETE_REPLY_NO_RIGHTS;
		}
	} else {
		$error = DELETE_REPLY_NO_RIGHTS;
	}
} else {
	$error = DELETE_REPLY_NO_ID;	
}

?><br>
<br>
</body></html>