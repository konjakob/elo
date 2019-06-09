<?php

require("dbclass.php");
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
	
if ( !in_array('IS_ADMIN', $user_rights ) )
	exit();
	
$query = $db->query("select elo_user.*, elo_lang.lang_code from elo_user left join elo_lang ON (elo_user.lang_id=elo_lang.lang_id) where user_id='".$userid."' limit 1");
$user_res = $db->fetch_array($query);
$username = $user_res['user_name'];
$langcode = $user_res['lang_code'];

if ( strlen($langcode) <1 )
	$langcode = "en";

require_once('includes/languages/'.$langcode.'.php');
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link href="style.css" rel="stylesheet" type="text/css" />
<script src="jquery.min.js"></script>
<script src="abcjs_editor_1.7-min.js" type="text/javascript"></script>
<script src="jquery-1.9.0.min.js" type="application/javascript"></script>
<script src="js/general.js"></script>
<script language="javascript">  

function tgldiv(divname){
	$(document).ready(function(){
		$("#"+divname).slideToggle("slow");
	  });
  };
  
  function loadUserData(id) {
	  $.ajax({
				url: "actions.php?action=getUser&userid="+id,
				success: function(respond)
				{
					$("#popupcontent").html(respond);
					$("#popupcontent").find("script").each(function(i) {
                    	eval($(this).text());
                	});
				}
		});
  }
  
  function loadGroupData(id) {
	  $.ajax({
				url: "actions.php?action=getGroupUser&group_id="+id,
				success: function(respond)
				{
					$("#popupcontent").html(respond);
					$("#popupcontent").find("script").each(function(i) {
                    	eval($(this).text());
                	});
				}
		});
  }
  
  function changeGroupData(id) {
	  $.ajax({
				url: "actions.php?action=getGroup&group_id="+id,
				success: function(respond)
				{
					$("#popupcontent").html(respond);
					$("#popupcontent").find("script").each(function(i) {
                    	eval($(this).text());
                	});
				}
		});
  }
  
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
</script>

</head>

<body>
<div id="linksoben"><a href="topic.php"><?=TOPIC_TEXT_BACK?></a></div>
<? createRightHeader() ?><br>
<br>
<br>

<div id="popup" style="display:none; "><div id="popupclose" onClick="tgldiv('popup');"><strong>X</strong></a></div><div id="popupcontent"></div></div>

<?

$msg_group = "";

if ( in_array('CREATE_NEW_USER', $user_rights) && isset($_POST['new_user']) && isset($_POST['t_name']) && isset($_POST['t_email']) && isset($_POST['t_pass']) )
{
	
	require_once( "PasswordHash.php" );
    $hasher = new PasswordHash( 8, TRUE );

	$error = "";
	$success = "";

	if ( $db->query_one("select user_id from elo_user where user_email='".addslashes($_POST['t_email'])."'") ) {
		$error .= "There is already an user with the given email address. Please use another email address.";
	} else {

		$db->query("insert into elo_user (user_name, user_email, user_password, lang_id, user_lastvisit) values ('".addslashes($_POST['t_name'])."', '".addslashes($_POST['t_email'])."', '".$hasher->HashPassword($_POST['t_pass'])."', '".intval($_POST['t_lang'])."', '".time()."')");	
		$success .= "<strong>Saved the new user: <i>".$_POST['t_name']."</i></strong>";
		
		if ( isset($_POST['t_send_email']) ) {
			$user_lang = $db->query_one("select lang_code from elo_lang where lang_id='".intval($_POST['t_lang'])."'");
			
			$email_text = str_replace(array("{user_name}", "{user_email}", "{admin_name}", "{user_passwort}", "{url}"), array($_POST['t_name'], $_POST['t_email'], $username, $_POST['t_pass'], $conf['url']), file_get_contents("includes/languages/template_new_user_".$user_lang.".html"));
	
			date_default_timezone_set('Etc/UTC');
			require 'class.phpmailer.php';
			
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
			$mail->WordWrap = 80;                                 // Set word wrap to 50 characters
			$mail->IsHTML(true); 
			
			$mail->AddAddress($_POST['t_email'], $_POST['t_name']);  // Add a recipient
			
			$mail->Subject = EMAIL_NEW_USER_TEXT_TITLE;
			$mail->Body    = $email_text; // html text
			$mail->AltBody = EMAIL_TEXT_NOSUPPORT;
			
			if(!$mail->Send()) {
			   $error .= 'Message could not be sent.';
			   $error .= 'Mailer Error: ' . $mail->ErrorInfo;
			} else {
				$success .= "<br>Send a registration email with all information to the user.";
			}

			
		}
	}
	
	if ( strlen($success) ) {
		echo '<div id="correct">'.$success.'</div>';
	}
	if ( strlen($error) ) {
		echo '<div id="error">'.$error.'</div>';
	}
}

if ( isset($_POST['new_group']) ) {
	$db->query("insert into elo_group (group_name) values ('".addslashes($_POST['t_group'])."')");
	$msg_group = "<div id='correct'>Saved the new group.</div>";
}
if ( isset($_GET['delete_group']) ) {
	$db->query("delete from elo_group where group_id='".intval($_GET['delete_group'])."'");
	$msg_group = "<div id='correct'>Group deleted.</div>";	
}
$query_groups = $db->query("select * from elo_group order by group_name");

$groups = array();
while ( $res = $db->fetch_array($query_groups) )
	$groups[] = $res;
	
$query_lang = $db->query("select * from elo_lang");
	
if (in_array('CREATE_NEW_USER', $user_rights) ) {	
?>
<div id="panel-header">New user</div>
<div id="panel-box">
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
Name: <input type="text" name="t_name" /><br />
Email: <input type="text" name="t_email" /><br />
Password: <input type="text" name="t_pass" /> <?=PANEL_EXAMPLE_PASSWORD?> <?=createCode(8)?><br />
Send registration email: <input type="checkbox" name="t_send_email" checked><br>
Language: 
<select name="t_lang">
<?
while ( $res = $db->fetch_array($query_lang))
	echo '<option value="'.$res['lang_id'].'">'.$res['lang_name'].'</option>';
?>
</select>
<br>
<input type="submit" value="Save" name="new_user" />
</form>
</div>
<br />
<?
}

if (in_array('CREATE_NEW_RIGHT', $user_rights) ) {
		
	if ( isset($_POST['new_right']) && isset($_POST['t_name']) && isset($_POST['t_key']) )
	{
		$db->query("insert into elo_right (right_name, right_key) values ('".addslashes($_POST['t_name'])."', '".addslashes($_POST['t_key'])."')");	
		echo "<div id='correct'>Saved the new right.</div>";
	}
?>
<div id="panel-header">New rights</div>
<div id="panel-box">
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
Name: <input type="text" name="t_name" /><br />
Key: <input type="text" name="t_key" /><br />
<input type="submit" value="Save" name="new_right" />
</form>
</div><br />
<?
}
?>

<?=$msg_group?>
<div id="panel-header">Groups</div>
<div id="panel-box">
<?
if (in_array('CREATE_GROUPS', $user_rights) ) {
?>
    <form action="<?=$_SERVER['PHP_SELF']?>" method="post">
    New Group: 
      <input type="text" name="t_group" />
      <input type="submit" value="Save" name="new_group" />
    </form>
<? 
} 
?>
Groups:<br />
<?
foreach ( $groups as $g ) 
{
	echo "<a onclick=\"javascript:loadGroupData(".$g['group_id'].");javascript:tgldiv('popup');\">".$g['group_name']."</a>";
	echo " (<a href='".$_SERVER['PHP_SELF']."?delete_group=".$g['group_id']."'>Delete</a> | <a onclick=\"javascript:changeGroupData(".$g['group_id'].");javascript:tgldiv('popup');\">Modify</a>)<br>";	
}
?>
</div><br />
<div id="panel-header">Edit users</div>

<?

if ( isset($_GET['action']) ) {
	
	if ( $_GET['action'] == 'add_right' ) {
		$db->query("insert into elo_right_user (user_id, right_id) values ('".intval($_GET['user_id'])."', '".intval($_GET['right_id'])."')");
	} else if ($_GET['action'] == 'delete_right') {
		$db->query("delete from elo_right_user where user_id='".intval($_GET['user_id'])."' and right_id='".intval($_GET['right_id'])."'");	
	}
		
}

$query_user = $db->query("select * from elo_user order by user_name");
$query_right = $db->query("select * from elo_right order by right_name");

$rights = array();
while ( $res = $db->fetch_array($query_right) )
	$rights[] = $res;

	
$users = array();

echo '<div id="panel-box">';
while ( $res = $db->fetch_array($query_user) ) {
	$users[] = $res;
	$query = $db->query("select right_id from elo_right_user where user_id=".$res['user_id']);
	$saved_rights = array();
	while ( $res2 = $db->fetch_array($query) )
		$saved_rights[] = $res2['right_id'];
	echo "<a onclick=\"javascript:loadUserData(".$res['user_id'].");javascript:tgldiv('popup');\">".$res['user_name']."</a><br>";
}
echo "</div>";	

?><br />
<?
$default_text = "";
if ( isset($_POST['new_topic']) ) {

	if ( isset( $_POST['t_topic_title'] ) && strlen($_POST['t_topic_title'] )) {
		$db->query("insert into elo_topic (topic_title) values ('".addslashes($_POST['t_topic_title'])."')");
		
		$topicid = $db->insert_id();
		$db->query("insert into elo_reply (user_id, topic_id, reply_date, reply_text) values ('".$userid."', '".$topicid."', '".time()."', '".addslashes($_POST['t_topic'])."')");
		$reply_id = $db->insert_id();
		if ( isset( $_POST['t_user'] )) {
			foreach ( $_POST['t_user'] as $u )
				$db->query("insert into elo_topic_user (user_id, topic_id) values ('".$u."', '".$topicid."')");
		}
		if ( isset($_POST['t_group'])) {
			foreach ( $_POST['t_group'] as $g )
				$db->query("insert into elo_topic_group (group_id, topic_id) values ('".$g."', '".$topicid."')");
		}
		
		if ( in_array('CREATE_ATTACHMENTS', $user_rights) && isset($_FILES['t_file']) ) {
			processAttachment();
		}
		
		if ( in_array('CREATE_SHEETS', $user_rights) && isset($_POST['abc']) && strlen($_POST['abc'])) {
			processMusic();
		}
		echo "<div id='correct'>New topic was created.</div>";
	} else {
		$default_text = $_POST['t_topic'];
		if ( isset($_POST['abc']) && strlen($_POST['abc']) ) {
			$default_text .= " # Please add this text to the music box: ".$_POST['abc'];
		}
		echo "<div id='error'>Please enter a title.</div>";
		
	}
}


?>
<div id="panel-header">New topic</div>
<div id="panel-box">
<form action="<?=$_SERVER['PHP_SELF']?>" method="post" enctype="multipart/form-data"  onsubmit="selectAllOptions('t_user');selectAllOptions('t_group');">
Users: 
<? 
	$user_select = "";
	foreach ( $users as $u ) 
		$user_select .= "<option value='".$u['user_id']."'>".$u['user_name']."</option>";	
	
	echo "<table><tr><td>Existing user:</td><td></td><td>User for this topic:</td></tr><tr><td><select id='ex_users' size='5' multiple='multiple'>".$user_select."</select></td>";
	echo '<td><input type="button" value=">>" onClick="javascript:addUserTopic();"><br><input type="button" value="<<" onClick="javascript:removeUserTopic();"></td>';
	echo "<td><select id='t_user' size='5' multiple='multiple' name='t_user[]'></select></td></tr></table>";
	

?><br />
Groups:
<? 
	$select_group = "";
	foreach ( $groups as $g ) 
		$select_group .= "<option value='".$g['group_id']."'>".$g['group_name']."</option>";	

	echo "<table><tr><td>Existing groups:</td><td></td><td>Groups for this topic:</td></tr><tr><td><select id='ex_groups' size='5' multiple='multiple'>".$select_group."</select></td>";
	echo '<td><input type="button" value=">>" onClick="javascript:addGroupTopic();"><br><input type="button" value="<<" onClick="javascript:removeGroupTopic();"></td>';
	echo "<td><select id='t_group' size='5' multiple='multiple' name='t_group[]'></select></td></tr></table>";
	
?><br />
Title: <input type="text" name="t_topic_title" /><br>
Text: <br><textarea name="t_topic" rows="10" cols="50"><?=$default_text?></textarea><br />
<?
	if ( in_array('CREATE_SHEETS', $user_rights) ) {
		showCreateSheet();
	}
    if ( in_array('CREATE_ATTACHMENTS', $user_rights) ) {
		echo 'Attachments:<br><input type="file" name="t_file">';	
	}
    ?><br />
    <input type="submit" value="Save" name="new_topic" />
</form>
</div><br />

<?
/*
$msg_file = "";
if (  isset($_POST['new_file']) ) {
	$db->query("insert into elo_attachment (attachment_filename, user_id) values ('".$_FILES['t_file']['name']."', '".$userid."')");
	$filename = $db->insert_id().base64_encode($_FILES['t_file']['name']);
	$c = @copy($_FILES['t_file']['tmp_name'],"files/".$filename);
	if ( $c ) {
		$msg_file = "File was successfully uploaded.";
		if (  preg_match('/[(pdf)]$/',$_FILES['t_file']['name']) ) {
			$file_sys_path = "C:\\wamp\\www\\elo\\";
			exec("convert \"".$file_sys_path."files/{".$filename."}[0]\" -colorspace RGB -geometry 200 \"".$file_sys_path."files/".$filename.".gif\"");
			$msg_file .= " Also a screenshot of the first page was generated.";
			echo "convert \"".$file_sys_path."files/{".$filename."}[0]\" -colorspace RGB -geometry 200 \"".$file_sys_path."files/".$filename.".gif\"";
		}
		
	}
}
*/
/*
if ( strlen($msg_file)) 
	echo "<div id='correct'>".$msg_file."</div>";
?>

<div style="border:thin;border-style:dashed;padding:10px">
<form action="<?=$_SERVER['PHP_SELF']?>" method="post" enctype="multipart/form-data">
Name: <input type="text" name="t_name" /><br />
File: <input type="file" name="t_file" /><br />
<input type="submit" value="Save" name="new_file" />
</form>
<?
*/
if ( in_array('CREATE_SHEETS', $user_rights) ) {
		?>
<script type="text/javascript">
	window.onload = function() {	
		abc_editor = new ABCJS.Editor("abc", { paper_id: "paper0", midi_id:"midi", warnings_id:"warnings" });
	}
	</script>
		<?
	}
$db->close();

?>
</body>
</html>