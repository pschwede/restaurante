<?php

function makemap($height,$width,$strdichte) {
	$map = array();

	$map["height"]=$height;
	$map["width"]=$width;
	$map["strdichte"]=$strdichte;
	
	$nummer = 1;
	for ($y = 0; $y < $height; $y++) {
		$map[] = array();
		for ($x = 0; $x < $width; $x++) {
			switch (rand(0,2)) {
				case 0: // Grundstück mit nummer
					$map[$x][] = ($nummer < 16) ? "0".dechex($nummer) : dechex($nummer);
					$nummer++;
					break;
				case 1: // Baum
					switch (rand(0,2)) {
						case 0:
							$map[$x][] = "GG"; //!! Alle nicht-Grundstücke dürfen keine HEX-Buchstaben enthalten
							break;
						case 1:
							$map[$x][] = "GH"; //!! Alle nicht-Grundstücke dürfen keine HEX-Buchstaben enthalten
							break;
						case 2:
							$map[$x][] = "GI"; //!! Alle nicht-Grundstücke dürfen keine HEX-Buchstaben enthalten
							break;
					}
					break;
				case 2: // Haus
					switch (rand(0,2)) {
						case 0:
							$map[$x][] = "HG"; //!! Alle nicht-Grundstücke dürfen keine HEX-Buchstaben enthalten
							break;
						case 1:
							$map[$x][] = "HH"; //!! Alle nicht-Grundstücke dürfen keine HEX-Buchstaben enthalten
							break;
						case 2:
							$map[$x][] = "HI"; //!! Alle nicht-Grundstücke dürfen keine HEX-Buchstaben enthalten
							break;
					}
			}
		}
	}

	//Straßen in x-Richtung
	$mem = array();
	for ($i = 0; $i< $height*$strdichte*0.5; $i++) { // Straßenanzahl = 0.5*höhe*strdichte
		$ycoord = rand(0,$height-1);
		while(in_array($ycoord,$mem) || in_array($ycoord-1,$mem) || in_array($ycoord+1,$mem)) {
			$ycoord = rand(0,$height-1);
		}
		$mem[] = $ycoord;
		for ($x = 0; $x <= $width; $x++) {
			$map[$x][$ycoord] = "SG";
		}
	}
	// Straßen in y-Richtung
	$mem = array();
	for ($i = 0; $i < $width*$strdichte*0.5; $i++) { // Straßenanzahl = 0.5*höhe*strdichte
		$xcoord = rand(0,$width-1);
		while(in_array($xcoord,$mem) || in_array($xcoord-1,$mem) || in_array($xcoord+1,$mem)) {
			$xcoord = rand(0,$width-1);
		}
		$mem[] = $xcoord;
		for ($y = 0; $y < $width; $y++) {
			$map[$xcoord][$y] = ($map[$xcoord][$y] == "SG") ? "SI" : "SH";
		}
	}
	return $map;
}

function makeCityName() {
	$part1 = array("Frank","Dres","See","Enten","Münch","Ber","Ingols","Karls","Kass","Müll",
					"Hall","Sieg","Leip","Branden","Pots","Braun","Mann","Nürn","Darm","Heidel",
					"Wies","Wein","Luck","Würz","Saar","Laub","Baum","Feld","Peters","Bergen",
					"Svens","Nord","Süd","West","Ost","Wind","Falken","Gött","Hobb","Schlaube");
	$part2 = array("furt","low","hausen","en","lin","stadt","ruhe","el","e","zig","rose",
					"burg","dam","schweig","heim","berg","baden","au","brücken","brück",
					"tal","see","lingen","rode","ingen","den");
	return $part1[rand(0,count($part1)-1)].$part2[rand(0,count($part2)-1)];
}

function makePersonName($fm) {
	switch ($fm) {
		case 0:
			$part1 = array("Ursula","Karin","Helga","Sabine","Ingrid","Renate","Monika","Susanne","Gisela",
							"Petra","Birgit","Andrea","Brigitte","Claudia","Erika","Christa","Erika","Krista",
							"Stefanie","Gertrud","Elisabeth","Maria","Angelika","Heike","Gabriele","Kathrin",
							"Ilse","Nicole","Anja","Barbara","Hildegard","Martina","Ingeborg","Gerda","Marion",
							"Jutta","Ute","Hannelore","Irmgard","Christine","Inge","Christina","Sylvia","Margarete",
							"Kerstin","Marianne","Edith","Marta","Sandra","Waltraut","Hina","Yui","Miyu","Hoji","Hiko");
			break;
		case 1:
			$part1 = array("Peter","Michael","Thomas","Andreas","Wolfgang","Klaus","Jürgen","Günther","Stefan","Christian",
							"Uwe","Werner","Horst","Frank","Dieter","Manfred","Gerhard","Hans","Bernd","Torsten","Matthias",
							"Hemlut","Walter","Heinz","Martin","Jörg","Rolf","Jens","Sven","Alexander","Jan","Reiner","Holger",
							"Karl","Dirk","Joachim","Ralf","Carten","Herbert","Oliver","Wilhelm","Kurt","Markus","Heinrich",
							"Harald","Gert","Paul","Andre","Norbert");
			break;
	}
	$part2 = array("Müller","Schmidt","Richter","Herrmann","Schneider","Wagner","Fischer","Schäfer","Köhler","Lehmann",
					"Weber","Neumann","Bauer","Berger","Schulze","Becker","Günther","Meyer","Lange","Krüger","Werner",
					"Stein","Schulz","Franke","Böhme","Kühn","Krause","Zimmermann","Wolf","Walter","Lorenz","Klein",
					"Koch","Arnold","Schreiber","Hartmann","Seifert","Scholz","Riedel","Schubert","Schwarz","Schwede",
					"Heinze","Uhlig","Seidel","Sato","Suzuki","Kato","Takahashi");
	return $part1[rand(0,count($part1)-1)]." ".$part2[rand(0,count($part2)-1)];
}

function makeNewChoosablePersonelTable($worldid) {
	$db = makeConn();
	
	$db->query("TRUNCATE TABLE choosablepersonel") or die("error deleting old choosable personel table"); //alle inhalte löschen
	
	$query = "SELECT * from cities WHERE worldid=$worldid";
	if ($result = $db->query($query)) {
		while($city = $result->fetch_array()) {
			for($i=0; $i<=$city["popdichte"]; $i++) {
				$name = makePersonName(rand(0,1));
				$relia = rand(1,20)*0.05;
				$jobid = rand(0,2);
				$capa = rand(1,5);
				$pay = rand(500,1000);
				$pay *= 1+$jobid*$jobid*($relia*$capa)*($relia*$capa)*0.1;
				$db->query("INSERT INTO choosablepersonel (city, name, relia, pay, jobid, capa, worldid) 
							VALUES ('".$city["hex"]."','$name',$relia,$pay,$jobid,$capa,$worldid)") or die("error adding personel");
			}
		}
	} else die("error creating choosable personel table #2 ".$query);
	$db->Close();
}

?>
