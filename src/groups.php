<?php

require('includes/application_top.php');

if ( !in_array('IS_ADMIN', $user_rights ) ) {
	echo $twig->render("no_access.twig", $twig_data);
	exit();
}

$start = isset($_GET['start']) ? (int)filter_input(INPUT_GET, 'start', FILTER_SANITIZE_NUMBER_INT) : 0;

$maxElements = query_one("select count(*) as no from elo_group");
$twig_data['pagination'] = preparePagination($start, $maxElements);

$statement = $pdo->prepare("select * from elo_group order by group_name LIMIT :start, 20");
$statement->bindValue(':start',$start, PDO::PARAM_INT);
$statement->execute();

$groups = array();
while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
	$groups[] = $res;

$twig_data['groups'] = $groups;

$msgs = array();

$twig_data['navElements'] = createAdminMenu();
$twig_data['exampleCode'] = createCode(8);
$twig_data['msgs'] = $msgs;

echo $twig->render("groups.twig", $twig_data);
