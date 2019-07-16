<?php

require('includes/application_top.php');

if ( !in_array('IS_ADMIN', $user_rights ) ) {
	echo $twig->render("no_access.twig", $twig_data);
	exit();
}

$msgs = array();

$statement = $pdo->prepare("select * from elo_attachment");
$statement->execute();

$files = array();

while ( ($res = $statement->fetch(PDO::FETCH_ASSOC)) !== false ) {

	$filesize_s = "";
	$img_s = "";
	$exists = 0;
	if ( file_exists("files/".$res['attachment_id'].base64_encode($res['attachment_filename'])) ) {	
		$exists = 1;
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
		
		if ( file_exists("files/".$res['attachment_id'].base64_encode($res['attachment_filename']).".png")) {
			$img_s = "files/".$res['attachment_id'].base64_encode($res['attachment_filename']).".png";
		}
		//echo "<tr><td>".$res['attachment_filename']."</td><td>".$filesize_s."</td><td>Delete</td><td>".$img_s."</td></tr>";	
	}
	
	$files[] = array(	'filename' => $res['attachment_filename'],
						'filesize' => $filesize_s,
						'exists' => $exists,
						'img_file' => $img_s,
						'fileId' => $res['attachment_id']
					);
	
}
$twig_data['navElements'] = createAdminMenu();
$twig_data['files'] = $files;
$twig_data['msgs'] = $msgs;
$twig_data['breadcrumb'] = $breadcrumb;
echo $twig->render("dashboard_attachments.twig", $twig_data);