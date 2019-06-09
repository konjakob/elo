<?

require_once("dbclass.php");
$db = new db;

require("authenticate.class.php");

$auth = new Authenticate;

if(!$auth->validateAuthCookie())
	header("Location: login.php?ref=".base64_encode($_SERVER['PHP_SELF']."?".$_SERVER["QUERY_STRING"]));

require("functions.php");


if ( isset($_POST['text'])) {

	$tmpfile = 	"tmp-".createCode(10).".abc";
	
	file_put_contents($tmpfile,$_POST['text']);
	
 	exec($conf['abc2abc']." ".$tmpfile, $out);
	
	unlink($tmpfile);
	
	foreach ( $out as $o ) {
		if ( preg_match('/^%E/',$o) ) {
			echo "<font color=red>".$o."</font>";
		} else if ( preg_match('/^[XTMLRK]/',$o) ) {
			echo "<font color=grey>".$o."</font>";
		} else {
			echo $o;
		}
		echo "\n";
	}

	
}


?>