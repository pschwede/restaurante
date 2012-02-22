<?php

include_once "connector.php";
include_once "creators.php";
include_once "smarttools.php";
include_once "generator.php";

function buyGround($worldid,$city,$hex,$playerid) {
	$db = makeConn();
	// testen ob feld unbesetzt, testen ob geld des spielers reicht, geld abziehen, grundstäck in player eintragen
	if (hexdec($hex)>0) {
		$plr = getPlayer($playerid);

		if($result = $db->query("SELECT * FROM grounds WHERE worldid=".$worldid." AND city='".$city."' AND hex='".$hex."'")) {
			$ground = $result->fetch_array(MYSQLI_ASSOC);
		} else {
			echo "error loading ground";
			return 4;
		}

		if (!$ground["ownerid"]>0) {
			if ($ground["price"] <= $plr["konto"]) {
				//gesamtes Grundstäck verkaufen
				$area = array();
				$map = getMapFromSQL($worldid,$city);
				$imax = getSizeOfArea($map, $ground["x"], $ground["y"], $hex, $area);
				for ($i=0; $i<$imax; $i++) {
					$xyp = split(",",$area[$i]);
					$db->query("UPDATE grounds SET ownerid=".$playerid." WHERE worldid='".$worldid."' AND x='".$xyp[0]."' AND y='".$xyp[1]."' AND city='".$city."'");
					$db->query("UPDATE grounds SET state=1 WHERE worldid='".$worldid."' AND x='".$xyp[0]."' AND y='".$xyp[1]."' AND city='".$city."'");
				}
				$db->query("UPDATE player SET konto=".($plr["konto"]-$ground["price"]).",outgo=".($plr["outgo"]+$ground["price"])." WHERE id=".$playerid) or die("Fehler bei äberweisung!");
			} else {
				echo "Kauf nicht finanzierbar.";
				return 2;
			}
		} else {
			echo "Grundstäck schon in Besitz.";
			return 1;
		}
	} else {
		echo "Kein Grundstäck.";
		return 3;
	}
	$db->Close();
	return 0;
}

function sellGround($worldid,$city,$hex,$playerid) {
	$db = makeConn();
	// testen ob feld unbesetzt, testen ob geld des spielers reicht, geld abziehen, grundstäck in player eintragen
	if (hexdec($hex)>0) {
		$plr = getPlayer($playerid);

		if($result = $db->query("SELECT * FROM grounds WHERE worldid=".$worldid." AND city='".$city."' AND hex='".$hex."'")) {
			$ground = $result->fetch_array(MYSQLI_ASSOC);
		} else {
			echo "error loading ground";
			return 4;
		}

		$frei = true;
		$area = array();
		$map = getMapFromSQL($worldid,$city);
		$imax = getSizeOfArea($map, $ground["x"], $ground["y"], $hex, $area);
		foreach($area as $xypstring) {
			$xyp = split(",",$xypstring);
			$gnd = getGround($worldid,$city,$xyp[2]);
			$frei &= $gnd["state"]<=1;
		}
		if ($ground["ownerid"]>0 && $ground["state"]<=1 && $frei) {
			//gesamtes Grundstäck verkaufen
			foreach($area as $xypstring) {
				$xyp = split(",",$xypstring);
				$db->query("UPDATE grounds SET ownerid=NULL, stars=0 ,state=0 WHERE worldid=".$worldid." AND hex='".$xyp[2]."' AND city='".$city."'");// or die("error selling ground");
			}
			$db->query("UPDATE player SET konto=konto+".($ground["price"]*0.9).",outgo=outgo-".($ground["price"]*0.9)." WHERE id=".$playerid) or die("Fehler bei äberweisung!");
		} else {
			echo "Kein Restaurant.";
			return 1;
		}
	} else {
		echo "Kein Grundstäck.";
		return 3;
	}
	$db->Close();
	return 0;
}

function buildOnGround($worldid,$city,$hex,$what,$playerid) {
	$db = makeConn();
	// testen ob feld unbesetzt, testen ob geld des spielers reicht, geld abziehen, grundstäck in player eintragen
	if (hexdec($hex)>0) {
		$player = getPlayer($playerid);
		if($result = $db->query("SELECT * FROM grounds WHERE worldid=".$worldid." AND city='".$city."' AND hex='".$hex."'")) {
			$ground = $result->fetch_array(MYSQLI_ASSOC);
		} else {
			echo "error loading ground";
			return 4;
		}

		$result = $db->query("SELECT COUNT(*) FROM hiredpersonel WHERE worldid=".$worldid." AND city='".$city."' AND hex='".$hex."'");
		$numpers = $result->fetch_array(MYSQLI_NUM);
		if ($ground["ownerid"]==$playerid && $ground["state"]!=$what
				&& $ground["kitchensnum"]<1 && $ground["tablesnum"]<1 && $ground["toiletsnum"]<1
				&& $numpers[0] == 0) {
			switch($what) {
				case 1:
					$kontoneu = 0; //Abreiäen
					$db->query("UPDATE city SET population=population-".$ground["customers"]." WHERE hex='".$city."'");
					break;
				case 2:
					$kontoneu = 500000; //Haus
					$r_nmyid = $db->query("SELECT ownerid FROM grounds WHERE city='".$city."' AND ownerid!=".$playerid." GROUP BY ownerid");
					while($nmyid = $r_nmyid->fetch_array(MYSQLI_NUM)) {
						sendMessage(0,$nmyid[0],"Gegner baut Filiale","In der Nähe deiner <a href=\"game.php?city=$city&x=".$ground["x"]."&y=".$ground["y"]."\">Filiale</a> hat ".$player["Kette"]." ein neues Filialengebäude errichtet.",5);
					}
					break;
				case 3:
					$kontoneu = 15000; //Platz
					break;
				case 4:
					$kontoneu = 5000; //begränt
					break;
				case 5:
					$kontoneu = 6000; // kl Wohnhaus
					break;
				case 6:
					$kontoneu = 22000; // gr Wohnhaus
					break;
				default:
					$kontoneu = 0;
			}
			if ($kontoneu <= $player["konto"] || $kontoneu <= 0) {
				$area = array();
				$map = getMapFromSQL($worldid,$city);
				$imax = getSizeOfArea($map, $ground["x"], $ground["y"], $hex, $area);
				$query = "UPDATE grounds SET customers=0, price4food=9, state=$what, stars=0 WHERE worldid=$worldid AND hex='$hex' AND city='$city'";
				$db->query($query)
					or die("error during building: ".$query);

				if (!$db->query("UPDATE player SET konto=konto-$kontoneu, outgo=outgo+$kontoneu WHERE id=$playerid")) {
					echo "Fehler bei äberweisung.";
					return 4;
				}
			} else {
				echo "Bau nicht finanzierbar.";
				return 2;
			}
		} else {
			switch($what) {
				case 1:
					echo "Kann nicht Abreiäen &rarr Mitarbeiter entlassen, Einrichtung verkaufen!";
					break;
				default:
					echo "Kein Platz fär Bau.";
			}
			return 1;
		}
	} else {
		echo "Feld ist kein Grundstäck.";
		return 3;
	}
	$db->Close();
	return 0;
}

function buyFixture($worldid,$city,$hex,$fixtureid,$playerid) {
	$db = makeConn();
	if($fixtureid>0) $fxt = getFixture($fixtureid);
	else die("error loading fixture $fixtureid at $worldid,$city,$hex");
	$plr = getPlayer($playerid);
	$gnd = getGround($worldid,$city,$hex);
	if(getRemainingSpaceOnGround($worldid,$city,$hex)<$fxt["space"]) {
		echo "Zu wenig Platz";
	} else if ($plr["konto"]<$fxt["price"]) {
		echo "Zu wenig Geld";
	} else {

		switch($fxt["class"]) {
			case 1: //tisch
				if($gnd["tablesid"]==$fxt["id"] || $gnd["tablesnum"]<=0){
					$db->query("UPDATE grounds SET tablesid=".$fxt["id"].", tablesnum=".($gnd["tablesnum"]+1)." WHERE hex='".$gnd["hex"]."' AND city='$city' AND worldid=$worldid")
						or die("error updating ground");
				} else echo "Erst alle Tische verkaufen!";
				break;
			case 2: //käche
				if($gnd["kitchensid"]==$fxt["id"] || $gnd["kitchensnum"]<=0){
					$db->query("UPDATE grounds SET kitchensid=".$fxt["id"].", kitchensnum=".($gnd["kitchensnum"]+1)." WHERE hex='".$gnd["hex"]."' AND city='$city' AND worldid=$worldid")
						or die("error updating ground");
				} else echo "Erst alle Kächen verkaufen!";
				break;
			case 3: //bad
				if($gnd["toiletsid"]==$fxt["id"] || $gnd["toiletsnum"]<=0){
					$db->query("UPDATE grounds SET toiletsid=".$fxt["id"].", toiletsnum=".($gnd["toiletsnum"]+1)." WHERE hex='".$gnd["hex"]."' AND city='$city' AND worldid=$worldid")
						or die("error updating ground");
				} else echo "Erst alle Toiletten verkaufen!";
				break;
		}
		$db->query("UPDATE grounds SET remainingarea=".getRemainingSpaceOnGround($worldid,$gnd["city"],$gnd["hex"])." WHERE hex='".$gnd["hex"]."' AND city='$city' AND worldid=$worldid") or die("error calcing space");
		$db->query("UPDATE player SET konto=".($plr["konto"]-$fxt["price"])." WHERE id=$playerid") or die("Fehler bei äberweisung");
		$db->query("UPDATE player SET outgo=".($plr["outgo"]+$fxt["price"])." WHERE id=$playerid") or die("Fehler bei äberweisung");
	}
	$db->Close();
}

function sellFixture($worldid,$city,$hex,$fixtureid,$playerid) {
	$db = makeConn();
	if($fixtureid>0)
		$fxt = getFixture($fixtureid);
	else die("error loading fixture $fixtureid at $worldid,$city,$hex");
	$gnd = getGround($worldid,$city,$hex);
	$plr = getPlayer($playerid);
	if($gnd["kitchensid"]==$fixtureid || $gnd["tablesid"]==$fixtureid || $gnd["toiletsid"]==$fixtureid) {
		switch($fxt["class"]) {
			case 1: //tisch
				if($gnd["tablesnum"]>0){
					if($gnd["tablesnum"]>1) {
						$db->query("UPDATE grounds SET tablesnum=".($gnd["tablesnum"]-1)." WHERE hex='$hex' AND city='$city' AND worldid=$worldid")
							or die("error updating ground");
					} else {
						$db->query("UPDATE grounds SET tablesid=0, tablesnum=0 WHERE hex='$hex' AND city='$city' AND worldid=$worldid")
							or die("error updating ground");
					}
				}
				break;
			case 2: //käche
				if($gnd["kitchensnum"]>0){
					if($gnd["kitchensnum"]>1) {
						$db->query("UPDATE grounds SET kitchensnum=".($gnd["kitchensnum"]-1)." WHERE hex='$hex' AND city='$city' AND worldid=$worldid")
							or die("error updating ground");
					} else {
						$db->query("UPDATE grounds SET kitchensid=0, kitchensnum=0 WHERE hex='$hex' AND city='$city' AND worldid=$worldid")
							or die("error updating ground");
					}
				}
				break;
			case 3: //bad
				if($gnd["toiletsnum"]>0){
					if($gnd["toiletsnum"]>1) {
						$db->query("UPDATE grounds SET toiletsnum=".($gnd["toiletsnum"]-1)." WHERE hex='$hex' AND city='$city' AND worldid=$worldid")
							or die("error updating ground");
					} else {
						$db->query("UPDATE grounds SET toiletsid=0, toiletsnum=0 WHERE hex='$hex' AND city='$city' AND worldid=$worldid")
							or die("error updating ground");
					}
				}
				break;
			default: break;
		}
		$db->query("UPDATE grounds SET remainingarea=".getRemainingSpaceOnGround($worldid,$gnd["city"],$gnd["hex"])." WHERE hex='$hex' AND city='$city' AND worldid=$worldid") or die("error calcing space");
		$db->query("UPDATE player SET konto=konto+".(0.75*$fxt["price"])." WHERE id=$playerid") or die("Fehler bei äberweisung");
		$db->query("UPDATE player SET outgo=outgo-".(0.75*$fxt["price"])." WHERE id=$playerid") or die("Fehler bei äberweisung");
		$db->Close();
	}
}

function hirePersonel($worldid,$city,$personel,$hire,$playerid) {
	$db = makeConn();
	$gnd=getGround($worldid,$city,$personel);
	if($gnd["ownerid"]==$playerid) {
		$result = $db->query("SELECT * FROM choosablepersonel WHERE id=$hire LIMIT 1");
		$pers = $result->fetch_array(MYSQLI_ASSOC);
		if($pers["city"] == $city) {
			$query = "INSERT INTO hiredpersonel (hex, city, name, relia, pay, jobid, capa, worldid)
					VALUES ('$personel','".$pers["city"]."','".$pers["name"]."',".$pers["relia"].",".$pers["pay"].",".$pers["jobid"].",".$pers["capa"].",".$pers["worldid"].")";
			$db->query($query)
				or die("error adding personel ".$query);
			$db->query("DELETE FROM choosablepersonel WHERE id=$hire") or die("error hiring personel #2");
		} else echo "Niemand";
	} else echo "nicht dein Grundstäck!";
	echo $pers["name"]." wurde eingestellt.";
	$db->Close();
}

function firePersonel($worldid,$city,$hex,$fire,$playerid) {
	$db = makeConn();
	$gnd = getGround($worldid,$city,$hex);
	if($gnd["ownerid"]==$playerid) {
		$result = $db->query("SELECT * FROM hiredpersonel WHERE id=$fire LIMIT 1");
		$pers = $result->fetch_array(MYSQLI_ASSOC);
		if($pers["id"] == $fire) {
			$query = "INSERT INTO choosablepersonel (city, name, relia, pay, jobid, capa, worldid)
				VALUES ('".$pers["city"]."','".$pers["name"]."',".$pers["relia"].",".$pers["pay"].",".$pers["jobid"].",".$pers["capa"].",".$pers["worldid"].")";
			$db->query($query)
				or die("error adding personel ".$query);
			$db->query("DELETE FROM hiredpersonel WHERE id=$fire");
		}
	}
	echo $pers["name"]." wurde entlassen.";
	$db->Close();
}

function setPrice4Food($worldid,$city,$hex,$value,$playerid) {
	$gnd = getGround($worldid,$city,$hex);
	if($value<10 && $value>0)
	if($gnd["ownerid"]==$playerid) {
		$db = makeConn();
		$db->query("UPDATE grounds SET price4food=$value WHERE hex='".$gnd["hex"]."' AND city='".$gnd["city"]."' AND worldid=".$gnd["worldid"]) or die("error updating price4food");
		$db->Close();
	}
	echo "Preis auf ".number_format($value,2,","," ")." &euro; gesetzt.";
}

function setCustomersOfCarparks($worldid,$city) {
	$db = makeConn();
	//Carparks
	if($r_carparks = $db->query("SELECT * FROM grounds WHERE state=3 AND city='".$city["hex"]."' AND worldid=".$worldid)) {
		while($gnd = $r_carparks->fetch_array(MYSQLI_ASSOC)) {
			$city = getCity($worldid,$gnd["city"]);
			$strassen = 0;
			$restaurants = 0;
			$attr = 0;
			$map = getMapFromSQL($gnd["worldid"],$gnd["city"]);
			$nbh = getNbh($map,$gnd["x"],$gnd["y"]);
			$nbarray = array();
			foreach($nbh as $nbstring) {
				$nb = split(",",$nbstring);
				if ($nb[2] == "SG" || $nb[2] == "SH" || $nb[2] == "SI") {
					$strassen++;
				} else if (hexdec($nb[2]) > 0) {
					$nbgnd = getGround($worldid,$city["hex"],$nb[2]);
					if($nbgnd["state"]==2) {
						$restaurants++;
						$attr += $nbgnd["attr"];
						$nbarray[] = $nbgnd;
					}
				}
			}
			$cust = 3*10*$city["strdichte"]*5*$city["population"]*$restaurants*$strassen/pow(count($nbh),2);
			$query="UPDATE grounds SET energycost=33, customers=$cust WHERE id=".$gnd["id"];
			$db->query($query) or alert("Error during ".$query);
			// Kunden an Restaurants verteilen
			foreach($nbarray as $nbgnd) {
				if($nbgnd["state"]==2) {
					// Restaurant kriegt zusätzliche kunden $nbgnd["attr"]/$attr
					if($attr!=0)
						$customers = $cust*$nbgnd["attr"]/$attr;
					else
						$customers = $cust;
					$query = "UPDATE grounds SET customers=customers+".round($customers)." WHERE id=".$nbgnd["id"];
					//alert($city["name"].": ".$query);
					if(!$db->query($query))
						echo "error during carpark calc, $query";
				}
			}
		}
	}
	$db->Close();
}

function cmpgnd($a,$b) {
	if($a["attr"]==$b["attr"]) return 0;
	if($a["attr"]<$b["attr"]) return 1;
	else return -1;
}

function setCustomersOfRestaurants($worldid,$city,$custOfCity,$newcusts) {
	$db = makeConn();
	//Restaurants
	if($r_rests = $db->query("SELECT * FROM grounds WHERE state=2 AND city='".$city["hex"]."' AND worldid=".$worldid)) {
		while($gnd = $r_rests->fetch_array(MYSQLI_ASSOC)) {
			// init 'em all
			$cust = 0;
			$enemyattr = 0;
			$ownattr = getAttrOfGround($gnd);
			$freeseats = 0;
			$enemies = array();
			// gegner analysieren
			$r_enemies = $db->query("SELECT * FROM grounds WHERE state=2 AND city='".$city["hex"]."' AND hex!='".$gnd["hex"]."' AND worldid=".$worldid);
			while($nmy = $r_enemies->fetch_array(MYSQLI_ASSOC)) {
				$nmy["attr"] = getAttrOfGround($nmy);
				$nmy["maxcust"] = getMaxCustOfGround($nmy);
				$freeseats += $nmy["maxcust"]-$nmy["customers"];
				$enemies[] = $nmy;
			}
			/* 	Der Gegner mit voller Kundenanzahl und
			 * hächster Attraktivität wird in der Betrachtung ausgelassen.*/
			usort($enemies,"cmpgnd");
			while(count($enemies)>0 && $enemies[0]["maxcust"]==$enemies[0]["customers"]) {
				$nmy = array_pop($enemies);
				//alert($nmy["name"]." mit ".$nmy["attr"]." Attr ausgelassen.");
			}
			/* Attraktivität der Gegner summieren */
			foreach($enemies as $nmy) {
				$enemyattr += $nmy["attr"];
			}

			$freeseats += $gnd["maxcust"]-$gnd["customers"];
			/* Kunden-Stromteiler */
			if($ownattr != 0) {
				$cust += $ownattr / ($ownattr + $enemyattr);
				if($newcusts)
					$cust -= (1-0.2)/(1+count($enemies));
				else
					$cust -= 0.5;
			} else $cust = 0;
			$cust *= $custOfCity;
			$maxcust = getMaxCustOfGround($gnd); //TODO: Werbefaktor

			if($ownattr!=0) if($maxcust > $custOfCity*$ownattr/($enemyattr+$ownattr))
				$maxcust = $custOfCity*$ownattr/($enemyattr+$ownattr);
			$customers = $gnd["customers"] + $cust;
			if($customers > $maxcust) {
				$customers = $maxcust;
			} else {
				if($customers<0)
					$customers = 0;
			}
			$restcust = $maxcust - $customers;
			if($db->query("UPDATE grounds SET customers=$customers WHERE id=".$gnd["id"]))
				if(floor($restcust)>$freeseats && $freeseats>0) {
					/* !! Recursiv !! */
					setCustomersOfRestaurants($worldid,$city,floor($restcust),true);
				}
		}
	}
	$db->Close();
}

function setStatsOfGroundsAndPlayers($worldid,$city) {
	$db = makeConn();
	if($r_grounds = $db->query("SELECT * FROM grounds WHERE state>1 AND ownerid>0 AND city='".$city["hex"]."' AND worldid=".$worldid)) {
		while($gnd = $r_grounds->fetch_array(MYSQLI_ASSOC)) {
			$plr = getPlayer($gnd["ownerid"]);
			// Lähne holen
			$pays = 0;
			if($r_pays = $db->query("SELECT SUM(pay) FROM hiredpersonel WHERE city='".$gnd["city"]."' AND hex='".$gnd["hex"]."' AND worldid=".$worldid))
				if($tmp = $r_pays->fetch_array(MYSQLI_NUM))
					if($tmp[0]!=null)
						$pays = $tmp[0];
			// energiekosten
			$energiekosten = getEnergyOfGround($gnd);
			// Ertrag berechenn
			$ingo = $gnd["price4food"]*$gnd["customers"];
			// ground update
			$query = "UPDATE grounds SET ingo=$ingo, pays=$pays, energycost=$energiekosten WHERE id=".$gnd["id"];
			if(!$db->query($query)) {
				alert("error during ground stats update: ".$query);
				exit();
			}
			// player update
			$query = "UPDATE player SET ingo=ingo+$ingo, pays=pays+$pays, energycost=energycost+$energiekosten WHERE id=".$plr["id"];
			$db->query($query) or
				die("error during player stats update #1: ".$query);
			}
		}
	$db->Close();
}

function setAllKontos($worldid) {
	$db = makeConn();
	// Gehe alle Spieler außer "System" durch!
	if($r_players = $db->query("SELECT * FROM player WHERE name!='System' AND kette!='System'")) {
		while($plr = $r_players->fetch_array(MYSQLI_ASSOC)) {
			/* Zinsen */
			if($plr["konto"]>1500000) {
				$zinsen = 0; 				//keine Zinsen mehr!
			} else if($plr["konto"]>500000) { //Zinsen!
				$zinsen = $plr["konto"]*0.001;
			} else if($plr["konto"]>50000) {
				$zinsen = $plr["konto"]*0.002;
			} else if($plr["konto"]>5000) {
				$zinsen = $plr["konto"]*0.0025;
			} else if($plr["konto"]>0) {
				$zinsen = $plr["konto"]*0.003;
			} else $zinsen = 0;
			// Summe
			$query = "UPDATE player SET konto=".$plr["konto"]."+".$zinsen."+".$plr["ingo"]."-".$plr["pays"]."-".$plr["energycost"].", zinsen=$zinsen WHERE id=".$plr["id"];
			if(!$db->query($query))
				alert("error during ".$query);
		}
	} else echo "error at trying to catch players";
	// outgo mit others tauschen, outgo soll am Ende gleich 0 sein
	if(!$db->query("UPDATE player SET others=outgo WHERE 1")) {
		alert("error during setting of player others=outgo");
		exit();
	}
	if(!$db->query("UPDATE player SET outgo=0 WHERE 1")) {
		alert("error resetting outgo of player ".$plr["name"]);
		exit();
	}

	// KE erhoehen
	$db->query("UPDATE player SET deeds=deeds+1 WHERE deeds<35") or die("error setting deeds of player");

	/* IA in alle player schreiben */
	$mainiavar = rand(-50,50);
	$r_player = $db->query("SELECT * FROM player WHERE name!='System' AND kette!='System'");
	while($plr = $r_player->fetch_array(MYSQLI_ASSOC)) {
		/* Strafe für leere Grundstücke */
		if($r_grounds = $db->query("SELECT COUNT(*) FROM grounds WHERE state=1 AND ownerid=".$plr["id"])) {
			$gnds = $r_grounds->fetch_array(MYSQLI_NUM);
			if($gnds[0]>0) {
				$strafe = $gnds[0]*300;
				sendMessage(0,$plr["id"],"Leerfeld-Strafe",$plr["name"]." hinterließ in der letzten Runde ".$gnds[0]." leere Grundstücke. Geldstrafe: ".$strafe." &euro;",5);
				//alert("Leerfeld-Strafe,".$plr["name"].", hinterließ in der letzten Runde ".$gnds[0]." leere Grundstücke. Geldstrafe: ".$strafe." &euro;");
				$db->query("UPDATE player SET outgo=outgo+$strafe, konto=konto-$strafe WHERE id=".$plr["id"]);
			}
		}

		$bodenwert=0;
		$cust = 0;
		$attr = 0;
		$maxcust = 0;
		$c = 0;
		if($r_gnd = $db->query("SELECT * FROM grounds WHERE ownerid=".$plr["id"])) {
			while($gnd = $r_gnd->fetch_array(MYSQLI_ASSOC)) {
				$bodenwert += $gnd["price"]*0.9+500000;
				if($gnd["kitchensid"]>0) {
					$fxt = getFixture($gnd["kitchensid"]);
					$bodenwert += 0.75*$fxt["price"];
				}
				if($gnd["tablesid"]>0) {
					$fxt = getFixture($gnd["tablesid"]);
					$bodenwert += 0.75*$fxt["price"];
				}
				if($gnd["toiletsid"]>0) {
					$fxt = getFixture($gnd["toiletsid"]);
					$bodenwert += 0.75*$fxt["price"];
				}
				$cust+=$gnd["customers"];
				$attr+=getAttrOfGround($gnd);
				$maxcust+=getMaxCustOfGround($gnd);
				$c++;
			}
		}

		$aktien = 0; //Ab hier werden auch die Aktien berechnet!?

		// Aktien außerhalb des Eigenbesitzes subtraieren, innerhalb addieren
		if($r_shares2 = $db->query("SELECT * FROM shares WHERE company=".$plr["id"]." AND shareholder!=".$plr["id"])) {
			$share2 = $r_shares2->fetch_array(MYSQLI_NUM);
			if($r_statements = $db->query("SELECT ia FROM statements WHERE playerid=".$plr["id"]." ORDER BY time DESC LIMIT 1"))
				$ia = $r_statements->fetch_array(MYSQLI_NUM);
				$db = makeConn();
				if($r_count = $db->query("SELECT SUM(count) FROM shares WHERE company=".$share2["company"])) {
					$count = $r_count->fetch_array(MYSQLI_NUM);
					$aktien -= $share2["count"]*$ia[0]/$count[0];
				}
		}
		if($r_shares = $db->query("SELECT * FROM shares WHERE company!=shareholder AND shareholder=".$plr["id"])) {
			while($share = $r_shares->fetch_array(MYSQLI_ASSOC)) {
				$r_statements = $db->query("SELECT ia FROM statements WHERE playerid=".$share["company"]." ORDER BY time DESC LIMIT 1");
				$ia = $r_statements->fetch_array(MYSQLI_NUM);
				if($r_count = $db->query("SELECT SUM(count) FROM shares WHERE company=".$share["company"])) {
					$count = $r_count->fetch_array(MYSQLI_NUM);
					$aktien += $share2["count"]*$ia[0]/$count[0];
				}
			}
		}


		/* ?????????????
		$ias = 0;
		if($r_shares = $db->query("SELECT id FROM player WHERE id!=".$plr["id"])) {
			if($r_statements = $db->query("SELECT SUM(ia) FROM statements WHERE playerid=".$share["company"]." ORDER BY time DESC LIMIT 1"))
				$tmp = $r_statements->fetch_array(MYSQLI_NUM);
			$ias += $tmp[0];
		}
		$ianew = $ia[0];
		if(($ia[0] + $ias)>0)
			$ianew /= ($ia[0] + $ias);
		$r_plrs = $db->query("SELECT COUNT(*) FROM player WHERE name!='System' AND kette!='System'");
		$plrs = $r_plrs->fetch_array(MYSQLI_NUM);
		$ianew -= (1-0.2)/($plrs[0]);
		?????????????? */
		$ianew = 1;
		if($attr == 0)
			$attr++;
		$ianew *= $plr["konto"]+$bodenwert+$plr["ingo"]+$aktien+$cust/$attr; // cust/attr=Ruf
		$ianew *= 1+0.0002*($mainiavar+rand(-50,50));
		//alert($plr["name"].": bodenwert = ".$bodenwert);
		$db->query("UPDATE player SET ia=$ianew WHERE id=".$plr["id"]) or die("error setting ia of player ".$plr["id"]);
	}
	// Kontoauszug erstellen
	$query = "SELECT * FROM player WHERE 1";
	$r_plr = $db->query($query) or die ("error in setAllKontos: ".$query);
	while($plr = $r_plr->fetch_array(MYSQLI_ASSOC)) {
		addStatement($plr["id"],getNextTick($worldid),$plr["konto"],$plr["ia"]);
	}

	// "System"-aktie simlieren
	$r = rand(-9,9);
	$r_sysplr = $db->query("SELECT * FROM player WHERE name='System' AND kette='System' LIMIT 1");
	$sysplr = $r_sysplr->fetch_array(MYSQLI_ASSOC);
	if($sysplr["ia"]+$r<500) {
		$db->query("DELETE FROM shares WHERE company=".$sysplr["id"]);
		$db->query("UPDATE player SET ia=2000000 WHERE id=".$sysplr["id"]);
		$db->query("INSERT INTO shares (forsale, count, shareholder, company, oldia, value) VALUES (1,4000,".$sysplr["id"].",".$sysplr["id"].",2000000,500)");
	} else
		$db->query("UPDATE player SET ia=ia*".(1+0.001*$r)." WHERE name='System' AND kette='System'");

	$db->Close();
}

function setCustomersOfHotels($worldid,$city) {
	$db = makeConn();
	$allemieter = 0;
	$cmap = getMapFromSQL($worldid,$city["hex"]);
	$cpop = (countPatternsInMap($cmap,"HG")+countPatternsInMap($cmap,"HH")+countPatternsInMap($cmap,"HI"))*$city["popdichte"];
	$query = "SELECT * FROM grounds WHERE state=2 AND city='".$city["hex"]."'";
	$r_rest = $db->query($query);
	$maxrestcust = 0;
	while($rest = $r_rest->fetch_array(MYSQLI_ASSOC)) {
		$maxrestcust += getMaxCustOfGround($rest);
	}

	$mglmieter = $maxrestcust - 3*$cpop[0];
	$mglmieter = ceil($mglmieter/3);
	if($mglmieter<0)
		$mglmieter = 0;

	for($state=5; $state<=6; $state++) {
		$query = "SELECT COUNT(*) FROM grounds WHERE state=$state AND city='".$city["hex"]."'";
		$r_count = $db->query($query);
		$count = $r_count->fetch_array(MYSQLI_NUM);
		if($count[0]>0)
			$mieter = ceil($mglmieter/$count[0]);
		else
			$mieter = 0;

		switch($state) {
			case 5:
				$maxmieter=30;
				break;
			case 6:
				$maxmieter=90;
				break;
		}
		if($mieter>$maxmieter)
			$mieter=$maxmieter;

		$allemieter += $mieter*$count[0];
		$mglmieter -= $mieter*$count[0];

		$query = "SELECT * FROM grounds WHERE state=$state AND city='".$city["hex"]."'";
		$r_gnd = $db->query($query) or die("Error during setCustomersOfHotels: ".$query);
		while($gnd = $r_gnd->fetch_array(MYSQLI_ASSOC)) {
			$query = "UPDATE grounds SET customers=$mieter WHERE id=".$gnd["id"];
			$db->query($query) or die("Error during setCustomersOfHotels: ".$query);
		}
	}
	$query = "UPDATE cities SET population=".($cpop+$allemieter)." WHERE id='".$city["id"]."'";
	$db->query($query) or die("Error during setCustomersOfHotels: ".$query);
	$db->Close();
}

function getRemainingCustOfCity($worldid, $city) {
	return 3*($city["population"]);
}

function makeTick($worldid, $hours) {
	$db = makeConn();

	$query = "SELECT ticking FROM world WHERE id=".$worldid;
	$r_ticking = $db->query($query) or die("Error during maketick: ".$query);
	$ticking = $r_ticking->fetch_array(MYSQLI_NUM);
	if(!$ticking[0]==0) {
		alert("Tickberechnung bereits in Arbeit! Bitte warten!");
		exit();
	}
	$db->query("UPDATE world SET ticking=1 WHERE id=".$worldid)
		or die("Bitte diesen Fehler dem Admin melden: ".$db->error);

	$db->query("DELETE FROM messages WHERE toid=(SELECT id FROM player WHERE kette='System' AND name='System')");

	while(time()>getNextTick($worldid)) {
		$db->query("UPDATE player SET ingo=0, pays=0, energycost=0 WHERE 1");
		$r_cities = $db->query("SELECT * FROM cities WHERE worldid=$worldid");
		while($city = $r_cities->fetch_array(MYSQLI_ASSOC)) {
			setCustomersOfHotels($worldid,$city);
		}
		$r_cities = $db->query("SELECT * FROM cities WHERE worldid=$worldid");
		while($city = $r_cities->fetch_array(MYSQLI_ASSOC)) {
			setCustomersOfRestaurants($worldid,$city,getRemainingCustOfCity($worldid, $city),true);
		}
		$r_cities = $db->query("SELECT * FROM cities WHERE worldid=$worldid");
		while($city = $r_cities->fetch_array(MYSQLI_ASSOC)) {
			setCustomersOfCarparks($worldid,$city);
		}
		$r_cities = $db->query("SELECT * FROM cities WHERE worldid=$worldid");
		while($city = $r_cities->fetch_array(MYSQLI_ASSOC)) {
			setStatsOfGroundsAndPlayers($worldid,$city);
		}
		setAllKontos($worldid);

		/* nächsten tick berechnen */
		//if(time()<getNextTick($worldid))
			$oldtick = getNextTick($worldid);
		//else
		//	$oldtick = time();
		/*//playerabhängige Stunden zwischen Ticks
		$r_onlineplayers = $db->query("SELECT count(*) FROM player WHERE lastlogin>lastlogout");
		$onlplr = $r_onlineplayers->fetch_array(MYSQLI_NUM);
		$r_numberofplayers = $db->query("SELECT count(*) FROM player WHERE 1");
		$numplr = $r_numberofplayers->fetch_array(MYSQLI_NUM);
		$hours = 2 - 1.9*$onlplr[0]/$numplr[0];*/
		$then = $oldtick+$hours*60*60;
		$then -= $then%(60*60);
		$then -= $then%60;
		$db->query("UPDATE world SET nexttick=$then, lasttick=$oldtick WHERE id=".$worldid)
			or die("Fehler bei Tickaktualisierung");
		makeNewChoosablePersonelTable($worldid);
		echo "Tick von ".date("G:i:s",$oldtick)." wurde ausgelöst. ";
	}

	$db->query("UPDATE lose SET jackpot=jackpot+5000");
	updateHighscore();

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
	$max = 0;
	$r_plr = $db->query("SELECT id FROM player WHERE 1");
	while($plr = $r_plr->fetch_array(MYSQLI_NUM)) {
		$cobp = citiesOwnedByPlayer($worldid,$plr[0]);
		if($cobp>$max)
			$max = $cobp;
	}
	if($votes4restart>$votes4NOrestart || $max>=10)
		restartGame($worldid);

	$db->query("UPDATE world SET ticking=0 WHERE id=".$worldid)
		or die("Bitte diesen Fehler dem Admin melden: Error no ticking");

	$db->Close();
}

function updateHighscore() {
	$db = makeconn();
	$errors = 0;
	$r_ia = $db->query("SELECT id,ia FROM player
		WHERE name!='System' AND kette!='System' ORDER BY ia DESC LIMIT 1");
	$ia = $r_ia->fetch_array(MYSQLI_NUM) or $errors++;
	$db->query("UPDATE highscore SET ia=".$ia[1].",iaid=".$ia[0]." WHERE ia<".$ia[1]." AND round=(SELECT round FROM world WHERE 1 LIMIT 1)");

	$r_konto = $db->query("SELECT id,konto FROM player
		WHERE name!='System' AND kette!='System' ORDER BY konto DESC LIMIT 1");
	$konto = $r_konto->fetch_array(MYSQLI_NUM) or $errors++;
	$db->query("UPDATE highscore SET konto=".$konto[1].",kontoid=".$konto[0]." WHERE konto<".$konto[1]." AND round=(SELECT round FROM world WHERE 1 LIMIT 1)");

	$r_customers = $db->query("SELECT id,(SELECT SUM(customers) FROM grounds WHERE ownerid=player.id) FROM player
		WHERE name!='System' AND kette!='System' ORDER BY (SELECT SUM(customers) FROM grounds WHERE ownerid=player.id) DESC LIMIT 1");
	$customers = $r_customers->fetch_array(MYSQLI_NUM) or $errors++;
	$db->query("UPDATE highscore SET customers=".$customers[1].",customersid=".$customers[0]." WHERE customers<".$customers[1]." AND round=(SELECT round FROM world WHERE 1 LIMIT 1)") or $errors++;

	$r_energycost = $db->query("SELECT id,(SELECT SUM(energycost) FROM grounds WHERE ownerid=player.id)/(SELECT COUNT(*) FROM grounds WHERE energycost>0 AND ownerid=player.id) FROM player
		WHERE name!='System' AND kette!='System' ORDER BY (SELECT SUM(energycost) FROM grounds WHERE ownerid=player.id)/(SELECT COUNT(*) FROM grounds WHERE energycost>0 AND ownerid=player.id) DESC LIMIT 1");
	$energycost = $r_energycost->fetch_array(MYSQLI_NUM) or $errors++;
	$db->query("UPDATE highscore SET energycost=".$energycost[1].",energycostid=".$energycost[0]." WHERE energycost>".$energycost[1]." AND round=(SELECT round FROM world WHERE 1 LIMIT 1)") or $errors++;

	$r_mieter = $db->query("SELECT id,(SELECT SUM(customers) FROM grounds WHERE state>4 AND state<7 AND ownerid=player.id) FROM player
		WHERE name!='System' AND kette!='System' ORDER BY (SELECT SUM(customers) FROM grounds WHERE state>4 AND state<7 AND ownerid=player.id) DESC LIMIT 1");
	$mieter = $r_mieter->fetch_array(MYSQLI_NUM) or $errors++;
	$db->query("UPDATE highscore SET mieter=".$mieter[1].",mieterid=".$mieter[0]." WHERE mieter<".$mieter[1]." AND round=(SELECT round FROM world WHERE 1 LIMIT 1)") or $errors++;

	$r_carparks = $db->query("SELECT id,(SELECT COUNT(*) FROM grounds WHERE state=3 AND ownerid=player.id) FROM player
		WHERE name!='System' AND kette!='System' ORDER BY (SELECT SUM(customers) FROM grounds WHERE state=3 AND ownerid=player.id) DESC LIMIT 1");
	$carparks = $r_carparks->fetch_array(MYSQLI_NUM) or $errors++;
	$db->query("UPDATE highscore SET carparks=".$carparks[1].",carparksid=".$carparks[0]." WHERE carparks<".$carparks[1]." AND round=(SELECT round FROM world WHERE 1 LIMIT 1)") or $errors++;

	$r_restaurants = $db->query("SELECT id,(SELECT COUNT(*) FROM grounds WHERE state=2 AND ownerid=player.id) FROM player
		WHERE name!='System' AND kette!='System' ORDER BY (SELECT COUNT(*) FROM grounds WHERE state=2 AND ownerid=player.id) DESC LIMIT 1");
	$restaurants = $r_restaurants->fetch_array(MYSQLI_NUM) or $errors++;
	$db->query("UPDATE highscore SET restaurants=".$restaurants[1].",restaurantsid=".$restaurants[0]." WHERE restaurants<".$restaurants[1]." AND round=(SELECT round FROM world WHERE 1 LIMIT 1)") or $errors++;

	$r_attr = $db->query("SELECT id,(SELECT SUM(stars) FROM grounds WHERE state=2 AND ownerid=player.id) FROM player
		WHERE name!='System' AND kette!='System' ORDER BY (SELECT SUM(stars) FROM grounds WHERE state=2 AND ownerid=player.id) DESC LIMIT 1");
	$attr = $r_attr->fetch_array(MYSQLI_NUM) or $errors++;
	$db->query("UPDATE highscore SET attr=".$attr[1].",attrid=".$attr[0]." WHERE attr<".$attr[1]." AND round=(SELECT round FROM world WHERE 1 LIMIT 1)") or $errors++;

	$r_pays = $db->query("SELECT id,(pays/(SELECT COUNT(*) FROM grounds WHERE pays>0)) FROM player
		WHERE name!='System' AND kette!='System' ORDER BY (pays/(SELECT COUNT(*) FROM grounds WHERE pays>0)) DESC");
	$pays = $r_pays->fetch_array(MYSQLI_NUM) or $errors++;
	$db->query("UPDATE highscore SET pays=".$pays[1].",paysid=".$pays[0]." WHERE pays<".$pays[1]." AND round=(SELECT round FROM world WHERE 1 LIMIT 1)") or $errors++;

	if($errors>0)
		alert($errors." Fehler beim Highscore-Update");

	$db->Close();
}

function kickSomeAss($worldid,$city,$hex,$what,$kickerid) {
	$kicker = getPlayer($kickerid);
	if($kicker["deeds"]>0) {
		$gnd = getGround($worldid,$city,$hex);
		$victim = getPlayer($gnd["ownerid"]);
		$db = makeConn();
		$result = $db->query("SELECT COUNT(*) FROM grounds WHERE worldid=".$gnd["worldid"]." AND city='".$gnd["city"]."' AND state=2 AND ownerid=".$kicker["id"]);
		$count = $result->fetch_array(MYSQLI_NUM);
		if($count[0]>0)
			switch($what) {
				case 1: //graffitti
					if($kicker["deeds"]>=5 && $kicker["konto"] >= 500) {
						if($victim["konto"] >= 5000) {
							$r_countRest = $db->query("SELECT COUNT(*) FROM grounds WHERE state=2 AND city='".$gnd["city"]."' AND ownerid=".$victim["id"]." AND worldid=".$gnd["worldid"]);
							$countRest = $r_countRest->fetch_array(MYSQLI_NUM);
							$db->query("UPDATE player SET deeds=deeds-5, konto=konto-".(500*$countRest[0]).", outgo=outgo+500 WHERE id=$kickerid");
							$db->query("UPDATE player SET konto=konto-5000, outgo=outgo+".(5000*$countRest[0])." WHERE id=".$vicim["id"]);
							$db->query("UPDATE grounds SET pays=pays+".(5000*$countRest[0])." WHERE city='$city' AND hex='$hex'");
							echo $victim["name"]."'s Filiale wurde mit Grafitti beschmiert! Das zu entfernen kostet ihn 5000 &euro;";
							sendMessage(0,$victim["id"],"Graffiti","<a href=\"game.php?city=".$gnd["city"]."&hex=".$gnd["hex"]."&x=".$gnd["x"]."&y=".$gnd["y"]."\" target=\"_top\">Eine Filiale</a> wurde mit Graffiti beschmiert! Das zu entfernen kostet Dich 5000 &euro;",5);
						} else echo "Dein Opfer hat kein Geld.";
					} else echo "Die Sprayer haben zu wenig Respekt vor dir!";
					break;
				case 2: //Gerächt
					if($kicker["deeds"]>=10) {
						$db->query("UPDATE grounds SET customers=0 WHERE hex='$hex' AND city='$city' AND worldid=$worldid");
						$db->query("UPDATE player SET deeds=deeds-10 WHERE id=$kickerid");
						echo "Ein Skandal! In ".$victim["name"]."'s Filiale gab's angeblich Stäcke von Pflanzen-Kadaver zu essen!";
						sendMessage(0,$victim["id"],"Gerächt","äber <a href=\"game.php?city=".$gnd["city"]."&hex=".$gnd["hex"]."&x=".$gnd["x"]."&y=".$gnd["y"]."\" target=\"_top\">eine Filiale</a> wurde ein mieses Gerächt verbreitet. Die Kunden blieben diesmal aus.",5);
					} else echo "Dir fallen keine Gerächte ein!";
					break;
				case 5: //Hygiene
					if($kicker["deeds"]>=20) {
						$averageRelia = 0;
						$counter = 0;
						if($result1 = $db->query("SELECT hex FROM grounds WHERE ownerid=".$kicker["id"])) {
							while($hex = $result1->fetch_array(MYSQLI_NUM))
								if($result = $db->query("SELECT * FROM hiredpersonel WHERE city='".$gnd["city"]."' AND hex='".$hex[0]."' AND worldid=".$gnd["worldid"]))
									while($pers = $result->fetch_array(MYSQLI_ASSOC)) {
										$averageRelia += $pers["relia"];
										$counter++;
									}
						}
						if($counter!=0) if($averageRelia/$counter > 0.65) {
							$averageRelia = 0;
							$counter = 0;
							$result = $db->query("SELECT * FROM hiredpersonel WHERE hex='".$gnd["hex"]."' AND city='".$gnd["city"]."' AND worldid=".$gnd["worldid"]);
							while($pers = $result->fetch_array(MYSQLI_ASSOC)) {
								$averageRelia += $pers["relia"];
								$counter++;
							}
							if($counter>0)
								$averageRelia /= $counter;
							else {
								echo("Hat noch keine Angestellten und damit noch nicht Eräffnet!");
								return;
							}

							if($counter !=0 && rand(0,round($averageRelia*5))==0) {
								$kontoneu = $victim["konto"];
								for($i=0; $i<$gnd["kitchensnum"]; $i++) {
									sellFixture($worldid,$gnd["city"],$gnd["hex"],$gnd["kitchensid"],$victim["id"]);
								}
								for($i=0; $i<$gnd["tablesnum"]; $i++) {
									sellFixture($worldid,$gnd["city"],$gnd["hex"],$gnd["tablesid"],$victim["id"]);
								}
								for($i=0; $i<$gnd["toiletsnum"]; $i++) {
									sellFixture($worldid,$gnd["city"],$gnd["hex"],$gnd["toiletsid"],$victim["id"]);
								}

								$db->query("UPDATE grounds SET stars=0 WHERE hex='".$gnd["hex"]."' AND city='".$gnd["city"]."' AND worldid=".$gnd["worldid"]);

								$result = $db->query("SELECT id FROM hiredpersonel WHERE hex='".$gnd["hex"]."' AND city='".$gnd["city"]."' AND worldid=".$gnd["worldid"]);
								while($id = $result->fetch_array(MYSQLI_NUM)) {
									firePersonel($worldid,$gnd["city"],$gnd["hex"],$id[0],$victim["id"]);
								}
								buildOnGround($worldid,$gnd["city"],$gnd["hex"],1,$victim["id"]);
								echo $victim["name"]."'s Filiale wurde geschlossen";
								sendMessage(0,$victim["id"],"Geschlossen","<a href=\"game.php?city=".$gnd["city"]."&hex=".$gnd["hex"]."&x=".$gnd["x"]."&y=".$gnd["y"]."\" target=\"_top\">Eine Filiale</a> musste wegen unzureichender Hygiene geschlossen werden!",5);
							} else {
								$stars = round($averageRelia*100/5 -15);
								if($stars<0)
									$stars = 0;
								$db->query("UPDATE grounds SET stars=$stars WHERE hex='".$gnd["hex"]."' AND city='".$gnd["city"]."' AND worldid=".$gnd["worldid"]);
								echo $victim["name"]."'s Filiale wurde mit $stars Sternen ausgezeichnet!";
								sendMessage(0,$victim["id"],"Vorzäglich","<a href=\"game.php?city=".$gnd["city"]."&hex=".$gnd["hex"]."&x=".$gnd["x"]."&y=".$gnd["y"]."\" target=\"_top\">Eine Filiale</a> wurde mit $stars Sternen ausgezeichnet! Gläckwunsch.",4);
							}
							$db->query("UPDATE player SET deeds=deeds-20 WHERE id=".$kicker["id"])
								or die("error during deeds update");
						} else echo "Mit deinem Drecksladen wärd ich nicht die Hygieneämter zu mehr Aufmerksamkeit anregen!";
					} else echo "Gedulde dich noch ein bisschen!";
					break;
			}
		else echo "Du hast in dieser Stadt kein Restaurant.";
	}
}

function addShare($shareholder,$company,$value,$ia,$count) {
	$db = makeConn();
	if($ia>50000) {
		if($ia/$count>=1) {
			if($ia/$count<=500) {
				$query = "INSERT INTO shares (forsale, count, shareholder, company, value, oldia) VALUES (0, $count, $shareholder, $company, $value, $ia)";
				$db->query($query) or die("Error during share adding: ".$query);
			} else return "Der Wert pro Aktie wäre zu hoch! Maximaler Einstiegswert: 500 &euro;";
		} else return "Der Wert pro Aktie wäre zu gering! Minimaler Einstiegswert: 1 &euro;";
	} else return "Dein Bärsenwert hat noch 50000 noch nicht erreicht";
	$db->Close();
}

function buyShare($buyer,$count,$share,$price) {
	if($count<=$share["count"]) {
		$plr = getPlayer($buyer);
		if($plr["konto"]>=$price*$count || $buyer==$share["shareholder"]) {
			$db = makeConn();
			//direct buying
			$query = "SELECT COUNT(*) FROM shares WHERE forsale=0 AND shareholder=$buyer AND company=".$share["company"]." LIMIT 1";
			$r_oshares = $db->query($query) or die("Error during share buying: ".$query);
			$oshares = $r_oshares->fetch_array(MYSQLI_NUM);
			$ag = getPlayer($share["company"]);
			if($oshares[0]==0) {
				$query = "INSERT INTO shares (forsale, shareholder, count, company, value, oldia) VALUES (0, $buyer, $count, ".$share["company"].", $price, ".$ag["ia"].")";
				$db->query($query) or die("Error during share buying: ".$query);
				$query = "UPDATE shares SET count=count-$count WHERE id=".$share["id"];
				$db->query($query) or die("Error during share buying: ".$query);
			} else {
				$query = "UPDATE shares SET value=(count*value+$count*$price)/(count+$count), count=count+$count WHERE forsale=0 AND shareholder=$buyer AND company=".$share["company"];
				$db->query($query) or die("Error during share buying: ".$query);
				$query = "UPDATE shares SET count=count-$count WHERE id=".$share["id"];
				$db->query($query) or die("Error during share buying: ".$query);
			}

			//money money money
			$query = "UPDATE player SET konto=konto+".$price*$count.", outgo=outgo-".$price*$count." WHERE id=".$share["shareholder"];
			$db->query($query) or die("Error during share buying: ".$query);
			$query = "UPDATE player SET konto=konto-".$price*$count.", outgo=outgo+".$price*$count." WHERE id=".$buyer;
			$db->query($query) or die("Error during share buying: ".$query);

			$query = "DELETE FROM shares WHERE count=0";
			$db->query($query) or die("Error during share buying: ".$query);

			//message
			if($buyer!=$share["shareholder"])
				sendmessage(0,$share["company"],"Aktien",$plr["name"]." hat von dir Aktien im Wert von ".number_format($count*$price,2,",","")." gekauft!",3);

			$db->Close();
			return "Aktien in Wert von ".number_format($count*$price,2,",","")." gekauft.";
		} return "Nicht genug Geld.";
	} else return "Es sind weniger als $count Aktien zum Verkauf angeboten.";
}

function sellShare($seller,$count,$share,$price) {
	if($count<=$share["count"]) {
		$plr = getPlayer($seller);
		$db = makeConn();
		//direct selling
		$ag = getPlayer($share["company"]);
		$query = "SELECT COUNT(*) FROM shares WHERE forsale=1 AND shareholder=".$share["company"]." AND company=".$share["company"]." LIMIT 1";
		$r_oshares = $db->query($query) or die("Error during share selling: ".$query);
		$oshares = $r_oshares->fetch_array(MYSQLI_NUM);
		if($oshares[0]==0) {
			$query = "INSERT INTO shares (forsale, shareholder, count, company, value, oldia) VALUES (1, ".$ag["id"].", $count, ".$share["company"].", $price, ".$ag["ia"].")";
			$db->query($query) or die("Error during share selling: ".$query);
			$query = "UPDATE shares SET count=count-$count WHERE id=".$share["id"];
			$db->query($query) or die("Error during share selling: ".$query);
		} else {
			$query = "UPDATE shares SET value=(count*value+$count*$price)/(count+$count), count=count+$count WHERE forsale=1 AND shareholder=".$ag["id"]." AND company=".$share["company"];
			$db->query($query) or die("Error during share selling: ".$query);
			$query = "UPDATE shares SET count=count-$count WHERE id=".$share["id"];
			$db->query($query) or die("Error during share selling: ".$query);
		}

		//money money money
		$query = "UPDATE player SET konto=konto-".$price*$count.", outgo=outgo+".$price*$count." WHERE id=".$share["company"];
		$db->query($query) or die("Error during share selling: ".$query) or die("Error during share selling: ".$query);
		$query = "UPDATE player SET konto=konto+".$price*$count.", outgo=outgo-".$price*$count." WHERE id=".$seller;
		$db->query($query) or die("Error during share selling: ".$query) or die("Error during share selling: ".$query);
		$_POST["drawGuthaben"] = $plr["konto"]+$price*$count;

		if($share["company"]!=$seller)
			sendmessage(0,$share["company"],"Aktien",$plr["name"]." hat Aktien von dir im Wert von ".number_format($count*$price,2,",","")." an dich verkauft!",3);

		$query = "DELETE FROM shares WHERE count=0";
			$db->query($query) or die("Error during share selling: ".$query);



		// indirect selling
		$db->Close();
		return "Aktien in Wert von ".number_format($count*$price,2,",","")." verkauft.";
	} else return "Es sind weniger als $count Aktien zum vorhanden.";
}

function insolvency($playerid,$nurBisKontoPositiv) {
	$db = makeConn();
	$plr = getPlayer($playerid);
	$r_gnd = $db->query("SELECT * FROM grounds WHERE ownerid=$playerid");
	while($gnd = $r_gnd->fetch_array(MYSQLI_ASSOC)) {
		$r_pers = $db->query("SELECT id FROM hiredpersonel WHERE hex='".$gnd["hex"]."' AND city='".$gnd["city"]."'");
		while($pers = $r_pers->fetch_array(MYSQLI_NUM)) {
			firePersonel($gnd["worldid"],$gnd["city"],$gnd["hex"],$pers[0],$playerid);
		}
		$fixarray = array("toilet","table","kitchen");
		foreach($fixarray as $fix) {
			for($i=$gnd[$fix."snum"];$i>0;$i--)
				sellFixture($gnd["worldid"],$gnd["city"],$gnd["hex"],$gnd[$fix."sid"],$playerid);
		}
		buildOnGround($gnd["worldid"],$gnd["city"],$gnd["hex"],1,$playerid);
		sellGround($gnd["worldid"],$gnd["city"],$gnd["hex"],$playerid);
		if($bisKontoPositiv)
			$plr = getPlayer($playerid); //TODO: Solange bis Konto positiv?
	}
	$db->query("DELETE FROM shares WHERE company=$playerid");
	$db->query("UPDATE player SET konto=1500000 WHERE id=$playerid");
	$db->Close();
	sendMessage(0,$playerid,"Insolvenz","Alles geschlossen und verkauft. Du fängst von vorn an!",5);
}

function restartGame($worldid) {
	$db = makeConn();

	// Highscore update
	if(!table_exists("highscore")) {
		createHighscoreTableSQL($worldid);
		alert("Highscore-Tabelle musste erstellt werden");
	}
	$r_num = $db->query("SELECT round FROM highscore WHERE 1 ORDER BY round DESC LIMIT 1");
	$num = $r_num->fetch_array(MYSQLI_NUM);
	$db->query("INSERT INTO highscore (round) VALUES (".($num[0]+1).")");
	$r_bd = $db->query("SELECT birthday FROM world WHERE 1"); //Mehre welten?
	$bd = $r_bd->fetch_array(MYSQLI_NUM);
	$playtime = floor((time()-$bd[0])/(24*60*60));
	$r_id = $db->query("SELECT id FROM player WHERE 1");
	while($id = $r_id->fetch_array(MYSQLI_NUM)) {
		if(citiesOwnedByPlayer($worldid,$id[0])>=10)
			$db->query("INSERT INTO highscore2 (playerid,round,playtime) VALUES ($id[0],$num[0],$playtime)");
	}

	//neue Welt
	$db->query("DROP TABLE chooseablepersonel");
	$db->query("DROP TABLE cities");
	$db->query("DROP TABLE fixtures");
	$db->query("DROP TABLE grounds");
	$db->query("DROP TABLE hiredpersonel");
	$db->query("DROP TABLE lose");
	$db->query("DROP TABLE shares");
	$db->query("DROP TABLE statements");
	$r_r = $db->query("SELECT round FROM world");
	$round = $r_r->fetch_array(MYSQLI_NUM);
	$db->query("DROP TABLE world");

	createGroundsTableSQL($worldid);
	createFixturesTableSQL($worldid);

	$worldmap = makemap(16,16,0.40);
	createWorldSQL($worldmap,$round[0]);
	createCitiesSQL($worldmap,$worldid);
	createHiredPersonelTableSQL($worldid);
	createChoosablePersonelTableSQL($worldid);
	createStatementsTableSQL();
	createSharesTableSQL($worldid);

	createLoseTableSQL($worldid);

	// Nachrichten zurücksetzen!
	$db->query("DELETE FROM messages WHERE 1");

	$r_plr = $db->query("SELECT * FROM player WHERE 1");
	while($plr = $r_plr->fetch_array(MYSQLI_ASSOC)) {
		addStatement($plr["id"],getNextTick($worldid),$plr["konto"],$plr["ia"]);
		addStatement($plr["id"],getNextTick($worldid),$plr["konto"],$plr["ia"]);
		sendMessage(0,$plr["id"],"Spiel-Neustart","Willkommen in der neuen Runde!<br>In der Highscore könnt ihr sehen, wer der beste war.",5);
		if($plr["email"]!=null)
			mail($plr["email"],"Restaurante: Restart","Willkommen in der neuen Runde!\nIn der Highscore könnt ihr sehen, wer der beste war.\nhttp://spz.kilu.de/restaurante/","From: no-reply-announces@restaurante.so");
	}

	$db->query("UPDATE player SET RVvote=0, deeds=0, ia=0, konto=1500000, outgo=0, energycost=0, pays=0, ingo=0, zinsen=0, others=0, customers=0 WHERE 1");
	$db->Close();
}
?>
