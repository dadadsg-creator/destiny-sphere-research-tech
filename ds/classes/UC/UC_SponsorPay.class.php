<?php

/**
 * —татический класс-библиотека дл€ работы со SponsorPay
 ** 
 */
class UC_SponsorPay extends DS_Base
{
   public static function isExistsTransID($trans_id)
   {
      $common = DS_Common::CreateForCurrentDB(0);
    	assert(empty($common->errormsg));

      $sql  = "SELECT trans_id FROM ds_sponsorpay_log WHERE trans_id='{$trans_id}'";
      $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
      assert(empty($common->errormsg));

      if ($res[0])
        return true;
      else  
        return false;
   }

   public static function insertData($data)
   {
        $oldDbId = $_SESSION["dbId"];
        $common = DS_Common::CreateForCurrentDB(0);
    		assert(empty($common->errormsg));

        $sql = "INSERT INTO ds_sponsorpay_log (trans_id, id_user, paym_sum, add_date) 
        VALUES('{$data['trans_id']}', {$data['id_user']}, {$data['paym_sum']}, NOW())";
        $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
        echo $common->errormsg;
        assert(empty($common->errormsg));

        return $res[0];
   }

   public static function checkSign($data, $controlworld)
   {
     $sign = sha1($controlworld.$data['uid'].$data['amount'].$data['_trans_id_']);
     return ($data['sid']==$sign);
   }
}

?>