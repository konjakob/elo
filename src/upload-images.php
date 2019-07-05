<?php

require('includes/application_top.php');

if (isset($_GET['mode'])) {
	
	if($_GET['mode'] == 'review') {

		foreach( $_FILES[ 'files' ][ 'tmp_name' ] as $index => $tmpName )
		{
			if( !empty( $_FILES[ 'files' ][ 'error' ][ $index ] ) )
			{
				return false;
			}
			
			$imageFileType = strtolower(pathinfo($_FILES[ 'files' ][ 'name' ][ $index ],PATHINFO_EXTENSION));
			
			// check whether it's not empty, and whether it indeed is an uploaded file
			if( !empty( $tmpName ) && is_uploaded_file( $tmpName ) )
			{				
				$statement = $pdo->prepare("insert into elo_attachment (attachment_filename, user_id, attachment_time) values (:file, :userid, now())");
				$statement->bindValue(':userid', $userid, PDO::PARAM_INT);
				$statement->bindValue(':file', $_FILES[ 'files' ][ 'name' ][ $index ]);
				$statement->execute();
		
				$filename = $pdo->lastInsertId().base64_encode($_FILES[ 'files' ][ 'name' ][ $index ]);
				//$c = @copy($_FILES['t_file']['tmp_name'],$conf['file_folder'].$filename);
				move_uploaded_file( $tmpName, $conf['file_folder'].$filename );
				$dataAr = array();
				
				// if it is a pdf or picture, create a thumbnail
				if (  preg_match('/[(pdf)|(gif)|(png)|(jpeg)|(jpg)]$/',$_FILES['files']['name'][ $index ]) ) {
					exec($conf['convert']." \"".$conf['file_folder']."{".$filename."}[0]\" -colorspace RGB -geometry 200 \"".$conf['file_folder'].$filename.".png\"");
					$dataAr['preview'] = $conf['file_folder'].$filename.".png";
				}
				$dataAr['fileName'] =  $_FILES[ 'files' ][ 'name' ][ $index ];
				$dataAr['filePath'] =  $conf['file_folder'].$filename;
				$dataAr['fileId'] = $pdo->lastInsertId();
				echo json_encode($dataAr);
			}
		}
	} else if ( $_GET['mode'] == 'profile') {
				
		if( !empty( $_FILES[ 'files' ][ 'error' ][0] ) ) {
			return false;
		}
		
		$tmpName = $_FILES[ 'files' ][ 'tmp_name' ][0];
		$imageFileType = strtolower(pathinfo($_FILES[ 'files' ][ 'name' ][0],PATHINFO_EXTENSION));
		
		// check whether it's not empty, and whether it indeed is an uploaded file
		if( !empty( $tmpName ) && is_uploaded_file( $tmpName ) )
		{	
			$tmpFileName = md5($userid).".".addslashes($imageFileType);
			
			$statement = $pdo->prepare("update elo_user set user_picture=:tmpFileName where user_id=:userid");
			$statement->bindValue(':userid', $userid, PDO::PARAM_INT);
			$statement->bindValue(':tmpFileName', $tmpFileName);
			$statement->execute();
			
			$destName = "images/profile/" . $tmpFileName;
			
			if ( file_exists($destName) ) {
				unlink($destName);
			}
			
			move_uploaded_file( $tmpName, $destName );
			
			$dataAr = array();
			$dataAr['state'] = 'ok';
			$dataAr['filePath'] =  $destName;
            
            list($width, $height) = getimagesize($destName);
            $dataAr['width'] = $width;
            $dataAr['height'] = $height;
            
			echo json_encode($dataAr);
		}
	}
}
