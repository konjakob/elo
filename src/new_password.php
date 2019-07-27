<?php

require_once(__DIR__ . '/includes/application_base.php');

$ref = "";

$error = "";
$success = "";

$langcode = "en";
	
if ( isset($_GET['id'])) {

	require_once("includes/functions.php");	
	require_once("includes/PasswordHash.php");
    $hasher = new PasswordHash( 8, TRUE );

	$statement = $pdo->prepare("select user_id from elo_pass_request where pr_code=:id and pr_time>'".(time()-3600)."' limit 1");
	$statement->bindValue(':id', $_GET['id']);
	$statement->execute();
	
	$res = $statement->fetch(PDO::FETCH_ASSOC);
	$user_id = $res['user_id'];
	
	if ( $user_id ) {
		
		// Delete any password reset request
		$statement = $pdo->prepare("delete from elo_pass_request where pr_code=:code");
		$statement->bindValue(':code', $_GET['id']);
		$statement->execute();
		
		$code = createCode(8);
		
		// Update the user with a new password
		$statement = $pdo->prepare("update elo_user set user_password=:pass where user_id=:user");
		$statement->bindValue(':user', $user_id, PDO::PARAM_INT);
		$statement->bindValue(':pass', $hasher->HashPassword($code));
		$statement->execute();
			
		$statement = $pdo->prepare("select elo_user.*, elo_lang.lang_code from elo_user left join elo_lang ON (elo_user.lang_id=elo_lang.lang_id) where user_id=:user limit 1");
		$statement->bindValue(':user', $user_id, PDO::PARAM_INT);
		$statement->execute();
			
		$user_res = $statement->fetch(PDO::FETCH_ASSOC);
	 
		$email_data['user_name'] = $user_res['user_name'];
		$email_data['new_password'] = $code;
		$email_data['url'] = $conf['url']."login.php";
		
		$email_text = $twig->render("emails/email_reset_".$user_res['lang_code'].".twig", $email_data);
	 	$email_text_text = strip_tags($email_text);
		
		$res = prepareEmailAndSend($email_text, $user_res['user_email'], $user_res['user_name'],'Password reseted',$email_text_text);
		if ( strlen($res[1])) {
			$msgs[] = array('state' => 'nok', 'text' => $res[1]);
		}
		if ( strlen($res[0])) {
			$msgs[] = array('state' => 'ok', 'text' => $res[0]);
		}
		
		$langcode = $user_res['lang_code'];
	
	} else {
		$msgs[] = array('state' => 'nok', 'text' => _('No password request found.'));
	}

} else {
	$msgs[] = array('state' => 'nok', 'text' => _('No password request found.'));
}

$twig_data['msgs'] = $msgs;
echo $twig->render("login.twig", $twig_data);
