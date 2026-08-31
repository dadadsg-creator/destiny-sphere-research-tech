<?php

/**
 * —татический класс-библиотека дл€ работы со SponsorPay
 ** 
 */
class UC_FreeToPay extends DS_Base
{
   public static function insertTransaction($user_id)
   {
      $common = DS_Common::CreateForCurrentDB(0);
    	assert(empty($common->errormsg));

    	$sql = "select nextval('ds_freetopay_id_transaction_seq'::regclass) as nv";
      $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
      assert(empty($common->errormsg));
      $id = $res[0]['nv'];
      
    	$sql  = "INSERT INTO ds_freetopay(id_transaction, id_user, date_start, date_pay, amount )
    	VALUES($id, $user_id, now(), null, 0)";
      $res = $common->executeQuery($sql);
      assert(empty($common->errormsg));

      return $id;
   }

   public static function checkTransaction($id)
   {
      $common = DS_Common::CreateForCurrentDB(0);

      $sql = "SELECT id_transaction, amount FROM ds_freetopay WHERE id_transaction = {$id} ";  
      $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
      assert(empty($common->errormsg));
      
      if ($res)
        return $res[0]['amount'];
      else 
        return -1;   
   }

   public static function commitTransaction($id, $sum)
   {
      $common = DS_Common::CreateForCurrentDB(0);
     
      $sql  = "UPDATE  ds_freetopay SET date_pay=now(), amount = {$sum} where id_transaction={$id}";
      $res = $common->executeQuery($sql);
      assert(empty($common->errormsg));
   }

   public static function checkSign($data)
   {
     $common = DS_Common::CreateForCurrentDB(0);
     
     $sign = md5($data['transac_id'].':'.$data['uid'].':'.$data['amount'].':'.constant('FREETOPAY_ApiSecretKeyB'));

     return ($data['sig']==$sign);
   }
   
   public static function checkSignfail($data)
   {
     $sign = md5($data['transac_id'].':'.$data['uid'].':'.constant('FREETOPAY_ApiSecretKeyB'));
     return ($data['sig']==$sign);
   }   
}

?>