<?php
/*
$host = "mysql5.jakob-wankel.de";
$user = "db16731_5";
$pass = "VjQ3abF81P";
$dbname = "db16731_5";
*/


  define('DB_SERVER', 'localhost');
  define('DB_SERVER_USERNAME', 'root');
  define('DB_SERVER_PASSWORD', '');
  define('DB_DATABASE', 'elo');
 
 
 
class db {

	var $ressource = 0;
	var $record   = array();
	var $query_id = 0;
	var $i = 0;

	function __construct() {
		$this->connect();
		$this->select_db();
	}

	function connect() {
		$this->con = mysqli_connect(DB_SERVER,DB_SERVER_USERNAME,DB_SERVER_PASSWORD);
		if(!$this->con) {
     			$this->error_reporter("Verbindung zum Datenbankserver \"$host\" konnte nicht hergestellt werden.", mysqli_errno().": ".mysqli_error());
     			die();
		}
		
	}

	function select_db() {
		$li = mysqli_select_db($this->con, DB_DATABASE);
		mysqli_set_charset($this->con, 'utf8');
		if(!$li) {
     			$this->error_reporter("Die Datenbank \"$dbname\" konnte nicht geöffnet werden.", mysqli_errno($this->con).": ".mysqli_error($this->con));
     			die();
		}
	}

	function query($sql) {
		$this->ressource = mysqli_query($this->con, $sql); 
		if(!$this->ressource) {
     			$this->error_reporter("SQL-Error: \"$sql\".", mysqli_errno($this->con).": ".mysqli_error($this->con));
		}
		$this->i++;
                return $this->ressource;
	}

	function data_seek($query_id,$satz) {
     		$this->query_id=$query_id;
		return mysqli_data_seek($this->query_id,$satz);
	}
  	function fetch_array($query_id) {
      		$this->query_id=$query_id;
    		$this->record = mysqli_fetch_array($this->query_id);
    		return $this->record;
  	}

	function num_rows($query_id) {
      	$this->query_id=$query_id;
		return mysqli_num_rows($this->query_id);
	}
	function affected_rows() {
		return mysqli_affected_rows($this->con);
	}
	function insert_id() {
		return mysqli_insert_id($this->con);
	}
	function free_result($query_id) {
		$this->query_id=$query_id;
		return @mysqli_free_result($this->query_id);
  	}
	function query_one($sql) {
		$query = $this->query($sql);
		$ar = $this->fetch_array($query);
		$this->free_result($query);
		return $ar[0];
	}
	function close() {
		if(!mysqli_close($this->con)) {
			$this->error_reporter("Fehler beim schliessen der Datenbank Verbindung.", mysqli_errno($this->con).": ".mysqli_error($this->con));
		}
	}

	function error_reporter($error, $mysqli_error) {
		echo $error." - ".$mysqli_error; die();
		return;
		global $PHP_SELF, $mail_on_error, $adminmail;
		$fname = "Logfile".date("Ymd").".xml";
		$this->fecheck($fname);
		$message = "<ERROR>
  <DateTime>".date("Y-m-d H:i:s")."</DateTime> 
  <MessageType>ERROR</MessageType> 
  <CallerName>".UBB_Uber(getenv("REQUEST_URI"))."</CallerName> 
  <MessageText>".UBB_Uber($error)."</MessageText>
  <ErrorText>".UBB_Uber($mysqli_error)."</ErrorText>
  <IP>".UBB_Uber(getenv("REMOTE_ADDR"))."</IP>
</ERROR>
</Logfile>";
		$fp = fopen("./logfiles/$fname", "r+");
		fseek($fp, (-10) ,SEEK_END);
		fputs($fp, $message);
		fclose($fp);
		if($mail_on_error) {
			$message = "In der Forums Datenbank ist ein Problem aufgetreten.\nReport:\n";
			$message .= "Datum: ".date("Y-m-d H:i:s");
			$message .= "\nScript: ".getenv("REQUEST_URI");
			$message .= "\nError: ".$error;
			$message .= "\nDB-Error: ".$mysqli_error;
			$message .= "\nIP: ".getenv("REMOTE_ADDR");
			$message .= "\n\nDer Error wurde geloggt.";
			@mail("$adminmail", "Datenbank Problem im Forum", $message, "From: forum@localhost.de\nReply-To: forum@localhost.de\nX-Mailer: PHP/" . phpversion());
		}
	}

	function fecheck($fname) {
		if(!file_exists("logfiles/".$fname)) {
			$message = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>
<?xml-stylesheet href=\"style.xsl\" type=\"text/xsl\" ?> 
<!DOCTYPE Logfile SYSTEM \"def.dtd\">
 <Logfile>
  <LogName>Logfile vom ".date("Y-m-d")."</LogName>
 </Logfile>";
			$fp = fopen("./logfiles/$fname", "w+");
			fputs($fp, $message);
			fclose($fp);
		}
	}
}
