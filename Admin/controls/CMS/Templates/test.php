<?
$str = "asvd <!--# a1_S2fc3  #//--> <!--#  s4zxc56  #//-->sddf	<!-- #Test#//-->dfds";

echo "Search: ".str_replace(">","&gt;",str_replace("<","&lt;",$str))." <br><br>";

if (preg_match_all ("/<!--\s*#\s*([A-Za-z0-9_]*?)\s*#\s*\/\/-->/", $str,$matches)) {
    print "A match was found. <br>";
} else {
    print "A match was not found.";
}


//while (list($key,$val) = each($matches)) echo "$key: $val <br>";
while (list($key,$val) = each($matches)) {
	echo "$key: <br>--------<br>";
	while (list($k,$v) = each($val)) echo "$k: $v <br>";
	echo "<br>";
}


echo "\n";

$str = "&nbsp; &lt &lt; &reg; &ahg=\$a";
echo preg_replace("/(\&)([A-Za-z0-9_]*);/","&amp;\\2;",$str);


?>

