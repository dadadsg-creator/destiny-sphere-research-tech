<?php
require_once('../Main.php');
SU_DebugTools::LogAll('request='.var_export($_REQUEST, true),'freetopay.log');

$transac_id = $_REQUEST['transac_id'];
$exists = UC_FreeToPay::checkTransaction($transac_id);
if ($exists==-1)
{
  die('NO_TRANSACTION:'.$transac_id);
}
elseif ($exists>0)
{
  die('ALREADY_PAYED:'.$transac_id);
}
else 
{
   if (UC_FreeToPay::checkSign($_REQUEST))
   {
      $uid     = $_REQUEST['uid'];
      $amount  = $_REQUEST['amount'];
      UC_FreeToPay::commitTransaction($transac_id, $amount);
		  UC_PaymentServiceMoneyOperation::EnterMoney($uid, $amount, 'SC', 'FREETOPAY_PAYMENT');      
      die('OK:'.$transac_id);
   }
   else 
   {
      die('BADSIGN:'.$transac_id);
   }
}
?>