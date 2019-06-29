<?php


require('includes/application_top.php');
	
if ( !in_array('IS_ADMIN', $user_rights ) ) {
    echo $twig->render("no-access.twig", $twig_data);
    exit();    
}
	
$allUser = array();
$query_user = $db->query("select user_id, user_name from elo_user order by user_name");
$db->close();

while ( $res = $db->fetch_array($query_user) ) {
    $allUser[] = $res;
}   


$twig_data['allUser'] = $allUser;

$breadcrumb[] = array( 'text' => 'Topics', 'href' => 'topic.php');
$breadcrumb[] = array( 'text' => 'Admin Panel', 'href' => 'panel.php');
$breadcrumb[] = array( 'text' => 'User view', 'href' => '');
$twig_data['breadcrumb'] = $breadcrumb;
//$twig_data['msgs'] = $msgs;

echo $twig->render("loaduser.twig", $twig_data);
    
 