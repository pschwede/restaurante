<?php
	include_once "smarttools.php";
	include_once "generator.php";
	include_once "creators.php";
?>

<html>
<head>
<meta name="keywords" content="mmog, game, browser, browsergame, browser game, spiel, multiplayer, online, restaurant, simulation, markt" />
<meta name="description" content="Restaurante - Die anderen haben widerlicheren Fraß als du!" />
<meta name="author" content="P. Schwede" />
<meta name="revisit-after" content="2 days" />
<meta name="copyright" content="Copyright (GPL) of P. Schwede &amp; S. Schubert, some rights reserved" />
<meta name="robots" content="FOLLOW,INDEX" />
<title>Restaurante (Beta)</title>
</head>
<link rel="stylesheet" type="text/css" href="style.css">
<style type="text/css">
div#console {
	overflow:scroll;
	padding-top:5px;
	padding-bottom:50px;
	border:1px solid #000;
	height:150px;
	background:#000;
	color:#fff;
	font-weight:bold;
	margin: 5px auto;
	padding: 5px;
	font-family:Courier, monospace;
	font-size: 9pt;
}
.main{
	color:#fff;
<?php
	if(rand(0,7)==0) {
		echo "background-image: url(\"img/intro.jpg\");";
	} else {
		echo "background-image: url(\"img/monty_python.jpg\");";
	}
?>
	background-color:#1d1c18;
	background-position: right bottom;
	background-repeat:no-repeat;
	padding:30px;
	height:320px;
}
span {
	background:#fff;
	font-weight:bold;
	border:1px solid black;
	padding:5px; margin:5px;
	float:left; color:#000;
	-moz-border-radius:10px;
}
</style>
<script type="text/javascript">
	function toggle(t) {
		tanm = '<h2>Account erstellen</h2>';
		tanm += '<form action="eintragen.php" method="post">';
		tanm += 'Dein Username:<br>';
		tanm += '<input type="text" size="24" maxlength="50"';
		tanm += 'name="username"><br><br>';
		tanm += 'Dein Passwort:<br>';
		tanm += '<input type="password" size="24" maxlength="50"';
		tanm += 'name="passwort"><br>';
		tanm += 'Passwort wiederholen:<br>';
		tanm += '<input type="password" size="24" maxlength="50"';
		tanm += 'name="passwort2"><br>';
		tanm += '<input type="submit" value="Abschicken">';
		tanm += '</form>';
		tanm += '<a class="clickable" onClick="toggle(2)">Mit bereits bestehendem Account einloggen</a>';

		tlogin = '<h2>Einloggen</h2>';
		tlogin += '<form action="login.php" method="post">';
		tlogin += 'Dein Username:<br>';
		tlogin += '<input type="text" size="24" maxlength="50"';
		tlogin += 'name="username"><br><br>';
		tlogin += 'Dein Passwort:<br>';
		tlogin += '<input type="password" size="24" maxlength="50"';
		tlogin += 'name="password"><br>';
		tlogin += '<input type="submit" value="Login">';
		tlogin += '</form>';
		tlogin += '<a class="clickable" onClick="toggle(1)">Neuen Account erstellen</a>';

		if(t==1)
			document.getElementById("login").innerHTML = tanm;
		else
			document.getElementById("login").innerHTML = tlogin;
	}
</script>

<body onLoad="toggle(2)">
<table class="content" style="width:60%;">
	<tr class="top" style="background-image:url(img/restaurante.png); background-position: 10px 10px; background-repeat: no-repeat;">
		<td>
			<h1 style="padding-left:275px;">Restaurante (Beta)</h1>
			<a class="withIconTop faq" target="_blank" href="faq/">Was ist das?</a>
			<a class="withIconTop blank" target="_blank" href="<?php $FORUM_URL ?>">Forum</a>
		</td>
	</tr><tr>
		<td class="main">
			<div id="login">Du musst für dieses <a href="faq/">Spiel</a> Javascript aktiviert haben!</div>
		</td>
	</tr><tr><td>

<div id="console">
<?php
$worldid = 1;

//createDatabaseSQL();

if(!table_exists("player")) {
	echo "Spielertabelle wird erstellt..<br>";
	createPlayerTableSQL();
} else {
	/*echo "Spieler werden zurück gesetzt..<br>";
	resetPlayerTable();*/
}
if(!table_exists("grounds")) {
	echo "Grundstückstabelle wird erstellt..<br>";
	createGroundsTableSQL($worldid);
}
if(!table_exists("fixtures")) {
	echo "Einrichtungstabelle wird erstellt..<br>";
	createFixturesTableSQL($worldid);
}
if (!table_exists("world")) { //braucht spielertabelle und grundstückstabelle
	echo "Welt wird geschaffen..<br>";
	$worldmap = makemap(16,16,0.40);
	createWorldSQL($worldmap,0);
	echo "..fertig nach weniger als 7 Tagen!<br>";

	echo "Städte werden gebaut..<br>";
	createCitiesSQL($worldmap,$worldid);
}
if(!table_exists("hiredpersonel")) {
	echo "Tabelle für Angestellte wird erstellt..<br>";
	createHiredPersonelTableSQL($worldid);
}
if(!table_exists("choosablepersonel")) {
	echo "Tabelle für einstellbare Angestellte wird erstellt..<br>";
	createChoosablePersonelTableSQL($worldid);
}
if(!table_exists("statements")) {
	echo "Tabelle für Bankstatistik wird erstellt..<br>";
	createStatementsTableSQL();
}
if(!table_exists("messages")) {
	echo "Tabelle für einstellbare Angestellte wird erstellt..<br>";
	createMessagesTableSQL();
}
if(!table_exists("shares")) {
	echo "Tabelle für Aktien wird erstellt..<br>";
	createSharesTableSQL($worldid);
}
if(!table_exists("lose")) {
	echo "Tabelle für Lose wird erstellt..<br>";
	createLoseTableSQL($worldid);
}
if(!table_exists("highscore")) {
	echo "Tabelle für Highscore wird erstellt..<br>";
	createHighscoreTableSQL($worldid);
}
if(!table_exists("highscore2")) {
	echo "Tabelle für Highscore2 wird erstellt..<br>";
	createHighscore2TableSQL($worldid);
}
echo "..fertig.<br>";
echo "Version 0.9.9.9<br>";
?>
<img src="img/cur.gif" alt="">
</div>
</td></tr>
<tr><td style="text-align:center;">
<a href="http://www.getfirefox.com" target="_blank"><img style="border:none;" src="http://fc04.deviantart.com/fs32/f/2008/221/8/6/Firefox_3_banner_by_El_Sato.gif"></a>
</td></tr></table>

</body>
</html>
