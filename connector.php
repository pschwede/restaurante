<?php

include_once $config.php;

function makeConn() {
	@$db = new mysqli("localhost", "yourname", "yourpassword", "yourdatabase");
	if (mysqli_connect_errno())
	  die("Verbindung fehlgeschlagen: ". mysqli_connect_error());
	else return $db;
}


function handleError($text) {
	$db = makeConn();
	$db->query("UPDATE world SET log=log+'<p>".date("G:i:s",time()).$text."</p>' WHERE");
	echo "<script type=\"text/javascript\">alert(\"";
	echo $text;
	echo "\");</script>";
}
?>
