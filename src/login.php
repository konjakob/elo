<?php

require_once 'ext/Twig/Autoloader.php';
Twig_Autoloader::register();

$loader = new Twig_Loader_Filesystem('C:\\wamp\www\\elo\\templates'); 
$twig = new Twig_Environment($loader);

$twig_data['current_url'] = $_SERVER['PHP_SELF'];

$ref = "";
$msgs = array();

require_once("dbclass.php");
$db = new db;
	
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
        $msgs[] = array('state' => 'nok', 'text' => "Login failed.");
	}
} else if (isset($_POST['action_passforgotten'])) {
	 $query = $db->query("select elo_user.*, elo_lang.lang_code from elo_user left join elo_lang ON (elo_user.lang_id=elo_lang.lang_id) where user_email='".addslashes($_POST['t_email'])."' limit 1");
	 if ( $db->num_rows($query) ) {
		 $user_res = $db->fetch_array($query);

		 require("functions.php");
		 
		 $code = createCode(8);
		 
		 $db->query("insert into elo_pass_request (user_id, pr_code, pr_time) values ('".$user_res['user_id']."', '".$code."', '".time()."')");
		 
		 $email_text = str_replace(array("{user_name}", "{url}"), array($user_res['user_name'], $conf['url']."new_password.php?id=".$code), file_get_contents("includes/languages/template_email_forgotten_".$user_res['lang_code'].".html"));
		 
		$email_text_text = preg_replace('/(\<style)(.*)(style>)/s','',$email_text);
		$email_text_text = str_replace(array("<!DOCTYPE html>","<br>"),array("","\n"),$email_text_text);
		$email_text_text = preg_replace('/(<\/?)(\w+)([^>]*>)/e','',$email_text_text);
		
		$res = prepareEmailAndSend($email_text, $user_res['user_email'], $user_res['user_name'],'Password reset requested',$email_text_text);

		if ( strlen($res[1])) {
            $msgs[] = array('state' => 'nok', 'text' => $res[1]);
		}
		if ( strlen($res[0])) {
            $msgs[] = array('state' => 'ok', 'text' => $res[1]);
		}
	 } else {
         $msgs[] = array('state' => 'nok', 'text' => 'Email is not known.');
	 }
	 $_GET['pass'] = 'forgotten';
} else {
	if ( isset($_GET['ref']))
		$ref = $_GET['ref'];
}

$langcode = "en";
require_once('includes/languages/'.$langcode.'.php');
$twig_data['showForgotten'] = (isset($_GET['pass']) && $_GET['pass'] == 'forgotten');
$twig_data['ref'] = $ref;
$twig_data['msgs'] = $msgs;
echo $twig->render("login.twig", $twig_data);
