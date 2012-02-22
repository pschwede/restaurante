<?php

include_once "connector.php";
include_once "smarttools.php";

function drawMapInGame($worldid,$city,$playerid) {
	$map = getMapFromSQL($worldid,$city);
	$height = $map["height"];
	$width = $map["width"];
	$strdichte = $map["strdichte"];
	$selected = array();

	if (isset($city)) {
		$type = "citymap";
	} else {
		$type = "worldmap";
	}

	echo "<table class=\"".$type."\">\n";
	for ($y = 0; $y < $height; $y++) {
		echo "<tr>\n";
		for ($x = 0; $x < $width; $x++) {
			echo "<td onClick=\"javascript:info(".$x.",".$y.")\"";
			//abfragen, ob keines,  oder gegnerisches grundstück
			$ground = getGround($worldid,$city,$map[$x][$y]);
			if(hexdec($map[$x][$y])>0) {
				if(!thisIsCityMap() && $cty = getCity($worldid,$map[$x][$y]))
					echo " title=\"".$cty["name"].": ".$cty["population"]."\"";
				switch ($ground["state"]) { //bebaut?
					case 1:
						if($ground["ownerid"] == $playerid) {
							$class = "II";
						} else {
							$class = "IH";
						}
						break;
					case 2:
						if($ground["ownerid"] == $playerid) {
							$class = "IJ";
						} else {
							$class = "IK";
						}
						break;
					case 3:
						if($ground["ownerid"] == $playerid) {
							$class = "IL";
						} else {
							$class = "IM";
						}
						break;
					case 4:
						if($ground["ownerid"] == $playerid) {
							$class = "GG";
						} else {
							$class = "GI";
						}
						break;
					case 5:
						if($ground["ownerid"] == $playerid) {
							$class = "IN";
						} else {
							$class = "IO";
						}
						break;
					case 6:
						if($ground["ownerid"] == $playerid) {
							$class = "IP";
						} else {
							$class = "IQ";
						}
						break;
					default:
						$class = "IG";
				}
			} else {
				$class = $map[$x][$y];
			}
			echo " class=\"".$class."\"";
			if($class=="IG")
				echo " title=\"".number_format($ground["area"],0,","," ")." m²: ".number_format($ground["price"],2,","," ")." &euro;\"";
			echo ">";

			if(isset($_GET["x"]) && isset($_GET["y"])) //Auswahl darstellen
				if($type=="citymap" && hexdec($map[$_GET["x"]][$_GET["y"]])>0) {
					if(count($selected)<1) getSizeOfArea($map,$_GET["x"],$_GET["y"],$map[$_GET["x"]][$_GET["y"]],$selected);
					if(in_array($x.",".$y.",".$map[$x][$y],$selected)) {
						if($_GET["x"]==$x && $_GET["y"]==$y || $ground["state"]<1 || $ground["state"]==$gnd["state"])
							echo "<img src=\"img/selected2ani.gif\" alt=\"\" title=\"".count($selected)."\">";
						else
							echo "<img src=\"img/selected2.gif\" alt=\"\" title=\"".count($selected)."\">";
					}
				} elseif($_GET["x"] == $x && $_GET["y"] == $y) {
					echo "<img src=\"img/selected2ani.gif\" alt=\"\" title=\"".$ground["population"]."\">";
				}
			echo "</td>\n";
		}
		echo "</tr>\n";
	}
	echo "</table>";
}

function drawTransfer() {
	$db = makeConn();
	$plr = getPlayer($_SESSION["id"]);
	$name = $plr["name"];
	if($_POST["transferMoney"]>0) {
		$r_plr = $db->query("SELECT * FROM player WHERE id=".$_POST["transferTo"]);
		if($plr = $r_plr->fetch_array(MYSQLI_ASSOC)) {
			$db->query("UPDATE player SET konto=konto+".$_POST["transferMoney"].", ingo=ingo-".$_POST["transferMoney"]." WHERE id=".$_POST["transferTo"]);
			$db->query("UPDATE player SET konto=konto-".$_POST["transferMoney"].", outgo=outgo+".$_POST["transferMoney"]." WHERE id=".$_SESSION["id"]);
			secondNoticeBar(number_format($_POST["transferMoney"],2,","," ")." &euro; an ".$plr["name"]." überwiesen.");
			sendMessage(0,$_POST["transferTo"],"Überweisung","$name überwies dir ".number_format($_POST["transferMoney"],2,","," ")." &euro;.",3);
			$plr = getPlayer($_SESSION["id"]);
			drawNewGuthaben(number_format($plr["konto"],2,",",""));
		}
	}
	echo "<table class=\"overview\">
	          <tr><th>Überweisung</th></tr>";
	echo "<tr><td><form action=\"\" method=\"POST\">
		<input type=\"text\" size=\"13\" name=\"transferMoney\" value=\"0\"> &euro an
		<select name=\"transferTo\">";
	$r_plr = $db->query("SELECT * FROM player WHERE kette!='System' AND name!='System' AND id!=".$_SESSION["id"]." ORDER BY name");
	while($plr = $r_plr->fetch_array(MYSQLI_ASSOC)) {
		echo "			<option value=\"".$plr["id"]."\">".$plr["name"]."</option>";
	}
	echo "		</select>
		<input type=\"submit\" value=\"Überweisen\">\n\t</form></td></tr></table>";
	$db->Close();
}

function drawShare($ownshare,$sell) {
	$ag = getPlayer($ownshare["company"]);
	$db = makeConn();
	$r_count = $db->query("SELECT SUM(count) FROM shares WHERE company=".$ag["id"]);
	$count = $r_count->fetch_array(MYSQLI_NUM);
	$db->Close();
	echo "<table class=\"overview\">
		<tr>
			<th colspan=3>".$ag["kette"]."</th>
		</tr><tr>
			<td style=\"width:65px;\" class=\"number titleOnly\" title=\"
				Dividende: ".number_format($ownshare[count]*($ag["ia"]/$count[0]-$ownshare["value"]),2,","," ")."\">
				".$ownshare["count"]." Stk <br>(";
	echo number_format(100*$ownshare["count"]/$count[0],2,",","")."%)
			</td>
			<td rowspan=2 colspan=2>";
	drawIADiagr($ag["id"],400);
	echo "</td>
		</tr><tr>
			<td class=\"number titleOnly\" title=\"Startwert\">
				".number_format($ownshare["value"],2,","," ")." &euro;
			</td>
		</tr><tr>
			<td class=\"number titleOnly\" title=\"Wertänderung seit dem letzten Tick\">";
	drawFinancialTendency($ownshare["company"]);
	echo "<div style=\" text-align:center; color:".(($ia>0) ? "#4d4\">+".$ia : "#d44\">".$ia);

	$plr = getPlayer($_SESSION["id"]);
	if($sell || $plr["id"] == $ownshare["shareholder"]) {
		$maxvalue = $ownshare["count"];
	} else {
		$maxvalue = floor($plr["konto"]/($ag["ia"]/$count[0]));
		if($maxvalue>$ownshare["count"])
			$maxvalue = $ownshare["count"];
	}
	echo "</td><td class=\"number titleOnly\" title=\"Stückpreis\">
				".number_format($ag["ia"]/$count[0],2,","," ")." &euro;
			</td><td>
				<form id=\"".(($sell) ? "sell" : "buy").$ag["id"]."\" method=\"POST\" action=\"\" style=\"margin:0px auto;\">
						<input type=\"text\" id=\"share_amount".(($sell) ? "sell" : "buy").$ag["id"]."\" size=\"5\" name=\"".(($sell) ? "sell" : "buy").$ag["id"]."\" value=\"0\">
						<input type=\"button\" onClick=\"document.getElementById('share_amount".(($sell) ? "sell" : "buy").$ag["id"]."').value=$maxvalue\" value=\"max\">
						<input type=\"submit\" value=\"".(($sell) ? "verkaufen" : "kaufen")."\">
				</form>
			</td>
		</tr></table>";
}

function sharesCenter($worldid) {
	drawTransfer();
	echo "<br>";
	$db = makeConn();
	$plr = getPlayer($_SESSION["id"]);

	//print_r($_POST);
	//drawIAMarquee($worldid);

	if(isset($_POST["bgang"]))
		echo addShare($plr["id"],$plr["id"],$plr["ia"]/$_POST["bgang"],$plr["ia"],$_POST["bgang"]);

	$r_shares = $db->query("SELECT * FROM shares WHERE forsale=0 AND shareholder=".$plr["id"]);
	while($share = $r_shares->fetch_array(MYSQLI_ASSOC)) {
		if($_POST["sell".$share["company"]]>0) {
			$ag = getPlayer($share["company"]);
			$r_count = $db->query("SELECT SUM(count) FROM shares WHERE company=".$share["company"]);
			$count = $r_count->fetch_array(MYSQLI_NUM);
			secondNoticeBar(sellShare($plr["id"],$_POST["sell".$share["company"]],$share,$ag["ia"]/$count[0]));
		}
	}

	$r_shares = $db->query("SELECT * FROM shares WHERE forsale=1");
	while($share = $r_shares->fetch_array(MYSQLI_ASSOC)) {
		//alert("buy".$share["company"]." => ".$_POST["buy".$share["company"]]);
		if($_POST["buy".$share["company"]]>0) {
			$ag = getPlayer($share["company"]);
			$r_count = $db->query("SELECT SUM(count) FROM shares WHERE company=".$share["company"]);
			$count = $r_count->fetch_array(MYSQLI_NUM);
			secondNoticeBar(buyShare($plr["id"],$_POST["buy".$share["company"]],$share,$ag["ia"]/$count[0]));
		}
	}

	$r_ownshare = $db->query("SELECT * FROM shares WHERE forsale=0 AND shareholder=".$plr["id"]);
	while($ownshare = $r_ownshare->fetch_array(MYSQLI_ASSOC)) {
		drawShare($ownshare,true);
	}

	$r_count = $db->query("SELECT COUNT(*) FROM shares WHERE company=".$plr["id"]);
	$count = $r_count->fetch_array(MYSQLI_NUM);
	if($count[0]==0) {
		echo "<br><table class=\"overview\">
		<tr>
			<th>Börsengang</th>
		</tr><tr>
			<td>
					<form method=\"POST\" action=\"\">
						Anzahl Aktien: <input title=\"Empfohlen: ".ceil($plr["ia"]/5000)." Stück zu je 5000&euro;\" type=\"text\" size=\"15\" name=\"bgang\" value=\"".ceil($plr["ia"]/5000)."\"> Stück &rArr;
						<input type=\"submit\" size=\"15\" value=\"AG werden\"></p>
					</form>
			</td>
		</tr></table><br>";
	} else echo "<br><br>";

	$r_ownshare = $db->query("SELECT * FROM shares WHERE forsale=1");
	while($ownshare = $r_ownshare->fetch_array(MYSQLI_ASSOC)) {
		drawShare($ownshare,false);
	}
	$plr = getPlayer($_SESSION["id"]);
	drawNewGuthaben(number_format($plr["konto"],2,".",""));

	$db->Close();
}

function messageCenter($playerid) {
	$db = makeConn();

	//print_r($_POST);

	if(isset($_POST["text"])) {
		if($_POST["toid"]>0)
			sendMessage($_SESSION["id"],$_POST["toid"],$_POST["title"],$_POST["text"],3);
		else {
			$result = $db->query("SELECT id FROM player");
			while($toid = $result->fetch_array(MYSQL_NUM))
				sendMessage($_SESSION["id"],$toid[0],$_POST["title"],$_POST["text"],3);
		}
		echo "Nachricht \"".$_POST["title"]."\" Gesendet!";
	}
	$title = "Kein Betreff";
	$text = "";
	$toid = 0;
	if($result = $db->query("SELECT * FROM messages WHERE 1 ORDER BY state DESC, time DESC")) {
		while($msg = $result->fetch_array(MYSQLI_ASSOC)) {
			switch ($_POST["do".$msg["id"]]) {
				case 2:
					$title = $msg["title"];
					$text = ">".str_replace("<br>","\n>",$msg["text"])."\n\n";
					$toid = $msg["fromid"];
					break;
				case 3:
					$query = "DELETE FROM messages WHERE id=".$msg["id"];
					$db->query($query) or die("Error during messages delete: ".$query);
					echo "Nachricht ".$msg["id"]." gelöscht.";
					break;
				case 4:
					$query = "DELETE FROM messages WHERE toid=".$msg["toid"]." AND state=".$msg["state"];
					$db->query($query) or die("Error during message delete: ".$query);
					echo "Alle Nachrichten vom Typ ".$msg["state"]." gelöscht.";
					break;
				case 5:
					$query = "DELETE FROM messages WHERE toid=".$msg["toid"]." AND state=".$msg["state"]." AND title='".$msg["title"]."'";
					$db->query($query) or die("Error during message delete: ".$query);
					echo "Alle gelesenen Nachrichten mit Betreff \"".$msg["title"]."\" gelöscht.";
					break;
			}
		}
	} else echo "Es konnten keine Nachrichten geladen werden.";

	echo "<form id=\"send\" action=\"\" method=\"POST\" target=\"_top\">";
	echo "<p><select name=\"toid\">";
	$result = $db->query("SELECT id,name FROM player ORDER BY name");
	while($plr = $result->fetch_array(MYSQLI_ASSOC)) {
		echo "\n<option value=\"".$plr["id"]."\"";
		if($toid == $plr["id"])
			echo " selected";
		echo ">".$plr["name"]."</option>";
	}
	echo "\n<option value=\"0\">Alle</option>";
	echo "</select> - <input type=\"text\" size=\"40\" name=\"title\" onClick=\"this.focus(); this.select();\" value=\"$title\"></p>";
	echo "<textarea name=\"text\" cols=\"60\" rows=\"6\">$text</textarea>";
	echo "<p><input type=\"submit\" value=\"senden\"></p>";
	echo "</form>";

	echo "<table class=\"overview\">
			<tr>
				<th>Ordner</th>
				<td class=\"".(($_GET["view"]==1 || !isset($_GET["view"]))?"mod2 ":"")."clickable\" onClick=\"location.href='game.php?messenger&view=1'\">Posteingang</td>
				<td class=\"".(($_GET["view"]==2)?"mod2 ":"")."clickable\" onClick=\"location.href='game.php?messenger&view=2'\">Gesendet</td></tr>
		</table>";

	if($_GET["view"]==1 || !isset($_GET["view"]))
		$result = $db->query("SELECT * FROM messages WHERE toid=".$playerid." ORDER BY time DESC, state DESC");
	else
		$result = $db->query("SELECT * FROM messages WHERE fromid=".$playerid." ORDER BY time DESC, state DESC");
	echo "<table class=\"overview\">";
	while($msg = $result->fetch_array(MYSQLI_ASSOC)) {
		$c++;
		echo "<tr".(($c%2==0)?" class=\"mod2\" ":"")."><td>";
		echo date("d.m.Y G:i:s",$msg["time"]);
		echo "</td><td";
		switch($msg["state"]) {
			case 5:
				echo " style=\"color:#ff0;\"";
				break;
			case 4:
				echo " style=\"color:#44f;\"";
				break;
			case 3:
				echo " style=\"color:#4f4;\"";
				break;
		}
		echo ">";
		if($_GET["view"]==1 || !isset($_GET["view"]))
			if($msg["fromid"]>0) {
				$sender = getPlayer($msg["fromid"]);
				echo $sender["name"];
			} else echo "System";
		else {
			$reciever = getPlayer($msg["toid"]);
			echo $reciever["name"];
		}

		if($msg["seen"]==0)
			echo ": <b>\"".$msg["title"]."\"</b>";
		else
			echo ": \"".$msg["title"]."\"";
		echo "</td><td>";
		echo "<form onChange=\"document.getElementById('doit".$msg["id"]."').submit();\"  id=\"doit".$msg["id"]."\" action=\"\" method=\"POST\" target=\"_top\">";
		echo "<select name=\"do".$msg["id"]."\">";
		echo "<option>Nachricht..</option>";
		echo "<option class=\"clickable\" value=\"2\">..beantworten</option>";
		echo "<option class=\"clickable\" value=\"3\">..löschen</option>";
		if($_GET["view"]==1 || !isset($_GET["view"])) {
			echo "<option class=\"clickable\" value=\"4\">..Typ löschen</option>";
			echo "<option class=\"clickable\" value=\"5\">..mit diesem Betreff löschen</option>";
		}
		echo "</select>";
		echo "</form>";
		echo "</td></tr><tr".(($c%2==0)?" class=\"mod2\" ":"")."><td colspan=3 style=\"max-height:50px; width:400px; overflow:auto;\">";
		echo $msg["text"];
		echo "</td></tr>\n";
	}
	echo "</table>";
	if($_GET["view"]==1 || !isset($_GET["view"]))
		$db->query("UPDATE messages SET seen=1 WHERE toid=".$playerid);
	$db->Close();
}

function drawBar($design,$width,$max,$value) {
	if($max>0) {
		$images = array(array("img/balkenr1.png","img/balkenr2.png","img/balkenr3.png"),
						array("img/balkeng1.png","img/balkeng2.png","img/balkeng3.png"),
						array("img/balkenb1.png","img/balkenb2.png","img/balkenb3.png"));
		echo "<img src=\"".$images[$design][0]."\">";
		$drawmax = $value*($width-6);
		$drawmax /= $max;
		echo "<img src=\"".$images[$design][1]."\" style=\"height:16px; width:".$drawmax."px;\">";
		echo "<img src=\"".$images[$design][2]."\">";
	}
}

function overview($who,$worldid) {
	function sortplr($a,$b) {
		$cobpa = citiesOwnedByPlayer($worldid,$a);
		$cobpb = citiesOwnedByPlayer($worldid,$b);
		if($cobpa>$cobpb)
			return -1;
		else if($cobpa<$cobpb)
			return 1;
		else
			return 0;
	}

	$db = makeConn();
	$ids = array();
	if($who<1 && $result = $db->query("SELECT id FROM player ORDER BY ia DESC")) {
		while($obj = $result->fetch_array())
			$ids[] = $obj[0];

		usort($ids,"sortplr");

	} elseif($result = $db->query("SELECT id FROM grounds WHERE ownerid=$who ORDER BY (SELECT name FROM cities WHERE hex=grounds.city) ASC, state DESC")) {
		while($obj = $result->fetch_array())
			$ids[] = $obj[0];
	}
	$c = 0;
	if($who<1) {
		echo "<table class=\"overview\">\n";
		echo "\t<tr>
				<th class=\"titleOnly\" title=\"Platz\">n</th>
				<th>Logo</th>
				<th>Restaurant</th>
				<th>Inhaber</th>
				<th class=\"titleOnly\" title=\"Städte mit 80% der Kunden\">S</th>
				<th class=\"titleOnly\" title=\"Kunden\">K</th>
				<th class=\"titleOnly\" title=\"Kurs (Investment Approach)\">IA</th>
				<th class=\"titleOnly\" title=\"An- /Abwesend seit..\">on</th>
				\t</tr>\n";
		foreach($ids as $id) {
			$c++;
			$plr = getPlayer($id);

			$result = $db->query("SELECT SUM(customers) FROM grounds WHERE customers>0 AND ownerid=".$id);
			$cust = $result->fetch_array(MYSQLI_NUM);
			$result = $db->query("SELECT COUNT(*) FROM grounds WHERE state=2 AND ownerid=".$id);
			$filanz = $result->fetch_array(MYSQLI_NUM);

			if(date("z",(time()-$plr["lastdeed"]-60*60))<=60) {
				echo "\t<tr class=\"".(($c%2 == 0) ? "mod2 " : "")."clickable\" title=\"Click to go show..\" onClick=\"window.location.href='game.php?ov=$id'\">
					<th>$c</th>";
				if($plr["logo"]!="")
					echo "<td class=\"logo\"><img src=\"".$plr["logo"]."\" alt=\"\"></td>";
				else
					echo "<td>/</td>";
				echo "<td style=\"width:110px; overflow:auto;\">".$plr["kette"]."</td>
					<td>".$plr["name"]."</td>";
				echo "<td>".citiesOwnedByPlayer($worldid,$plr["id"])."</td>";
				if($filanz[0]==0)
					$filanz[0]=0;
				echo "<td class=\"number titleOnly\" title=\"".$filanz[0]." Filialen".(($filanz[0]==0) ? "" : " mit je ca. ".number_format($cust[0]/$filanz[0],2,",",""))." Kunden\">".number_format($cust[0],0,","," ")."</td>";
				echo "<td>";
				drawFinancialTendency($plr["id"]);
				echo "</td>";
				echo "<td class=\"number titleOnly\"
						style=\"
							color:#"
							.(($plr["lastlogin"]>$plr["lastlogout"])
								? "4d4"
								: "444").";
							text-align:right;
							font-size:7pt;\"
						title=\"
							Inaktiv seit ".
							(($plr["lastdeed"]==0)
								? "Beginn der Aufzeichnung"
								: date("z, G:i",(time()-$plr["lastdeed"]-60*60)))
						."\">"
							.(($plr["lastlogin"]==0)
							? "nie"
							: date("z, G:i",(time()-$plr["lastlogin"]-60*60)))
						."<br>";
				if($plr["lastlogin"]>$plr["lastlogout"])
					echo date("z, G:i",(time()-$plr["lastdeed"]-60*60));
				echo "</td>\t</tr>\n";
			}
		}
	} else {
		if($who == $_SESSION["id"])
		{	echo "<table class=\"overview\"><tr><th>";
		  	drawTipp();
			echo "</th></tr></table>";
		}

		$plr = getPlayer($who);
		drawIADiagr($who,500);

		if($who == $_SESSION["id"])
		{	drawFinancialDiagr($who,500);
			drawBilance($who);
		}

		$cityarray = array();
		$statearray = array();

		foreach($ids as $id) {
			if($result = $db->query("SELECT * FROM grounds WHERE id=$id")) {
				while($gnd = $result->fetch_array(MYSQLI_ASSOC)) {
					if(!in_array($gnd["city"],$cityarray))
						$cityarray[] = $gnd["city"];
				}
			}
		}

		if(!isset($_POST["city"])/* || !in_array($gnd["city"],$cityarray)*/)
			$_POST["city"]=0; //notwendig?

		foreach($ids as $id) {
			$query = "SELECT * FROM grounds WHERE id=$id";
			if($_POST["city"]!=0)
				$query .= " AND city='".$_POST["city"]."'";
			if($result = $db->query($query)) {
				while($gnd = $result->fetch_array(MYSQLI_ASSOC)) {
					if(!in_array($gnd["state"],$statearray))
						$statearray[] = $gnd["state"];
				}
				if(!isset($_POST["state"])) {
					$_POST["state"]=0; //alles
				}
			}
		}
		if(count($statearray)>0) {
			echo "<form id=\"filter\" action=\"\" method=\"POST\" target=\"_top\">";
			echo "Zeige ".$plr["name"]."'s ";
			echo "\n<select name=\"state\">";
			foreach($statearray as $state) {
				echo "\n\t<option onClick=\"document.getElementById('filter').submit();\" ".(($state==$_POST["state"]) ? "selected " : "")."value=\"$state\">";
				switch($state) {
					case 1:
						echo "Grundstücke";
						break;
					case 2:
						echo "Restaurants";
						break;
					case 3:
						echo "Parkplätze";
						break;
					case 4:
						echo "Grünanlagen";
						break;
					case 5:
						echo "kl. Wohnhaus";
						break;
					case 6:
						echo "gr. Wohnhaus";
						break;
				}
				echo "</option>";
			}
			echo "\n\t<option onClick=\"document.getElementById('filter').submit();\" ".(($_POST["state"]==0 || !isset($_POST["state"])) ? "selected " : "")."value=\"0\">Alles</option>";
			echo "</select>";
			echo " in ";
			echo "<select name=\"city\">";
			foreach($cityarray as $ctyid) {
				$cty = getCity($worldid,$ctyid);
				echo "<option onClick=\"document.getElementById('filter').submit();\" value=\"".$cty["hex"]."\" ".($cty["hex"]==$_POST["city"] ? "selected " : "").">".$cty["name"]."</option>";
			}
			echo "<option onClick=\"document.getElementById('filter').submit();\" value=\"0\" ".(0==$_POST["city"] ? "selected " : "").">der ganzen Welt</option>";
			echo "</select>";
			echo "</form>";

			$s_cust = 0;
			$s_ingo = 0;
			echo "<table class=\"overview\">\n";
			echo "\t<tr><th colspan=\"2\"><img style=\"vertical-align:middle;\" class=\"logo\" src=\"".$plr["logo"]."\"> <b>".$plr["kette"]."</b></th>
					<th colspan=\"2\"> Inhaber: ".$plr["name"]."</th></tr>";
			echo "\t<tr>
					<th>&cong;</th>
					<th>Stadt</th>
					<th>Gewinn</th>
					<th>Kunden</th>
					\t</tr>\n";
			foreach($ids as $id) {
				if($_POST["state"]==0)
					$query = "SELECT * FROM grounds WHERE id=$id AND state>0";
				else
					$query = "SELECT * FROM grounds WHERE id=$id AND state=".$_POST["state"];
				if($_POST["city"]!=0)
					$query .= " AND city='".$_POST["city"]."'";

				if($result = $db->query($query)) {
					while($gnd = $result->fetch_array(MYSQLI_ASSOC)) {
						$c++;
						$s_ingo += $gnd["ingo"]-$gnd["energycost"]-$gnd["pays"];
						$s_cust += $gnd["customers"];
						$cty = getCity($worldid,$gnd["city"]);
						echo "\t<tr class=\"".(($c%2 == 0) ? "mod2 " : "")."clickable\" title=\"Click to go to..\" onClick=\"window.location.href='game.php?city=".$cty["hex"]."&hex=".$gnd["hex"]."&x=".$gnd["x"]."&y=".$gnd["y"]."'\">";
						echo "<td style=\"height:32px; padding-left:32px; background-position: left center; background-repeat: no-repeat; ";
						if($gnd["ownerid"]==$_SESSION["id"])
							switch($gnd["state"]) {
								case 1:
									echo "background-image: url(img/II.png);";
									break;
								case 2:
									echo "background-image: url(img/IJ.png);";
									break;
								case 3:
									echo "background-image: url(img/IL.png);";
									break;
								case 4:
									echo "background-image: url(img/GI.png);";
									break;
								case 5:
									echo "background-image: url(img/IN.png);";
									break;
								case 6:
									echo "background-image: url(img/IP.png);";
									break;
							}
						else
							switch($gnd["state"]) {
								case 1:
									echo "background-image: url(img/IH.png);";
									break;
								case 2:
									echo "background-image: url(img/IK.png);";
									break;
								case 3:
									echo "background-image: url(img/IM.png);";
									break;
								case 4:
									echo "background-image: url(img/GI.png);";
									break;
								case 5:
									echo "background-image: url(img/IO.png);";
									break;
								case 6:
									echo "background-image: url(img/IQ.png);";
									break;
							}
						echo " text-align:center; color:#ffaa00;\">";
						if($gnd["stars"]>0) {
							for($i=0; $i<$gnd["stars"]; $i++) {
								echo "&#x2605;";
							}
						} else echo "&ensp;";
						echo "</td>";
						echo "<td>".$cty["name"]."</td>";
						$gewinn = $gnd["ingo"]-$gnd["energycost"]-$gnd["pays"];
						if($_SESSION["id"]==$who) // spionage
							echo "<td style=\"text-align:right;".(($gewinn<0) ? " color:#f44" : "")."\">".number_format($gewinn,0,","," ")."&euro;</td>";
						else
							echo "<td style=\"text-align:right;\">~?~</td>";
						echo "<td class=\"titleOnly\" style=\"text-align:right;";
						$custq = $gnd["customers"]/getMaxCustOfGround($gnd);
						if($custq > 0.66)
							echo "color: #4f4;";
						else if($custq > 0.33)
							echo "color: #ff4;";
						else
							echo "color: #f44;";
						echo "\" title=\"".$gnd["customers"]."/".getMaxCustOfGround($gnd)."\">".number_format($gnd["customers"],0,","," ")."</td>\t</tr>\n";
					}
				}
			}
			echo "<tr><th>&sum;</th><th colspan=\"1\">=</th><th style=\"text-align:right;\">";
			if($_SESSION["id"]==$who) // spionage
				echo number_format($s_ingo,0,","," ")."&euro;</th>";
			else
				echo "~?~";
			echo "<th style=\"text-align:right;\">".number_format($s_cust,0,","," ")."</th></tr></table>";
		}
	}

	$db->Close();
}

function furnish($worldid,$city,$hex,$what,$playerid) {
	$gnd = getGround($worldid,$city,$hex);

	if($what==null)
		if(isset($_GET["sellfixture"]))
		{	$fxt = getFixture($_GET["sellfixture"]);
			$what = $fxt["class"];
		} else
		{	$what = 1;
		}
	#alert($what);

	if($gnd["ownerid"]==$playerid) {
		echo "<table class=\"overview\">\n\t<tr>\n\t\t<th colspan=\"6\">Verkaufen (75%)</th>\n\t</tr>\n\t<tr>\n";
		echo "\t\t<th>Typ</th>\n";
		echo "\t\t<th>Anzahl</th>\n";
		echo "\t\t<th>Name</th>\n";
		echo "\t\t<th colspan=\"2\">Kunden</th>\n";
		echo "\t\t<th>P*</th>\n";
		echo "\t</tr>\n";

		$cust = 999999999999;
		if($gnd["tablesnum"]>0) {
			echo "\t<tr class=\"clickable\" onClick=\"window.location.href='game.php?city=".$gnd["city"]."&x=".$gnd["x"]."&y=".$gnd["y"]."&furnish=$hex&sellfixture=".$gnd["tablesid"]."&what=".$gnd["what"]."'\">\n\t\t<td>Tische</td>\n";
			$fxt = getFixture($gnd["tablesid"]);
			echo "\t\t<td style=\"text-align:right\">".$gnd["tablesnum"]."</td><td>".$fxt["name"]."</td>\n";
			echo "\t\t<td style=\"text-align:right\">".($gnd["tablesnum"]*$fxt["max_customers"])."</td>\n";
			echo "\t\t<td>\n";
			if($cust>$gnd["tablesnum"]*$fxt["max_customers"])
				drawBar(1,25,100,$gnd["tablesnum"]*$fxt["max_customers"]);
			echo "\t\t</td><td>".($gnd["tablesnum"]*$fxt["personal"])."</td>\n\t</tr>\n";
		} else $cust=0;
		if($gnd["kitchensnum"]>0) {
			echo "\t<tr class=\"clickable\" onClick=\"window.location.href='game.php?city=".$gnd["city"]."&x=".$gnd["x"]."&y=".$gnd["y"]."&furnish=$hex&sellfixture=".$gnd["kitchensid"]."&what=".$gnd["what"]."'\">\n\t\t<td>Küchen</td>\n";
			$fxt = getFixture($gnd["kitchensid"]);
			echo "\t\t<td style=\"text-align:right\">".$gnd["kitchensnum"]."</td><td>".$fxt["name"]."</td>\n";
			echo "\t\t<td style=\"text-align:right\">".($gnd["kitchensnum"]*$fxt["max_customers"])."</td>\n";
			echo "\t\t<td>\n";
			if($cust>$gnd["kitchensnum"]*$fxt["max_customers"])
				drawBar(1,25,100,$gnd["kitchensnum"]*$fxt["max_customers"]);
			echo "\t\t</td><td>".($gnd["kitchensnum"]*$fxt["personal"])."</td>\n\t</tr>\n";
		}else $cust=0;
		if($gnd["toiletsnum"]>0) {
			echo "\t<tr class=\"clickable\" onClick=\"window.location.href='game.php?city=".$gnd["city"]."&x=".$gnd["x"]."&y=".$gnd["y"]."&furnish=$hex&sellfixture=".$gnd["toiletsid"]."&what=".$gnd["what"]."'\">\n\t\t<td>Sanitäranlagen</td>\n";
			$fxt = getFixture($gnd["toiletsid"]);
			echo "\t\t<td style=\"text-align:right\">".$gnd["toiletsnum"]."</td><td>".$fxt["name"]."</td>\n";
			echo "\t\t<td style=\"text-align:right\">".($gnd["toiletsnum"]*$fxt["max_customers"])."</td>\n";
			echo "\t\t<td>\n";
			/*if($cust>$gnd["toiletsnum"]*$fxt["max_customers"])
				drawBar(1,25,100,$gnd["kitchensnum"]*$fxt["max_customers"]);*/
			echo "\t\t</td><td>".($gnd["toiletsnum"]*$fxt["personal"])."</td>\n\t</tr>\n";
		} else $cust=0;
		echo "</table>";

		echo getRemainingSpaceOnGround($worldid,$city,$hex)."m² an Platz sind noch frei. Maximal sind ".getMaxCustOfGround($gnd)." Kunden bedienbar<br>";

		echo "<table class=\"overview\"><tr><th colspan=\"8\">Kaufen</th></tr><tr>\n";
		echo "<th>Name</th>";
		echo "<th>";

		echo "<form style=\"margin: 0px auto; text-align:center;\">\n";
		echo "\t<select>\n";

		$db = makeConn();
		$result = $db->query("SELECT class FROM fixtures ORDER BY class ASC");
		$items = array();
		while($array = $result->fetch_array(MYSQL_NUM)) {
			if(!in_array($array[0],$items))
				$items[] = $array[0];
		}
		foreach($items as $class) {
			$bez = "";
			switch ($class) {
				case 1:
					$bez.="Tisch";
					break;
				case 2:
					$bez.="Küche";
					break;
				case 3:
					$bez.="Sanitär";
					break;
				default:
					$bez.="Mysteriös";
			}
			echo "\t\t<option ".(($class == $what) ? "selected" : "")." onClick=\"window.location.href='game.php?city=$city&x=".$_GET["x"]."&y=".$_GET["y"]."&furnish=$hex&x=".$_GET["x"]."&y=".$_GET["y"]."&what=$class'\">$bez</option>\n";
		}
		//echo "\t\t<option ".(($what == "") ? "selected" : "")." onClick=\"window.location.href='game.php?city=$city&furnish=$hex'\">Alles</option>\n";
		echo "\t</select>\n";
		echo "</form>\n";
		echo "</th>";
		echo "<th>Preis</th>";
		echo "<th title=\"Platzbedarf\">m²*</th>";
		echo "<th title=\"Bedienbare Kunden\">K*</th>";
		echo "<th title=\"Benötigtes Personal\">P*</th>";
		echo "<th title=\"Energiekosten\">E*</th>";
		echo "<th title=\"Attraktivität\">A*</th></tr>";

		$result = $db->query("SELECT * FROM fixtures WHERE class='$what'") or die("error loading fixtures #1");

		while($fxt = $result->fetch_array(MYSQLI_ASSOC)){
			echo "\t<tr class=\"clickable\" onClick=\"window.location.href='game.php?city=".$_GET["city"]."&x=".$_GET["x"]."&y=".$_GET["y"]."&furnish=$hex&buyfixture=".$fxt["id"]."&what=$what'\">\n";
			echo "\t\t<td>".$fxt["name"]."</td>\n";
			$bez = "";
			switch ($fxt["class"]) {
				case 1:
					$bez.="Tisch";
					break;
				case 2:
					$bez.="Küche";
					break;
				case 3:
					$bez.="Sanitär";
					break;
				default:
					$bez.="Mysteriös";
			}
			echo "\t\t<td>".$bez."</td>\n";
			echo "\t\t<td style=\"text-align:right\">".number_format($fxt["price"],2,",","")."&euro;</td>\n";
			echo "\t\t<td style=\"text-align:right\">".$fxt["space"]."</td>\n";
			echo "\t\t<td style=\"text-align:right\">".$fxt["max_customers"]."</td>\n";
			echo "\t\t<td style=\"text-align:right\">".$fxt["personal"]."</td>\n";
			echo "\t\t<td style=\"text-align:right\">".$fxt["energy"]."</td>\n";
			echo "\t\t<td style=\"text-align:right\">".$fxt["attr"]."</td>\n";
			echo " \t</tr>\n";
		}
		echo "</table>\n";
		echo "<p style=\"font-size:8pt; text-align:center;\">*) m²= Platzbedarf, K=Bedienbare Kunden, P=Benötigtes Personal, E=Energiekosten, A=Attraktivität</p>";
	$db->Close();
	}
}

function personel($worldid,$city,$hex,$hire,$playerid,$what) {
	$gnd = getGround($worldid,$city,$hex);

	if($what==null)
			$what = 1;

	if($gnd["ownerid"]==$playerid) {
		$db = makeConn();
		switch($_GET["what"]) {
			case 1: $bez="Kellner"; break;
			case 2: $bez="Köche"; break;
			case 0: $bez="Cleaner"; break;
		}

		echo "<table class=\"overview\">
		<tr>
			<th colspan=\"5\">Angestellte $bez</th>
		</tr>
		<tr>
			<th>Job</th>
			<th>Name</th>
			<th title\"Tüchtigkeit bzw. effektives Personal\">P*</th>
			<th title=\"Zuverlässigkeit\">Z*</th>
			<th>Lohn</th>\n
		</tr>
		<tr>\n";

		$zuverl = 0;
		$counter = 0;
		$leistung = 0;
		$payment = 0;

		$query = "SELECT * FROM hiredpersonel WHERE city='$city' AND hex='$hex' AND jobid=$what ORDER BY pay ASC";
		$result = $db->query($query) or die ("error during ".$query);
		while($pers = $result->fetch_array(MYSQLI_ASSOC)) {
			echo "<tr class=\"clickable\" title=\"".$pers["name"]." entlassen\" onClick=\"window.location.href='game.php?city=".$_GET["city"]."&x=".$_GET["x"]."&y=".$_GET["y"]."&personel=$hex&fire=".$pers["id"]."&what=$what'\">";
			switch($pers["jobid"]) {
				case 0:
					$job = "Cleaner";
					break;
				case 1:
					$job = "Kellner";
					break;
				case 2:
					$job = "Koch";
			}
			echo "<td>".$job."</td>";
			echo "<td>".$pers["name"]."</td>";
			echo "<td>".$pers["capa"]."</td>";
			$leistung += $pers["capa"];
			echo "<td>".number_format($pers["relia"]*100,0)." %</td>";
			$zuverl += $pers["relia"];
			$counter ++;
			echo "<td>".number_format($pers["pay"],2,","," ")." &euro;</td>";
			$payment += $pers["pay"];
			echo "</tr>";
		}
		if($counter>0)
			$zuverl /= $counter;

		echo "\t<tr>\n";
		echo "<th>&sum;";
		echo "</th>";
		echo "\t\t<th>P<sub>eff</sub>: ".number_format($leistung*$zuverl,2,",","")."</th>\n";
		echo "\t\t<th>$leistung</th>\n";
		echo "\t\t<th>".number_format($zuverl*100,0)." %</th>\n";
		echo "\t\t<th>".number_format($payment,2,","," ")." &euro;</th>\n";
		echo "\t</tr>\n";

		echo "</table>\n";

		echo "<table class=\"overview\">\n\t<tr>\n";
		echo "\t\t<th>Typ</th>\n";
		echo "\t\t<th>Benötigtes Personal</th>\n";
		echo "\t</tr>\n";
		$fxtids = array("toilet","table","kitchen");
		for($i=0;$i<count($fxtids);$i++) {
			echo "\t<tr class=\"".(($what == $i) ? "selected" : "clickable")."\" title=\"Kellner einstellen\" onClick=\"window.location.href='game.php?city=$city&x=".$_GET["x"]."&y=".$_GET["y"]."&personel=$hex&x=".$_GET["x"]."&y=".$_GET["y"]."&what=".($i)."'\">\n\t\t<td>";
			switch($i) {
				case 0:
					echo "Putzen";
					break;
				case 1:
					echo "Kellner";
					break;
				case 2:
					echo "Köche";
					break;
			}
			echo "</td>\n";
			$fxt = getFixture($gnd[$fxtids[$i]."sid"]);
			if($what == $i) {
				if($gnd[$fxtids[$i]."snum"]>0)
					echo "\t\t<td style=\"text-align:right;\">".number_format($leistung*$zuverl,2,",","")."/".($gnd[$fxtids[$i]."snum"]*$fxt["personal"])."</td>\n\t</tr>\n";
				else
					echo "\t\t<td style=\"text-align:right; color:#d44;\"> 0/".number_format($leistung*$zuverl,2,",","")." </td>\n\t</tr>\n";
			} else {
				if($gnd[$fxtids[$i]."snum"]>0)
					echo "\t\t<td style=\"text-align:right;\">".($gnd[$fxtids[$i]."snum"]*$fxt["personal"])."</td>\n\t</tr>\n";
				else
					echo "\t\t<td style=\"text-align:right; color:#d44;\"> 0 </td>\n\t</tr>\n";
			}
		}
		echo "</table>";


		echo "<table class=\"overview\">\n";
		echo "\t<tr>\n";
		echo "\t\t<th colspan=\"5\">Bewerber</th>\n";
		echo "\t</tr>\n";
		echo "\t<tr>\n";
		echo "\t\t<th>Name</th>\n";
		echo "\t\t<th title=\"Tüchtigkeit bzw effektives Personal\">P*</th>\n";
		echo "\t\t<th title=\"Zuverlässigkeit\">Z*</th>\n";
		echo "\t\t<th>Lohn</th>\n";
		echo "\t\t<th>&rArr;P<sub>eff</sub></th>\n";
		echo "\t</tr>\n";

		$result = $db->query("SELECT * FROM choosablepersonel WHERE city='$city' AND jobid=$what ORDER BY pay ASC")
			or die("error loading employees #2");
		while($pers = $result->fetch_array(MYSQLI_ASSOC)) {
			echo "<tr class=\"clickable\" title=\"".$pers["name"]." einstellen\" onClick=\"window.location.href='game.php?city=".$_GET["city"]."&x=".$_GET["x"]."&y=".$_GET["y"]."&personel=$hex&hire=".$pers["id"]."&what=$what'\">";
			echo "<td>".$pers["name"]."</td>";
			echo "<td>".$pers["capa"]."</td>";
			echo "<td>".number_format($pers["relia"]*100,0)." %</td>";
			echo "<td>".number_format($pers["pay"],2,","," ")." &euro;</td>";
			echo "<td>".number_format(($leistung+$pers["capa"])*($zuverl*$counter+$pers["relia"])/($counter+1),2,","," ")."</td>";
			echo "</tr>";
		}
	}
	echo "</table>\n";
	echo "<p style=\"font-size:8pt; text-align:center;\">*) P=Personal=T&uuml;chtigkeit, Z=Zuverl&auml;ssigkeit</p>";
	$db->Close();
}

function drawIADiagr($playerid,$width) {
$db = makeConn();
	$plr = getPlayer($playerid);
	if($result = $db->query("SELECT ia FROM statements WHERE playerid=$playerid ORDER BY ia DESC LIMIT 1"))
		$iamax = $result->fetch_array(MYSQLI_NUM);
	if($result = $db->query("SELECT ia FROM statements WHERE playerid=$playerid ORDER BY ia ASC LIMIT 1"))
		$iamin = $result->fetch_array(MYSQLI_NUM);
	if ($iamax[0] == $iamin[0])
		$iamax[0] = $iamin[0]+1;
	$ias = array();
	$times = array();
	if($result = $db->query("SELECT konto,time,ia FROM statements WHERE playerid=$playerid ORDER BY id DESC,time DESC") or die("No player ".$playerid))
		while($stm = $result->fetch_array(MYSQLI_NUM)) {
			$times[] = $stm[1];
			$ias[] = $stm[2];
		}
	$imax = count($ias);
	if($imax > 1) {
		$average = array_sum($ias)/count($ias);
		echo "<table class=\"diagram\"><tr><th>Börsenwert \"".$plr["kette"]."\"</th></tr>\n";
		echo "\t<tr><td>";
		for($i=$imax-2; $i>=0; $i--) {
			echo "<div class=\"titleOnly point\"
					title=\"".date("d.m.Y G:i:s",$times[$i])."&rArr; ".number_format($ias[$i],2,","," ")."\"
					style=\"width:".(($width-2)/($imax-1))."px; background-position:5px ".round(50-50*($ias[$i]-$iamin[0])/($iamax[0]-$iamin[0]))."px;\">".
					(($i%5 == 0) ? -$i : "&nbsp;").
					"</div>
					";
		}
		echo "</th></tr>";
		echo "</table>";
	}
}

function drawFinancialDiagr($playerid,$width) {
$db = makeConn();
	$plr = getPlayer($playerid);
	if($result = $db->query("SELECT konto FROM statements WHERE playerid=$playerid ORDER BY konto DESC LIMIT 1"))
		$kontomax = $result->fetch_array(MYSQLI_NUM);
	if($result = $db->query("SELECT konto FROM statements WHERE playerid=$playerid ORDER BY konto ASC LIMIT 1"))
		$kontomin = $result->fetch_array(MYSQLI_NUM);
	if ($kontomax[0] == $kontomin[0])
		$kontomax[0] = $kontomin[0]+1;
	$kontos = array();
	$times = array();
	if($result = $db->query("SELECT konto,time,ia FROM statements WHERE playerid=$playerid ORDER BY id DESC,time DESC") or die("No player ".$playerid))
		while($stm = $result->fetch_array(MYSQLI_NUM)) {
			$times[] = $stm[1];
			$kontos[] = $stm[0];
		}
	$imax = count($kontos);
	if($imax > 1) {
		$average = array_sum($kontos)/count($kontos);
		echo "<table class=\"diagram\"><tr><th>Kontostand \"".$plr["kette"]."\"</th></tr><tr><td>\n";
		for($i=$imax-2; $i>=0; $i--) {
			echo "<div class=\"titleOnly\"
					title=\"".date("d.m.Y G:i:s",$times[$i])."&rArr; ".number_format($kontos[$i],2,","," ")."&euro;\"
					style=\"width:".(($width-2)/($imax-1))."px;
							padding-top:55px;
							float:left;
							font-size:6pt;
							text-align:center;
							overflow:hidden;
							";
			if($kontos[$i]>0)
				echo "background-image:url('img/whitedot.png');";
			else
				echo "background-image:url('img/reddot.png');";
			echo "				background-repeat:repeat-x;
							background-position:5px ";
			echo " ".round(50-50*($kontos[$i]-$kontomin[0])/($kontomax[0]-$kontomin[0]))."px;\">".(($i%5 == 0) ? -$i : "&nbsp;")."</div>";
		}
		echo "</td></tr>";
		echo "</table>";
	}
}

function drawBilance($playerid) {
	$db = makeConn();

	$plr = getPlayer($playerid);

	if($result = $db->query("SELECT konto FROM statements WHERE playerid=$playerid ORDER BY time DESC LIMIT 2")) {
		echo "<table class=\"overview\"><tr><th colspan=\"2\">Bilanz von ".$plr["name"]."'s \"".$plr["kette"]."\"</th></tr>\n";
		echo "\t<tr><td>Alter Kontostand</td>\n";
		echo "\t\t<td style=\"text-align:right;\">";
		if(!$konto2 = $result->fetch_array(MYSQLI_NUM))
			$konto2[0] = 1500000;
		if(!$konto1 = $result->fetch_array(MYSQLI_NUM))
			$konto1[0] = 1500000;
		echo number_format($konto1[0],2,","," ");
		echo " &euro;</td></tr>\n";
		echo "\t<tr><td>Ertrag</td>\n";
		echo "\t\t<td style=\"text-align:right;\">";
		if($plr["ingo"]>0) echo "+";
		echo number_format($plr["ingo"],2,","," ");
		echo " &euro;</td></tr>";
		echo "\t<tr><td>Zinsen</td>\n";
		echo "\t\t<td style=\"text-align:right;\">";
		if($plr["zinsen"]>0) echo "+";
		echo number_format($plr["zinsen"],2,","," ");
		echo " &euro;</td></tr>\n";
		echo "\t<tr><td>Personalkosten</td>\n";
		echo "\t\t<td style=\"text-align:right;\">";
		echo number_format(-$plr["pays"],2,","," ");
		echo " &euro;</td></tr>\n";
		echo "\t<tr><td>Energiekosten</td>\n";
		echo "\t\t<td style=\"text-align:right;\">";
		echo number_format(-$plr["energycost"],2,","," ");
		echo " &euro;</td></tr>\n";
		echo "\t<tr><td>Zwischensumme</td>\n";
		echo "\t\t<td style=\"text-align:right; ";
		$gewinn = $plr["ingo"]+$plr["zinsen"]-$plr["pays"]-$plr["energycost"];
		if($gewinn>0) echo "color:#4d4;\">+";
		else echo "color:#d44;\">";
		echo number_format($gewinn,2,","," ");
		echo " &euro;</td></tr>\n";
		echo "\t<tr><td>Sonstige Kosten</td>\n";
		echo "\t\t<td style=\"text-align:right;\">";
		echo number_format(-$plr["others"],2,","," ");
		echo " &euro;</td></tr>\n";
		echo "\t<tr><td>Summe</td>\n";
		echo "\t\t<td style=\"text-decoration:underline; text-align:right; ";
		$gewinn = $konto2[0]-$konto1[0];
		if($gewinn>0) echo "color:#4d4;\">+";
		else echo "color:#d44;\">";
		echo number_format($gewinn,2,","," ");
		echo " &euro;</td></tr>\n";
		echo "\t<tr><td>Neuer Kontostand</td>\n";
		echo "\t\t<td style=\"text-decoration:underline; text-align:right;\">";
		echo number_format($konto2[0],2,","," ");
		echo " &euro;</td></tr>\n";
		echo "</table>";
	}
	$db->Close();
}

function drawPrice4FoodMenu($gnd) {
	echo "<select title=\"Preis auswählen\">\n\t\n";
	for($i=1; $i<6; $i++) {
		echo "<option";
		if($gnd["price4food"]==$i) {
			echo " selected";
		}
		echo " class=\"clickable\" onClick=\"window.location.href='game.php?city=".$_GET["city"]."&x=".$_GET["x"]."&y=".$_GET["y"]."&setprice=".$gnd["hex"]."&what=".$i."'\">".number_format($i,2,","," ");
		echo " &euro;</option>\t\t\n";
	}
	echo "\t\n</select>";
}

function drawKickSomeAssMenu($worldid,$city,$hex) {
	$cty = getCity($worldid,$city);
	$gnd = getCity($worldid,$city,$hex);
	echo "<select>\n";
	echo "\t<option selected>--Bel&auml;stigen--</option>\n";
	echo "\t<option onClick=\"window.location.href='game.php?city=".$_GET["city"]."&kicksome=$hex&x=".$_GET["x"]."&y=".$_GET["y"]."&what=1'\">Grafitti (500&euro;,5KE)</option>\n";
	echo "\t<option onClick=\"window.location.href='game.php?city=".$_GET["city"]."&kicksome=$hex&x=".$_GET["x"]."&y=".$_GET["y"]."&what=2'\">Gerücht (10KE)</option>\n";
	echo "\t<option onClick=\"window.location.href='game.php?city=".$_GET["city"]."&kicksome=$hex&x=".$_GET["x"]."&y=".$_GET["y"]."&what=5'\">Hygiene&Kritiker (20KE)</option>\n";
	echo "\t</select><br>\n";
	echo "<a href=\"http://psprince2.ps.funpic.de/forum/index.php?topic=46.0\" target=\"_blank\"><u>Diskutiere im Forum</u></a> über Attacken, die eingeführt werden sollten!";
}

function drawFinancialTendency($playerid) {
	$db = makeConn();
	echo "<span class=\"titleOnly\"style=\"";
	if($r_statement = $db->query("SELECT * FROM statements WHERE playerid=".$playerid." ORDER BY time DESC LIMIT 2")) {
		$ia = array();
		while($statement = $r_statement->fetch_array(MYSQLI_ASSOC)) {
			$ia[] = $statement["ia"];
		}
		if($ia[0] > $ia[1]) echo "color:#4d4;\" title=\"".number_format($ia[1],2,","," ")."&euro;\">+";
			else echo "color:#d44;\" title=\"".number_format($ia[1],2,","," ")."&euro;\">";
	} else echo "color:#ddd;\" title=\"".number_format($ia[1],2,","," ")."&euro;\">0,00";
	if($ia[1]==0)
		$ia[1]=1;
	echo number_format(100*($ia[0]-$ia[1])/$ia[1],2,","," ")."%</span>";
	$db->Close();
}

function drawIAMarquee($worldid) {
	$db = makeConn();
	echo "<marquee scrollamount=5 scrolldelay=100>&bull; ";
	$r_comp = $db->query("SELECT company FROM shares WHERE forsale=0 ORDER BY count");
	while($company = $r_comp->fetch_array(MYSQLI_NUM)) {
		$plr = getPlayer($company[0]);
		echo $plr["kette"].": ";
		drawFinacnialTenency($company[0]);
		echo " &bull; ";
	}
	echo "</marquee>";
	$db->Close();
}

function drawTipp() {
	$tipps = array(
		"Falls du Fragen über den Spielablauf oder andere Fragen hast, schau zuerst im FAQ nach, ob deine Frage bereits beantwortet wurde. Wenn nicht, kannst du selber eine Frage stellen! Jeder Spieler kann Antworten schreiben. Der Admin kann diese Editieren.",
		"Um auf den Aktuellen Stand der diskutierten Spieländerungen zu bleiben, oder selbst Änderungen vorzuschlagen: Geht ins <a class=\"withIconLeft blank\" href=\"http://psprince2.ps.funpic.de/forum/index.php\" target=\"_blank\">Forum</a>!",
		"Fang klein an! Hilfe findest du in der <a href=\"faq/faq.php\" target=\"_blank\">FAQ</a>",
		"Wenn du zu wenig Geld hast: Versuche Aktien zu verkaufen!",
		"Jede Runde bekommst du 1 KE bis du 35 KE hast. Manchmal kannst du aber auch im Glücksspiel 5 KE auf einmal gewinnen!"
	);
	$plr = getPlayer($_SESSION["id"]);
	if($plr["ingo"]==0)
		$r=1;
	else if($plr["konto"]<=50000)
		$r=2;
	else if($plr["deeds"]<10)
		$r=3;
	else
		$r = rand(0,count($tipps)-1);
	echo "Tip #".($r+1).":<br>".$tipps[$r];
}

function alert($text) {
	echo "<script type=\"text/javascript\">alert(\"";
	echo $text;
	echo "\");</script>";
}

function prompt($frage,$url) {
	echo "javascript:if(confirm('$frage')){location.href='$url';}";
}

function secondNoticeBar($text) {
	echo "<script type=\"text/javascript\">
		document.getElementById('topnotice').innerHTML='$text';
		document.getElementById('topnotice').style.top=0;
		document.getElementById('topnotice').style.visibility = \"visible\";
		window.setTimeout(\"hide('topnotice',0,0,-25,-0.01)\",5555);
		</script>";
}

function drawNewGuthaben($kontoneu) {
	echo "<script type=\"text/javascript\">drawGuthaben($kontoneu);</script>";
}

function drawNewKE($keneu) {
	echo "<script type=\"text/javascript\">drawKE($keneu);</script>";
}

function drawLose() {
	$db = makeConn();
	if(isset($_GET["los"])) {
		$plr = getPlayer($_SESSION["id"]);
		if($plr["konto"]>=5000) {
			if($plr["deeds"]>=1) {
				$db->query("UPDATE player SET konto=konto-5000, outgo=outgo+1, deeds=deeds-1 WHERE id=".$_SESSION["id"]);
				$db->query("UPDATE lose SET jackpot=jackpot+5000");
				$r = rand(0,20); //20.. ~10*5000 = 50000?
				switch($r) {
					case 0:
						$r_jp = $db->query("SELECT jackpot FROM lose");
						$jp = $r_jp->fetch_array(MYSQLI_NUM);
						$db->query("UPDATE lose SET jackpot=0, lastwinner=".$_SESSION["id"]);
						$db->query("UPDATE player SET konto=konto+".$jp[0].", outgo=outgo-".$jp[0].", deeds=deeds+6 WHERE id=".$_SESSION["id"]);
						$plr = getPlayer($_SESSION["id"]);
						secondNoticeBar("Jackpot! ".number_format($jp[0],2,","," ")."&euro; und 5KE");
						break;
					case 1:
						$r_jp = $db->query("SELECT jackpot FROM lose");
						$jp = $r_jp->fetch_array(MYSQLI_NUM);
						if($jp[0]>=15000) {
							$db->query("UPDATE lose SET jackpot=jackpot-15000");
							$db->query("UPDATE player SET konto=konto+15000, outgo=outgo-15000, deeds=deeds+1 WHERE id=".$_SESSION["id"]);
							$plr = getPlayer($_SESSION["id"]);
							secondNoticeBar("Small Pot! 15 000&euro;");
						}
						break;
					case 2:
						$db->query("UPDATE player SET deeds=deeds+6 WHERE id=".$_SESSION["id"]);
						$plr = getPlayer($_SESSION["id"]);
						secondNoticeBar("Small Pot! 5KE");
						break;
					default:
						secondNoticeBar("Niete!");
				}
			} else alert("Glücksspiele sind verboten! Nicht genug Kriminelle Energie!");
		} else alert("Nicht genug Geld um ein Los kaufen zu können!");
	}
	$plr = getPlayer($_SESSION["id"]);
	drawNewGuthaben($plr["konto"]);
	drawNewKE($plr["deeds"]);
	$r_jp = $db->query("SELECT jackpot FROM lose");
	if($jp = $r_jp->fetch_array(MYSQLI_NUM)) {
		if($plr["deeds"]>=1) {
			echo "Verbotenes Glücksspiel:<br>Jeder 7te kann gewinnen!<br>Jackpot: <b>".number_format($jp[0],0,","," ")."&euro;</b>!<br>
					<a href=\"game.php?los\" class=\"withIconLeft buy\" target=\"_top\">Ein Los kaufen</a> (5000&euro;, 1KE)";
		} else echo "Jackpot: ".number_format($jp[0],0,","," ")."&euro;!";
	}
	$r_lw = $db->query("SELECT lastwinner FROM lose LIMIT 1");
	if($lw = $r_lw->fetch_array(MYSQLI_NUM)) {
		if($lw[0]>0) {
			$plr = getPlayer($lw[0]);
			echo "<br>Letzter Gewinner: <a class=\"withIconRight journal\" href=\"game.php?ov=".$lw[0]."\" target=\"_top\">".$plr["name"]."</a>";
		}
	}
	$db->Close();
}

function getNumberOfMessages($playerid) {
	$db = makeConn();
	if($result = $db->query("SELECT COUNT(*) FROM messages WHERE toid=".$playerid)) {
		$all = $result->fetch_array(MYSQLI_NUM);
		if($result = $db->query("SELECT COUNT(*) FROM messages WHERE seen=0 AND toid=".$playerid)) {
			$new = $result->fetch_array(MYSQLI_NUM);
		}
		if($new[0]>0)
			echo "<b>".$new[0]."</b> ungel. von ".$all[0];
		else
			echo $new[0]." ungel. von ".$all[0];
	}
	$db->Close();
}

function drawHighscore() {
	$db = makeConn();

	echo "<table class=\"overview\"><th title=\"Runde\">#</th><th>Spieler</th><th>Spielzeit</th></tr>";
	$r_hs2 = $db->query("SELECT * FROM highscore2 WHERE 1 ORDER BY round DESC");
	$mod2=0;
	while($hs2 = $r_hs2->fetch_array(MYSQL_ASSOC)) {
		if($hs2["playerid"]>0)
			$plr = getPlayer($hs2["playerid"]);
		else
			$plr["name"] = "Niemand";
		echo "<tr".(($mod2%2==0) ? " class=\"mod2\"" : "")."><th>".$hs2["round"]."</th><td>".$plr["name"]."</td><td>".date("z,G:i:s",$hs2["playtime"])."</td></tr>";
		$mod2++;
	}
	echo "</table>";


	echo "<table class=\"overview\"><tr><th title=\"Runde\">#</th><th>Kategorie</th><th>Spieler</th><th>Wert</th>";
	$r_hs = $db->query("SELECT * FROM highscore WHERE 1 ORDER BY round DESC");
	$mod2=0;
	while($hs = $r_hs->fetch_array(MYSQL_ASSOC)) {
		echo "</tr><tr".(($mod2%2==0) ? " class=\"mod2\"" : "")."><th rowspan=\"9\">".$hs["round"]."</th>";
		$plr = getPlayer($hs["iaid"]);
		echo "<th>IA</th><td>".$plr["name"]."</td><td>".number_format($hs["ia"],2,","," ")."</td></tr><tr".(($mod2%2==0) ? " class=\"mod2\"" : "").">";

		$plr = getPlayer($hs["kontoid"]);
		echo "<th>Konto</th><td>".$plr["name"]."</td><td>".number_format($hs["konto"],2,","," ")."&euro;</td></tr><tr".(($mod2%2==0) ? " class=\"mod2\"" : "").">";

		$plr = getPlayer($hs["customersid"]);
		echo "<th>Kunden</th><td>".$plr["name"]."</td><td>".number_format($hs["customers"],0,","," ")."</td></tr><tr".(($mod2%2==0) ? " class=\"mod2\"" : "").">";

		$plr = getPlayer($hs["energycostid"]);
		echo "<th>Energie</th><td>".$plr["name"]."</td><td>".number_format($hs["energycost"],2,","," ")."&euro;</td></tr><tr".(($mod2%2==0) ? " class=\"mod2\"" : "").">";

		$plr = getPlayer($hs["mieterid"]);
		echo "<th>Mieter</th><td>".$plr["name"]."</td><td>".number_format($hs["mieter"],0,","," ")."</td></tr><tr".(($mod2%2==0) ? " class=\"mod2\"" : "").">";

		$plr = getPlayer($hs["carparksid"]);
		echo "<th>Parkplätze</th><td>".$plr["name"]."</td><td>".number_format($hs["carparks"],0,","," ")."</td></tr><tr".(($mod2%2==0) ? " class=\"mod2\"" : "").">";

		$plr = getPlayer($hs["restaurantsid"]);
		echo "<th>Filialen</th><td>".$plr["name"]."</td><td>".number_format($hs["restaurants"],0,","," ")."</td></tr><tr".(($mod2%2==0) ? " class=\"mod2\"" : "").">";

		$plr = getPlayer($hs["attrid"]);
		echo "<th>Sterne</th><td>".$plr["name"]."</td><td>";
		for($i=0; $i<round($hs["attr"]); $i++) {
			echo "&#x2605";
		}
		echo " (".round($hs["attr"]).")</td></tr><tr".(($mod2%2==0) ? " class=\"mod2\"" : "").">";

		$plr = getPlayer($hs["paysid"]);
		echo "<th>Löhne</th><td>".$plr["name"]."</td><td>".number_format($hs["pays"],2,","," ")."&euro;</td></tr>";
		$mod2++;
	}
	echo "      </table>";
	echo "<table class=\"overview\">
	          <tr>
	              <th>
	              Name
	              </th>
	              <th>
	              H&auml;ufigkeit
	              </th>
	          </tr>";
	$cols = array("ia","konto","customers","energycost","mieter","carparks","restaurants","attr","pays");
	$plrs = array();
	$r_plr = $db->query("SELECT id,name FROM player ORDER BY name");
	while($plr = $r_plr->fetch_array(MYSQLI_ASSOC)) {
		$c = 0;
		foreach($cols as $col) {
			$r_count = $db->query("SELECT COUNT(*) FROM highscore WHERE ".$col."id=".$plr["id"]);
			$count = $r_count->fetch_array(MYSQLI_NUM);
			$c += $count[0];
		}
		$plrs[$plr["id"]] = array($plr["name"],$c);
	}

	function sortplr($a,$b) {
		if($a[1]>$b[1])
			return -1;
		else if($a[1]<$b[1])
			return 1;
		else
			return 0;
	}
	usort($plrs,"sortplr");
	$mod2=0;
	foreach($plrs as $plr) {
		echo "<tr".(($mod2%2==0) ? " class=\"mod2\"" : "")."><th>".$plr[0]."</th><td class=\"number\">".$plr[1]."</td></tr>";
		$mod2++;
	}
	echo "</table>";
	$db->Close();
}

function drawRVMenu() {
	$db = makeConn();
	//print_r($_POST);
	$r_bd = $db->query("SELECT birthday FROM world WHERE 1"); //Mehre welten?
	$bd = $r_bd->fetch_array(MYSQLI_NUM);
	if($bd[0]+26*24*60*60<time()) {
		if(isset($_POST["RVrestart"])) {
			$query = "UPDATE player SET RVvote=".$_POST["RVrestart"]." WHERE id=".$_SESSION["id"];
			$db->query($query);
			switch($_POST["RVrestart"]) {
				case 0:
					secondNoticeBar("Du wählst: das Spiel weiter spielen.");
					break;
				case 1:
					secondNoticeBar("Du wählst: das Spiel neustarten.");
					break;
			}
		}
		$r_rst = $db->query("SELECT RVvote FROM player WHERE id=".$_SESSION["id"]);
		$rst = $r_rst->fetch_array(MYSQLI_NUM);
		//print_r($rst);
		$timespan = floor((time()-$bd[0])/(24*60*60));
		echo "<form action=\"\" method=\"POST\" id=\"RVmenu\">
				Spiel neustarten?<br>
				<select name=\"RVrestart\" onChange=\"document.getElementById('RVsubmit').disabled=false\">
					<option ".(($rst[0]==1) ? "selected " : "")."value=\"1\" class=\"withIconLeft logout\">Ja, neustarten</option>
					<option ".(($rst[0]==0) ? "selected " : "")."value=\"0\" class=\"withIconLeft blank\">Nö, weiter gehts</option>
				</select>
				<input disabled=\"true\" type=\"submit\" id=\"RVsubmit\" value=\"Ok\"><br>
				Das wäre nach ".$timespan." Tagen Spielzeit. ";

		$votes4restart = 0;
		$votes4NOrestart = 0;
		$time = time();
		$maxLastDeedTime = $time-3*24*60*60;
		$query = "SELECT * FROM player WHERE lastdeed>$maxLastDeedTime ORDER BY ia DESC";
		$r_plr = $db->query($query);
		$r_bestplr = $db->query("SELECT * FROM player WHERE 1 ORDER BY ia DESC");
		$bestplr = $r_bestplr->fetch_array(MYSQLI_ASSOC);
		while($plr = $r_plr->fetch_array(MYSQLI_ASSOC)) {
			if($plr["id"]!=$bestplr["id"]) { // stärksten Spieler auslassen
				if($plr["RVvote"]>0)
					$votes4restart += abs($plr["ia"]);
				else
					$votes4NOrestart += abs($plr["ia"]);
			}
		}
		echo "Den Restart wollen bis jetzt ".number_format(100*$votes4restart/($votes4restart+$votes4NOrestart),2,",","")."%.</form>";
		if($votes4restart>$votes4NOrestart)
			echo "Das heißt, beim nächsten Tick passiert ein Restart!<br>";
	}
	$db->Close();
}

function drawInsolvencyMenu() {
	echo "<input type=\"button\" onClick=\"";
	prompt("Wirklich Insolvenz anmelden?","game.php?insolvenz=".$_SESSION["id"]);
	echo "\" value=\"Insolvenz beantragen\">";
}
?>
