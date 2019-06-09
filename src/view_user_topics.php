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
<style type="text/css">
ul {
    list-style-type:none;
	padding:5px;
	border:thin;
	border-style:dashed;
  	border-left:none;
	}
li {
	float: none;
	width:200px;
	padding:2px;
	margin-left:10px;
	cursor:pointer;
}
</style>

<script language="javascript">  

var start = 10;
var stopscrol = 0;
var userid; 
function loadUser(id){
	start = 10;
	stopscrol = 0;
	userid = id;
	$.ajax({
		url: "loadtopic.php?showUserid="+id,
		success: function(respond)
		{
			$("#userdata").html(respond);
		}
	});
  };
  
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
						$("#userdata").append(html);
						$('div#loadmoreajaxloader').hide();
						start = start + 10;
					}else
					{
						stopscrol = 1;
						$("#postswrapper").append('<div style="border:thin;border-style:dashed;align:center;border-right:none;border-left:none;border-bottom:none"><center><?=NO_MORE_POSTS?></center></div>');
						$('div#loadmoreajaxloader').hide();
					}
				}
				});
			}
		}
	});
</script>
</head>

<body>

<div id="linksoben"><a href="topic.php"><?=TOPIC_TEXT_BACK?></a></div>
<? createRightHeader() ?><br>
<br>
<br>
<div style="float:left;width:300;">
<ul>
<?

$query_user = $db->query("select * from elo_user order by user_name");
while ( $res = $db->fetch_array($query_user) ) {
?>
<li class="even" onClick="javascript:loadUser(<?=$res['user_id']?>);" onMouseOut="this.className='even'" onMouseOver="this.className='odd';"><?=$res['user_name']?></li>
<?
}

?>
</ul>
</div><div id="userdata" style="float:left;margin-top:20px;width:80%;"></div>
<div id="postswrapper">
   <div class="item"></div>
   <div id="loadmoreajaxloader" style="display:none;"><center><img src="ajax-loader.gif" /></center></div>
</div>
</body>
</html>