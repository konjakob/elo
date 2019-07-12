<?php

require('includes/application_top.php');

if ( !in_array('IS_ADMIN', $user_rights ) ) {
	echo $twig->render("no_access.twig", $twig_data);
	exit();
}

if ( isset($_POST['new_group']) ) {
	$statement = $pdo->prepare("insert into elo_group (group_name) values (:t_group)");
	$statement->bindValue(':t_group', filter_input(INPUT_POST, 't_group'));
	$statement->execute();
	$msgs[] = array('state' => 'ok', 'text' => 'Saved the new group.');
}

$statement = $pdo->prepare("select * from elo_group order by group_name");
$statement->execute();

$groups = array();
while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
	$groups[] = $res;

$twig_data['groups'] = $groups;


$msgs = array();

$twig_data['exampleCode'] = createCode(8);
$breadcrumb[] = array( 'text' => 'Topics', 'href' => 'topic.php');
$breadcrumb[] = array( 'text' => 'Admin Panel', 'href' => '');
$twig_data['breadcrumb'] = $breadcrumb;
$twig_data['msgs'] = $msgs;
echo $twig->render("groups.twig", $twig_data);