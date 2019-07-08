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


$twig_data['data'] = $data;
$twig_data['msgs'] = $msgs;
$twig_data['breadcrumb'] = $breadcrumb;
echo $twig->render("dashboard.twig", $twig_data);
