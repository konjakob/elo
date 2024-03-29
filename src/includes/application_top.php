<?php

require __DIR__ . '/../vendor/autoload.php'; 

require_once(__DIR__ . '/application_base.php');
require_once(__DIR__ . '/authenticate.class.php');

if ( DEBUG_MODE_ON ) {
    $whoops = new \Whoops\Run;
    $whoops->prependHandler(new \Whoops\Handler\PrettyPageHandler);
    $whoops->register();
}

$auth = new Authenticate;

if(!$auth->validateAuthCookie()) {
	if ( isset($jsonMode) ) {
		echo json_encode(array('state' => 'nok', 'key' => 'no_valid_auth', 'text' => 'Please log in.', 'title' => 'Error', 'type' => 'error'));
	} else {
		header("Location: login.php?ref=".base64_encode($_SERVER['PHP_SELF']."?".$_SERVER["QUERY_STRING"]));
	}
	exit();
}

require_once(__DIR__ . '/functions.php');

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
	$langcode = "en_US";

$twig_data['langcode'] = $langcode;

$language = $langcode.".UTF-8";
putenv("LANGUAGE=" . $language);
setlocale(LC_ALL, $language);

$time = time();

define('IMAGE_CROP_MAX_WIDTH_HEIGHT', 300);