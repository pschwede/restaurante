<?php
include_once "smarttools.php";
include_once "displays.php";
include_once "connector.php";

$db=makeConn();
$r_statements = $db->query("SELECT COUNT(*) FROM statements WHERE playerid=".$_SESSION["id"]." ORDER BY time DESC LIMIT 1");
$ticksMitNegKonto = 0;
while($statements = $r_statements->fetch_array(MYSQLI_NUM))
	$ticksMitNegKonto++;
$plr = getPlayer($_SESSION["id"]);
if($ticksMitNegKonto[0] >= 5 && $plr["konto"]<0) {
	echo "<div class=\"info blue\">";
		drawInsolvencyMenu();
	echo "</div>";
}

$map = getMapFromSQL($worldid,$_GET["city"]);
echo "<div class=\"info orange\">";
if (isset($_GET["x"],$_GET["y"])) {
	$ground = getGround($worldid,$_GET["city"],$token);
	if($ground["ownerid"]>0)
		$owner = getPlayer($ground["ownerid"]);
	if (thisIsCityMap()) { //citymap
		switch ($token) {
			case "GG":
			case "GH":
			case "GI":
				echo "Natur in <a href=\"game.php?x=".$city["x"]."&y=".$city["y"]."\">".$city["name"]."</a><br>";
				break;
			case "HG":
			case "HH":
			case "HI":
				echo "Wohnhaus in <a href=\"game.php?x=".$city["x"]."&y=".$city["y"]."\">".$city["name"]."</a><br>";
				echo "Größe: ".$city["popdichte"]." Bewohner";
				break;
			case "SG":
			case "SH":
			case "SI":
				echo "Straße in <a href=\"game.php?x=".$city["x"]."&y=".$city["y"]."\">".$city["name"]."</a><br>";
				break;
			default:
				switch($ground["state"]) {
					case 0:
						echo "Grundstück in <a href=\"game.php?x=".$city["x"]."&y=".$city["y"]."\">".$city["name"]."</a><br>";
						echo "Fläche: ".$ground["area"]."m²<br>";
						echo "Wert:  ".number_format($ground["price"],2,","," ")."&euro;<br>";
						echo "<a class=\"withIconLeft buy\" href=\"".$PHP_SELF."?city=".$_GET["city"]."&buy=".$token."&x=".$_GET["x"]."&y=".$_GET["y"]."\">Kaufen</a><br>";
						break;
					case 1:
						echo "Grundstück in <a href=\"game.php?x=".$city["x"]."&y=".$city["y"]."\">".$city["name"]."</a><br>";
						echo "Fläche: ".$ground["area"]."m²<br>";
						echo "Eigentümer: <a class=\"withIconRight journal\" href=\"game.php?ov=".$owner["id"]."\">".$owner["name"]."</a><br>";
						if ($ground["ownerid"] == $_SESSION["id"]) { //Spieler ist Besitzer
							echo "</div><div class=\"info blue\">";
							echo "<a href=\"".$PHP_SELF."?city=".$_GET["city"]."&build=".$token."&what=2&x=".$_GET["x"]."&y=".$_GET["y"]."\" target=\"_top\" class=\"withIconLeft build\">Haus bauen</a> (500 000&euro;)<br>";
							echo "<a href=\"".$PHP_SELF."?city=".$_GET["city"]."&build=".$token."&what=3&x=".$_GET["x"]."&y=".$_GET["y"]."\" target=\"_top\" class=\"withIconLeft build\">Platz bauen</a> (15 000&euro;)<br>";
							echo "<a href=\"".$PHP_SELF."?city=".$_GET["city"]."&build=".$token."&what=4&x=".$_GET["x"]."&y=".$_GET["y"]."\" target=\"_top\" class=\"withIconLeft build\">begrünen</a> (5 000&euro;)<br>";
							echo "<a href=\"".$PHP_SELF."?city=".$_GET["city"]."&build=".$token."&what=5&x=".$_GET["x"]."&y=".$_GET["y"]."\" target=\"_top\" class=\"withIconLeft build\">Wohnhaus bauen</a> (6 000&euro;)<br>";
							echo "<a href=\"".$PHP_SELF."?city=".$_GET["city"]."&build=".$token."&what=6&x=".$_GET["x"]."&y=".$_GET["y"]."\" target=\"_top\" class=\"withIconLeft build\">Hochhaus bauen</a> (22 000&euro;)<br>";
							echo "<a class=\"withIconLeft buy\" href=\"".$PHP_SELF."?city=".$_GET["city"]."&sell=".$token."&x=".$_GET["x"]."&y=".$_GET["y"]."\">Verkaufen</a> (".number_format(-$ground["price"]*0.9,2,","," ")." &euro;)<br>";
						} else {
							echo "Kette: ".$owner["kette"];
						}
						break;
					case 2:
						$city = getCity($worldid,$ground["city"]);
						#alert("Also, ich hab ".countPatternsInMap($map,"HG")+countPatternsInMap($map,"HH")+countPatternsInMap($map,"HI")." Häuser gezählt..");
						echo "<a class=\"withIconRight view\" href=\"".$PHP_SELF."?city=".$_GET["city"]."&x=".$_GET["x"]."&y=".$_GET["y"]."\">Filiale</a> in <a class=\"withIconRight view\" href=\"game.php?x=".$city["x"]."&y=".$city["y"]."\">".$city["name"]."</a><br>";
						if ($ground["ownerid"] == $_SESSION["id"]) { //Spieler ist Besitzer
							echo "Eigentümer: Du!<br>";
							echo "Kunden: ".$ground["customers"]."/".round(getMaxCustOfGround($ground))." (".(3*$city["population"]).")<br>";
							echo "Energie: ".getEnergyOfGround($ground)." kWh<br>";
							echo "Attraktivität: ".number_format(getAttrOfGround($ground),3,",","")." ";
							for($i=0; $i<$ground["stars"]; $i++) {
								echo "&#x2605";
							}
							echo "<br>";
							echo "Ertrag: ".number_format($ground["ingo"],2,",","")."&euro;<br>";
							echo "Löhne: ".number_format($ground["pays"],2,",","")."&euro;<br>";
							echo "Energie: ".number_format($ground["energycost"],2,",","")."&euro;<br>";
							echo "</div><div class=\"info blue\">";
							echo "Preise: "; 
							drawPrice4FoodMenu($ground);
							echo "<br>";
							echo "<a class=\"withIconLeft furnish\" href=\"".$PHP_SELF."?city=".$_GET["city"]."&furnish=".$token."&x=".$_GET["x"]."&y=".$_GET["y"]."&what=".$_GET["what"]."\">Einrichtung</a><br>";
							echo "<a class=\"withIconLeft employee\" href=\"".$PHP_SELF."?city=".$_GET["city"]."&personel=".$token."&x=".$_GET["x"]."&y=".$_GET["y"]."&what=".$_GET["what"]."\">Personal</a><br>";
						} else {
							echo "Eigentümer: <a class=\"withIconRight journal\" href=\"game.php?ov=".$owner["id"]."\">".$owner["name"]."</a><br>";
							echo "Kette: ".$owner["kette"]."<br>";
							echo "Kunden: ".$ground["customers"]."<br>";
							echo "Attraktivität: ".number_format(getAttrOfGround($ground),3,",","")." ";
							for($i=0; $i<$ground["stars"]; $i++) {
								echo "&#x2605";
							}
							echo "<br>";
						}
						break;
					case 3:
						echo "Parkplatz in <a href=\"game.php?x=".$city["x"]."&y=".$city["y"]."\">".$city["name"]."</a><br>";
						echo "Eigentümer: ".$owner["name"]."<br>";
						if ($ground["ownerid"] == $_SESSION["id"]) { //Spieler ist Besitzer
							echo "Ertrag: ".number_format($ground["ingo"],2,",","")."&euro;<br>";
							echo "Kunden: ".$ground["customers"]."<br>";
						} else {
							echo "Kette: ".$owner["kette"];
						}
						break;
					case 4:
						echo "Begrünung in <a href=\"game.php?x=".$city["x"]."&y=".$city["y"]."\">".$city["name"]."</a><br>";
						echo "Eigentümer: ".$owner["name"]."<br>";
						if ($ground["ownerid"] == $_SESSION["id"]) { //Spieler ist Besitzer
							//echo "<a class=\"withIcon furnish\" href=\"".$PHP_SELF."?city=".$_GET["city"]."&furnish=".$token."&x=".$_GET["x"]."&y=".$_GET["y"]."\">Ausstatten</a><br>";
						} else {
							echo "Kette: ".$owner["kette"];
						}
						break;
					case 5:
						echo "Wohnhaus in <a href=\"game.php?x=".$city["x"]."&y=".$city["y"]."\">".$city["name"]."</a><br>";
						if ($ground["ownerid"] == $_SESSION["id"]) { //Spieler ist Besitzer
							echo "Eigentümer: Du!<br>";
							echo "Kunden: ".$ground["customers"]."/30<br>";
							echo "Energie: ".getEnergyOfGround($ground)." kWh<br>";
							echo "Ertrag: ".number_format($ground["ingo"],2,",","")."&euro;<br>";
							echo "Löhne: ".number_format($ground["pays"],2,",","")."&euro;<br>";
							echo "Energie: ".number_format($ground["energycost"],2,",","")."&euro;<br>";
							echo "</div><div class=\"info blue\">";
						} else {
							echo "Eigentümer: <a class=\"withIconRight journal\" href=\"game.php?ov=".$owner["id"]."\">".$owner["name"]."</a><br>";
							echo "Kette: ".$owner["kette"]."<br>";
							echo "Kunden: ".$ground["customers"]."<br>";
							echo "<br>";
						}
						break;
					case 6:
						echo "Hochhaus in <a href=\"game.php?x=".$city["x"]."&y=".$city["y"]."\">".$city["name"]."</a><br>";
						if ($ground["ownerid"] == $_SESSION["id"]) { //Spieler ist Besitzer
							echo "Eigentümer: Du!<br>";
							echo "Kunden: ".$ground["customers"]."/90<br>";
							echo "Energie: ".getEnergyOfGround($ground)." kWh<br>";
							echo "Ertrag: ".number_format($ground["ingo"],2,",","")."&euro;<br>";
							echo "Löhne: ".number_format($ground["pays"],2,",","")."&euro;<br>";
							echo "Energie: ".number_format($ground["energycost"],2,",","")."&euro;<br>";
							echo "</div><div class=\"info blue\">";
						} else {
							echo "Eigentümer: <a class=\"withIconRight journal\" href=\"game.php?ov=".$owner["id"]."\">".$owner["name"]."</a><br>";
							echo "Kette: ".$owner["kette"]."<br>";
							echo "Kunden: ".$ground["customers"]."<br>";
							echo "<br>";
						}
						break;
					default:
						echo "Mysteriöse Gegend in <a href=\"game.php?x=".$city["x"]."&y=".$city["y"]."\">".$city["name"]."</a>!<br>";
						break;
				}
				if($ground["ownerid"]==$_SESSION["id"] && $ground["state"]>1) {
					echo "<a href=\"";
					prompt("Wirklich abreißen?",$PHP_SELF."?city=".$_GET["city"]."&build=".$token."&what=1&x=".$_GET["x"]."&y=".$_GET["y"]);
					echo "\" target=\"_top\" class=\"withIconLeft build\">Abreißen (0&euro;)</a><br>";
				}
				if ($ground["ownerid"]>0 && $ground["ownerid"] != $_SESSION["id"] && $ground["state"]==2) {
					echo "</div><div class=\"info red\">";
					drawKickSomeAssMenu($worldid,$_GET["city"],$token);
				} 
		}	
	} else { //worldmap
		switch ($token) {
			case "GG":
				echo "Natur (See)<br>";
				break;
			case "GH":
			case "GI":
			case "HG":
			case "HH":
			case "HI":
				echo "Natur<br>";
				break;
			case "SG":
			case "SH":
			case "SI":
				echo "Autobahn<br>";
				break;
			default:
				$city = getCity($worldid,$map[$_GET["x"]][$_GET["y"]]);
				echo $city["name"]."<br>";
				echo "GPS: (".$_GET["x"].",".$_GET["y"].")<br>";
				echo "Einwohner: ".$city["population"]."<br>";
				echo "Einwohnerdichte: ".$city["popdichte"]."/Haus<br>";
				
				$r_plrid = $db->query("SELECT ownerid FROM grounds WHERE city='".$city["hex"]."' AND state=2");
				if($plrid = $r_plrid->fetch_array(MYSQLI_NUM)) {
					echo "Filialen: ";
					$fil[] = $plrid[0];
					$filanz[$plrid[0]] = 1;
					while($plrid = $r_plrid->fetch_array(MYSQLI_NUM)) {
						if(!in_array($plrid[0],$fil)) {
							$fil[] = $plrid[0];
							$filanz[$plrid[0]] = 1;
						} else {
							$filanz[$plrid[0]]++;
						}
					}
					foreach($fil as $plrid) {
						$plr = getPlayer($plrid);
						
						echo "<a href=\"game.php?ov=".$plr["id"]."\">".$plr["kette"];
						if($filanz[$plrid]>1)
							echo " (".$filanz[$plrid].")";
						echo "</a> ";
					}
					echo "<br>";
				}
				echo " <a class=\"withIconLeft city\" href=\"game.php?city=".$token."\">Ansehen</a><br>";
		}
	}
} else if(isset($_GET["wallstreet"])) {
	$r_count = $db->query("SELECT SUM(count) FROM shares WHERE company=".$_SESSION["id"]);
	$count = $r_count->fetch_array(MYSQLI_NUM);
	if($count[0]>0) {
		echo "Besitzer deiner Aktien:<br>";
		$query = "SELECT SUM(count) FROM shares WHERE company=".$_SESSION["id"]." AND shareholder=".$_SESSION["id"];
			$r_count2 = $db->query($query)
				or die("Error during query: ".$query);
			if($count2 = $r_count2->fetch_array(MYSQLI_NUM)) {
				echo " Du: ".number_format(100*$count2[0]/$count[0],2,",","")."%<br>";
			} else echo $sh["name"].": keine<br>";
		$query = "SELECT shareholder FROM shares WHERE shareholder!=company AND company=".$_SESSION["id"];
		$r_shid = $db->query($query)
			or die("Error during query: ".$query);
		while($shid = $r_shid->fetch_array(MYSQLI_NUM)) {
			$sh = getPlayer($shid[0]);
			$query = "SELECT SUM(count) FROM shares WHERE company=".$_SESSION["id"]." AND shareholder=".$sh["id"];
			$r_count4 = $db->query($query)
				or die("Error during query: ".$query);
			if($count4 = $r_count4->fetch_array(MYSQLI_NUM)) {
				echo $sh["name"].": ".number_format(100*$count4[0]/$count[0],2,",","")."%<br>";
			} else echo $sh["name"].": keine<br>";
		}
		$query = "SELECT SUM(count) FROM shares WHERE shareholder=".$_SESSION["id"];
		$r_count3 = $db->query($query)
			or die("Error during query: ".$query);
		if($count3 = $r_count3->fetch_array(MYSQLI_NUM)) {
			$plr = getPlayer($_SESSION["id"]);
			echo "<br>Deine Firmenanteile:<br>";
			echo $plr["kette"].": ".number_format(100*$count2[0]/$count[0],2,",","")."%<br>";
			
			$query = "SELECT company FROM shares WHERE company!=".$_SESSION["id"]." AND shareholder=".$_SESSION["id"];
			$r_comid = $db->query($query)
				or die("Error during query: ".$query);
			while($comid = $r_comid->fetch_array(MYSQLI_NUM)) {
				$plr = getPlayer($comid[0]);
				$query = "SELECT SUM(count) FROM shares WHERE company=".$plr["id"]." AND shareholder=".$_SESSION["id"];
				$r_count5 = $db->query($query)
					or die("Error during query: ".$query);
				$count5 = $r_count5->fetch_array(MYSQLI_NUM);
				
				$query = "SELECT SUM(count) FROM shares WHERE company=".$plr["id"];
				$r_count6 = $db->query($query)
					or die("Error during query: ".$query);
				$count6 = $r_count6->fetch_array(MYSQLI_NUM);
				echo $plr["kette"].": ".number_format(100*$count5[0]/$count6[0],2,",","")."%<br>";
			}
		}
	}
} else{
	echo "Bitte Feld anklicken, oder auf..<br>";
	echo "<a href=\"game.php?ov=0\" target=\"_top\" class=\"withIconLeft view\">Überblick</a><br>
					<a href=\"game.php\" target=\"_top\" class=\"withIconLeft world\">Karte</a>";
}
echo "</div>";

$plr = getPlayer($_SESSION["id"]);
if($plr["deeds"]>=1) {
	echo "<div class=\"info yellow\">";
} else {
	echo "<div class=\"info orange\">";
}
drawLose();
echo "</div>";

$r_bd = $db->query("SELECT birthday FROM world WHERE 1"); //Mehre welten?
$bd = $r_bd->fetch_array(MYSQLI_NUM);
if($bd[0]+26*24*60*60<time()) {
	echo "<div class=\"info green\">";
	drawRVMenu();
	echo "</div>";
}
if(empty($_GET)) {
	echo "<div class=\"info orange\">";
	$r_cities = $db->query("SELECT * FROM cities WHERE worldid=".$worldid." ORDER BY name")
		or die("Error: ".$db->error);
	while($city = $r_cities->fetch_array(MYSQLI_ASSOC)) {
		echo "<a href=\"game.php?city=".$city["hex"]."\" target=\"_top\">";
		echo $city["name"];
		echo "</a> ";
	}
	echo "</div>";
}
$db->Close();
?>
