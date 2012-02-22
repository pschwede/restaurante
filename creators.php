<?php

include_once "smarttools.php";
include_once "connector.php";

function createDatabaseSQL() {
	// Verbindungs-Objekt samt Zugangsdaten festlegen
	@$db = new mysqli("localhost", "spz@1", "kiluPHP$", "");
	if (mysqli_connect_errno())
		die("#1 Verbindung fehlgeschlagen: ". mysqli_connect_error()."<br>");

	$db->query("CREATE DATABASE IF NOT EXISTS spz@1-restaurante")
		or die("restaurante-Datenbank konnte nicht angelegt werden!<br>");

	$db->Close();
}

function createPlayerTableSQL() {
	$db = makeConn();

	$sql_befehl = "CREATE TABLE IF NOT EXISTS player (
	id INT(11) NOT NULL AUTO_INCREMENT,
	name VARCHAR(50) DEFAULT NULL,
	passwd VARCHAR(50) DEFAULT NULL,
	lastlogin INT DEFAULT 0,
	lastdeed INT DEFAULT 0,
	lastlogout INT DEFAULT 0,
	kette VARCHAR(50) DEFAULT NULL,
	logo VARCHAR(255) DEFAULT NULL,
	deeds INT DEFAULT 0,
	ia DOUBLE DEFAULT 0,
	konto DOUBLE DEFAULT 1500000,
	outgo DOUBLE DEFAULT 0,
	energycost DOUBLE DEFAULT 0,
	pays DOUBLE DEFAULT 0,
	ingo DOUBLE DEFAULT 0,
	zinsen DOUBLE DEFAULT 0,
	others DOUBLE DEFAULT 0,
	customers INT DEFAULT NULL,
	RVvote TINYINT(1) DEFAULT 0,
	email VARCHAR(30) DEFAULT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY (name)
	)";

	if (!$db->query($sql_befehl))
		die("Spielertabelle konnte nicht angelegt werden! ".$sql_befehl."<br>");

	$db->query("INSERT INTO player (name, kette, ia) VALUES ('System', 'System', 2000000)");

	$db->Close();
}

function resetPlayerTable() {
	$db = makeConn();
	$query = "UPDATE player SET konto=1500000, ia=0, ingo=0, outgo=0, others=0, zinsen=0, deeds=0, pays=0, customers=null WHERE 1";
	$db->query($query) or die("Spielertabelle konnte nicht erfolgreich zurückgesetzt werden.");
	$db->Close();
}

function createStatementsTableSQL() {
	$db = makeConn();

	$sql_befehl = "CREATE TABLE IF NOT EXISTS statements (
	id INT(11) NOT NULL AUTO_INCREMENT,
	playerid INT(11) DEFAULT NULL,
	time INT DEFAULT NULL,
	konto DOUBLE DEFAULT NULL,
	ia DOUBLE DEFAULT 0,
	PRIMARY KEY (id)
	)";

	if (!$db->query($sql_befehl))
		die("Statementstabelle konnte nicht angelegt werden! ".$sql_befehl."<br>");
	$db->Close();
}

function createMessagesTableSQL() {
	$db = makeConn();

	$sql_befehl = "CREATE TABLE IF NOT EXISTS messages (
	id INT(11) NOT NULL AUTO_INCREMENT,
	toid INT(11) DEFAULT NULL,
	fromid INT(11) DEFAULT NULL,
	time INT DEFAULT NULL,
	title TINYTEXT DEFAULT NULL,
	text TEXT DEFAULT NULL,
	state VARCHAR(2) DEFAULT NULL,
	seen TINYINT(1) DEFAULT 0,
	PRIMARY KEY (id)
	)";

	if (!$db->query($sql_befehl))
		die("Nachrichtentabelle konnte nicht angelegt werden! ".$sql_befehl."<br>");
	$db->Close();
}

function createFixturesTableSQL() {
	$db = makeConn();

	// Verbindung überprüfen
	if (mysqli_connect_errno())
	  die("Verbindung fehlgeschlagen: ". mysqli_connect_error());

	$sql_befehl = "CREATE TABLE IF NOT EXISTS fixtures (
	id INT(11) NOT NULL AUTO_INCREMENT,
	name VARCHAR(50) DEFAULT NULL,
	class INT DEFAULT NULL,
	attr INT DEFAULT NULL,
	price FLOAT DEFAULT NULL,
	max_customers INT DEFAULT NULL,
	personal INT DEFAULT NULL,
	space INT DEFAULT 0,
	energy INT DEFAULT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY (name)
	)";

	if (!$db->query($sql_befehl))
		die("Einrichtungstabelle konnte nicht angelegt werden! ".$sql_befehl."<br>");

	$cols =                                         " name,     class, attr,   price,max_customers,personal,space,energy";
	$db->query("INSERT INTO fixtures ($cols) VALUES ('ImBiss',			1,		2,		3080,			15,				1,			15,		0)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('BistRoh',			1,		4,		1577.5,		20,				1,			20,		1)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('Sushi',				1,		8,		1577.5,		35,				1,			20,		3)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('Tirol',				1,		2,		1757.50,	45,				1,			45,		0)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('Romantisch',	1,		14,		1806,			16,				1,			48,		2)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('Lounge',			1,		20,		14000.9,	15,				1,			9,		5)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('Saloon',			1,		30,		12000,		7,				2,			9,		5)");

	$db->query("INSERT INTO fixtures ($cols) VALUES ('Hot Wok',			2,		2,		1305,			65,				3,			45,		65)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('Klein&Fettig',2,		2,		2000,			55,				1,			45,		80)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('°Modern-^',		2,		9,		17999.99,	50,				2,			99,		150)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('Mittelgroß',	2,		12,		29000,		70,				5,			99,		450)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('Rustikal',		2,		15,		51000,		75,				5,			99,		450)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('FastFood',		2,		12,		600006,		180,			25,			99,		800)");

	$db->query("INSERT INTO fixtures ($cols) VALUES ('Plumpsklo',		3,		-3,		298.99,		1200,			1,			50,		30)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('Chemo',				3,		0,		3880.98,	1200,			5,			50,		300)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('NoRm41',			3, 		1,		16000,		1000,			1,			60,		200)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('ImmerRein',		3, 		1,		29000,		1000,			2,			60,		140)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('Thermiert',		3, 		2,		29000,		1000,			3,			60,		175)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('NieB.setzt',	3, 		7,		35000,		1200,			7,			60,		150)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('Engagiert',		3, 		10,		333000,		1000,			15,			65,		50)");
	$db->query("INSERT INTO fixtures ($cols) VALUES ('HAL',					3, 		10,		990099,		1900,			1,			60,		404)");

	$db->Close();
}

function createWorldSQL($map,$round) {
	$db = makeConn();


	$sql_befehl = "CREATE TABLE IF NOT EXISTS world (
	id INT(11) NOT NULL AUTO_INCREMENT,
	round INT DEFAULT 0,
	birthday INT DEFAULT 0,
	name VARCHAR(50) DEFAULT NULL,
	height INT DEFAULT NULL,
	width INT DEFAULT NULL,
	strdichte FLOAT DEFAULT NULL,
	lasttick INT DEFAULT 0,
	nexttick INT DEFAULT 0,
	log TEXT DEFAULT NULL,
	ticking TINYINT(1) DEFAULT 0,
	PRIMARY KEY (id))";
	if (!$db->query($sql_befehl))
		die("Welttabelle konnte nicht angelegt werden! ".$sql_befehl."<br>");

	for ($i=0; $i<$map["width"]; $i++) {
		$sql_befehl = "ALTER TABLE world ADD y".$i." VARCHAR(".(2*$map["width"]).") DEFAULT NULL";
		if (!$db->query($sql_befehl))
			die("Welt konnte nicht erweitert werden! ".$sql_befehl."<br>");
	}


//Welt hinzufügen
	$today = time();
	$then = $today+2*60*60;
	$then -= $then%(60*60);
	$then -= $then%60;
	$sql_befehl = "INSERT INTO world (name, height, width, strdichte, lasttick, nexttick, birthday, round) VALUES ('$name',".$map["height"].",".$map["width"].",".$map["strdichte"].",$today,$then,$today,".($round+1).")";
	if (!$db->query($sql_befehl))
		die("Welt konnte nicht hinzugefügt werden! ".$sql_befehl."<br>");

//map eintragen
	for ($y=0; $y<$map["height"]; $y++) {
		$string = "";
		for ($x=0; $x<$map["width"]; $x++) {
			$string .= $map[$x][$y];
		}
		$sql_befehl = "UPDATE world SET y".$y."='".$string."' WHERE name='$name'";
		if (!$db->query($sql_befehl))
			die("Karte konnte nicht eingetragen werden! ".$sql_befehl."<br>");
	}
	$db->Close();
}

function createCitiesSQL($map,$worldid) {
	$db = makeConn();
	// Tabelle erstellen
	$sql_befehl = "CREATE TABLE IF NOT EXISTS cities (
	id INT(11) NOT NULL AUTO_INCREMENT,
	hex VARCHAR(2) DEFAULT NULL,
	x INT DEFAULT NULL,
	y INT DEFAULT NULL,
	worldid INT DEFAULT NULL,
	name VARCHAR(50) DEFAULT NULL,
	height INT DEFAULT NULL,
	width INT DEFAULT NULL,
	strdichte FLOAT DEFAULT NULL,
	popdichte INT DEFAULT NULL,
	population INT DEFAULT NULL,
	restcust INT DEFAULT NULL,
	PRIMARY KEY (id))";
	if (!$db->query($sql_befehl))
		die("Städtetabelle konnte nicht angelegt werden! ".$sql_befehl."<br>");

	for ($i=0; $i<$map["width"]; $i++) {
		$sql_befehl = "ALTER TABLE cities ADD y".$i." VARCHAR(".(2*$map["width"]).") DEFAULT NULL";
		if (!$db->query($sql_befehl))
			die("Städtetabelle konnte nicht erweitert werden! ".$sql_befehl."<br>");
	}

	//cities dimensions
	$height = 16;
	$width = 16;

	// stadtnamen generieren, stadt eintragen, stadt generieren, stadtkarte eintragen
	for ($y=0; $y<$map["height"]; $y++) {
		for ($x=0; $x<$map["width"]; $x++) {
			if (hexdec($map[$x][$y]) > 0) {
				$cmap = makemap($height,$width,$strdichte);
				$hex = $map[$x][$y];
				$nbh = getNbh($map,$x,$y);
				$citycomplex = array();
				$strdichte = 0.04*(countPatterns($nbh,"SG","SH","SI"))+0.05;
				$popdichte = 11 + 6*countPatterns($nbh,"SI","SG","SH") + 100*countPatterns($nbh,"HG","HH","HI")/12;
				$population = 1.5*$popdichte*(countPatternsInMap($map,"HG")+countPatternsInMap($map,"HH")+countPatternsInMap($map,"HI"));
				$name = makeCityName();

				$sql_befehl = 'INSERT INTO cities (hex, worldid, name, x, y, height, width, strdichte, popdichte, population) VALUES ("'.$hex.'",'.$worldid.',"'.$name.'",'.$x.','.$y.','.$height.','.$width.','.$strdichte.','.$popdichte.','.$population.')';
				if (!$db->query($sql_befehl))
					die("Stadt konnte nicht erfolgreich eingetragen werden! ".$sql_befehl."<br>");

				for ($cy=0; $cy<$cmap["height"]; $cy++) {
					$string = "";
					for ($cx=0; $cx<$cmap["width"]; $cx++) {
						$string .= $cmap[$cx][$cy];
						if (hexdec($cmap[$cx][$cy])>0) {
							$area = array();
							$size = getSizeOfArea($cmap,$cx,$cy,$cmap[$cx][$cy],$area)*220;
							$nbh = getNbhOfArea($cmap,$area);
							$price = (450-10*$size/220)*$size;
							$price *= 0.05*$popdichte*(1+0.5*countPatterns($nbh,"SG","SH","SI"));
							$sql_befehl = "INSERT INTO grounds (hex, worldid, city, x, y, area, remainingarea, price, state) VALUES ('".$cmap[$cx][$cy]."',".$worldid.",'".$hex."',".$cx.",".$cy.",".$size.",".$size.",".$price.",0)";
							if(!$db->query($sql_befehl))
								die("Grundstück erfolgreich eingetragen<br>");
						}
					}
					$sql_befehl = "UPDATE cities SET y".$cy."='".$string."' WHERE hex='".$hex."'";
					if (!$db->query($sql_befehl))
						die("Stadtkarte konnte nicht ergänzt werden! ".$sql_befehl."<br>");
				}
			}
		}
	}

	$db->Close();
}

function createGroundsTableSQL($worldid) {
	$db = makeConn();
	// Tabelle erstellen
	$sql_befehl = "CREATE TABLE IF NOT EXISTS grounds (
	id INT(11) NOT NULL AUTO_INCREMENT,
	hex VARCHAR(2) DEFAULT NULL,
	worldid INT DEFAULT NULL,
	city VARCHAR(2) DEFAULT NULL,
	x INT DEFAULT NULL,
	y INT DEFAULT NULL,
	area INT DEFAULT 220,
	remainingarea INT DEFAULT 220,
	price FLOAT DEFAULT NULL,
	ownerid INT(11) DEFAULT NULL,
	ingo FLOAT DEFAULT NULL,
	pays FLOAT DEFAULT NULL,
	energycost FLOAT DEFAULT NULL,
	note TEXT DEFAULT NULL,
	state INT DEFAULT NULL,
	customers INT DEFAULT NULL,
	stars INT DEFAULT 0,
	kitchensnum INT DEFAULT 0,
	kitchensid INT(11) DEFAULT 0,
	tablesnum INT DEFAULT 0,
	tablesid INT(11) DEFAULT 0,
	toiletsnum INT DEFAULT 0,
	toiletsid INT(11) DEFAULT 0,
	price4food INT DEFAULT 5,
	PRIMARY KEY (id))";
	if (!$db->query($sql_befehl))
	  die("Grundstückstabelle konnte nicht angelegt werden! ".$sql_befehl."<br>");

	$db->Close();
}

function createHiredPersonelTableSQL($worldid) {
	$db = makeConn();

	$db->query("CREATE TABLE IF NOT EXISTS hiredpersonel (
	id INT(11) NOT NULL AUTO_INCREMENT,
	hex VARCHAR(2) DEFAULT NULL,
	worldid INT(11) DEFAULT NULL,
	city VARCHAR(2) DEFAULT NULL,
	name VARCHAR(50) DEFAULT NULL,
	relia FLOAT DEFAULT NULL,
	pay FLOAT DEFAULT NULL,
	jobid INT DEFAULT NULL,
	capa INT DEFAULT NULL,
	PRIMARY KEY (id))") or die("error creating hired personel table");

	$db->Close();
}

function createHighscoreTableSQL($worldid) {
	$db = makeConn();

	$db->query("CREATE TABLE IF NOT EXISTS highscore (
	id INT(11) NOT NULL AUTO_INCREMENT,
	round INT DEFAULT 0,
	ia DOUBLE DEFAULT 0,
	iaid INT(11) DEFAULT 1,
	konto DOUBLE DEFAULT 0,
	kontoid INT(11) DEFAULT 1,
	customers INT DEFAULT 0,
	customersid INT(11) DEFAULT 1,
	energycost DOUBLE DEFAULT 9E+99,
	energyid INT(11) DEFAULT 1,
	mieter INT DEFAULT 0,
	mieterid INT(11) DEFAULT 1,
	carparks INT DEFAULT 0,
	carparksid INT(11) DEFAULT 1,
	restaurants INT DEFAULT 0,
	restaurantsid INT(11) DEFAULT 1,
	attr INT DEFAULT 0,
	attrid INT(11) DEFAULT 1,
	pays INT DEFAULT 0,
	paysid INT(11) DEFAULT 1,

	PRIMARY KEY (id))") or die("error creating highscore table");

	$db->query("INSERT INTO highscore (round) VALUES (0)");

	$db->Close();
}

function createHighscore2TableSQL($worldid) {
	$db = makeConn();

	$db->query("CREATE TABLE IF NOT EXISTS highscore2 (
	id INT(11) NOT NULL AUTO_INCREMENT,
	round INT DEFAULT 0,
	playerid INT(11) DEFAULT 0,
	playtime INT DEFAULT 0,
	PRIMARY KEY (id))") or die("error creating highscore table");

	$db->query("INSERT INTO highscore2 (round) VALUES (0)");

	$db->Close();
}

function createChoosablePersonelTableSQL($worldid) {
	$db = makeConn();

	$db->query("CREATE TABLE IF NOT EXISTS choosablepersonel (
	id INT(11) NOT NULL AUTO_INCREMENT,
	worldid INT(11) DEFAULT NULL,
	city VARCHAR(2) DEFAULT NULL,
	name VARCHAR(50) DEFAULT NULL,
	relia FLOAT DEFAULT NULL,
	pay FLOAT DEFAULT NULL,
	jobid INT DEFAULT NULL,
	capa INT DEFAULT NULL,
	PRIMARY KEY (id))") or die("error creating choosable personel table");

	makeNewChoosablePersonelTable($worldid);
	$db->Close();
}

function createSharesTableSQL($worldid) {
	$db = makeConn();

	$db->query("CREATE TABLE IF NOT EXISTS shares (
	id INT(11) NOT NULL AUTO_INCREMENT,
	forsale BOOL DEFAULT FALSE,
	count INT DEFAULT FALSE,
	shareholder INT(11) DEFAULT NULL,
	company INT(11) DEFAULT NULL,
	oldia FLOAT DEFAULT NULL,
	value FLOAT DEFAULT NULL,
	PRIMARY KEY (id))") or die("error creating shares table");

	$r_sysplr = $db->query("SELECT * FROM player WHERE name='System' AND kette='System'");
	$sysplr = $r_sysplr->fetch_array(MYSQLI_ASSOC);
	$db->query("INSERT INTO shares (forsale, count, shareholder, company, oldia, value) VALUES (1,2000,".$sysplr["id"].",".$sysplr["id"].",10000000,5000)");

	$db->Close();
}

function createLoseTableSQL($worldid) {
	$db = makeConn();
	$query = "CREATE TABLE IF NOT EXISTS lose (
	id INT(11) NOT NULL AUTO_INCREMENT,
	jackpot FLOAT DEFAULT 15000,
	lastwinner INT(11) DEFAULT NULL,
	PRIMARY KEY (id))";
	$db->query($query) or die("error creating lose table: ".$query);

	$db->query("INSERT INTO lose (jackpot) VALUES (0)");
	$db->Close();
}
?>
