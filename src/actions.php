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
	
	$action = isset($_GET['action'])  ?  $_GET['action'] : $_POST['action'];
	
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
			
		$query_user_groups = $db->query("select group_id from elo_group_user where user_id=".intval($_GET['userid']));
		$saved_groups = array();
		while ( $res2 = $db->fetch_array($query_user_groups) )
			$saved_groups[] = $res2['group_id'];
		
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

		echo json_encode($returnData);
		exit();
			
	}
	else if ($action == 'changeUser') {
		$returnData = array();
		if ( isset($_POST['userid']) && isset($_POST['t_name']) && isset($_POST['t_email']) && isset($_POST['t_l']) ) {
			if (strlen($_POST['t_name']) && strlen($_POST['t_email'])) {
				$sql_pass = "";
				if ( isset($_POST['t_pass']) && strlen($_POST['t_pass'])) {
					require_once( "PasswordHash.php" );
					$hasher = new PasswordHash( 8, TRUE );
					$sql_pass = ", user_password='".$hasher->HashPassword($_POST['t_pass'])."' ";
				}
				$db->query("update elo_user set user_name='".addslashes($_POST['t_name'])."', user_email='".addslashes($_POST['t_email'])."' ".$sql_pass.", lang_id='".intval($_POST['t_l'])."' where user_id='".intval($_POST['userid'])."'");
			} else {
				$returnData['state'] = 'nok';
				$returnData['text'] = 'Please enter an email address and a name.';
				$returnData['title'] = 'Error';		
			}
		} else {
			$returnData['state'] = 'nok';
			$returnData['text'] = 'Please enter all the data';
			$returnData['title'] = 'Error';			
		}
		echo json_encode($returnData);
		exit();
	}
	
	else if ($action == 'removeGroup') {
		$returnData = array();
		if ( isset($_POST['userid']) && isset($_POST['t_r']) && is_array($_POST['t_r']) ) {		
			$user = intval($_POST['userid']);
			for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ )
				$db->query("delete from elo_group_user where user_id='".$user."' and group_id='".intval($_POST['t_r'][$i])."'");
		} else {
			$returnData['state'] = 'nok';
			$returnData['text'] = 'Please select a user.';
			$returnData['title'] = 'Error';			
		}
		echo json_encode($returnData);
		exit();
	}
	
	else if ($action == 'addGroup') {
		$returnData = array();
		if ( isset($_POST['userid']) && isset($_POST['t_r']) && is_array($_POST['t_r']) ) {
			$user = intval($_POST['userid']);
			$returnData['state'] = 'ok';
			for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ )
				$db->query("insert into elo_group_user (user_id, group_id) values ('".$user."', '".intval($_POST['t_r'][$i])."')");	
		} else {
			$returnData['state'] = 'nok';
			$returnData['text'] = 'Please select a user.';
			$returnData['title'] = 'Error';			
		}
		echo json_encode($returnData);
		exit();
	}
	
	else if ($action == 'removeRight') {
		$returnData = array();
		if ( isset($_POST['userid']) && isset($_POST['t_r']) && is_array($_POST['t_r']) ) {
			$returnData['state'] = 'ok';
			$user = intval($_POST['userid']);
			for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ )
				$db->query("delete from elo_right_user where user_id='".$user."' and right_id='".intval($_POST['t_r'][$i])."'");		
		} else {
			$returnData['state'] = 'nok';
			$returnData['text'] = 'Please select a user.';
			$returnData['title'] = 'Error';
		}
		echo json_encode($returnData);
		exit();
	}
	
	else if ($action == 'removeUserFromGoup') {
		$returnData = array();
		if ( isset($_POST['guid']) ) {
			$returnData['state'] = 'ok';
			$db->query("delete from elo_group_user where gu_id='".intval($_POST['guid'])."'");	
		} else {
			$returnData['state'] = 'nok';
			$returnData['text'] = 'Please select a user.';
			$returnData['title'] = 'Error';
		}
		echo json_encode($returnData);
		exit();
	}
	else if ($action == 'deleteGroup') {
		$returnData = array();
		if ( isset($_POST['delete_group']) ) {
			$returnData['state'] = 'ok';
			// todo: delete user relations to group
			$db->query("delete from elo_group where group_id='".intval($_POST['delete_group'])."'");
		} else {
			$returnData['state'] = 'nok';
			$returnData['text'] = 'Please select a group.';
			$returnData['title'] = 'Error';
		}
		echo json_encode($returnData);
		exit();
	}
	else if ($action == 'addRight') {
		$returnData = array();
		if ( isset($_POST['userid']) && isset($_POST['t_r']) && is_array($_POST['t_r']) ) {
			$returnData['state'] = 'ok';
			$user = intval($_POST['userid']);
			for ( $i = 0; $i < sizeof($_POST['t_r']); $i++ )
				$db->query("insert into elo_right_user (user_id, right_id) values ('".$user."', '".intval($_POST['t_r'][$i])."')");
		} else {
			$returnData['state'] = 'nok';
			$returnData['text'] = 'Please select a user.';
			$returnData['title'] = 'Error';
		}	
		echo json_encode($returnData);
		exit();
	}
	
	else if ( $action == 'getGroupUser') {
		
		$returnData = array();
		if ( isset($_GET['group_id']) ) {
			$returnData['state'] = 'ok';
			
			$query = $db->query("SELECT elo_user.user_id, elo_user.user_name, elo_group_user.gu_id
	FROM elo_group INNER JOIN (elo_group_user INNER JOIN elo_user ON elo_group_user.user_id = elo_user.user_id) ON elo_group.group_id = elo_group_user.group_id
	GROUP BY elo_group.group_id, elo_user.user_id, elo_user.user_name
	HAVING (((elo_group.group_id)=".intval($_GET['group_id'])."));");
			$returnData['users'] = array();
			while ($res = $db->fetch_array($query)) {
				$returnData['users'][$res['gu_id']] = $res['user_name'];
			}
		} else {
			$returnData['state'] = 'nok';
			$returnData['text'] = 'Please select a group';
			$returnData['title'] = 'error';
		}
		echo json_encode($returnData);
		exit();
	}
	else if ( $action == 'changeGoup' ) {
		$returnData = array();
		if ( isset($_POST['guid']) && isset($_POST['t_name']) && strlen($_POST['t_name']) > 0) {
			$returnData['state'] = 'ok';
			$db->query("update elo_group set group_name='".addslashes($_POST['t_name'])."' where group_id='".intval($_POST['guid'])."'");
		} else {
			$returnData['state'] = 'nok';
			$returnData['text'] = 'Please select a group and enter a name.';
			$returnData['title'] = 'Error';
		}
		echo json_encode($returnData);
		exit();
	}
	else if ( $action == 'getGroup') {
		$returnData = array();
		if ( isset($_GET['group_id'])) {
			$returnData['state'] = 'ok';
			$query = $db->query("select group_id, group_name from elo_group where group_id='".intval($_GET['group_id'])."'");
			$returnData['data'] = $db->fetch_array($query);
		} else {
			$returnData['state'] = 'nok';
			$returnData['text'] = 'Please select a group.';
			$returnData['title'] = 'Error';
		}
		echo json_encode($returnData);
		exit();
	}
	else if ( $action == 'newTopic') {
		
	}
}
	
