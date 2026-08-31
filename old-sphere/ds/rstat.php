<?php
header("Content-type: text/plain");
echo file_get_contents('utils/files/rstat.csv');
?>