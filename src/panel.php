<?php

require('includes/application_top.php');

if ( !in_array('IS_ADMIN', $user_rights ) ) {
	echo $twig->render("no_access.twig", $twig_data);
	exit();
}


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
				$success .= "<br>Sent a registration email with all information to the user.";
			}

			
		}
	}
}

if ( isset($_POST['new_group']) ) {
	$statement = $pdo->prepare("insert into elo_group (group_name) values (:t_group)");
	$query->bindValue(':t_group', filter_input(INPUT_POST, 'new_group'));
	$statement->execute();
	$msgs[] = array('state' => 'ok', 'text' => 'Saved the new group.');
}

$statement = $pdo->prepare("select * from elo_group order by group_name");
$statement->execute();

$groups = array();
while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
	$groups[] = $res;

$twig_data['groups'] = $groups;

/* Get the available languages */
$statement = $pdo->prepare("select * from elo_lang");
$statement->execute();

$langs = array();
while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
	$langs[] = $res;
$twig_data['langs'] = $langs;	


if ( isset($_GET['action']) ) {
	
	if ( $_GET['action'] == 'add_right' ) {
		$db->query("insert into elo_right_user (user_id, right_id) values ('".intval($_GET['user_id'])."', '".intval($_GET['right_id'])."')");
	} else if ($_GET['action'] == 'delete_right') {
		$db->query("delete from elo_right_user where user_id='".intval($_GET['user_id'])."' and right_id='".intval($_GET['right_id'])."'");	
	}
		
}

$query_user = $pdo->prepare("select * from elo_user order by user_name");
$query_user->execute();

$query_right = $pdo->prepare("select * from elo_right order by right_name");
$query_right->execute();

if ( in_array('CREATE_NEW_RIGHT', $user_rights) && isset($_POST['new_right']) && isset($_POST['t_name']) && isset($_POST['t_key']) ) {
	$query_user = $pdo->prepare("insert into elo_right (right_name, right_key) values (:t_name, :t_key)");
	$query->bindValue(':t_name', filter_input(INPUT_POST, 't_name'));
	$query->bindValue(':userid', filter_input(INPUT_POST, 't_key'));
	$query_user->execute();
	$twig_data['saved_new_right'] = 1;
}

$rights = array();
while ( ($res = $query_right->fetch(PDO::FETCH_ASSOC)) !== false )
	$rights[] = $res;

$twig_data['rights'] = $rights;
	
$users = array();

while ( ($res = $query_user->fetch(PDO::FETCH_ASSOC)) !== false ) {
	$users[] = $res;
	// todo: check if this can be done in one query
	$query = $pdo->prepare("select right_id from elo_right_user where user_id=:userid");
	$query->bindValue(':userid', $res['user_id'], PDO::PARAM_INT);
	$query->execute();
	
	$saved_rights = array();
	while ( ($res2 = $query->fetch(PDO::FETCH_ASSOC)) !== false )
		$saved_rights[] = $res2['right_id'];
}
$twig_data['users'] = $users;

$msgs = array();

/* this is now available in actions.php */
/*
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
		
		if ( in_array('CREATE_ATTACHMENTS', $user_rights) && isset($_POST['picture']) ) {
			//processAttachment();
			foreach ( $_POST['picture'] as $p ) 
				$db->query("insert into elo_reply_attachment (reply_id, attachment_id) values ('".$reply_id."', '".(int)$p."')");
		}
		
		if ( in_array('CREATE_SHEETS', $user_rights) && isset($_POST['abc']) && strlen($_POST['abc'])) {
			processMusic();
		}
		$msgs[] = array('state' => 'ok', 'text' => 'New topic was created.');
	} else {
		$default_text = $_POST['t_topic'];
		if ( isset($_POST['abc']) && strlen($_POST['abc']) ) {
			$default_text .= " # Please add this text to the music box: ".$_POST['abc'];
		}
		$msgs[] = array('state' => 'nok', 'text' => 'Please enter a title.');
	
	}
}
*/

$twig_data['exampleCode'] = createCode(8);
$breadcrumb[] = array( 'text' => 'Topics', 'href' => 'topic.php');
$breadcrumb[] = array( 'text' => 'Admin Panel', 'href' => '');
$twig_data['breadcrumb'] = $breadcrumb;
$twig_data['msgs'] = $msgs;
echo $twig->render("panel.twig", $twig_data);