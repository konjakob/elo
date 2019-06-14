<?

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

$fid = 1;

if ( isset($_GET['fid'])) 
	$fid = $_GET['fid'];

if ( $fid == 1 ) {
	
	require_once('SBBCodeParser.php');

	if ( in_array('ALLOW_HTML', $user_rights) ) {
		echo NEW_TEXT_HTML_ALLOWED;
	} else {
		echo NEW_TEXT_HTML_NOT_ALLOWED;	
	}
	
	echo "<hr>".TOPIC_TEXT_BBCODES."<ul>";	
	?>
	<div class="uk-column-1-6">
	<?php
	
	$ubbParser = new SBBCodeParser_Document();
	foreach ( $ubbParser->list_bbcodes() as $c ) 
		echo "<li>".$c;
	
	echo "</ul></div>";
	}
else if ( $fid == 2 ) {
	echo TOPIC_MAX_FILESIZE;
	if ($conf['max_filesize'] < 1024)
		echo $conf['max_filesize']." kB";	
	else
		echo round($conf['max_filesize']/1024,2)." MB";
} else if ( $fid == 4 ) {
	$max_time = $conf['max_edit_time'];
	if ( $max_time < 60 )
		$max_time = $max_time." seconds";
	else if ( $max_time < 3600 )
		$max_time = ($max_time/60)." minutes";
	else if ( $max_time < 24*3600 )
		$max_time = ($max_time/3600)." hours";
	else if ( $max_time < 24*3600*7 )
		$max_time = ($max_time/3600/24)." days";
	else
		$max_time = $max_time." seconds";	
	echo "You can edit or delete your reply till ".$max_time." after posting it.";	
	
} else if ( $fid == 3) {
	?>
    More information about the ABC notation can be found on following websites:
<ul style="list-style:disc;padding-left:10px;"><li><a href="http://penzeng.de/Geige/Abc.htm">http://penzeng.de/Geige/Abc.htm</a> (de)</li>
<li><a href="http://abcnotation.com/blog/2010/01/31/how-to-understand-abc-the-basics/">http://abcnotation.com/blog/2010/01/31/how-to-understand-abc-the-basics/</a> (en)</li></ul>
    <?
/*
	require_once('SBBCodeParser.php');
	$ubbParser = new SBBCodeParser_Document();
	foreach ( $ubbParser->list_bbcodes() as $c )  {
		$ubbParser2 = new SBBCodeParser_Document();
		echo "<li>"."[".$c."]text[/".$c."] ".$ubbParser2->parse("[".$c."]text[/".$c."]")->get_html()." ".htmlentities($ubbParser2->get_html());
	}
	echo "</ul>";
*/
}
?>