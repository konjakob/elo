<?php

require_once("dbclass.php");
$db = new db;

$userid = 1;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<link href="style.css" rel="stylesheet" type="text/css" />

</head>

<body>
<table>
<?

$query = $db->query("select * from elo_attachment");

while ( $res = $db->fetch_array($query) ) {

	if ( file_exists("files/".$res['attachment_id'].base64_encode($res['attachment_filename'])) ) {
		$filesize_s = "";
		$filesize = filesize("files/".$res['attachment_id'].base64_encode($res['attachment_filename']));
		if ( $filesize < 1024 ) {
			$filesize_s = $filesize." Byte";
		} else if ( $filesize < 1024*1024 ) {
			$filesize_s = round($filesize/1024)." kB";
		} else if ( $filesize < 1024*1024*1024 ) {
			$filesize_s = round($filesize/(1024*1024))." MB";
		} else {
			$filesize_s = $filesize." Byte";
		}
		$img_s = "";
		if ( file_exists("files/".$res['attachment_id'].base64_encode($res['attachment_filename']).".gif")) {
			$img_s = "<img src='files/".$res['attachment_id'].base64_encode($res['attachment_filename']).".gif'>";
		}
		echo "<tr><td>".$res['attachment_filename']."</td><td>".$filesize_s."</td><td>Delete</td><td>".$img_s."</td></tr>";	
	} else {
		echo "<tr><td colospan=4>".$res['attachment_filename']." does not exsit</td></tr>";
	}
	
	
}
?></table>