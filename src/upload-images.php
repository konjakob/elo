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
				$db->query("insert into elo_attachment (attachment_filename, user_id, attachment_time) values ('".addslashes($_FILES[ 'files' ][ 'name' ][ $index ])."', '".$userid."', now())");
				$filename = $db->insert_id().base64_encode($_FILES[ 'files' ][ 'name' ][ $index ]);
				//$c = @copy($_FILES['t_file']['tmp_name'],$conf['file_folder'].$filename);
				move_uploaded_file( $tmpName, $conf['file_folder'].$filename );
				$dataAr = array();
				
				// if it is a pdf or picture, create a thumbnail
				/*
				if (  preg_match('/[(pdf)|(gif)|(png)|(jpeg)|(jpg)]$/',$_FILES['t_file']['name']) ) {
					exec($conf['convert']." \"".$conf['file_folder']."{".$filename."}[0]\" -colorspace RGB -geometry 200 \"".$conf['file_folder'].$filename.".png\"");
				}*/
				$dataAr['fileName'] =  $_FILES[ 'files' ][ 'name' ][ $index ];
				$dataAr['filePath'] =  $conf['file_folder'].$filename;
				$dataAr['fileId'] = $db->insert_id();
				echo json_encode($dataAr);
			}
		}
	} else if ( $_GET['mode'] == 'profile') {
				
		if( !empty( $_FILES[ 'files' ][ 'error' ][0] ) )
		{
			return false;
		}
		
		$tmpName = $_FILES[ 'files' ][ 'tmp_name' ][0];
		$imageFileType = strtolower(pathinfo($_FILES[ 'files' ][ 'name' ][0],PATHINFO_EXTENSION));
		
		// check whether it's not empty, and whether it indeed is an uploaded file
		if( !empty( $tmpName ) && is_uploaded_file( $tmpName ) )
		{	
			$tmpFileName = md5($customer_id).".".addslashes($imageFileType);
			tep_db_query("update elo_user set user_picture='".$tmpFileName."' where user_id='" . $userid . "'");
			
			echo $destName = "images/profile/" . $tmpFileName;
			
			if ( file_exists($destName) ) {
				unlink($destName);
			}
			
			move_uploaded_file( $tmpName, $destName );
		}
	}
}
