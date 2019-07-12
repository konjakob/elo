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
	
	if ( strlen($newUserName) < 1 ) {
		$msgs[] = array('state' => 'nok', 'text' => 'Please provide a user name.');	
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

			$statement = $pdo->prepare("insert into elo_user (user_name, user_email, user_password, lang_id, user_lastvisit) values (:t_name, :t_email, :pass, :t_lang, '".time()."')"); //todo, change time to be default of DB
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
				
				$email_text = str_replace(array("{user_name}", "{user_email}", "{admin_name}", "{url}"), array($newUserName, $newUserEmail, $username, $conf['url']), file_get_contents("includes/languages/template_new_user_".$user_lang.".html"));
		
				date_default_timezone_set('Etc/UTC');
				require_once 'class.phpmailer.php';
				
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
				
				$mail->AddAddress($newUserEmail, $newUserName);  // Add a recipient
				
				$mail->Subject = EMAIL_NEW_USER_TEXT_TITLE;
				$mail->Body    = $email_text; // html text
				$mail->AltBody = EMAIL_TEXT_NOSUPPORT;
				
				if(!$mail->Send()) {
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

$twig_data['exampleCode'] = createCode(8);
$twig_data['msgs'] = $msgs;
echo $twig->render("user_create.twig", $twig_data);