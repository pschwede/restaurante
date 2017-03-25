<?php

$FORUM_URL = "https://github.com/pschwede/restaurante/issues";

session_start();

if(!isset($_SESSION["id"]))
{
   echo "Bitte erst <a href=\"login.html\">einloggen</a>";
   exit;
}

include_once "connector.php";
include_once "smarttools.php";

$db = makeConn();

$kette = "";
$logo = "";


if($_POST["kette"] != NULL) {
	if (!$db->query("UPDATE player SET kette='".htmlentities($_POST['kette'])."' WHERE id='".$_SESSION['id']."'"))
		die("Name der Restaurantkette konnte nicht übernommen werden!<br>");
	else {
		$plr = getPlayer($_SESSION['id']);
		echo "Neuer Name der Restaurantkette: ".$plr["kette"]."<br>";
	}
	if (!$db->query("UPDATE player SET logo='".htmlentities($_POST['logo'])."' WHERE id='".$_SESSION['id']."'"))
		die("Logo konnte nicht übernommen werden!<br>");
	else {
		$plr = getPlayer($_SESSION['id']);
		echo "Neues logo: <img src=\"".$plr["logo"]."\" alt=\"Kann nicht angezeigt werden\"><br>";
	}
	if (!$db->query("UPDATE player SET email='".$_POST['email']."' WHERE id='".$_SESSION['id']."'"))
		die("E-Mail-Adresse konnte nicht übernommen werden!<br>");
	else {
		$plr = getPlayer($_SESSION['id']);
		echo "Neue E-Mail-Adresse: ".$plr["email"]."<br>";
	}
}

$resultat = $db->query('SELECT * FROM player WHERE id="'.$_SESSION['id'].'" LIMIT 1')
	or die("Es konnten keine Daten aus der Datenbank ausgelesen werden");

$player = $resultat->fetch_array(MYSQLI_ASSOC);
// Speicher freigeben
$resultat->close();

?>


<html>
	<head>
		<title>Einstellungen - Restaurante</title>
	</head>
	<link rel="stylesheet" type="text/css" href="style.css">
	<body>
		<table class="content">
			<tr class="top">
				<td>
					<h1>Restaurante</h1>
				</td>
			</tr>
			<tr>
				<td class="right">
					<h2>Einstellungen von <?php echo $player["name"]; ?></h2>

					<form action="<?php $PHP_SELF; ?>" method="post">
						<p>Name der Restaurant-Kette (max 50 Zeichen): <input type="text" size="30" maxlength="50" value="<?php  echo $player["kette"]; ?>" name="kette"></p>
						<p>URL des Logos: <input type="text" size="30" maxlength="255" name="logo" value="<?php echo $player["logo"]; ?>"></p>
						<p>E-mail-Adresse: <input type="text" size="30" maxlength="255" name="email" value="<?php echo $player["email"]; ?>"></p>
						<input style="float:right; margin:5px" type="submit" value="Übernehmen">
						<input style="float:left; margin:5px" type="button" value="Zurück" onClick="javascript:window.history.back()">
						<input style="float:left; margin:5px" type="button" value="Zurück zur Weltkarte" onClick="javascript:window.location.href='game.php'">
					</form>
				</td>
			</tr>
		</table>
	</body>
</html>

<?php $db->Close(); ?>
