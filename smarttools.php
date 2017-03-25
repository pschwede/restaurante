<?php

include_once "connector.php";

function citiesOwnedByPlayer($worldid,$playerid) {
	$plr = getPlayer($playerid);
	$db = makeConn();
	/*$r_gnd = $db->query("SELECT * FROM grounds WHERE ownerid=$playerid AND state=2 AND worldid=".$worldid);
	$gnd_array = Array();
	$tmpcty = "";
	$sum = 0;
	$num = 0;
	while($gnd = $r_gnd->fetch_array(MYSQLI_ASSOC)) {
		$cty = getCity($worldid,$gnd["city"]);
		if($tmp!=$gnd["city"]) {
			$tmpcty = $gnd["city"];
			$sum = $gnd["customers"]/$cty["population"];
		} else {
			$sum += $gnd["customers"]/$cty["population"];
			if($sum >=0.8)
				$num += 1;
		}
	}*/

	$r_gnd = $db->query("SELECT * FROM grounds WHERE customers>0 AND ownerid=$playerid");
	$cities= Array();
	while($gnd = $r_gnd->fetch_array(MYSQLI_ASSOC)) {
		if(!in_array($gnd["city"],$cities))
			$cities[] = $gnd["city"];
	}
	$num = 0;
	foreach($cities as $city) {
		$r_owncust = $db->query("SELECT SUM(customers) FROM grounds WHERE ownerid=$playerid AND city='".$city."'");
		$owncust = $r_owncust->fetch_array(MYSQLI_NUM);
		$r_cust = $db->query("SELECT SUM(customers) FROM grounds WHERE city='".$city."'");
		$cust = $r_cust->fetch_array(MYSQLI_NUM);
		if($owncust[0]/$cust[0]>=0.8)
			$num++;
	}
	$db->Close();
	return $num;
}

function addStatement($playerid,$time,$konto,$ia) {
	$db = makeConn();
	$query = "INSERT INTO statements (playerid,time,konto,ia) VALUES ($playerid,$time,$konto,$ia)";
	$db->query($query) or die("error during statment addition: ".$query);
	$query = "SELECT COUNT(*) FROM statements WHERE playerid=$playerid";
	$result = $db->query($query) or die("error during statment addition: ".$query);
	$count = $result->fetch_array(MYSQLI_NUM);
	if($count[0] > 40) //Maximale Anzahl von Statistischen Einträgen
		$query = "DELETE FROM statements WHERE playerid=$playerid ORDER BY time ASC, id DESC LIMIT 1";
		$db->query($query);
	$db->Close();
}

function getPlayer($id) {
	$db = makeConn();
	if ($resultat = $db->query('SELECT * FROM player WHERE id='.$id.' LIMIT 1')) {
		// Antwort der Datenbank in ein Objekt übergeben und
		// mithilfe der while-Schleife durchlaufen
		if(!$daten = $resultat->fetch_array() )
			die("error loading player ".$id);
		// Speicher freigeben
		$resultat->close();
	} else
		die("getPlayer($id): Es konnten keine Daten aus der Datenbank ausgelesen werden: plrid=".$id);

	$db->Close();
	return $daten;
}

function getMapFromSQL($worldid,$hex) {
	$map = array();

	$db = makeConn();

	if (hexdec($hex)>0) {
		if($result = $db->query("SELECT * FROM cities WHERE worldid=".$worldid." AND hex='".$hex."' LIMIT 1")) {
			$city = $result->fetch_array(MYSQLI_ASSOC);
			$result->Close();
			$map["height"]=$city["height"];
			$map["width"]=$city["width"];
			$map["strdichte"]=$city["strdichte"];
			for ($y = 0; $y < $map["height"]; $y++) {
				$map[] = array();
				for ($x = 0; $x < $map["height"]; $x++) {
					$map[$x][$y] = substr($city["y".$y],2*$x,2);
				}
			}
		} else
			die("Error loading city ".$hex);
	} else {
		if($result = $db->query("SELECT * FROM world WHERE id=".$worldid." LIMIT 1")) {
			$world = $result->fetch_array(MYSQLI_ASSOC);
			$result->Close();
			$map["height"]=$world["height"];
			$map["width"]=$world["width"];
			$map["strdichte"]=$world["strdichte"];
			for ($y = 0; $y < $map["height"]; $y++) {
				$map[] = array();
				for ($x = 0; $x < $map["height"]; $x++) {
					$map[$x][$y] = substr($world["y".$y],2*$x,2);
				}
			}
		} else
			die("Error loading world");
	}

	$db->Close();

	return $map;
}

function getGround($worldid,$city,$hex) {
	$db = makeConn();
	if (hexdec($hex)>0) {
		if($result = $db->query("SELECT * FROM grounds WHERE worldid=".$worldid." AND hex='".$hex."' AND city='".$city."' LIMIT 1"))
			$ground = $result->fetch_array();
		else echo "error loading state of ground";
	}
	$db->Close();
	return $ground;
}

function getCity($worldid,$hex) {
	$db = makeConn();
	if (hexdec($hex)>0) {
		if($result = $db->query("SELECT * FROM cities WHERE worldid=".$worldid." AND hex='".$hex."' LIMIT 1"))
			$city = $result->fetch_array();
		else
			die("error loading city");
	}
	$db->Close();
	return $city;
}

function countPatterns($area,$pattern1,$pattern2,$pattern3) {
	$i = 0;
	if(hexdec($pattern1)>0 || hexdec($pattern2)>0 || hexdec($pattern3)>0)
		foreach($area as $field) { //x,y,pattern
			$xyp = explode(",",$field);
			if(hexdec($xyp[2])>0) {
				$i++;
			}
		}
	else
		foreach($area as $field) { //x,y,pattern
			$xyp = explode(",",$field);
			if($xyp[2] == $pattern1 || $xyp[2] == $pattern2 || $xyp[2] == $pattern3) {
				$i++;
			}
		}
	return $i;
}

function getNbhOfArea(&$map, $area) {
	$nbh = array();
	foreach($area as $field) {
		$xyp = explode(",",$field);
		$nbh = array_merge($nbh,getNbh($map,$xyp[0],$xyp[1]));
	}
	//array_unique($nbh);
	return $nbh;
}

function getNbh(&$map, $x, $y) {
	$nbh = array();
	$dx = -1;	$dy = -1;
	$nbh[] = (($x+$dx).",".($y+$dy).",".$map[$x+$dx][$y+$dy]);
	$dx = 0;
	$nbh[] = (($x+$dx).",".($y+$dy).",".$map[$x+$dx][$y+$dy]);
	$dx = 1;
	$nbh[] = (($x+$dx).",".($y+$dy).",".$map[$x+$dx][$y+$dy]);
	$dy = 0;
	$nbh[] = (($x+$dx).",".($y+$dy).",".$map[$x+$dx][$y+$dy]);
	$dy = 1;
	$nbh[] = (($x+$dx).",".($y+$dy).",".$map[$x+$dx][$y+$dy]);
	$dx = 0;
	$nbh[] = (($x+$dx).",".($y+$dy).",".$map[$x+$dx][$y+$dy]);
	$dx = -1;
	$nbh[] = (($x+$dx).",".($y+$dy).",".$map[$x+$dx][$y+$dy]);
	$dy = 0;
	$nbh[] = (($x+$dx).",".($y+$dy).",".$map[$x+$dx][$y+$dy]);
	$dx = -2;
	$nbh[] = (($x+$dx).",".($y+$dy).",".$map[$x+$dx][$y+$dy]);
	$dx = 2;
	$nbh[] = (($x+$dx).",".($y+$dy).",".$map[$x+$dx][$y+$dy]);
	$dx = 0; $dy = 2;
	$nbh[] = (($x+$dx).",".($y+$dy).",".$map[$x+$dx][$y+$dy]);
	$dy = -2;
	$nbh[] = (($x+$dx).",".($y+$dy).",".$map[$x+$dx][$y+$dy]);
	return $nbh;
}

function countPatternsInMap(&$map, $pattern) {
	$num = 0;
	if (hexdec($pattern)>0) {
		for ($y=0; $y<$map["height"]; $y++) {
			for ($x=0; $x<$map["width"]; $x++) {
				if(hexdec($map[$x][$y])>0) {
					$num++;
				}
			}
		}
	} else {
		for ($y=0; $y<$map["height"]; $y++) {
			for ($x=0; $x<$map["width"]; $x++) {
				if($map[$x][$y] == $pattern) {
					$num++;
				}
			}
		}
	}
	return $num;
}

function getSizeOfArea(&$map, $x, $y, $pattern, &$old) {
	$a = 0;
	if (($x < 0) || ($x >= $map["width"])) return $a;
	if (($y < 0) || ($y >= $map["height"])) return $a;
	if (!in_array(($x.",".$y.",".$map[$x][$y]),$old)
			&& ($map[$x][$y] == $pattern || (hexdec($pattern)>0 && hexdec($map[$x][$y])>0))) {
		$a=1;
		$old[] = $x.",".$y.",".$map[$x][$y];
		$a += getSizeOfArea($map, $x+1, $y, $pattern, $old);
		$a += getSizeOfArea($map, $x, $y+1, $pattern, $old);
		$a += getSizeOfArea($map, $x-1, $y, $pattern, $old);
		$a += getSizeOfArea($map, $x, $y-1, $pattern, $old);
	}
	return $a;
}

function getFixture($id) {
	$db = makeConn();
	$result = $db->query("SELECT * FROM fixtures WHERE id=".$id) or die("error unknown fixture '$i'");
	$fogr = $result->fetch_array();

	$db->Close();
	return $fogr;
}

function getRemainingSpaceOnGround($worldid,$city,$hex) {
	$db = makeConn();
	$gnd = getGround($worldid,$city,$hex);
	$db->Close();
	if($gnd["state"]>0) {
		$space = $gnd["size"];
		$takenSpace = 0;
		if($gnd["state"]==2) {
			$space+=220;
		}
		if($gnd["kitchensid"]>0) {
			$tmp = getFixture($gnd["kitchensid"]);
			$takenSpace += $gnd["kitchensnum"]*$tmp["space"];
		}
		if($gnd["tablesid"]>0) {
			$tmp = getFixture($gnd["tablesid"]);
			$takenSpace += $gnd["tablesnum"]*$tmp["space"];
		}
		if($gnd["toiletsid"]>0) {
			$tmp = getFixture($gnd["toiletsid"]);
			$takenSpace += $gnd["toiletsnum"]*$tmp["space"];
		}
		return ($space-$takenSpace);
	} else return 0;
}

function getAreaGrounds($gnd) {
	$grounds = array();

	$area = array();
	$map = getMapFromSQL($gnd["worldid"],$gnd["city"]);
	getSizeOfArea($map,$gnd["x"],$gnd["y"],$map[$gnd["x"]][$gnd["y"]],$area);
	foreach($area as $xypstr) {
		$xyp = explode(",",$xypstr);
		$grounds[] = getGround($gnd["worldid"],$gnd["city"],$xyp[2]);
	}
	return $grounds;
}

function getMaxCustOfGround($gnd) {
	if($gnd["state"]==2) {
		$maxcust = 99999999999999999;
		$fxts = array(array($gnd["toiletsid"],$gnd["toiletsnum"]),array($gnd["tablesid"],$gnd["tablesnum"]),array($gnd["kitchensid"],$gnd["kitchensnum"]));
		for($i=0; $i<count($fxts); $i++) {
			if($fxts[$i][1]>0) {
				$perscust = 0.0;
				$zuverl = 0;
				$counter = 0;
				$db = makeConn();
				if($result = $db->query("SELECT * FROM hiredpersonel WHERE jobid=".($i)." AND hex='".$gnd["hex"]."' AND city='".$gnd["city"]."'")) {
					while($pers = $result->fetch_array(MYSQLI_ASSOC)) {
						$perscust += $pers["capa"];
						$zuverl += $pers["relia"];
						$counter ++;
					}
				}
				$db->Close();
				if($counter>0)
					$zuverl /= $counter;
				$perscust *= $zuverl;
				if($perscust>0) {
					$fxt = getFixture($fxts[$i][0]);
					if($perscust>$fxts[$i][1]*$fxt["personal"])
						$perscust = $fxts[$i][1]*$fxt["personal"];
					if($maxcust>$fxt["max_customers"]*($perscust/$fxt["personal"]))
						$maxcust=$fxt["max_customers"]*($perscust/$fxt["personal"]);
				} else $maxcust=0;
			} else $maxcust=0;
		}
		return pow($maxcust,2);
	}
}

function getAttrOfGround($gnd) {
	$attr = 0;
	if($gnd["tablesnum"]>0) {
		$fxt = getFixture($gnd["tablesid"]);
		$attr += $fxt["attr"];
	}
	if($gnd["kitchensnum"]>0) {
		$fxt = getFixture($gnd["kitchensid"]);
		$attr += $fxt["attr"];
	}
	if($gnd["toiletsnum"]) {
		$fxt = getFixture($gnd["toiletsid"]);
		$attr += $fxt["attr"];
	}
	if($attr!=0) {
		$map = getMapFromSQL($gnd["worldid"],$gnd["city"]);
		$nbh = getNbh($map,$gnd["x"],$gnd["y"]);
		$num = 0;
		foreach($nbh as $xypstring) {
			$xyp = explode(",",$xypstring);
			if(hexdec($xyp[2])>0) {
				$ng = getGround($gnd["worldid"],$gnd["city"],$xyp[2]);
				if($ng["state"]==4)
					$num++;
				//alert($num);
			}
			if($xyp[2]=="GG" || $xyp[2]=="GH" || $xyp[2]=="GI")
				$num += 3;
			if($xyp[2]=="SG" || $xyp[2]=="SH" || $xyp[2]=="SI")
				$num += 3;
		}
		$attr *= 9/$gnd["price4food"];
		$attr += 5*$gnd["stars"];
		$attr += $num;
	}
	return $attr;
}

function getEnergyOfGround($gnd) {
	$nrj = 0;
	$fxts = array(array($gnd["tablesid"],$gnd["tablesnum"]),array($gnd["kitchensid"],$gnd["kitchensnum"]),array($gnd["toiletsid"],$gnd["toiletsnum"]));
	foreach($fxts as $idnum) {
		if($idnum[1]>0) {
			$fxt = getFixture($idnum[0]);
			$nrj += $fxt["energy"]*$idnum[1];
		}
	}
	return $nrj;
}

function getNextTick($worldid) {
	$db = makeConn();
	$result = $db->query("SELECT nexttick FROM world WHERE id=$worldid") or die("error loading nexttick");
	$tick = $result->fetch_array(MYSQLI_NUM);
	$db->Close();
	return $tick[0];
}

function getFinancialTendency($playerid) {
	$db = makeConn();
	if($result = $db->query("SELECT konto FROM statements WHERE playerid=$playerid ORDER BY time DESC, id ASC LIMIT 2")) {
		$kontos = array();
		while($konto = $result->fetch_array(MYSQLI_NUM)) {
			$kontos[] = $konto[0];
		}
		$db->Close();
		if(count($kontos)>1)
			return ($kontos[0]-$kontos[1])*100 / $kontos[0];
		} else {
			return 999;
	}
}

function table_exists($table_name){
	$db = makeConn();
	$tablecount = $db->query("SELECT COUNT(*) FROM ".$table_name);
	$exists = $tablecount != FALSE && $tablecount->num_rows >= 1;

	$db->Close();
	return $exists;
}

function resetNextTickTime($worldid) {
	$db = makeConn();
	$time = time();
	$db->query("UPDATE world SET nexttick=".$time." WHERE id=".$worldid) or die("error resetting nextTick");
	echo "Next Tick set to ".date("G:i:s",$time);
	$db->Close();
}

function sendMessage($fromid,$toid,$title,$text,$state) {
	$db = makeConn();
	$time = time();
	$text = str_replace("\n","<br>",$text);
	$msgcount = $db->query("SELECT COUNT(*) FROM messages WHERE fromid=$fromid AND toid=$toid AND text=$text AND title=$title");
	if(!$msgcount->num_rows >= 1) {
		$query="INSERT INTO messages (fromid,toid,time,text,title,state) VALUES ($fromid,$toid,$time,'$text','$title',$state)";
		$db->query($query) or die("Error during sending message: ".$query);
	} else {
		echo "Nachricht nicht versendet: Nachricht wurde schonmal versandt.";
	}

	$db->Close();
}

function forceLogOuts($worldid,$time,$hours) {
	$db = makeConn();
	$query = "SELECT * FROM player WHERE lastdeed<".($time-$hours*60*60);
	if($r_player = $db->query($query)) {
		while($plr = $r_player->fetch_array(MYSQLI_ASSOC)) {
			if($db->query("UPDATE player SET lastlogout=$time WHERE id=".$plr["id"]))
				if($plr["id"]==$_SESSION["id"]) {
					//alert("Du hast zu lange nichts gemacht und wurdest nach $hours Stunden ausgeloggt!");
					$_SESSION["angemeldet"] = false;
				}
		}
	}
	$db->Close();
}

function updateLastDeed($worldid,$time,$playerid,$hours) {
	$db = makeConn();
	$query = "UPDATE player SET lastdeed=$time WHERE id=$playerid";
	$db->query($query);
	$db->Close();
}

?>
