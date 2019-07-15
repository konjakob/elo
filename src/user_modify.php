<?php

require('includes/application_top.php');

if ( !in_array('IS_ADMIN', $user_rights ) ) {
	echo $twig->render("no_access.twig", $twig_data);
	exit();
}

/* Get all groups */
$statement = $pdo->prepare("select * from elo_group order by group_name");
$statement->execute();

$groups = array();
while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
	$groups[] = $res;

$twig_data['groups'] = $groups;

/* Get the available languages */
$statement = $pdo->prepare("select * from elo_lang");
$statement->execute();

$langs = array();
while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
	$langs[] = $res;
$twig_data['langs'] = $langs;	

/* Get the rights */
$query_right = $pdo->prepare("select * from elo_right order by right_name");
$query_right->execute();

/* moved to actions 
if ( in_array('CREATE_NEW_RIGHT', $user_rights) && isset($_POST['new_right']) && isset($_POST['t_name']) && isset($_POST['t_key']) ) {
	$query_user = $pdo->prepare("insert into elo_right (right_name, right_key) values (:t_name, :t_key)");
	$query->bindValue(':t_name', filter_input(INPUT_POST, 't_name'));
	$query->bindValue(':userid', filter_input(INPUT_POST, 't_key'));
	$query_user->execute();
	$twig_data['saved_new_right'] = 1;
}*/

$rights = array();
while ( ($res = $query_right->fetch(PDO::FETCH_ASSOC)) !== false )
	$rights[] = $res;

$twig_data['rights'] = $rights;
	
/* Get the users */
$start = isset($_GET['start']) ? (int)filter_input(INPUT_GET, 'start', FILTER_SANITIZE_NUMBER_INT) : 0;

$maxElements = query_one("select count(*) as no from user_name");
$pages = (int) $maxElements / 20;
$pages += ($maxElements % 20) ? 1 : 0 
$twig_data['pages'] = array('pages' => $pages, 'limit' => 20, 'current' => (int) $start / 20);
for ( $i = 1; $i < $pages; $i++)
    $twig_data['pages'][] = array('text' => $i,'href' => $pages, 'active' => ($i*20 == $start) ? 1 : 0);


$query_user = $pdo->prepare("select * from elo_user order by user_name LIMIT :start, 20");
$query_user->bindValue(':start',$start, PDO::PARAM_INT);
$query_user->execute();

$users = array();

while ( ($res = $query_user->fetch(PDO::FETCH_ASSOC)) !== false ) {
	$users[] = $res;
	// todo: check if this can be done in one query
	$query = $pdo->prepare("select right_id from elo_right_user where user_id=:userid");
	$query->bindValue(':userid', $res['user_id'], PDO::PARAM_INT);
	$query->execute();
	
	$saved_rights = array();
	while ( ($res2 = $query->fetch(PDO::FETCH_ASSOC)) !== false )
		$saved_rights[] = $res2['right_id'];
}
$twig_data['users'] = $users;

$msgs = array();

$twig_data['navElements'] = createAdminMenu();
$twig_data['exampleCode'] = createCode(8);
$breadcrumb[] = array( 'text' => 'Topics', 'href' => 'topic.php');
$breadcrumb[] = array( 'text' => 'Admin Panel', 'href' => '');
$twig_data['breadcrumb'] = $breadcrumb;
$twig_data['msgs'] = $msgs;
echo $twig->render("user_modify.twig", $twig_data);
