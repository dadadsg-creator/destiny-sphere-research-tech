<?php


$url = "http://www.rambler.ru/db/raccess/special/footer.html?href_class=wt&self_style=0&partner=1&line_color=%23FFFFFF&copy_color=%23FFFFFF&format=html";

if($handle = fopen($url, "rb")) {

$contents = "";

while (!feof($handle)) {
  $contents .= fread($handle, 8192);
}
fclose($handle);

$contents=convert_cyr_string($contents,"k","w");

print $contents; }

?>