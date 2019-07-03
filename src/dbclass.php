<?php

 include('../settings.php');
 
 try {
	 $pdo = new PDO(
				sprintf('mysql:host=%s;dbname=%s;port=%s;charset=%s',
					DB_SERVER,
					DB_DATABASE,
					DB_PORT,
					DB_CHARSET
				),
				DB_SERVER_USERNAME,
				DB_SERVER_PASSWORD
			);
 } catch (PDOException $e) {
	echo "Database connection failed.";
	exit();
 }
