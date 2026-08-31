<?php

header("Pragma: public"); 
header("Cache-Control: max-age=525600"); 
header("Accept-Ranges: bytes");
header("Content-Type: application/octet-stream"); 
header("Content-Transfer-Encoding: binary");
header('Content-Disposition: inline');

readfile ( "flash/ds3.swf" );

?>