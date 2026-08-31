<?php

/**
 * Статический класс-библиотека для прокупки золота.
 ** Изначально предназначался для литовской версии
 */
class UC_PaymentGC extends DS_Base
{
   public static function AddOrder($id_user, $good_type, $quantity)
   {
        assert(is_numeric($quantity));
        $oldDbId = $_SESSION["dbId"];
        $common = DS_Common::CreateForCurrentDB(0);
    		assert(empty($common->errormsg));

        $sql  = "SELECT nextval('ds_payment_gc_id_payment_gc_seq') as id";
        $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
        assert(empty($common->errormsg));
        $id_payment = $res[0]['id'];

        $date = date("Ymd");
       	$year = substr($date,0,4);
    		$month =  substr($date,4,2);
		    $day = substr($date,6,2);

        $isoDate = 'CAST(\''.$year.'0101\' as TIMESTAMP)';
      	$sql = "SELECT MAX(id_payment_gc) as max FROM ds_payment_gc WHERE (order_date < ".$isoDate.")";

       	$res = $common->getResultArray('',$sql,'','','');
        assert(empty($common->errormsg));

        if(empty($res[0]['max']))
    		   $prevId = 1;
        else
    		   $prevId = $res[0]['max'];

        $strYearLastDigits = substr($year, -1);
      	$number = $id_payment - $prevId;
	      $number = str_pad($number, 5, "0", STR_PAD_LEFT);

		    $paymentcode =  'L'. $strYearLastDigits. '-'. $number;
        //echo $paymentcode;
        
        list($good_id, $good_price) = self::GetPrice($good_type, $common);

        $price = $quantity*$good_price;
        

        $sql = "INSERT INTO ds_payment_gc(id_payment_gc, paymentcode, id_user, good_type, quantity, price,  order_date, payment_date, order_status, paymenttype ) ";
        $sql .= "VALUES($id_payment, '$paymentcode', $id_user, $good_id, $quantity, '$price',  now(), null, 0, null)";

       	$res = $common->executeQuery($sql);
        echo $common->errormsg;
        assert(empty($common->errormsg));
        

        $_SESSION["dbId"] = $oldDbId;
        return array($paymentcode, $price);
   }

   public static function getOrder($paymentcode)
   {
        $oldDbId = $_SESSION["dbId"];
        $common = DS_Common::CreateForCurrentDB(0);
    		assert(empty($common->errormsg));

        $sql = "SELECT * from  ds_payment_gc WHERE paymentcode='$paymentcode'";
        $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
        echo $common->errormsg;
        assert(empty($common->errormsg));

        $_SESSION["dbId"] = $oldDbId;
        return $res[0];
   }

   public static function commitOrder($order_id, $quantity, $crncy, $paymenttype, $status, $ref_pay)
   {
        $oldDbId = $_SESSION["dbId"];
        $common = DS_Common::CreateForCurrentDB(0);
    		assert(empty($common->errormsg));

        $sql = "UPDATE ds_payment_gc SET quantity={$quantity}, order_status={$status}, payment_date=now(), 
        id_currency='$crncy', paymenttype='$paymenttype', ref_pay=$ref_pay  WHERE id_payment_gc='$order_id'";
       	$res = $common->executeQuery($sql);

        //SU_DebugTools::LogAll("commit: $sql, error=".$common->errormsg,'litva.log');
        assert(empty($common->errormsg));

        $_SESSION["dbId"] = $oldDbId;
        return $res[0];
   }

  static public function GetCurrentDiscount()
  {
     $oldDbId = $_SESSION["dbId"];
     $common = DS_Common::CreateForCurrentDB(0);
		 assert(empty($common->errormsg));

     $offset = ($pg-1)*$items_num;
  	 $sql = "SELECT * FROM ds_payment_gc_discount WHERE start_date<=now() and now()<=end_date";
     $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
     assert(empty($common->errormsg));
     $_SESSION["dbId"] = $oldDbId;
     return $res[0];
  }

  static public function GetPrice($good_type, $common='')
  {
     $oldDbId = $_SESSION["dbId"];

     if (!$common)
       $common = DS_Common::CreateForCurrentDB(0);

     $sql  = "SELECT id_price FROM ds_money_payments_price WHERE id_money_action=";
     $sql  .= "(SELECT id_money_action FROM ds_money_action_types WHERE action_name='$good_type') and
     is_current='t'";
     //echo $sql;
     $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');


     assert(empty($common->errormsg));

     $good_id = $res[0]['id_price'];

     $sql  = "SELECT gc_price FROM ds_money_payments_price WHERE id_price=$good_id";
     $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');

     assert(empty($common->errormsg));
     $good_price = $res[0]['gc_price'];
     $_SESSION["dbId"] = $oldDbId; 
     return array($good_id, $good_price); 
  }

  static public function GetParam($param, $common='')
  {
     $oldDbId = $_SESSION["dbId"];

     if (!$common)
       $common = DS_Common::CreateForCurrentDB(0);

     $sql  = "SELECT pval FROM ds_payment_conf WHERE param='$param'";
     $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
  	 if ($_SERVER['REMOTE_ADDR']=='195.182.156.98')
		    SU_DebugTools::LogAll("COLONIZE GetParam sql=$sql, res=".var_export($res, true),'ref.log');
     assert(empty($common->errormsg));

     $_SESSION["dbId"] = $oldDbId; 
     if ($res)
       return $res[0]['pval']; 
     else  
       return '';      
  }
   
  static public function SetParam($param, $value,  $common='')
  {
     $oldDbId = $_SESSION["dbId"];

     if (!$common)
       $common = DS_Common::CreateForCurrentDB(0);

     $sql  = "SELECT param FROM ds_payment_conf WHERE param='$param'";
     $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
     assert(empty($common->errormsg));

     if ($res)
       $sql = "UPDATE ds_payment_conf SET pval='{$value}' WHERE param='$param'";
     else 
       $sql = "INSERT INTO ds_payment_conf (param, pval) values('$param', '$value')";

     $res = $common->executeQuery($sql);
     assert(empty($common->errormsg));
           
     $_SESSION["dbId"] = $oldDbId; 
     return $res; 
  }
     
  static public function SetNewPrice($price)
  {
     $oldDbId = $_SESSION["dbId"];
     $common = DS_Common::CreateForCurrentDB(0);


     $sql  .= "SELECT id_money_action FROM ds_money_action_types WHERE action_name='ENTER_MONEY' and
     is_active='t'";
     $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
     //echo $sql."<br>".$common->errormsg;
     assert(empty($common->errormsg));
     $id_money_action = $res[0]['id_money_action'];

     $sql  = "UPDATE  ds_money_payments_price SET is_current='f' WHERE id_money_action=$id_money_action";
     $res = $common->executeQuery($sql);
     assert(empty($common->errormsg));


     $sql  = "INSERT INTO ds_money_payments_price  (gc_price, sc_price, id_money_action) 
         VALUES   ($price, 1, $id_money_action)";
    
     $res = $common->executeQuery($sql);
     echo $common->errormsg;
     assert(empty($common->errormsg));

     $_SESSION["dbId"] = $oldDbId; 
     return array($good_id, $good_price); 
  }

}

?>