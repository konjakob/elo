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
	
	$topic_id = (int)$db->query_one("SELECT r.topic_id FROM elo_reply_music as a, elo_reply as r, elo_topic_user as t where a.rm_id='".$mid."' and a.reply_id=r.reply_id and r.topic_id=t.topic_id and t.user_id='".$userid."'");

	if ( !$topic_id ) {
		echo $twig->render("no_access.twig", $twig_data);
		exit();
	}
	
	// check if allowed to download this file
    if ( !in_array('IS_ADMIN',$user_rights) )
    {
        if ( (int)$db->query_one("SELECT count(*) FROM elo_reply_music as a, elo_reply as r, elo_topic_group as g, elo_group_user as gu where a.rm_id='".$aid."' and a.reply_id=r.reply_id and r.topic_id=g.topic_id and gu.user_id='".$userid."' and g.group_id=gu.group_id") < 1 )
        {
            echo $twig->render("no_access.twig", $twig_data);
            exit();
        }
    }
	 
	$folder = $conf['file_folder']."m-".$mid."/";

	if ( !file_exists($folder) )
		header("Location: topic.php?id=".$topic_id);
		
	if ( $type == 'midi') {
		$file = $folder.$mid.".mid";
		if ( file_exists($file) ) {
			header("Location: ".$file);
			exit();
		} else {
			echo $twig->render("404.twig", $twig_data);
            exit();
		}
	} else if ($type == 'pdf') {
		$file = $folder.$mid.".pdf";
		if ( file_exists($file) ) {
			header("Location: ".$file);
			exit();
		} else {
			echo $twig->render("404.twig", $twig_data);
            exit();
		}
	} else if ($type == 'abc') {
		$file = $folder.$mid.".abc";
		if ( file_exists($file) ) {
			header("Location: ".$file);
			exit();
		} else {
			echo $twig->render("404.twig", $twig_data);
            exit();
		}	
	}
} else if  ( isset($_GET['aid']) ) {
	$aid = intval($_GET['aid']);	
	$filename = $db->query_one("select attachment_filename from elo_attachment where attachment_id='".$aid."'");
    
    $topic_id = $db->query_one("SELECT r.topic_id FROM elo_reply_attachment as a, elo_reply as r, elo_topic_user as t where a.ra_id='".$aid."' and a.reply_id=r.reply_id and r.topic_id=t.topic_id and t.user_id='".$userid."'");

    // check if allowed to download this file
    if ( !$topic_id && !in_array('IS_ADMIN',$user_rights) )
    {
        if ( (int)$db->query_one("SELECT count(*) FROM elo_reply_attachment as a, elo_reply as r, elo_topic_group as g, elo_group_user as gu where a.ra_id='".$aid."' and a.reply_id=r.reply_id and r.topic_id=g.topic_id and gu.user_id='".$userid."' and g.group_id=gu.group_id") < 1 )
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
