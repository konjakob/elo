<?php

function createCode($no) {
	$letters = array("0","1","2","3","4","5","6","7","8","9","q","w","e","r","t","y","u","i","o","p","a","s","d","f","g","h","j","k","l","z","x","c","v","b","n","m");
	$word = "";
	for ( $i = 0; $i < $no; $i++) {
		$word .= $letters[rand(0,(sizeof($letters)-1))];
	}
	return $word;
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
	global $db, $userid, $reply_id, $conf;
	
	if ( strlen($_FILES['t_file']['name'] )) {
		
		$db->query("insert into elo_attachment (attachment_filename, user_id) values ('".$_FILES['t_file']['name']."', '".$userid."')");
		$filename = $db->insert_id().base64_encode($_FILES['t_file']['name']);
		$c = @copy($_FILES['t_file']['tmp_name'],$conf['file_folder'].$filename);
		$db->query("insert into elo_reply_attachment (reply_id, attachment_id) values ('".$reply_id."', '".$db->insert_id()."')");	
		
		// if it is a pdf or picture, create a thumbnail
		if (  preg_match('/[(pdf)|(gif)|(png)|(jpeg)|(jpg)]$/',$_FILES['t_file']['name']) ) {
			exec($conf['convert']." \"".$conf['file_folder']."{".$filename."}[0]\" -colorspace RGB -geometry 200 \"".$conf['file_folder'].$filename.".png\"");
		}
	}
}

function processMusicFiles($musicid, $text) {
	global $db,$conf;
	
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
	//exec($conf['ps2pdf']." ".$psfile." ".$pdffile);
	//exec($conf['convert']." ".$conf['params4png']." ".$psfile." ".$pngfile);
	//exec("pdfcrop ".$pdffile." ".$pdffile."-crop");
	//exec($conf['convert']." ".$conf['params4png']." ".$pdffile."-crop ".$pngfile);
	exec($conf['convert']." \"".$mfolder."/{".$musicid.".pdf}[0]\" -colorspace RGB -trim -geometry 200 \"".$pngfile."\"");
	
	exec($conf['convert']." \"".$mfolder."/{".$musicid.".pdf}[0]\" -colorspace RGB -trim \"".$mfolder."/".$musicid."-big.png\"");
	
	if(file_exists($psfile)) 
		unlink($psfile);	
}

function processMusic() {
	global $db, $reply_id,$conf;

	$db->query("insert into elo_music (music_text) values ('".addslashes($_POST['abc'])."')");	
	$musicid = $db->insert_id();
	$db->query("insert into elo_reply_music (reply_id, music_id) values ('".$reply_id."', '".$musicid."')");	
	
	processMusicFiles($musicid,$_POST['abc']);
}

/**
* create img file
*/
function _createImgFile($abcFile, $fileBase) {
	global $conf;
	$epsFile = $fileBase.'001.eps';
	$imgFile = $fileBase.'.png';

	// create eps file
	passthru(fullpath($this->getConf('abc2ps'))." $abcFile ".$this->getConf('params4img')." -E -O $fileBase. 2>&1");

	// convert eps to png file
	passthru(fullpath($conf['im_convert'])." $epsFile $imgFile");

	if(file_exists($epsFile)) 
		unlink($epsFile);
}

/**
 * create ps file
 */
function _createPsFile($abcFile, $fileBase) {
	$psFile  = $fileBase.'.ps';
	$fmt = $this->getConf('fmt');
	$addFmt = ($fmt && file_exists($fmt)) ? " -F ".fullpath($fmt) : "";
	passthru(fullpath($this->getConf('abc2ps'))." $abcFile $addFmt ".$this->getConf('params4ps')." -O $psFile 2>&1");
}

/**
 * create pdf file
 */
function _createPdfFile($abcFile, $fileBase) {
	$psFile  = $fileBase.'.ps';
	$pdfFile  = $fileBase.'.pdf';
	passthru(fullpath($this->getConf('ps2pdf'))." $psFile $pdfFile");
	if(file_exists($psFile)) 
		unlink($psFile);
}

/**
 * create midi file
 */
function _createMidiFile($abcFile, $fileBase) {
	$midFile = $fileBase.'.mid';
	passthru(fullpath($this->getConf('abc2midi'))." $abcFile -o $midFile");
}

