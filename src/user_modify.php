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
while (($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
    $groups[] = $res;
}

$twig_data['groups'] = $groups;

/* Get the available languages */
$statement = $pdo->prepare("select * from elo_lang");
$statement->execute();

$langs = array();
while (($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
    $langs[] = $res;
}
$twig_data['langs'] = $langs;	

/* Get the rights */
$query_right = $pdo->prepare("select * from elo_right order by right_name");
$query_right->execute();

$rights = array();
while (($res = $query_right->fetch(PDO::FETCH_ASSOC)) !== false) {
    $rights[] = $res;
}

$twig_data['rights'] = $rights;
$twig_data['pages'] = array();
/* Get the users */
$start = isset($_GET['start']) ? (int)filter_input(INPUT_GET, 'start', FILTER_SANITIZE_NUMBER_INT) : 0;

$maxElements = query_one("select count(*) as no from elo_user");
$twig_data['pagination'] = preparePagination($start, $maxElements);

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
$breadcrumb[] = array( 'text' => _('Topics'), 'href' => 'topic.php');
$breadcrumb[] = array( 'text' => _('Admin Panel'), 'href' => '');
$twig_data['breadcrumb'] = $breadcrumb;
$twig_data['msgs'] = $msgs;
echo $twig->render("user_modify.twig", $twig_data);
