<?php

$tags_s = file("tag_s.csv");
$tags_p = file("tag_p.csv");

$fp = fopen("same_tags.txt", "w+");

$counter = 0;

foreach($tags_s as $k => $tag_s)
{
	foreach($tags_p as $l => $tag_p)
	{
		if($tag_p == $tag_s)
		{
			$tag = substr($tag_p, 1, strlen($tag_p)-4);
			
			fwrite($fp, $tag."\n");
			
			echo $tag."<br>\n";
			
			unset($tags_s[$k]);
			unset($tags_p[$l]);
			
			$counter++;
		}
	}
}

fclose($fp);

echo "<h1>TOTAL COUNT : $counter</h1>";
?>