<?php

require('includes/application_top.php');

if ( !in_array('IS_ADMIN', $user_rights ) ) {
	echo $twig->render("no_access.twig", $twig_data);
	exit();
}

$msgs = array();

$statement = $pdo->prepare("SELECT * FROM `elo_topic` where visible_till<NOW()");
$statement->execute();

$topics = array();

while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false ) {
	
	$topics[] = array(	'topic_id' => $res['topic_id'],
						'topic_title' => $res['topic_title'],
						'visible_from' => $res['visible_from'],
						'visible_till' => $res['visible_till']
					);
	
}
$twig_data['navElements'] = createAdminMenu();
$twig_data['topics'] = $topics;
$twig_data['msgs'] = $msgs;
$twig_data['breadcrumb'] = $breadcrumb;
echo $twig->render("dashboard_old_topics.twig", $twig_data);