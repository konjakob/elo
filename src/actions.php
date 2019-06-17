<?php

require_once("dbclass.php");
$db = new db;

require("authenticate.class.php");

$auth = new Authenticate;

if(!$auth->validateAuthCookie())
	header("Location: login.php");

require("functions.php");

$userid = $auth->getUserId();

$query = $db->query("select r.right_key from elo_right as r, elo_right_user as ru where r.right_id=ru.right_id and ru.user_id='".$userid."'");
$user_rights = array();

while ( $res = $db->fetch_array($query) )
	$user_rights[] = $res['right_key'];
	
	
if(isset($_GET['action']) || isset($_POST['action'])) {
	
	if ( isset($_GET['action']) ) {
		$action = $_GET['action'];	
	} else {
		$action = $_POST['action'];
	}
	
	if ( $action == 'getUser' && isset($_GET['userid'])) {

		$returnData = array();
	
		$query_groups = $db->query("select * from elo_group order by group_name");
		$groups = array();
		while ( $res = $db->fetch_array($query_groups) )
			$groups[] = $res;
			
		$query_right = $db->query("select * from elo_right order by right_name");
		$rights = array();
		while ( $res = $db->fetch_array($query_right) )
			$rights[] = $res;	
			
		$query_user_rights = $db->query("select right_id from elo_right_user where user_id=".intval($_GET['userid']));
		$saved_rights = array();
		while ( $res2 = $db->fetch_array($query_user_rights) )
			$saved_rights[] = $res2['right_id'];
		//$returnData['user_rights'] = $saved_rights;
			
		$query_user_groups = $db->query("select group_id from elo_group_user where user_id=".intval($_GET['userid']));
		$saved_groups = array();
		while ( $res2 = $db->fetch_array($query_user_groups) )
			$saved_groups[] = $res2['group_id'];
		//$returnData['user_groups'] = $saved_groups;
		
		$query = $db->query("select user_id, user_name, user_email, lang_id, user_lastvisit from elo_user where user_id='".intval($_GET['userid'])."'");
		$res = $db->fetch_array($query);
		
		$returnData['user_data'] = $res;
		
		$saved_languages = array();
		$query_lang = $db->query("select * from elo_lang order by lang_name desc");
		while ( $res2 = $db->fetch_array($query_lang) )
			$saved_languages[] = $res2;
		
		$returnData['state'] = 'ok';
		
		$option_right_yes = array();
		$option_right_no = array();
		foreach ( $rights as $r ) {
		if ( in_array($r['right_id'],$saved_rights)) {
			$option_right_yes[$r['right_id']] = $r['right_name'];
		} else {
			$option_right_no[$r['right_id']] = $r['right_name'];
		}
		$returnData['option_right_yes'] = $option_right_yes;
		$returnData['option_right_no'] = $option_right_no;
		
		$option_group_yes = array();
		$option_group_no = array();

		foreach ( $groups as $g ) {
			if ( in_array($g['group_id'],$saved_groups)) {
				$option_group_yes[$g['group_id']] = $g['group_name'];
			} else {
				$option_group_no[$g['group_id']] = $g['group_name'];
			}
		}
		$returnData['option_group_yes'] = $option_group_yes;
		$returnData['option_group_no'] = $option_group_no;
		
		$returnData['exampleCode'] = createCode(8);
	}


	
		echo json_encode($returnData);
		exit();
		/*
		?>

       
<div id="changeUser">
        <table width="100%" border="0" cellspacing="2" cellpadding="3">
  <tr>
    <td>Username:</td>
    <td><input type="text" id="t_name_c" value="<?=stripslashes($res['user_name'])?>"></td>
  </tr>
  <tr>
    <td>Email:</td>
    <td><input type="text" id="t_email_c" value="<?=stripslashes($res['user_email'])?>"></td>
  </tr>
  <tr>
    <td>Password:</td>
    <td><input type="text" id="t_pass_c" value=""></td>
  </tr>
  <tr>
    <td>Language:</td>
    <td><select id="t_lang_c"><?
    	foreach ($saved_languages as $l) {
			echo '<option value="'.$l['lang_id'].'"';
			if ($l['lang_id'] == $res['lang_id']) 
				echo " selected";
			echo '>'.$l['lang_name'].'</option>';
		}
	?></select></td>
  </tr>
</table><div align="center"><input type="button" value="Save" onClick="javascript:changeUser(<?=$res['user_id']?>)"></div>
   
   <?
   $option_right_yes = "";
   $option_right_no = "";
   
   foreach ( $rights as $r ) {
		if ( in_array($r['right_id'],$saved_rights)) {
			$option_right_yes .= "<option value='".$r['right_id']."'>".$r['right_name']."</option>";
		} else {
			$option_right_no .= "<option value='".$r['right_id']."'>".$r['right_name']."</option>";
		}
	}
	echo "<table><tr><td>Existing rights:</td><td></td><td>User rights:</td></tr><tr><td><select id='ex_rights' size='5' multiple='multiple'>".$option_right_no."</select></td>";
	echo '<td><input type="button" value=">>" onClick="javascript:addRight('.$res['user_id'].');"><br><input type="button" value="<<" onClick="javascript:removeRight('.$res['user_id'].');"></td>';
	echo "<td><select id='us_rights' size='5' multiple='multiple'>".$option_right_yes."</select></td>";
	
   $option_group_yes = "";
   $option_group_no = "";
   
   foreach ( $groups as $g ) {
		if ( in_array($g['group_id'],$saved_groups)) {
			$option_group_yes .= "<option value='".$g['group_id']."'>".$g['group_name']."</option>";
		} else {
			$option_group_no .= "<option value='".$g['group_id']."'>".$g['group_name']."</option>";
		}
	}
	
	echo "<table><tr><td>Existing groups:</td><td></td><td>User groups:</td></tr><tr><td><select id='ex_groups' size='5' multiple='multiple'>".$option_group_no."</select></td>";
	echo '<td><input type="button" value=">>" onClick="javascript:addGroup('.$res['user_id'].');"><br><input type="button" value="<<" onClick="javascript:removeGroup('.$res['user_id'].');"></td>';
	echo "<td><select id='us_groups' size='5' multiple='multiple'>".$option_group_yes."</select></td>";	

   ?>
   </div>
   <script type="text/javascript">
   	function removeRight(id) {
		$(document).ready(function(){
		$.post("actions.php",
		{	  
		  userid:id,
		  action:'removeRight',
		  t_r:$("#us_rights").val()
		},
		function(data,status){
			$('#us_rights option:selected').each(function(){
				$("<option/>").val($(this).val()).text($(this).text()).prependTo("#ex_rights");
				$("#us_rights option[value="+$(this).val()+"]").remove();
			});
		});
	  });
	}
	function addRight(id) {
		$(document).ready(function(){
		$.post("actions.php",
		{	  
		  userid:id,
		  action:'addRight',
		  t_r:$("#ex_rights").val()
		},
		function(data,status){
			$('#ex_rights option:selected').each(function(){
				$("<option/>").val($(this).val()).text($(this).text()).prependTo("#us_rights");
				$("#ex_rights option[value="+$(this).val()+"]").remove();
			});
		});
	  });
	}
	function removeGroup(id) {
		$(document).ready(function(){
		$.post("actions.php",
		{	  
		  userid:id,
		  action:'removeGroup',
		  t_r:$("#us_groups").val()
		},
		function(data,status){
			$('#us_groups option:selected').each(function(){
				$("<option/>").val($(this).val()).text($(this).text()).prependTo("#ex_groups");
				$("#us_groups option[value="+$(this).val()+"]").remove();
			});
		});
	  });
	}
	function addGroup(id) {
		$(document).ready(function(){
		$.post("actions.php",
		{	  
		  userid:id,
		  action:'addGroup',
		  t_r:$("#ex_groups").val()
		},
		function(data,status){
			$('#ex_groups option:selected').each(function(){
				$("<option/>").val($(this).val()).text($(this).text()).prependTo("#us_groups");
				$("#ex_groups option[value="+$(this).val()+"]").remove();
			});
		});
	  });
	}
    function changeUser(id){
	var t4;
	var t5;
	$(document).ready(function(){
		t4 = $("#t_name_c").val();
		if ( t4.length < 1 ) {
			$("#t_name_c").css('background-color','#F96');
			$("#t_name_c").focus();
			return;
		}	
		t5 = $("#t_email_c").val();
		if ( t5.length < 1 ) {
			$("#t_email_c").css('background-color','#F96');
			$("#t_email_c").focus();
			return;
		}	
		$.post("actions.php",
		{	  
		  userid:id,
		  t_name:t4,
		  t_email:t5,
		  action:'changeUser',
		  t_pass:$("#t_pass_c").val(),
		  t_l:$("#t_lang_c").val()
		},
		function(data,status){
			$("#changeUser").html(data);
		});
	  });
  };
  </script>
        <?
		*/
		
	}
	else if ($action == 'changeUser') {
		$sql_pass = "";
		if ( isset($_POST['t_pass']) && strlen($_POST['t_pass'])) {
			require_once( "PasswordHash.php" );
    		$hasher = new PasswordHash( 8, TRUE );
			$sql_pass = ", user_password='".$hasher->HashPassword($_POST['t_pass'])."' ";
		}
		$db->query("update elo_user set user_name='".addslashes($_POST['t_name'])."', user_email='".addslashes($_POST['t_email'])."' ".$sql_pass.", lang_id='".intval($_POST['t_l'])."' where user_id='".intval($_POST['userid'])."'");
		echo "User saved.";
	}
	
	else if ($action == 'removeGroup') {
		$user = intval($_POST['userid']);
		for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ )
			$db->query("delete from elo_group_user where user_id='".$user."' and group_id='".intval($_POST['t_r'][$i])."'");
	}
	
	else if ($action == 'addGroup') {
		$user = intval($_POST['userid']);
		for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ )
			$db->query("insert into elo_group_user (user_id, group_id) values ('".$user."', '".intval($_POST['t_r'][$i])."')");		
	}
	
	else if ($action == 'removeRight') {
		$user = intval($_POST['userid']);
		for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ )
			$db->query("delete from elo_right_user where user_id='".$user."' and right_id='".intval($_POST['t_r'][$i])."'");		
	}
	
	else if ($action == 'removeUserFromGoup') {
		$db->query("delete from elo_group_user where gu_id='".intval($_POST['guid'])."'");		
	}
	
	else if ($action == 'addRight') {
		$user = intval($_POST['userid']);
		for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ )
			$db->query("insert into elo_right_user (user_id, right_id) values ('".$user."', '".intval($_POST['t_r'][$i])."')");			
	}
	
	else if ( $action == 'getGroupUser') {
		$query = $db->query("SELECT elo_user.user_id, elo_user.user_name, elo_group_user.gu_id
FROM elo_group INNER JOIN (elo_group_user INNER JOIN elo_user ON elo_group_user.user_id = elo_user.user_id) ON elo_group.group_id = elo_group_user.group_id
GROUP BY elo_group.group_id, elo_user.user_id, elo_user.user_name
HAVING (((elo_group.group_id)=".intval($_GET['group_id'])."));");

		echo "User:<hr><ul>";
		while ($res = $db->fetch_array($query)) {
			echo "<li id='user".$res['gu_id']."'>".$res['user_name']." (<a onclick='javascript:removeUserFromGroup(".$res['gu_id'].")'>Remove</a>)<br>";	
		}
		echo "</ul>";
		?>
           <script type="text/javascript">
   	function removeUserFromGroup(id) {
		$(document).ready(function(){
		$.post("actions.php",
		{	  
		  guid:id,
		  action:'removeUserFromGoup'
		},
		function(data,status){
			$('#user'+id).remove();
		});
	  });
	}
    </script>
        
        <?
	}
	else if ( $action == 'changeGoup' ) {
		$db->query("update elo_group set group_name='".addslashes($_POST['t_name'])."' where group_id='".intval($_POST['guid'])."'");
	}
	else if ( $action == 'getGroup') {
		$query = $db->query("select * from elo_group where group_id='".intval($_GET['group_id'])."'");
		$res = $db->fetch_array($query);
		?>
        Change a group<hr />
        <input type="text" id="group_name" value="<?=$res['group_name']?>" /><input type="button" value="Save" onClick="changeGroup();" />
        <script type="text/javascript">
   	function changeGroup() {
		$(document).ready(function(){
		$.post("actions.php",
		{	  
		  guid:<?=intval($_GET['group_id'])?>,
		  t_name:$('#group_name').val(),
		  action:'changeGoup'
		},
		function(data,status){
			alert("Saved");
		});
	  });
	}
    </script>
        <?
	}
}
	
