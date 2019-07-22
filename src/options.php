<?php

require('includes/application_top.php');

if ( !in_array('IS_ADMIN', $user_rights ) ) {
    echo $twig->render("no-access.twig", $twig_data);
    exit();    
}

$action = isset($_POST["action"]) ? $_POST["action"] : "options";

$msgs = array();

if($action == 'dosave') {

	while (list($key,$val)=each($_POST['setting'])) {
		$statement = $pdo->prepare("UPDATE elo_config SET value=:val WHERE settingid=:key");
		$statement->bindValue(':key', $key);
		$statement->bindValue(':val', $val);
		$statement->execute();
	}
	$msgs[] = array('state' => 'ok', 'text' => _('The settings were saved.'));
}

$settinggroupid = (int)filter_input(INPUT_GET, 'settinggroupid');

if ($settinggroupid) {
	$sqlwhere=" WHERE settinggroupid=:settinggroupid ";
} else {
	$sqlwhere=" WHERE displayorder<>0 ";
}

$statement = $pdo->prepare("SELECT * FROM elo_settinggroup ".$sqlwhere." ORDER BY displayorder");
if ($settinggroupid)
	$statement->bindValue(':settinggroupid', $settinggroupid, PDO::PARAM_INT);
$statement->execute();

$options = array();
while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false ) {
	$options[] = $res; 
}

$twig_data['settinggroups'] = $options;

$settings = array();
$statement = $pdo->prepare("SELECT * FROM elo_config ".$sqlwhere." ORDER BY displayorder");
if ($settinggroupid != "")
	$statement->bindValue(':settinggroupid', $settinggroupid, PDO::PARAM_INT);	
$statement->execute();
while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false )
	$settings[] = $res;

$twig_data['settings'] = $settings;


$breadcrumb[] = array( 'text' => 'Topics', 'href' => 'topic.php');
$breadcrumb[] = array( 'text' => 'Admin Panel', 'href' => 'panel.php');
$breadcrumb[] = array( 'text' => 'Settings', 'href' => '');

$twig_data['msgs'] = $msgs;
$twig_data['breadcrumb'] = $breadcrumb;
echo $twig->render("options.twig", $twig_data);
