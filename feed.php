<?php
	include_once "gamemaster.php";
	include_once "connector.php";

echo '<?xml version="1.0" encoding="utf-8"?>';
?>

<rss version="2.0">

  <channel>
    <title>Restaurante Spielrunden</title>
    <link>http://spz.kilu.de/restaurante</link>
    <description>Wird bei einem Rundenbeginn aktualisiert</description>
    <language>de-de</language>
    <copyright>Peter Schwede</copyright>
    <pubDate><?php echo date("D, d m y G:i:s",time());?></pubDate>
    <image>
      <url>http://spz.kilu.de/restaurante/img/restaurante.png</url>
      <title>Restaurante</title>
      <link>http://spz.kilu.de/restaurante</link>
    </image>
<?php
	$db=makeConn();
	$r_h2 = $db->query("SELECT * FROM highscore2 WHERE 1");
	while($h2 = $r_h2->fetch_array(MYSQLI_ASSOC)) {
		echo "
    <item>
      <title>Runde ".$h2["round"]." beendet!</title>
      <description>Nach einer Dauer von ".date("d, G:i:s",$h2->playtime)." wurde diese Spielrunde beendet und die naechste hat somit gerade angefangen!</description>
      <link>http://spz.kilu.de/restaurante</link>
      <author>Restaurantew</author>
      <guid>".$h2["round"]."</guid>
      <pubDate>".date("D, d m y G:i:s",time())."</pubDate>
    </item>";
    print_r(h2);
  }
?>

  </channel>

</rss>
