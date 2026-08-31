<?php $CVS_Version = '$Revision$ ; $Date$';

ini_set('display_errors', 1);
error_reporting(E_ALL);
if (unlink('utils/files/rstat.csv'))
 echo "OK";
else  
 echo "ERROR";


?>