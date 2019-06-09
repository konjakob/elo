<?php

require_once("dbclass.php");
$db = new db;

require("authenticate.class.php");

$auth = new Authenticate;

if(!$auth->validateAuthCookie()) {
	echo "Please log in.";
	exit;
}

require("functions.php");

// check if user is allowed to see the topic
$userid = $auth->getUserId();

if ( isset($_GET['mid']) ) {

	$type = $_GET['type'];
	$mid = intval($_GET['mid']);
	
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
	$filename = $db->query_one("select attachment_filename from elo_attachment where attachment_id='".$aid."'");
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
?>