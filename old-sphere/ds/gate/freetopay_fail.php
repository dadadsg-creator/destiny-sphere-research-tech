<?php
require_once('../Main.php');
SU_DebugTools::LogAll('request='.var_export($_REQUEST, true),'freetopay.log');

$transac_id = $_REQUEST['transac_id'];


if (UC_FreeToPay::checkSignfail($_REQUEST))
{
   $uid     = $_REQUEST['uid'];
   $amount  = $_REQUEST['amount'];
   UC_FreeToPay::commitTransaction($transac_id, -1);
   die('OKF:'.$transac_id);
}
else 
{
    die('BADSIGN:'.$transac_id);
}

?>