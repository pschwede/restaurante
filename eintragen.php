<?php

include_once "smarttools.php";

$db = makeConn();

$username = $_POST["username"];
$passwort = $_POST["passwort"];
$passwort2 = $_POST["passwort2"];

if($passwort != $passwort2 OR $username == "" OR $passwort == "")
    {
    echo "Eingabefehler. Bitte alle Felder korekt ausfüllen. <a href=\"index.php\">Zurück</a>";
    exit;
    }
$passwort = md5($passwort);

$result = $db->query("SELECT id FROM player WHERE name LIKE '$username'");
$menge = $resutl->num_rows;

if($menge == 0)
    {
    $db->query("INSERT INTO player (name, passwd) VALUES ('$username', '$passwort')")
		or die("Fehler beim Speichern des Benutzernames. <a href=\"index.php\">Zurück</a>");
	echo "Benutzername <b>$username</b> wurde erstellt. <meta http-equiv=\"refresh\" content=\"5; URL=index.php\">";
	echo " <a href=\"index.php\">Login</a>";
	$result = $db->query("SELECT id FROM player WHERE name='$username' AND passwd='$passwort' LIMIT 1");
	$playerid = $result->fetch_array(MYSQLI_NUM);
	$plr = getPlayer($playerid[0]);
	addStatement($playerid[0],time(),$plr["konto"],0);
    } else {
    	echo "Benutzername schon vorhanden. <a href=\"index.php\">Zurück</a>";
    }
?> 
