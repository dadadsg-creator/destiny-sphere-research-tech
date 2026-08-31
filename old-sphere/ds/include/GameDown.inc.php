<?php
include_once ($_SERVER['DOCUMENT_ROOT']."/ds/config/settings.php");

function GameDown($dbId = 0, $id_world = 0)
{
    if (!constant("CACHING_PREFIX")) return FALSE;
    return file_exists('/etc/cron.ds/'.CACHING_PREFIX.'down_db'.$dbId.'_'.$id_world)
        || file_exists('/etc/cron.ds/'.CACHING_PREFIX.'down_db'.$dbId)
        || file_exists('/etc/cron.ds/'.CACHING_PREFIX.'down');
}

?>