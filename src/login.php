<?php
require_once("dbclass.php");
require_once 'ext/Twig/Autoloader.php';
Twig_Autoloader::register();

$loader = new Twig_Loader_Filesystem('C:\\wamp\www\\elo\\templates'); 
$twig = new Twig_Environment($loader);

$sql = "select varname as config_name, value as config_value from elo_config";
$statement = $pdo->prepare($sql);
$statement->execute();
		
$conf = array();
while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
	$conf[$res['config_name']] = $res['config_value'];

$twig_data['current_url'] = $_SERVER['PHP_SELF'];

$ref = "";
$msgs = array();


	
$twig_data['showForgotten'] = (isset($_GET['pass']) && $_GET['pass'] == 'forgotten');

if ( isset($_POST['action_login'])) {

	require("authenticate.class.php");
	
	$auth = new Authenticate;
	
	$ref = $_POST['ref'];
	
	try {
		
		$auth->authenticate($_POST['t_email'], $_POST['t_pass'], 0);
				
		if ( strlen($ref) ) {
			header("Location: ".base64_decode($ref));
		} else {
			header("Location: topic.php");
		}
		exit;
	} catch(  AuthException $a)  {
        $msgs[] = array('state' => 'nok', 'text' => _("Login failed."));
	}
} else if (isset($_POST['action_passforgotten'])) {
	
	$statement = $pdo->prepare("select elo_user.*, elo_lang.lang_code from elo_user left join elo_lang ON (elo_user.lang_id=elo_lang.lang_id) where user_email=:t_email limit 1");
	$statement->bindValue(':t_email', filter_input(INPUT_POST, 't_email'));
	$statement->execute();
	
	if ( $statement->rowCount() ) {
		$user_res = $statement->fetch(PDO::FETCH_ASSOC);

		require_once("functions.php");
		 
		 $code = createCode(8);
		 
		$statement = $pdo->prepare("insert into elo_pass_request (user_id, pr_code, pr_time) values (:user_id, :code, now())");
		$statement->bindValue(':user_id', $user_res['user_id'], PDO::PARAM_INT);
		$statement->bindValue(':code', $code);
		$statement->execute();
		 
		$email_data = array();
		$email_data['user_name'] = $user_res['user_name'];
		$email_data['url'] = $conf['url']."new_password.php?id=".$code;
        $email_data['urlText'] = _('Reset password');
		
		$email_text = $twig->render("emails/email_forgotten.twig", $email_data);		 
		$email_text_text = strip_tags($email_text);
		
		$res = prepareEmailAndSend($email_text, $user_res['user_email'], $user_res['user_name'],_('Password reset requested'),$email_text_text);

		if ( strlen($res[1])) {
            $msgs[] = array('state' => 'nok', 'text' => $res[1]);
		}
		if ( strlen($res[0])) {
            $msgs[] = array('state' => 'ok', 'text' => $res[0]);
		}
	} else {
         $msgs[] = array('state' => 'nok', 'text' => _('Email is not known.'));
	}
	$twig_data['showForgotten'] = 1;
} else {
	if ( isset($_GET['ref']))
		$ref = $_GET['ref'];
}

$twig_data['ref'] = $ref;
$twig_data['msgs'] = $msgs;
echo $twig->render("login.twig", $twig_data);
