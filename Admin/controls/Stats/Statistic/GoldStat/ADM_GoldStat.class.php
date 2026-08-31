<?php
class  ADM_GoldStat extends DS_Base
{
  static public function GetAgregatedStat($date1, $date2)
  {
     $oldDbId = $_SESSION["dbId"];
     $common = DS_Common::CreateForCurrentDB(0);
     assert(empty($common->errormsg));

  	 $sql = "SELECT count(id_payment_gc) as cnt , sum(price) as sum_money, sum(quantity) as cnt_gc FROM 
     ds_payment_gc WHERE payment_date >= to_timestamp('$date1','YYYY-MM-DD') and
     payment_date <= to_timestamp('$date2','YYYY-MM-DD HH24:MI:SS') AND order_status=1";
     $res1 = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
     assert(empty($common->errormsg));
     $res1[0]['sum_money'] = str_replace('$','',$res1[0]['sum_money']);

  	 $sql = "SELECT count(id_payment_gc) as curr_cnt , sum(price) as curr_sum_money, sum(quantity) as curr_cnt_gc FROM  ds_payment_gc WHERE
      payment_date >= to_timestamp('$date1','YYYY-MM-DD') and
     payment_date <= to_timestamp('$date2','YYYY-MM-DD HH24:MI:SS') AND order_status=0";
     $res2 = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
     assert(empty($common->errormsg));
     $res2[0]['curr_sum_money'] = str_replace('$','',$res2[0]['curr_sum_money']);

  	 $sql = "SELECT count(id_payment_gc) as cancel_cnt , sum(price) as cancel_sum_money, sum(quantity) as cancel_cnt_gc FROM   ds_payment_gc WHERE
      payment_date >= to_timestamp('$date1','YYYY-MM-DD') and
     payment_date <= to_timestamp('$date2','YYYY-MM-DD HH24:MI:SS') AND order_status=-1";
     $res3 = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
     assert(empty($common->errormsg));
     $res3[0]['cancel_sum_money'] = str_replace('$','',$res3[0]['cancel_sum_money']);
     
     $_SESSION["dbId"] = $oldDbId;   
     return array_merge($res1[0], $res2[0], $res3[0]);
  }

  static public function GetDetailedStat($pg, $items_num, $date1, $date2, $status)
  {
     $oldDbId = $_SESSION["dbId"];
     $common = DS_Common::CreateForCurrentDB(0);
		 assert(empty($common->errormsg));

     $offset = ($pg-1)*$items_num;
  	 $sql = "SELECT ps.*, u.username  FROM ds_payment_gc ps INNER JOIN ds_user u ON ps.id_user=u.id_user WHERE  ps.payment_date >= to_timestamp('$date1','YYYY-MM-DD') and
     ps.payment_date <= to_timestamp('$date2','YYYY-MM-DD HH24:MI:SS') AND order_status=$status LIMIT {$items_num} OFFSET {$offset}";
     $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
     assert(empty($common->errormsg));
     $_SESSION["dbId"] = $oldDbId;
     return $res;
  }

  static public function GetAllDiscounts()
  {
     $oldDbId = $_SESSION["dbId"];
     $common = DS_Common::CreateForCurrentDB(0);
		 assert(empty($common->errormsg));

     $offset = ($pg-1)*$items_num;
  	 $sql = "SELECT * FROM ds_payment_gc_discount order by id_discount DESC LIMIT 100 ";
     $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
     assert(empty($common->errormsg));
     $_SESSION["dbId"] = $oldDbId;
     return $res;
  }

  static public function updateDiscount($curdisc_id, $start_date, $end_date, $proc)
  {
     $oldDbId = $_SESSION["dbId"];
     $common = DS_Common::CreateForCurrentDB(0);
		 assert(empty($common->errormsg));

     $offset = ($pg-1)*$items_num;
  	 $sql = "UPDATE ds_payment_gc_discount SET start_date=to_timestamp('$start_date','YYYY-MM-DD'),
     end_date=to_timestamp('$end_date','YYYY-MM-DD HH24:MI:SS'), discount_size=$proc WHERE id_discount={$curdisc_id}";
     $res = $common->executeQuery($sql);
     assert(empty($common->errormsg));
     $_SESSION["dbId"] = $oldDbId;
  }

  static public function addDiscount($start_date, $end_date, $proc)
  {
     $oldDbId = $_SESSION["dbId"];
     $common = DS_Common::CreateForCurrentDB(0);
		 assert(empty($common->errormsg));

     $offset = ($pg-1)*$items_num;
  	 $sql = "insert into ds_payment_gc_discount(start_date, end_date, discount_size)
     values(to_timestamp('$start_date','YYYY-MM-DD'),to_timestamp('$end_date','YYYY-MM-DD HH24:MI:SS'), $proc)";
     $res = $common->executeQuery($sql);
     assert(empty($common->errormsg));
     return $res;
  }
}

?>