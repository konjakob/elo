<?php

require('includes/application_top.php');

if ( !in_array('IS_ADMIN', $user_rights ) ) {
	echo $twig->render("no_access.twig", $twig_data);
	exit();
}


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

$query_user = $pdo->prepare("select * from elo_user order by user_name");
$query_user->execute();

$query_right = $pdo->prepare("select * from elo_right order by right_name");
$query_right->execute();

if ( in_array('CREATE_NEW_RIGHT', $user_rights) && isset($_POST['new_right']) && isset($_POST['t_name']) && isset($_POST['t_key']) ) {
	$query_user = $pdo->prepare("insert into elo_right (right_name, right_key) values (:t_name, :t_key)");
	$query->bindValue(':t_name', filter_input(INPUT_POST, 't_name'));
	$query->bindValue(':userid', filter_input(INPUT_POST, 't_key'));
	$query_user->execute();
	$twig_data['saved_new_right'] = 1;
}

$rights = array();
while ( ($res = $query_right->fetch(PDO::FETCH_ASSOC)) !== false )
	$rights[] = $res;

$twig_data['rights'] = $rights;
	
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

$twig_data['exampleCode'] = createCode(8);
$breadcrumb[] = array( 'text' => 'Topics', 'href' => 'topic.php');
$breadcrumb[] = array( 'text' => 'Admin Panel', 'href' => '');
$twig_data['breadcrumb'] = $breadcrumb;
$twig_data['msgs'] = $msgs;
echo $twig->render("user_modify.twig", $twig_data);