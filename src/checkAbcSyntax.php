<?

require('includes/application_top.php');

if ( isset($_POST['text'])) {

    // TODO: Add this to a temp folder
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