<?php

function createCode($no) {
	$letters = array("0","1","2","3","4","5","6","7","8","9","q","w","e","r","t","y","u","i","o","p","a","s","d","f","g","h","j","k","l","z","x","c","v","b","n","m");
	$word = "";
	for ( $i = 0; $i < $no; $i++) {
		$word .= $letters[rand(0,(sizeof($letters)-1))];
	}
	return $word;
}

function query_one($sql) {
    global $pdo;
    $row = $pdo->query( $sql )->fetch();
    return $row[0];
}

function createAdminMenu() {
    
    $currentPage = basename($_SERVER['PHP_SELF']);
    
    $elements = array();
    $elements[] = array('text' => 'Create topic', 'href' => 'panel.php', 'icon' => 'comments', 'active' => ($currentPage == 'panel.php') ? 1 : 0);
    $elements[] = array('text' => 'User view', 'href' => 'view_user_topics.php', 'icon' => 'users', 'active' => ($currentPage == 'view_user_topics.php') ? 1 : 0);
    $elements[] = array('text' => 'Attachments', 'href' => 'attachments.php', 'icon' => 'album', 'active' => ($currentPage == 'attachments.php') ? 1 : 0);
    $elements[] = array('text' => 'Groups', 'href' => 'groups.php', 'icon' => 'users', 'active' => ($currentPage == 'groups.php') ? 1 : 0);
    
    $tempEl = array();
    $tempEl[] = array('text' => 'Create', 'href' => 'user_create.php', 'active' => ($currentPage == 'user_create.php') ? 1 : 0);
    $tempEl[] = array('text' => 'Modify', 'href' => 'user_modify.php', 'active' => ($currentPage == 'user_modify.php') ? 1 : 0);
    $elements[] = array('text' => 'Users', 'icon' => 'album', 'group' => 1, 'subElements' => $tempEl);                  
    
    return $elements;
}

function toastFeedback($state, $text, $title) {
	return array('state' => $state, 'text' => $text, 'title' => $title);
}

function prepareAttachments($attachments) {
	global $conf;
	$attachments_reply = array();
	foreach ( $attachments as $a) {
		$file = $conf['file_folder'].$a['attachment_id'].base64_encode($a['attachment_filename']);
		if ( file_exists( $file ) ) {
			$att = array();
			if ( file_exists($file.".png") ) {
				$img_data = getimagesize($file.".png");
				$att['img'] = $file.'.png';
				$att['img_data'] = $img_data[3];
			}
			$filesize_s = "";
			$filesize = filesize($file);
			if ( $filesize < 1024 ) {
				$filesize_s = $filesize." Byte";
			} else if ( $filesize < 1024*1024 ) {
				$filesize_s = round($filesize/1024)." kB";
			} else if ( $filesize < 1024*1024*1024 ) {
				$filesize_s = round($filesize/(1024*1024))." MB";
			} else {
				$filesize_s = $filesize." Byte";
			}
			$att['attachment_filename'] = $a['attachment_filename'];
			$att['attachment_id'] = $a['attachment_id'];
			$att['filesize'] = $filesize_s;
			
			$attachments_reply[] = $att;
		}
	}	
	return $attachments_reply;
}

function prepareEmailAndSend($email_text, $email_ad, $email_name,$subject="", $alt_text="") {
		global $conf;
	
		date_default_timezone_set('Etc/UTC');
		require_once 'class.phpmailer.php';
		
		$mail = new PHPMailer;
		
		$mail->SMTPDebug  = 0;
		$mail->IsSMTP();                                      // Set mailer to use SMTP
		$mail->Host = $conf['smtp_server'];  // Specify main and backup server
		$mail->Port = $conf['smtp_port'];
		$mail->SMTPAuth = true;                               // Enable SMTP authentication
		$mail->CharSet = 'UTF-8';
		$mail->Username = $conf['smtp_username'];                            // SMTP username
		$mail->Password = $conf['smtp_password'];                           // SMTP password
		$mail->SMTPSecure = '';                            // Enable encryption, 'ssl' also accepted tls
		
		$mail->SetFrom($conf['from_email'], $conf['from_name']);
		$mail->FromName = $conf['from_name'];
		$mail->WordWrap = 80;                                 // Set word wrap to 50 characters
		$mail->IsHTML(true); 
		
		$mail->AddAddress($email_ad, $email_name);  // Add a recipient
		
		$mail->Subject = $subject;
		$mail->Body    = $email_text; // html text
		$mail->AltBody = $alt_text;
		
		$error = "";
		$success = "";
		
		if(!$mail->Send()) {
		   $error = 'Message could not be sent.';
		   $error .= 'Mailer Error: ' . $mail->ErrorInfo;
		} else {
			$success = "<br>Email successfully send.";
		}
		return array($success, $error);
		
}
		

function processAttachment() {
	global $pdo, $userid, $reply_id, $conf;
	
	if ( strlen($_FILES['t_file']['name'] )) {
		
		$statement = $pdo->prepare("insert into elo_attachment (attachment_filename, user_id) values (:attachment_filename, :userid)");
        $statement->bindValue(':userid', $userid, PDO::PARAM_INT);
		$statement->bindValue(':attachment_filename', $_FILES['t_file']['name']);
        $statement->execute();
		
		$attachment_id = $pdo->lastInsertId();
		$filename = $attachment_id.base64_encode($_FILES['t_file']['name']);
		$c = @copy($_FILES['t_file']['tmp_name'],$conf['file_folder'].$filename);
		
		$statement = $pdo->prepare("insert into elo_reply_attachment (reply_id, attachment_id) values (:reply_id, :attachment_id)");
        $statement->bindValue(':reply_id', $reply_id, PDO::PARAM_INT);
		$statement->bindValue(':attachment_id', $attachment_id, PDO::PARAM_INT);
        $statement->execute();
		
		// if it is a pdf or picture, create a thumbnail
		if (  preg_match('/[(pdf)|(gif)|(png)|(jpeg)|(jpg)]$/',$_FILES['t_file']['name']) ) {
			exec($conf['convert']." \"".$conf['file_folder']."{".$filename."}[0]\" -colorspace RGB -geometry 200 \"".$conf['file_folder'].$filename.".png\"");
		}
	}
}

function processMusicFiles($musicid, $text) {
	global $conf;
	
	// save to an abc file
	$mfolder = $conf['file_folder']."m-".$musicid;
	$abcfile = $mfolder."/".$musicid.".abc";
	mkdir($mfolder);
	file_put_contents($abcfile,$text);
	
	// create midi file
	exec($conf['abc2midi']." ".$abcfile." -o ".$mfolder."/".$musicid.".mid");
	
	// create the pdf
	// check if there is a format file given
	$fmt = array_key_exists('fmt', $conf) ? $conf['fmt'] : "";
	$psfile = $mfolder."/".$musicid.".ps";
	$pdffile = $mfolder."/".$musicid.".pdf";
	$pngfile = $mfolder."/".$musicid.".png";
	$addFmt = ($fmt && file_exists($fmt)) ? " -F ".$fmt : "";
	
	exec($conf['abc2ps']." ".$abcfile." ".$addFmt." ".$conf['params4ps']." -O ".$psfile);//." 2>&1");
	exec($conf['ps2pdf']." ".$psfile." ".$pdffile);
	exec($conf['convert']." \"".$mfolder."/{".$musicid.".pdf}[0]\" -colorspace RGB -trim -geometry 200 \"".$pngfile."\"");	
	exec($conf['convert']." \"".$mfolder."/{".$musicid.".pdf}[0]\" -colorspace RGB -trim \"".$mfolder."/".$musicid."-big.png\"");
	
	if(file_exists($psfile)) 
		unlink($psfile);	
}

function processMusic() {
	global $pdo, $reply_id,$conf;

	$statement = $pdo->prepare("insert into elo_music (music_text) values (:abc)");
	$statement->bindValue(':abc', $_POST['abc']);
	$statement->execute();
		
	$musicid = $pdo->lastInsertId();
	
	$statement = $pdo->prepare("insert into elo_reply_music (reply_id, music_id) values (:reply_id, :musicid)");
	$statement->bindValue(':reply_id', $reply_id, PDO::PARAM_INT);
	$statement->bindValue(':musicid', $musicid, PDO::PARAM_INT);
	$statement->execute();
	
	processMusicFiles($musicid,$_POST['abc']);
}
