<?php

require('includes/application_top.php');

$saved_languages = array();
$query_lang = $db->query("select * from elo_lang order by lang_name desc");
while ( $res2 = $db->fetch_array($query_lang) )
	$saved_languages[] = $res2;

$twig_data['saved_languages'] = $saved_languages;

$breadcrumb[] = array('href' => 'topic.php', 'text' => 'Topics');
$breadcrumb[] = array('href' => '', 'text' => 'User settings');

$msgs = array();

if (isset($_POST['action'])) {
    $sql_pass = "";
    
    
    if ( $db->query_one("select user_id from elo_user where user_email='".addslashes($_POST['t_email'])."' and user_id<>'".$userid."'") ) {
        $msgs[] = array('state' => 'nok', 'text' => USER_SETTINGS_EMAIL_EXISTS);
    } else {
        
        if ( isset($_POST['t_pass']) && strlen($_POST['t_pass'])) {
            require_once( "PasswordHash.php" );
            $hasher = new PasswordHash( 8, TRUE );
            $sql_pass = ", user_password='".$hasher->HashPassword($_POST['t_pass'])."' ";
        }
        $db->query("update elo_user set user_name='".addslashes($_POST['t_name'])."', user_email='".addslashes($_POST['t_email'])."' ".$sql_pass.", lang_id='".intval($_POST['t_lang'])."' where user_id='".$userid."'");
        header("Location: user-settings.php?saved=1");
    }
}
if( isset($_GET['saved']))
    $msgs[] = array('state' => 'ok', 'text' => USER_SETTINGS_SAVED);


$db->close();

$twig_data['user_email'] = $user_res['user_email'];
$twig_data['lang_id'] = $user_res['lang_id'];

$twig_data['msgs'] = $msgs;
$twig_data['breadcrumb'] = $breadcrumb;
echo $twig->render("user-settings.twig", $twig_data);


