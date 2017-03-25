<?php
     session_start();
	 
	 include_once "connector.php";
	 
	 $db = makeConn();
	 $db->query("UPDATE player SET lastlogout=".time()." WHERE id=".$_SESSION["id"]) or die("error setting lastlogout");
	 $db->Close();
	 
     session_destroy();

     $hostname = $_SERVER['HTTP_HOST'];
     $path = dirname($_SERVER['PHP_SELF']);
	
     header('Location: http://'.$hostname.($path == '/' ? '' : $path).'/index.php');
?>
