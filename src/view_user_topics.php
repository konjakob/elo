<?php

require('includes/application_top.php');
	
if ( !in_array('IS_ADMIN', $user_rights ) ) {
    echo $twig->render("no-access.twig", $twig_data);
    exit();    
}
	
$allUser = array();

$start = isset($_GET['start']) ? (int)filter_input(INPUT_GET, 'start', FILTER_SANITIZE_NUMBER_INT) : 0;

$statement = $pdo->prepare("select user_id, user_name from elo_user order by user_name LIMIT :start, 20");
$statement->bindValue(':start',$start, PDO::PARAM_INT);
$statement->execute();

while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false ) {
    $allUser[] = $res;
}   

$twig_data['allUser'] = $allUser;
$twig_data['navElements'] = createAdminMenu();

echo $twig->render("loaduser.twig", $twig_data);
    
 
