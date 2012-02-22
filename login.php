<?php
session_start();

include_once "connector.php";
include_once "smarttools.php";

$db = makeConn();

$username = $_POST["username"];
$passwort = md5($_POST["password"]);

$abfrage = "SELECT name, passwd, id FROM player WHERE name LIKE '$username' LIMIT 1";
$ergebnis = $db->query($abfrage);
$row = $ergebnis->fetch_object();

if($row->passwd == $passwort)
    {
    $_SESSION["name"] = $username;
	$_SESSION["angemeldet"] = true;
	$_SESSION["id"] = $row->id;
	$hostname = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
	$plr = getPlayer($_SESSION["id"]);
	if($plr["lastlogin"]>$plr["lastlogout"])
		$nologout = "?nologout=1&ov=".$plr["id"];
	else $nologout = "?ov=".$plr["id"];
	$time = time();
	$db->query("UPDATE player SET lastlogin=$time, lastdeed=$time WHERE id=".$plr["id"]) or die("error setting last login and last deed signature");
	header('Location: http://'.$hostname.($path == '/' ? '' : $path).'/game.php'.$nologout);
    }
else
    {
    echo "Benutzername und/oder Passwort waren falsch. <a href=\"index.php\">Login</a>";
    }
$db->close();
?>
