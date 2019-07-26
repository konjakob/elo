<?php

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

require_once 'ext/Twig/Autoloader.php';
Twig_Autoloader::register();

$loader = new Twig_Loader_Filesystem('C:\\wamp\www\\elo\\templates'); 
$twig = new Twig_Environment($loader, array(
	'cache' => false
));

 /*, array(
		'cache' => 'ext/twig-cache',
	));*/

$twig->addExtension(new Twig_Extensions_Extension_I18n());
$twig->addExtension(new Twig_Extensions_Extension_Date());

$twig_data['current_url'] = $_SERVER['PHP_SELF'];

require_once("dbclass.php");

$statement = $pdo->prepare("select varname as config_name, value as config_value from elo_config");
$statement->execute();
		
$conf = array();
while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
	$conf[$res['config_name']] = $res['config_value'];

$twig_data['conf'] = $conf;