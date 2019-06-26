<?php

require('includes/application_top.php');

if ( isset($_GET['mid']) ) {

    if ( !isset($_GET['type'])) {
            $msgs[] = array('state' => 'nok', 'text' => "No type specified.");
            echo $twig->render("missing-information.twig", $twig_data);
            exit();
    }
    
	$type = $_GET['type'];
	$mid = (int)$_GET['mid'];

    // TODO: check if allowed to see the topic
    if ( !$db->query_one("select tu_id from elo_topic_user where topic_id='".$topicid."' and user_id='".$userid."' limit 1") && !in_array('IS_ADMIN',$user_rights) )
    {
        if ( !$db->query_one("select tg_id from elo_topic_group as tg, elo_group_user AS gu where tg.topic_id='".$topicid."' and tg.group_id=gu.group_id and gu.user_id='".$userid."' limit 1") )
        {
            echo $twig->render("no_access.twig", $twig_data);
            exit();
        }
    }
	
    // check if is allowed to download file
    
	$folder = $conf['file_folder']."m-".$mid."/";

	if ( !file_exists($folder) )
		header("Location: topic.php");
		
	if ( $type == 'midi') {
		$file = $folder.$mid.".mid";
		if ( file_exists($file) ) {
			header("Location: ".$file);
			exit();
		}
	} else if ($type == 'pdf') {
		$file = $folder.$mid.".pdf";
		if ( file_exists($file) ) {
			header("Location: ".$file);
			exit();
		}	
	} else if ($type == 'abc') {
		$file = $folder.$mid.".abc";
		if ( file_exists($file) ) {
			header("Location: ".$file);
			exit();
		}	
	}
} else if  ( isset($_GET['aid']) ) {
	$aid = intval($_GET['aid']);	
	$filename = $db->query("select attachment_filename from elo_attachment where attachment_id='".$aid."'");
    
    // TODO: check if allowed to see the topic
    if ( !$db->query_one("select tu_id from elo_topic_user where topic_id='".$topicid."' and user_id='".$userid."' limit 1") && !in_array('IS_ADMIN',$user_rights) )
    {
        if ( !$db->query_one("select tg_id from elo_topic_group as tg, elo_group_user AS gu where tg.topic_id='".$topicid."' and tg.group_id=gu.group_id and gu.user_id='".$userid."' limit 1") )
        {
            echo $twig->render("no_access.twig", $twig_data);
            exit();
        }
    }
    
	$path = $conf['file_folder'].$aid.base64_encode($filename);
	if ( file_exists($path) ) {
		$fp = fopen($path,"r");

		header("Content-Disposition: atachment; filename=\"".$filename."\"");
		header("Content-Type: application/zip");
		header("Content-Length: ".filesize($path));
		header("Pragma: no-cache");
		header("Expires: 0");
	
		fpassthru($fp);

		exit();
	}	
} else {

	header("Location: topic.php");

}
