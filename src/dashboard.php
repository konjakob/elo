<?php

require('includes/application_top.php');

$msgs = array();

if ( !in_array('IS_ADMIN', $user_rights ) ) {
    echo $twig->render("no-access.twig", $twig_data);
    exit();    
}

// Data info the top of the page
$data = array();
// Number of users total
$data['users']['icon'] = 'users';
$data['users']['title'] = 'Users';
$statement = $pdo->prepare("SELECT count(user_id) as no FROM `elo_user` ");
$statement->execute();
$res = $statement->fetch(PDO::FETCH_ASSOC);
$data['users']['total'] = $res['no'];

// Number of users before last week
$statement = $pdo->prepare("SELECT count(user_id) as no FROM `elo_user` where user_registration< DATE(NOW() - INTERVAL 7 DAY) ");
$statement->execute();
$res = $statement->fetch(PDO::FETCH_ASSOC);
$data['users']['change'] = $data['users']['total'] - $res['no'];

// Number of posts
$data['posts']['icon'] = 'social';
$data['posts']['title'] = 'Posts';
$statement = $pdo->prepare("SELECT count(reply_id) as no FROM `elo_reply`");
$statement->execute();
$res = $statement->fetch(PDO::FETCH_ASSOC);
$data['posts']['total'] = $res['no'];

// Number of posts before last week
$statement = $pdo->prepare("SELECT count(reply_id) as no FROM `elo_reply` where reply_date< UNIX_TIMESTAMP(DATE(NOW() - INTERVAL 7 DAY)) ");
$statement->execute();
$res = $statement->fetch(PDO::FETCH_ASSOC);
$data['posts']['change'] = $data['posts']['total'] - $res['no'];

$charts = array();

/* Replies by group */
$chartLabels = array();
$chartData = array();
foreach ( $pdo->query("SELECT Count(elo_reply.reply_id) AS no, elo_group.group_name
FROM ((elo_reply INNER JOIN elo_topic ON elo_reply.topic_id = elo_topic.topic_id) INNER JOIN elo_topic_group ON elo_topic.topic_id = elo_topic_group.topic_id) INNER JOIN elo_group ON elo_topic_group.group_id = elo_group.group_id
GROUP BY elo_group.group_id order by no desc limit 5") as $res) {
	$chartLabels[] = '"'.$res['group_name'].'"';
	$chartData[] = $res['no'];
}

$charts[] = array(	'labels' => implode(", ", $chartLabels),
					'label' => 'Groups',
					'data' => implode(", ", $chartData),
					'text' => 'Most active groups (top 5)',
					'id' => 1,
					'style' => 'bar'
				);

/* Attachments per week */
$chartLabels = array();
$chartData = array();			
foreach ( $pdo->query("SELECT count(*) as no, str_to_date(concat(yearweek(attachment_time), ' monday'), '%X%V %W') as `date` FROM `elo_attachment` group by yearweek(attachment_time)") as $res) {
	$chartLabels[] = '"'.$res['date'].'"';
	$chartData[] = $res['no'];
}
$charts[] = array(	'labels' => implode(", ", $chartLabels),
					'label' => 'Attachments',
					'datasets' => array(array('label' => 'Attachments', 'data' => implode(", ", $chartData))),
					'text' => 'Files per week',
					'id' => 2,
					'style' => 'line'
				);

				// SELECT count(user_id) as no, date(user_login) as day, user_id FROM `elo_user_login` group by day,user_id
				
$twig_data['charts'] = $charts;

$twig_data['data'] = $data;
$twig_data['msgs'] = $msgs;
$twig_data['breadcrumb'] = $breadcrumb;
echo $twig->render("dashboard.twig", $twig_data);
