<?php

// Set language
$language = "de_DE.UTF-8";
putenv("LANGUAGE=" . $language);
setlocale(LC_ALL, $language);

// Specify the location of the translation tables
$domain = "elo";
bindtextdomain($domain, __DIR__ . '/locale');
bind_textdomain_codeset($domain, 'UTF-8');	
// Choose domain
textdomain($domain);

header("Content-type: text/html; charset=utf-8");

$breadcrumb = array();

if ( !isset($jsonMode) ) {
	require_once 'ext/Twig/Autoloader.php';
	Twig_Autoloader::register();

	$loader = new Twig_Loader_Filesystem('C:\\wamp\www\\elo\\templates'); 
	$twig = new Twig_Environment($loader, array(
		'cache' => false
	));
	
	$twig->addExtension(new Twig_Extensions_Extension_I18n());
    $twig->addExtension(new Twig_Extensions_Extension_Date());

}
 /*, array(
		'cache' => 'ext/twig-cache',
	));*/
// $function = new \Twig\TwigFunction('function_name', function () {
    // $calcFrom = $from;
	// $calcTo = $to;
	// $now->diff($calcFrom)->format("%a")
// });
// $twig->addFunction($function);

$twig_data['current_url'] = $_SERVER['PHP_SELF'];

require_once("dbclass.php");
require_once("authenticate.class.php");

$auth = new Authenticate;

if(!$auth->validateAuthCookie()) {
	if ( isset($jsonMode) ) {
		echo json_encode(array('state' => 'nok', 'key' => 'no_valid_auth', 'text' => 'Please log in.', 'title' => 'Error', 'type' => 'error'));
	} else {
		header("Location: login.php?ref=".base64_encode($_SERVER['PHP_SELF']."?".$_SERVER["QUERY_STRING"]));
	}
	exit();
}

$statement = $pdo->prepare("select varname as config_name, value as config_value from elo_config");
$statement->execute();
		
$conf = array();
while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
	$conf[$res['config_name']] = $res['config_value'];

$twig_data['conf'] = $conf;

require_once("functions.php");

$userid = $auth->getUserId();

/* Get the rights of the user */
$sql = "select r.right_key from elo_right as r, elo_right_user as ru where r.right_id=ru.right_id and ru.user_id = :userid";
$statement = $pdo->prepare($sql);
$statement->bindValue(':userid', $userid, PDO::PARAM_INT);
$statement->execute();
		
$user_rights = array();
while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
	$user_rights[] = $res['right_key'];

$twig_data['user_rights'] = $user_rights;

/* Get the user data of the logged user */
$sql = "select elo_user.*, elo_lang.lang_id, elo_lang.lang_code from elo_user left join elo_lang ON (elo_user.lang_id=elo_lang.lang_id) where user_id=:userid limit 1";
$statement = $pdo->prepare($sql);
$statement->bindValue(':userid', $userid, PDO::PARAM_INT);
$statement->execute();

$user_res = $statement->fetch(PDO::FETCH_ASSOC);
$username = $twig_data['user_name'] = $user_res['user_name'];
$langcode = $user_res['lang_code'];
$twig_data['user_picture'] = $user_res['user_picture'];

if ( strlen($langcode) <1 )
	$langcode = "en";

$twig_data['langcode'] = $langcode;

$time = time();

define('IMAGE_CROP_MAX_WIDTH_HEIGHT', 300);