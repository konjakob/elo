<?php

require('includes/application_top.php');

if ( !in_array('IS_ADMIN', $user_rights ) || !in_array('CREATE_NEW_USER', $user_rights) ) {
	echo $twig->render("no_access.twig", $twig_data);
	exit();
}

$msgs = array();

if ( isset($_POST['new_user']) ) {
	
	$newUserEmail = filter_input(INPUT_POST, 't_email', FILTER_SANITIZE_EMAIL);
	$newUserName = filter_input(INPUT_POST, 't_name');
	$newUserLang = (int)filter_input(INPUT_POST, 't_lang', FILTER_VALIDATE_INT);
	
	if ( !filter_var($newUserEmail, FILTER_VALIDATE_EMAIL) ) {
		$msgs[] = array('state' => 'nok', 'text' => 'Please provide a valid email address.');
	}
	
	if ( !isset($_POST['t_pass']) || strlen($_POST['t_pass']) < 1 ) {
		$msgs[] = array('state' => 'nok', 'text' => 'Please provide a password.');	
	}
	
	if ( strlen($newUserName) < $conf['min_length_username'] ) {
		$msgs[] = array('state' => 'nok', 'text' => 'Please provide a user name with minimum '.$conf['min_length_username'].' characters.');	
	}

	if ( sizeof($msgs) < 1 ) {	
		
		require_once( "PasswordHash.php" );
		$hasher = new PasswordHash( 8, TRUE );

		$statement = $pdo->prepare("select user_id from elo_user where user_email=:t_email");
		$statement->bindValue(':t_email', $newUserEmail);
		$statement->execute();	
		
		if ( $statement->rowCount() ) {
			$msgs[] = array('state' => 'nok', 'text' => 'There is already an user with the given email address. Please use another email address.');
		} else {

			$statement = $pdo->prepare("insert into elo_user (user_name, user_email, user_password, lang_id) values (:t_name, :t_email, :pass, :t_lang)"); 
			$statement->bindValue(':t_name', $newUserName);
			$statement->bindValue(':t_email', $newUserEmail);
			$statement->bindValue(':pass', $hasher->HashPassword(filter_input(INPUT_POST, 't_pass')));
			$statement->bindValue(':t_lang', $newUserLang, PDO::PARAM_INT);
			$statement->execute();
			
			$newUserId= $pdo->lastInsertId();
		
			$msgs[] = array('state' => 'ok', 'text' => 'Created new user: '.filter_input(INPUT_POST, 't_name'));
			
			foreach ( $_POST['t_group'] as $g ) {
				$statement = $pdo->prepare("insert into elo_group_user (user_id, group_id) values (:user, :group)");
                $statement->bindValue(':user', $newUserId, PDO::PARAM_INT);
                $statement->bindValue(':group',(int)$g, PDO::PARAM_INT);
                $statement->execute();
			}
			
			foreach ( $_POST['t_rights'] as $r ) {
				$statement = $pdo->prepare("insert into elo_right_user (user_id, right_id) values (:user, :right)");
                $statement->bindValue(':user',$newUserId, PDO::PARAM_INT);
                $statement->bindValue(':right',(int)$r, PDO::PARAM_INT);
                $statement->execute();
			}
			
			if ( isset($_POST['t_send_email']) ) {
				
				$statement = $pdo->prepare("select lang_code from elo_lang where lang_id=:t_lang");
				$statement->bindValue(':t_lang', $newUserLang, PDO::PARAM_INT);
				$statement->execute();	
				
				$res = $statement->fetch(PDO::FETCH_ASSOC);
				$user_lang = $res['lang_code'];
						
				$email_data = array();
				$email_data['user_name'] = $newUserName;
				$email_data['user_email'] = $newUserEmail;
				$email_data['admin_name'] = $username;
				$email_data['url'] = $conf['url'];
				
				$email_text = $twig->render("emails/new_user_".$user_lang.".twig", $email_data);
				$email_text_text = strip_tags($email_text);
				
				$res = prepareEmailAndSend($email_text, $newUserEmail, $newUserName, EMAIL_NEW_USER_TEXT_TITLE, $email_text_text);
				
				if(!$res) {
				   $msgs[] = array('state' => 'nok', 'text' => 'Message could not be sent. Mailer Error: ' . $mail->ErrorInfo);
				} else {
					$msgs[] = array('state' => 'ok', 'text' => 'Sent a registration email with all information to the user.');	
				}
				
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
$twig_data['navElements'] = createAdminMenu();
$twig_data['exampleCode'] = createCode(8);
$twig_data['msgs'] = $msgs;
echo $twig->render("user_create.twig", $twig_data);