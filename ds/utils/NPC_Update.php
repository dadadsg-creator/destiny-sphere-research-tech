<?php
ini_set('memory_limit', '4096M');
set_time_limit(0);

require_once '../Main.php';
require_once constant('INCLUDE_PATH').'/GameDown.inc.php';

$DBID = (int)$_SERVER['argv'][1];
assert($DBID >= 0);
$u = new SU_NPC((int)$DBID);
?>
