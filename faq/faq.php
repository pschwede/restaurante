<?php
	session_start();
	#print_r($_SESSION);
	if (isset($_SESSION['angemeldet']) || $_SESSION['angemeldet']) {
		$player = true;
		if($_SESSION["id"]==1)
			$admin = true;
	} else {
		$player = false;
		$admin = false;
	}
?>
<html>
<head>
<title>Restaurante-FAQ</title>
</head>
<style type="text/css">
body {
	background-color: #850;
	background-image:url(../img/background.png);
	background-repeat:repeat-x;
	background-attachment:fixed;
	color: #FEB;
	font-family:sans-serif;
}
.content {
	background-color:#fff;
	color:#000;
	margin:0px auto;
	padding:64px;
	width:600px;
}
.menu-l {
	position:fixed;
	width:192px;
	top:0px;
	left:0px;
	padding:10px;
}
dl{
	padding-bottom:80px;
	padding-top:0px;
}
dt {
	font-size: 16pt;
	padding-top: 13px;
}
h5 {
	font-size: 7pt;
	font-weight: normal;
	border-bottom: 1px dotted #ccc;
	text-align: right;
	margin: 0px;
	padding-bottom:10px;
}
img {
	margin-left:128px
}
</style>
<?php
	include_once "../connector.php";
	include_once "../smarttools.php";
	$db = makeConn();

	if(isset($_POST["reset"])) {
		if(!$db->query("DROP TABLE faq"))
			echo "Truncate: ".$db->error;
		if(!$db->query("CREATE TABLE IF NOT EXISTS faq (
			id INT(11) NOT NULL AUTO_INCREMENT,
			freq INT(11) DEFAULT 0,
			headline CHAR(35) DEFAULT NULL,
			tag CHAR(35) DEFAULT NULL,
			question CHAR(255) DEFAULT NULL,
			answer BLOB DEFAULT NULL,
			time INT DEFAULT ".time().",
			writer INT(11) DEFAULT 1,
			PRIMARY KEY (id),
			UNIQUE KEY (tag)
			)"))
			echo "Create: ".$db->error;

		if(!$db->query("INSERT INTO faq (freq,headline,tag,question,answer) VALUES (999999999,'Spiel','Was ist das','Was ist das?','Ein Spiel. Es geht darum, eine Restaurantkette aufzubauen und
			sich gegen&uuml;ber den anderen Mitspielern zu behaupten und m&ouml;glichst viele Kunden anzulocken.
			Sp&auml;ter soll man auch direkte Angriffe auf die Gegner aus&uuml;ben k&ouml;nnen (Giftattacken, Gesundheitsamt,..)')"))
			echo "Insert: ".$db->error;
	}
	#print_r($_POST);
	if(isset($_POST["headline"]) && isset($_POST["question"]) && isset($_POST["settag"])) {
		if(!$db->query("INSERT INTO faq (freq,headline,tag,question,time,writer) VALUES (0,'".$_POST["headline"]."','".$_POST["settag"]."','".$_POST["question"]."',".time().",".$_SESSION["id"].")"))
			echo "Insert: ".$db->error;
	}
	$r_faq = $db->query("SELECT * FROM faq WHERE 1");
	while($faq = $r_faq->fetch_array(MYSQLI_ASSOC)) {
		$POSTvalue = "answer".$faq["id"];
		if(isset($_POST[$POSTvalue]) && isset($_POST["settag"])) {
			if(!$db->query("UPDATE faq SET tag='".$_POST["settag"]."', answer='".$_POST[$POSTvalue]."', writer=".$_SESSION["id"].", time=".time()." WHERE id=".$faq["id"])) {
				echo "Update: ".$db->error;
			}
		}
		$POSTvalue = "delete".$faq["id"];
		if($_POST["do"]==$POSTvalue) {
			if(!$db->query("DELETE FROM faq WHERE id=".$faq["id"])) {
				echo "Delete: ".$db->error;
			}
		}
	}
?>
<body>
	<div class="menu-l">
		<h2>Restaurante</h2>
		<h1><a name="top">FAQ</a></h1>
		<form action="" id="menu-search" method="POST">
			Suche:<br>
			<input type="text" name="search_query" size="15" value="<?php echo $_POST["search_query"];?>">
			<input type="submit" value="Go">
			<?php
				if(!$r_faq = $db->query("SELECT * FROM faq WHERE MATCH (question,answer,tag) AGAINST ('".$_POST["search_query"]."' IN BOOLEAN MODE)"))
					echo "Suche: ".$db->error;
				else if($r_faq->num_rows>0) {
					echo "<form action=\"\" method=\"POST\">Funde in:<br>";
					echo "<select name=\"tag\">";
					while($faq = $r_faq->fetch_array(MYSQLI_ASSOC)) {
						echo "<option ".(($faq["tag"]==$_POST["tag"]) ? "selected " : "")."value=\"".$faq["tag"]."\">".$faq["tag"]."</option>";
					}
					echo "</select>";
					echo " <input type=\"submit\" value=\"Ok\">";
					echo "<a href=\"faq.php#".$faq["id"]."\">".$faq["question"]."</a><br>";
				} else { echo "<form action=\"\" id=\"menu-form\" method=\"POST\">
						Stichworte:<br><select name=\"tag\">";
					$r_faq = $db->query("SELECT * FROM faq WHERE 1 ORDER BY headline ASC, tag ASC");
					while($faq = $r_faq->fetch_array(MYSQLI_ASSOC))
						echo "<option ".(($faq["tag"]==$_POST["tag"]) ? "selected " : "")."value=\"".$faq["tag"]."\">".$faq["tag"]."</option>";
					echo "</select>
							<input type=\"submit\" value=\"Ok\">
						</form>";
				}
			?>
	</div>
	<div class="content">
		<?php
			$oldtopic = "";
			$r_faq = $db->query("SELECT * FROM faq WHERE 1 ORDER BY headline ASC, freq DESC");
			while($faq = $r_faq->fetch_array(MYSQLI_ASSOC)) {
				if($faq["headline"]!=$oldtopic) {
					echo "</dl>";
					echo "<h2>".$faq["headline"]."</h2>";
					echo "<dl>";
				}
				echo "<dt".(($faq["tag"]==$_POST["tag"])? " style=\"background-color:#ff6;\" " : "").">
						<a name=\"".$faq["id"]."\">".$faq["question"]."</a>
					</dt>
					<dd>";
					if($faq["tag"]==$_POST["tag"]) {
						$db->query("UPDATE faq SET freq=freq+1 WHERE id=".$faq["id"]);
						echo "<script type=\"text/javascript\">
								window.location.href='faq.php#".$faq["id"]."';
							</script>";
					}
				$POSTvalue = "edit".$faq["id"];
				if($faq["answer"]!="" && $_POST["do"]!=$POSTvalue) {
					echo $faq["answer"];
					if($admin)
						echo "<form action=\"faq.php#".$faq["id"]."\" method=\"POST\">
							<select name=\"do\">
								<option selected value=\"edit".$faq["id"]."\">&Auml;ndern</option>
								<option value=\"delete".$faq["id"]."\">L&ouml;schen</option>
							</select>
							<input type=\"submit\" value=\"Ok\">
						</form>";
				} else {
					echo "<form action=\"faq.php#".$faq["id"]."\" method=\"POST\">
						<textarea name=\"answer".$faq["id"]."\" cols=\"50\" rows=\"10\">".$faq["answer"]."</textarea>
						<input name=\"settag\" value=\"".$faq["tag"]."\">
						<input type=\"submit\" value=\"Ok\">
					</form>";
				}
				$plr = getPlayer($faq["writer"]);
				echo "<h5 class=\"small\">Stichwort: ".$faq["tag"].", ".$faq["freq"]."x, ".date("d.m.Y",$faq["time"])." by ".$plr["name"]."</h5></dd>";
				$oldtopic = $faq["headline"];
			}
			echo "</dl>";
		if($player) {
			echo "<dl><dt>Frage stellen</dt><dd><br>
				<form action=\"faq.php\" method=\"POST\">
					Thema: <select name=\"headline\">";
			$r_hl = $db->query("SELECT headline FROM faq WHERE 1 GROUP BY headline");
			while($hl = $r_hl->fetch_assoc()) {
				echo "<option value=\"".$hl["headline"]."\">".$hl["headline"]."</option>";
			}
			echo "</select><br>
					Frage: <input name=\"question\" type=\"text\" size=\"36\"><br>
					Stichwort: <input name=\"settag\" type=\"text\" size=\"36\"><br>
					<input type=\"submit\" value=\"Frage einsenden\">
				</form></dd></dl>";
		}?>
	</div>
</body>
</html>
