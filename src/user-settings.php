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

$saved_languages = array();
$query_lang = $db->query("select * from elo_lang order by lang_name desc");
while ( $res2 = $db->fetch_array($query_lang) )
	$saved_languages[] = $res2;

?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
		<!-- CSS FILES -->
		<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/uikit/3.0.2/css/uikit.min.css">
</head>
<body>
<? createRightHeader() ?>

<div class="uk-container">

<ul class="uk-breadcrumb">
    <li><a href="topics.php">Topics</a></li>
    <li class="uk-disabled">User settings</li>
</ul>


   <?php
   if (isset($_POST['action'])) {
		$sql_pass = "";
		
		
		if ( $db->query_one("select user_id from elo_user where user_email='".addslashes($_POST['t_email'])."' and user_id<>'".$userid."'") ) {
			echo "<div id='error'>".USER_SETTINGS_EMAIL_EXISTS."</div>";
		} else {
			
			if ( isset($_POST['t_pass']) && strlen($_POST['t_pass'])) {
				require_once( "PasswordHash.php" );
				$hasher = new PasswordHash( 8, TRUE );
				$sql_pass = ", user_password='".$hasher->HashPassword($_POST['t_pass'])."' ";
			}
			$db->query("update elo_user set user_name='".addslashes($_POST['t_name'])."', user_email='".addslashes($_POST['t_email'])."' ".$sql_pass.", lang_id='".intval($_POST['t_lang'])."' where user_id='".$userid."'");
			header("Location: user-settings.php?saved=1");
		}
	}
	if( isset($_GET['saved']))
		echo "<div id='correct'>".USER_SETTINGS_SAVED."</div>";
	?>
   <h1><?=USER_SETTINGS_HEADER?></h1>

   <form action="<?=$_SERVER['PHP_SELF']?>" method="post" class="uk-form-stacked">
   
    <div class="uk-margin">
        <label class="uk-form-label" for="form-element1"><?=USER_SETTINGS_USERNAME?></label>
        <div class="uk-form-controls">
            <input class="uk-input" id="form-element1" type="text" name="t_name" value="<?=stripslashes($user_res['user_name'])?>">
        </div>
    </div>
	
        <table width="100%" border="0" cellspacing="2" cellpadding="3">
  <tr>
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td><?=USER_SETTINGS_EMAIL?></td>
    <td><input type="text" name="t_email" value="<?=stripslashes($user_res['user_email'])?>"></td>
  </tr>
  <tr>
    <td><?=USER_SETTINGS_PASSWORD?></td>
    <td><input type="text" name="t_pass" value=""> <span id="small"><?=USER_SETTINGS_PASSWORD_NOT_CHANGE?></span></td>
  </tr>
  <tr>
    <td><?=USER_SETTINGS_LANGUAGE?></td>
    <td><select name="t_lang"><?
    	foreach ($saved_languages as $l) {
			echo '<option value="'.$l['lang_id'].'"';
			if ($l['lang_id'] == $user_res['lang_id']) 
				echo " selected";
			echo '>'.$l['lang_name'].'</option>';
		}
	?></select></td>
  </tr>
</table><div align="center"><input type="submit" value="<?=TOPIC_TEXT_SAVE?>" name="action"></div></form>
<?php

$db->close();

?>
</div>
		<!-- JS FILES -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/uikit/3.0.2/js/uikit.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/uikit/3.0.2/js/uikit-icons.min.js"></script>
</body>
</html>