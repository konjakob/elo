<?php

$query = $db->query("select * from elo_config");
$conf = array();
while ( $res = $db->fetch_array($query) )
	$conf[$res['config_name']] = $res['config_value'];
	
function createCode($no) {
	$letters = array("0","1","2","3","4","5","6","7","8","9","q","w","e","r","t","y","u","i","o","p","a","s","d","f","g","h","j","k","l","z","x","c","v","b","n","m");
	$word = "";
	for ( $i = 0; $i < $no; $i++) {
		$word .= $letters[rand(0,(sizeof($letters)-1))];
	}
	return $word;
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
		
	
function createRightHeader() {
	global $user_rights;
?>	

        <div class="tm-header">
            <div uk-sticky="sel-target: .uk-navbar-container; cls-active: uk-navbar-sticky; bottom: #sticky-dropdown">
                <nav class="uk-navbar-container">
                    <div class="uk-container">
                        <div uk-navbar>
                            <div class="uk-navbar-left">

                                <a class="uk-navbar-item uk-logo" href="#">Logo</a>

                                <ul class="uk-navbar-nav">
<?php
	if ( in_array('IS_ADMIN',$user_rights) ) {
		?>
								<li><a href="panel.php">Admin panel</a></li>
								<li><a href="view_user_topics.php">User view</a></li>
		<?php
	}
?>
								
                                    <!--<li class="uk-active"><a href="">Active</a></li>-->
                                </ul>
								
                            </div>
							
							<div class="uk-navbar-right">

                                <ul class="uk-navbar-nav">
									<li><a href="user-settings.php"><?=TOPIC_USER_SETTINGS?></a></li>
                                    <li><a href="logout.php"><?=TOPIC_TEXT_LOGOUT?></a></li>
                                </ul>
								
                            </div>
							
                        </div>
                    </div>
                </nav>
            </div>
        </div>
<?php	
}

function showCreateSheet() {
		?>
        <div id="abcCheck" style="display:none"><pre id="abcCheckText" class="abcCheckText"></pre></div>
        <div id="newMusicSheet" style="display:none"><div style="float:left">
        
        <textarea name="abc" id="abc" cols="80" rows="15"></textarea></div><div style="float:left"><span class="formInfo"><a rel="faq.php?fid=3" href="faq.php?fid=3" class="jt" title="<?=TOPIC_SHEET_INFO?>">?</a></span></div><div style="clear:both"></div><input type="button" value="Check syntax" onclick="checkAbcSyntax('abc')" /><div id="midi"></div>
<div id="warnings"></div>
<div id="music"></div>
<div id="paper0"></div>
</div><div id="addmusicdiv"><input type="button" value="<?=TOPIC_TEXT_ADD_MUSIC?>" onClick="javascript:tgldiv('addmusicdiv');javascript:tgldiv('newMusicSheet');"></div>
        <?
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
	$fmt = $conf['fmt'];
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

function createReply() {
?>

<?	
}

/**
* 
*/
function createTopic() {
	global $res,$conf;

	?>

    <!--
    <div id="topic" class="<?=$oddeventext?>">
		<? echo stripslashes($res['topic_title']); ?><hr /><div id="small">
		<a href=""></a> </div>
		</div>
    -->
    <?
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

function createReplyText() {
	
	
}

?>