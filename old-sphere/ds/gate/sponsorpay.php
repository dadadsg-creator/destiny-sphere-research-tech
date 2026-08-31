<?php
require_once('../Main.php');
SU_DebugTools::LogAll('request='.var_export($_REQUEST, true),'sponsorpay.log');

$token = 'REDACTED_API_SECRET';

if (!UC_SponsorPay::isExistsTransID(addslashes($_REQUEST['_trans_id_'])))
{
   SU_DebugTools::LogAll('NOT EXISTS trans_id='.$_REQUEST['_trans_id_'],'sponsorpay.log');
  
   if (UC_Sponsorpay::checkSign($_REQUEST, $token))
   {
      SU_DebugTools::LogAll('checkSign OK','sponsorpay.log');
      if (intval(@$_REQUEST['uid']))
      {
        $data = array();
        $data['trans_id']  = $_REQUEST['_trans_id_'];
        $data['id_user']   = intval(@$_REQUEST['uid']);
        $data['paym_sum']  = intval(@$_REQUEST['amount']);
        UC_SponsorPay::insertData($data);
		    UC_PaymentServiceMoneyOperation::EnterMoney($data['id_user'], $data['paym_sum'], 'SC', 'SPONSORPAY_PAYMENT');      
      }  
   }
   else 
   {
     SU_DebugTools::LogAll('BAD SIGN ','sponsorpay.log');
   }
}
else 
{
   SU_DebugTools::LogAll('ALREADY EXISTS trans_id='.$_REQUEST['_trans_id_'],'sponsorpay.log');
}


?>