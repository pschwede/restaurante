<?php
session_start();

include_once "config.php";
include_once "smarttools.php";
include_once "gamemaster.php";
include_once "displays.php";

// local globals 1/2
$worldid = 1;
$map = getMapFromSQL($worldid,$_GET["city"]);
$nextTick = getNextTick($worldid);
$time = time();

forceLogOuts($worldid,$time,1);
updateLastDeed($worldid,$time,$_SESSION["id"],1);

$_SESSION["angemeldet"] == true or header('Location: http://'.$hostname.($path == '/' ? '' : $path).'/index.php');;

$hostname = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']);

if (!isset($_SESSION['angemeldet']) || !$_SESSION['angemeldet']) {
	header('Location: http://'.$hostname.($path == '/' ? '' : $path).'/index.php');
	exit;
}

if (isset($_GET["insolvency"]) && $_GET["insolvency"]==$_SESSION["id"]) {
	insolvency($_SESSION["id"],false);
}

function thisIsCityMap() {
	return (isset($_GET["city"]) && (!isset($_GET["ov"]) || !isset($_GET["furnish"])));
}

?>

<html>
	<head>
		<title>Restaurante</title>
		<link rel="stylesheet" type="text/css" href="style.css">
		<link rel="alternate" type="application/rss+xml" title="Hinweise zu beendeten Runden" href="http://spz.kilu.de/restaurante/feed.php">
		<script type="text/javascript">
function info(x,y) {
	<?php if (isset($_GET["city"])) {
			echo "adr = \"game.php?x=\"+x+\"&y=\"+y+\"&city=".$_GET["city"]."\";\n";
			echo "window.location.href = adr;\n";
	} elseif (isset($_GET["x"],$_GET["y"])) {
		echo "if(x==".$_GET["x"]."&&y==".$_GET["y"].")\n";
		echo "adr = \"game.php?city=".$map[$_GET["x"]][$_GET["y"]]."\";\n";
		echo "else ";
		echo "adr = \"game.php?x=\"+x+\"&y=\"+y;\n";
		echo "window.location.href = adr;\n";
	} else {
		echo "adr = \"game.php?x=\"+x+\"&y=\"+y;";
		echo "window.location.href = adr;\n";
	}?>

}

function hide(id,from_y,t,to_y,v) {
	document.getElementById(id).style.top = from_y+"px";
	if((from_y>to_y && v<0) || (from_y<to_y && v>0)) {
		window.setTimeout("hide('"+id+"',"+(t*t*v)+","+(t+1)+","+to_y+","+v+")",40);
	} else if((from_y<=to_y && v<0) || (from_y>=to_y && v>0)) {
		document.getElementById(id).style.visibility = "hidden";
	}
}

function doTimer(id,nextTick) {
	var now = new Date();
	if(nextTick>=Math.floor(now.getTime()/1000)) {
		var timeto = Math.floor(nextTick-now.getTime()/1000);
		hours = Math.floor(timeto/60/60);
		timeto-=hours*60*60;
		if(hours<10) hours="0"+hours;
		minutes = Math.floor(timeto/60);
		//if(minutes<0) hours--;
		timeto-=minutes*60;
		if(minutes<10) minutes="0"+minutes;
		seconds = Math.floor(timeto);
		if(seconds<10) seconds="0"+seconds;

		document.getElementById(id).innerHTML = hours+":"+minutes+":"+seconds;
	} else {
		document.getElementById(id).innerHTML = "00:00:00";
	}
	window.setTimeout("doTimer('timer',<?php echo $nextTick;?>)",1000);
}

// usage: format_zahl( number [, number]  [, bool]  )
function formatZahl(zahl, k, fix)
{
	if(!k) k = 0;
	var neu = '';
	// Runden
	var f = Math.pow(10, k);
	zahl = '' + parseInt( zahl * f + (.5 * (zahl > 0 ? 1 : -1)) ) / f ;
	// Komma ermittlen
	var idx = zahl.indexOf('.');
	// fehlende Nullen einfügen
	if(fix)
	{
		 zahl += (idx == -1 ? '.' : '' )
		 + f.toString().substring(1);
	}
	// Nachkommastellen ermittlen
	idx = zahl.indexOf('.');
	if( idx == -1) idx = zahl.length;
	else neu = ',' + zahl.substr(idx + 1, k);

	// Tausendertrennzeichen
	while(idx > 0)
	{
		if(idx - 3 > 0)
		neu = ' ' + zahl.substring( idx - 3, idx) + neu;
		else
		neu = zahl.substring(0, idx) + neu;
		idx -= 3;
	}
	return neu;
}

var kontoalt = <?php $player = getPlayer($_SESSION["id"]); echo $player["konto"];?>;
var kealt = <?php $player = getPlayer($_SESSION["id"]); echo $player["deeds"];?>;

function drawGuthaben(kontoneu) {
	var tmp = (kontoneu-kontoalt)/3;
	if(Math.abs(tmp)>0.01) {
		document.getElementById('guthaben').innerHTML = formatZahl(kontoalt+tmp,2,true);
		kontoalt+=tmp;
		window.setTimeout("drawGuthaben("+kontoneu+")",40);
	} else {
		document.getElementById('guthaben').innerHTML = formatZahl(kontoneu,2,true);
	}
}

function drawKE(keneu) {
	var tmp = (keneu-kealt)/3;
	if(Math.abs(tmp)>0.01) {
		document.getElementById('kriminelle_energie').innerHTML = formatZahl(kealt+tmp,0,false);
		kealt+=tmp;
		window.setTimeout("drawKE("+keneu+")",40);
	} else {
		document.getElementById('kriminelle_energie').innerHTML = formatZahl(keneu,0,false);
	}
}

function init() {
	doTimer('timer',<?php echo $nextTick ?>);
	if(document.getElementById('topnotice').innerHTML!="")
		document.getElementById('topnotice').style.visibility = "visible";
	else
		window.setTimeout("hide('topnotice',0,0,-25,-0.01)",5555);
}
		</script>
	</head>

	<body onLoad="init()">
	<div class="notice close" id="topnotice" title="Ok" onClick="hide('topnotice',0,0,-25,-1)"><?php
//results
if(isset($_GET["buy"]))
	$buy = buyGround($worldid,$_GET["city"],$_GET["buy"],$_SESSION["id"]);
if(isset($_GET["sell"]))
	$sell = sellGround($worldid,$_GET["city"],$_GET["sell"],$_SESSION["id"]);
if(isset($_GET["build"]))
	$build = buildOnGround($worldid,$_GET["city"],$_GET["build"],$_GET["what"],$_SESSION["id"]);
if(isset($_GET["buyfixture"]))
	$buyfixture = buyFixture($worldid,$_GET["city"],$_GET["furnish"],$_GET["buyfixture"],$_SESSION["id"]);
if(isset($_GET["sellfixture"]))
	$sellfixture = sellFixture($worldid,$_GET["city"],$_GET["furnish"],$_GET["sellfixture"],$_SESSION["id"]);
if(isset($_GET["hire"]))
	$hirepersonel = hirePersonel($worldid,$_GET["city"],$_GET["personel"],$_GET["hire"],$_SESSION["id"]);
if(isset($_GET["fire"]))
	$hirepersonel = firePersonel($worldid,$_GET["city"],$_GET["personel"],$_GET["fire"],$_SESSION["id"]);
if(isset($_GET["setprice"]))
	setPrice4Food(1,$_GET["city"],$_GET["setprice"],$_GET["what"],$_SESSION["id"]);
if(isset($_GET["kicksome"]))
	kickSomeAss(1,$_GET["city"],$_GET["kicksome"],$_GET["what"],$_SESSION["id"]);

if(isset($_GET["rstgm"]) && $_SESSION["id"]==1)
	restartGame($worldid);

if($nextTick <= $time) {
	makeTick($worldid,4); //2h //8h
}
if(isset($_GET["rsttm"]) && $_SESSION["id"]==1) {
	resetNextTickTime($worldid);
}

if(isset($_GET["pdthghscr"]) && $_SESSION["id"]==1) {
	updateHighscore();
}

// local globals 2/2
$player = getPlayer($_SESSION["id"]); //muss _nach_ den results geladen werden

if(isset($_GET["nologout"])) {
	echo "<b>Das nächste mal bitte mit \"</b><a class=\"withIconLeft logout\">Logout</a><b>\" das Spiel verlassen!</b>";
}

if(isset($_GET["x"]) && isset($_GET["y"])) {
	$token = $map[$_GET["x"]][$_GET["y"]];
	if(!thisIsCityMap() && hexdec($token)>0)
		$city = getCity($worldid,$token);
}
if(thisIsCityMap())
	$city = getCity($worldid,$_GET["city"]);

?></div>
		<table class="content">
			<tr class="top">
				<td class="logo">
					<?php
					if($player["logo"]!="")
						echo "<img class=\"logo\" src=".$player["logo"]." alt=\"[Logo nicht darstellbar]\">";
					else
						echo "<a href=\"config.php\" target=\"_top\">[Logo festlegen]</a>";
					?>
				</td>
				<td>
					<h1><?php
						echo $player["kette"];
						?></h1>
					<a id="timer" class="titleOnly" title="<?php echo "next Tick: ".date("G:i:s",$nextTick); ?>"><?php echo date("G:i:s",$nextTick-$time) ?></a>
					<a href="faq/faq.php" target="_blank" class="withIconTop faq">FAQ</a>
					<a href="game.php?highscore" target="_top" class="withIconTop blank">Highscore</a>
					<a href="logout.php" target="_top" class="withIconTop logout">Logout</a>
					<a href="config.php" target="_top" class="withIconTop config">Profil</a>
					<a href="game.php?messenger" target="_top" class="withIconTop shares">Post</a>
					<a href="game.php?wallstreet" target="_top" class="withIconTop buy">Bank</a>
					<a href="game.php?ov=0" target="_top" class="withIconTop view">Überblick</a>
					<a href="game.php" target="_top" class="withIconTop world">Karte</a>
				</td>
			</tr><tr>
				<td class="left">
					<div class="info blue">
					Name: <a class="withIconRight journal" href="game.php?ov=<?php echo $player["id"];?>" target="_top"><b><?php echo $player["name"]; ?></b></a><br>
					Kette: <?php echo $player["kette"]; ?><br>
					Post: <a class="withIconRight shares" href="game.php?messenger"><?php getNumberOfMessages($_SESSION["id"]); ?></a><br>
					Kriminelle Energie: <span id="kriminelle_energie"><?php echo $player["deeds"]; ?></span> KE<br>
					Guthaben: <b><span id="guthaben"><?php echo number_format($player["konto"],2,","," ");?></span> &euro;</b>
					</div>
					<?php
					include "fieldinformation.php";
					?>
				</td>
				<td class="right">
					<h2>
						<?php
							if (thisIsCityMap() && !isset($_GET["ov"]) && !isset($_GET["furnish"])) {
								echo $city["name"];
							} elseif(isset($_GET["ov"])) {
								echo "Überblick";
							} elseif(isset($_GET["furnish"])) {
								echo "Ausstatter";
							} elseif(isset($_GET["messenger"])) {
								echo "Nachrichten";
							} elseif(isset($_GET["wallstreet"])) {
								echo "Börse";
							} elseif(isset($_GET["highscore"])) {
								echo "&#x2605; Highscore &#x2605;";
							} else {
								echo "Weltkarte";
							}
						?>
					</h2>
						<?php
							if(isset($_GET["ov"]))
								overview($_GET["ov"],$worldid);
							elseif(isset($_GET["furnish"]))
								furnish($worldid, $_GET["city"], $_GET["furnish"], $_GET["what"], $_SESSION["id"]);
							elseif(isset($_GET["personel"]))
								personel($worldid,$_GET["city"],$_GET["personel"],$_GET["hire"],$_SESSION["id"], $_GET["what"]);
							elseif(isset($_GET["messenger"]))
								messageCenter($_SESSION["id"]);
							elseif(isset($_GET["wallstreet"]))
								sharesCenter($worldid);
							elseif(isset($_GET["highscore"]))
								drawHighscore();
							else
								drawMapInGame($worldid,$_GET["city"],$_SESSION["id"]);
						?>
				</td>
			</tr>
		</table>
	</body>
</html>
