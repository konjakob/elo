<?php

require('includes/application_top.php');

if ( !in_array('IS_ADMIN', $user_rights ) ) {
	echo $twig->render("no_access.twig", $twig_data);
	exit();
}

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


$query_right = $pdo->prepare("select * from elo_right order by right_name");
$query_right->execute();


$rights = array();
while ( ($res = $query_right->fetch(PDO::FETCH_ASSOC)) !== false )
	$rights[] = $res;

$twig_data['rights'] = $rights;

$msgs = array();

$twig_data['exampleCode'] = createCode(8);
$twig_data['msgs'] = $msgs;
echo $twig->render("user_create.twig", $twig_data);