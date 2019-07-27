<?php

require('includes/application_top.php');

$saved_languages = array();

$statement = $pdo->prepare("select * from elo_lang order by lang_name asc");
$statement->execute();
	
while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
	$saved_languages[] = $res;

$twig_data['saved_languages'] = $saved_languages;

$breadcrumb[] = array('href' => 'topic.php', 'text' => _('Topics'));
$breadcrumb[] = array('href' => '', 'text' => _('User settings'));

$msgs = array();

/* will be deleted, now in actions.php */
if (isset($_POST['action'])) {
    $sql_pass = "";
    
	$statement = $pdo->prepare("select user_id from elo_user where user_email=:t_email and user_id<>:userid");
	$statement->bindValue(':userid', $userid, PDO::PARAM_INT);
	$statement->bindValue(':t_email', $_POST['t_email']);
	$statement->execute();
    
    if ( $statement->rowCount() ) {
        $msgs[] = array('state' => 'nok', 'text' => _('There is already an user with the given email address. Please use another email address.'));
    } else {
        
		require_once( "includes/PasswordHash.php" );
		$hasher = new PasswordHash( 8, TRUE );
			
        if ( isset($_POST['t_pass']) && strlen($_POST['t_pass'])) {
            $sql_pass = ", user_password=:user_password ";
        }
		
		$statement = $pdo->prepare("update elo_user set user_name=:t_name, user_email=:t_email ".$sql_pass.", lang_id=:t_lang where user_id=:userid");
		$statement->bindValue(':userid', $userid, PDO::PARAM_INT);
		$statement->bindValue(':t_email', $_POST['t_email']);
		$statement->bindValue(':t_lang', (int)$_POST['t_lang'], PDO::PARAM_INT);
		$statement->bindValue(':t_name', $_POST['t_name']);
		if ( isset($_POST['t_pass']) && strlen($_POST['t_pass']))
			$statement->bindValue(':user_password', $hasher->HashPassword($_POST['t_pass']));
		$statement->execute();
		
        header("Location: user-settings.php?saved=1");
    }
}
if( isset($_GET['saved']))
    $msgs[] = array('state' => 'ok', 'text' => _('Saved'));

$twig_data['user_email'] = $user_res['user_email'];
$twig_data['lang_id'] = $user_res['lang_id'];

$twig_data['msgs'] = $msgs;
$twig_data['breadcrumb'] = $breadcrumb;
echo $twig->render("user-settings.twig", $twig_data);


