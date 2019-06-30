<?php

header("Content-type: text/html; charset=utf-8");

$breadcrumb = array();

if ( !isset($jsonMode) ) {
	require_once 'ext/Twig/Autoloader.php';
	Twig_Autoloader::register();

	$loader = new Twig_Loader_Filesystem('C:\\wamp\www\\elo\\templates'); 
	$twig = new Twig_Environment($loader);
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
$db = new db;

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

$query = $db->query("select * from elo_config");
$conf = array();
while ( $res = $db->fetch_array($query) )
	$conf[$res['config_name']] = $res['config_value'];

$twig_data['conf'] = $conf;

require_once("functions.php");

$userid = $auth->getUserId();

$query = $db->query("select r.right_key from elo_right as r, elo_right_user as ru where r.right_id=ru.right_id and ru.user_id='".$userid."'");
$user_rights = array();

while ( $res = $db->fetch_array($query) )
	$user_rights[] = $res['right_key'];

$twig_data['user_rights'] = $user_rights;

$query = $db->query("select elo_user.*, elo_lang.lang_id, elo_lang.lang_code from elo_user left join elo_lang ON (elo_user.lang_id=elo_lang.lang_id) where user_id='".$userid."' limit 1");
$user_res = $db->fetch_array($query);
$username = $twig_data['user_name'] = $user_res['user_name'];
$langcode = $user_res['lang_code'];
$twig_data['user_picture'] = $user_res['user_picture'];

if ( strlen($langcode) <1 )
	$langcode = "en";

$twig_data['langcode'] = $langcode;
require_once('includes/languages/'.$langcode.'.php');

$time = time();