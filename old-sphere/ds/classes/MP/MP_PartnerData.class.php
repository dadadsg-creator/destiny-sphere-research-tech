<?php $CVS_Version = '$Revision$ ; $Date$';

class MP_PartnerData extends MP_Patch
{
	static public $activatePrice = 0;
	static public	$pressPrice = 0;
	static public $gatePrice = 0;
	static public $datec;
	static public $curdate;
	static public $userCombid=0;
    static public $pu_arr;
    static public $partner_id;
        
	public function __construct(DS_Common $common, $pid)
	{
		self::$common = $common;
		self::$partner_id = $pid;
	}

	public function setCommon(DS_Common $common)
	{
		self::$common = $common;
	}

	public function getParam($param_name)
	{
	   $sql = "SELECT param_value FROM ds_cron_data WHERE param_name='{$param_name}'";
	   $res = self::$common->getResultArray('', $sql, '', '', '');
	   echo self::$common->errormsg;
	   assert(empty(self::$common->errormsg));
       return $res[0]['param_value'];
	}

	public function patch()
	{
		$sql = "SELECT to_char(param_date, 'YYYY-MM-DD') as cdate FROM ds_cron_data WHERE param_name='partner_".self::$partner_id."'";
		
		$res = self::$common->getResultArray('', $sql, '', '', '');
		echo self::$common->errormsg;
	    assert(empty(self::$common->errormsg));

	    self::$datec  = $res[0]['cdate'];
	    //echo $sql.'datec='.var_export($res, true)."\n";
	    list($year, $mon, $day) = explode('-',self::$datec);
		$starttm   = mktime(0,0,0, $mon, $day, $year);

		self::$curdate = date("Y-m-d");

  	    list($cur_year, $cur_mon, $cur_day) = explode('-', self::$curdate);
		$enddate =  mktime(0,0,0, $cur_mon, $cur_day, $cur_year);

	  self::$activatePrice = self::getParam('activate_price_'.self::$partner_id)*1;
	  self::$pressPrice = self::getParam('press_price_'.self::$partner_id)*1;
	  self::$gatePrice = self::getParam('gate_price_'.self::$partner_id)*1;

    //echo "activatePrice={$activatePrice},pressPrice={$pressPrice},gatePrice={$gatePrice} datec=$datec, starttm=$starttm  curdate=$curdate, enddate=$enddate, delta=".($enddate-$starttm)."\n";
	  if ($enddate > $starttm)
	  {
	    $secretkey = 'REDACTED_API_SECRET';
        $data = array();

        $data['pid'] = self::$partner_id;
        $data['sign'] = md5(self::$partner_id.$secretkey);
        $data['sdate'] = self::$datec;
        $data['edate'] = self::$curdate;
        //$data['edate'] = '2011-04-02';
        $data['game_id'] = constant('BILLING_PARTNER_ID');
        if (constant("VERSION_NAME")=="LOCAL")
          $url = "http://billing/gate/statforpartners.php";
        else
          $url = "https://billing.destinysphere.ru/gate/statforpartners.php";
        $params = '';

        $i = 0;
        foreach ($data as $key=>$val)
        {
          if ($i==0)
            $params .= "{$key}=$val";
          else
            $params .= "&{$key}=$val";

          $i++;
        }

        $pu = '';
        if (function_exists('curl_init'))
        { 
          $ch = curl_init ($url);
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 	      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  	      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

  	      ob_start();
  	      $pu = curl_exec($ch);
	      ob_end_clean();
	      curl_close($ch);
	    //echo "**pu=$pu\n";
        }
        else
        {
        	$msg = "Eto soobschenie ot servera ".constant('GAME_HOST').
        	"Esli vi ego poluchili, znachit otvalilsya modul curl. Partnerskaya statistika rabotat't ne mozhet.
        	Soobschite sistemnomu administratoru.";
        	mail('dmitri@destinysphere.com','!!Destiny Sphere Achtung!!',$msg);
        	mail('maxim@destinysphere.com','!!Destiny Sphere Achtung!!',$msg);
        	
        } 	    
	    $pu_arr_bill = array();
        if (strlen($pu)>0)
          $pu_arr_bill = explode(';', $pu);

        foreach ($pu_arr_bill as $p)
        {  
       	  list($uid, $tsum, $dt) = explode(',',$p);
       	  self::$pu_arr[$uid] = array('sum'=>$tsum, 'date'=>$dt);
        }  
        
      //die(var_export(self::$pu_arr, true));
        $users = DS_Partner::getPartnerRegStatSimple(self::$partner_id);
        //echo 'users='.var_export($users, true)."<hr>";
        foreach ($users as $u)
        {
           self::checkUser($u['id_user']);
        }

	    $stat = true;
	    //$stat = false;
	    if ($stat)
	    {
		  $sql = "UPDATE ds_cron_data SET param_date='".self::$curdate."' WHERE param_name='partner_".self::$partner_id."'";
		  $res = self::$common->executeQuery($sql);
  	    //echo self::$common->errormsg;

		    //assert(empty(self::$common->errormsg));

/*
	      $fh = fopen('files/rstat.csv','a');
	      fwrite($fh, $stat);
	      fclose($fh);
*/
	    }
	  }
	}

	static function getAgregatedStat($tm1, $tm2, $pid)
	{
	  $date1 = self::getDate($tm1);
	  $date2 = self::getDate($tm2);

	  $sql = "select id_user from ds_user_partnerstat where action_date>='{$date1}' and action_date<='{$date2}' and id_partner={$pid}";

	  $users = self::$common->getResultArray('', $sql, '', '', 'NO_CACHE');
	  $sql = "select id_user, activated, psum, date_part('epoch', action_date) as action_date, date_part('epoch', first_login) as first_login, date_part('epoch', last_login) as last_login,
	  press_build, gate_open, auth_count, date_part('epoch', paymentdate) as paymentdate, paymentsum from ds_user_partnerstat where action_date>='{$date1}' and action_date<='{$date2}' order by id_stat";
		$stat = self::$common->getResultArray('', $sql, '', '', 'NO_CACHE');

		$userstat = array();
		foreach ($users as $u)
		{
		  foreach ($stat as $r)
		  {
		     //echo $r['id_user'].','.$u['id_user'].'<br>';

		     if ($r['id_user']==$u['id_user'])
		     {
		       $id_user = $u['id_user'];
		       $userstat[$id_user]['id'] = $r['id_user'];
		       $userstat[$id_user]['dl'] = 0;

		       if (!array_key_exists('paymentsum', $userstat[$id_user]))
		       {
		          $userstat[$id_user]['paymentsum'] = 0;
		       	  $userstat[$id_user]['paymentdate'] = '';
		       }
		       
		       if ($r['paymentdate']) //гарантируем, что в итоге в статистику попадЄт
		                              //последн€€ дата покупки, т.к. отсортировано всЄ по id_stat 
		       {
		          $userstat[$id_user]['paymentdate'] = $r['paymentdate'];
		          $userstat[$id_user]['paymentsum'] +=$r['paymentsum'];
		       }


		       if (!$userstat[$id_user]['first_login'])
		         $userstat[$id_user]['first_login'] = $r['first_login'];

              if ($userstat[$id_user]['eff_reg']<1) 
 		         $userstat[$id_user]['eff_reg'] += $r['activated'];

		       if (!$r['gate_open'] && !$r['press_build'] && $userstat[$id_user]['eff_reg'])
		         $userstat[$id_user]['level'] = 1;
		       else if ($r['press_build']==1 && $r['gate_open']==0)
		         $userstat[$id_user]['level'] = 2;
		       else if ($r['gate_open']==1)
		         $userstat[$id_user]['level'] = 3;

		       $userstat[$id_user]['auth_count'] +=  $r['auth_count'];
		       $userstat[$id_user]['time_game'] = 0;

		       if ($r['last_login'])
		         $userstat[$id_user]['last_login'] = $r['last_login'];

		       $userstat[$id_user]['price'] += $r['psum'];
		       $userstat[$id_user]['currency'] = 'RUR';

		       if ($r['psum'])
		         $userstat[$id_user]['date'] = $r['action_date'];
		     }
		  }
		}

		return $userstat;
	}

	static function CollectUserInfo($id_user)
	{
	  $sql = "SELECT * FROM ds_user_partnerstat WHERE id_user={$id_user}";
	  $res = self::$common->getResultArray('', $sql, '', '', 'NO_CACHE');
	  $userdata = array('activated'=>0,'press_build'=>0,'gate_open'=>0, 'psum' => 0);

	  if (!$res)
	    return $userdata;
	  //assert(empty(self::$common->errormsg));
	  
	  foreach($res as $r)
	  {
	    $userdata['activated'] += $r['activated'];
	    $userdata['press_build'] += $r['press_build'];
	    $userdata['gate_open'] += $r['gate_open'];
        $userdata['psum'] += $r['psum'];
	  }
	  if ($userdata['press_build'] > 1)
	    $userdata['press_build'] = 1;

	  if ($userdata['gate_open'] > 1)
	    $userdata['gate_open'] = 1;

	  if ($userdata['activated'] > 1)
	    $userdata['activated'] = 1;

	  return $userdata;
	}

	static function isActivated($id_user)
	{
		$sql = "SELECT activate_mail FROM ds_user WHERE id_user={$id_user}";
		$res = self::$common->getResultArray('', $sql, '', '', 'NO_CACHE');

	  if ($res)
  	    return $res[0]['activate_mail'];
  	  else
  	    return 0;
	}

	static function isPressBuilded($combID)
	{
	  $buildingID = 21;//KADERIUM_PRESS
	  $r = DS_Building::GetAttachedBuilddingNotUnderConstruction($combID, $buildingID);
	  if ($r)
	   return 1;
	  else
	   return 0;
	}

	static function isGateOpened($combID)
	{
	  $buildingID = 6;//CENTRAL_GATE
	  $r = DS_Building::GetAttachedBuilddingNotUnderConstruction($combID,  $buildingID);
	  if ($r)
	   return 1;
	  else
	   return 0;
	}

	static function getUserComb($id_user)
  {
      $sql = "select id_comb from ds_comb c inner join ds_combcomplex cp
        ON c.id_combcomplex=cp.id_combcomplex and
        c.id_userinstance= (select id_userinstance from ds_userinstance
        where id_user={$id_user}) and cp.id_ring<>4";

  		$res = self::$common->getResultArray('', $sql, '', '', 'NO_CACHE');
      if  ($res)
        return $res[0]['id_comb'];
      else
        return 0;
  }

	static function getLastLogin($id_user)
	{
      $sql = "select max(lastvisit) as lv from ds_loginhistory
        where id_user={$id_user} and (lastvisit>='".self::$datec."' and lastvisit<='".self::$curdate."')";

      $res = self::$common->getResultArray('', $sql, '', '', 'NO_CACHE');
      if  ($res)
        return "'{$res[0]['lv']}'";
      else
        return 'NULL';
	}

	static function getFirstLogin($id_user)
	{
      $sql = "select min(lastvisit) as lv from ds_loginhistory
        where id_user={$id_user} and (lastvisit>='".self::$datec."' and lastvisit<='".self::$curdate."')";

      $res = self::$common->getResultArray('', $sql, '', '', 'NO_CACHE');
      if  ($res)
        return "'{$res[0]['lv']}'";
      else
        return 'NULL';
	}

	static function getAuthCount($id_user)
	{
      $sql = "select count(lastvisit) as cnt from ds_loginhistory
        where id_user={$id_user} and lastvisit>='".self::$datec."' and lastvisit<='".self::$curdate."'";
  		$res = self::$common->getResultArray('', $sql, '', '', 'NO_CACHE');

  		if  ($res)
        return $res[0]['cnt'];
      else
        return 0;
	}

	static function checkUser($id_user)
	{
	  $statdata = array('activated' => 0,'press_build' => 0, 'gate_open' => 0, 'psum' =>0, 'last_login'=>'', 'auth_count'=>0, 'first_login'=>'', 'paymentdate'=>"NULL", 'paymentsum'=>0);
	  $userdata = self::CollectUserInfo($id_user);
	  
	  //echo 'userdata='.var_export($userdata, true)."<hr>";
	  //if ($id_user!=740091)
	  //  return;
	  //echo "id_user=$id_user, userdata=".var_export($userdata, true)."\n";
    /*
	  if ($userdata['activated'] && $userdata['press_build'] && $userdata['gate_open'])
	  {
	    //ловить тут уже нечего
	    return;
	  }
*/
/*	  
	  if (!$userdata)
	  {
        $was_change = 1; //такого юзера ещЄ не было, фиксируем
        $userdata = array('activated'=>0,'press_build'=>0,'gate_open'=>0, 'psum' => 0);
	  }  
      else
        $was_change = 0;
*/
	  $was_change = 0;
	  if (!$userdata['activated'])
	  {
	    $statdata['activated'] = self::isActivated($id_user);
	    if ($statdata['activated'])
	    {
	      $statdata['psum'] += self::$activatePrice;
	      $was_change = 1;
	    }  
	  }
      else
      {
      	$statdata['activated'] = $userdata['activated'];
      }
      
	  //echo "id_user=$id_user, pu_arr= ".var_export(self::$pu_arr)."\n";
	  if (self::$pu_arr)
	  {
	  	if (array_key_exists($id_user, self::$pu_arr))
	    {
	      $statdata['paymentsum'] =  self::$pu_arr[$id_user]['sum'];	
	      $statdata['paymentdate'] =  "'".self::$pu_arr[$id_user]['date']."'";
	      $was_change = 1;
	    }
	  }


    $statdata['first_login'] = self::getFirstLogin($id_user);
    $statdata['last_login']  = self::getLastLogin($id_user);
    $statdata['auth_count']  = self::getAuthCount($id_user);
    $statdata['press_build'] = $userdata['press_build']; //если один раз построили пресс или ворота
    $statdata['gate_open']   = $userdata['gate_open']; //то далее эта инфа так и т€нетс€


	GE_SearchStart::SetCommon(self::$common);

    $userworlds = GE_SearchStart::GetWorldsWhereUserIsRegistered(intval($id_user));
    //echo 'userworlds='.var_export($userworlds, true)."<hr>";
    //провер€ем   на открытость ворот и постройку пресса только если хоть что-то из этого
    //не построено ранее

    if (!$userdata['press_build'] || !$userdata['gate_open'])
    {
      //хрен знает в каком мире игрок по факту начнЄт играть - приходитс€ провер€ть все, где зарегалс€
      foreach ($userworlds as $w)
	  {
	    $dbid = DS_Common::GetServerID($w['dbname']);
	    //echo "dbname=".$w['dbname'].", dbid=$dbid<br>";
	    if ($dbid==-1)
	        continue;

  	    self::$common->setDbId($dbid );
	      $userCombid = intval(self::getUserComb($id_user));
	      //echo "userCombid={$userCombid}<br>";
        if (!$userCombid)
          continue;

	      if (!$userdata['press_build'])
	      {

	        $statdata['press_build'] = self::isPressBuilded($userCombid);
	        if ($statdata['press_build'])
	        {
	          $statdata['psum'] += self::$pressPrice;
	          $userdata['press_build'] = 1;
	          $was_change = 1; //ставим флаг, что уровень юзера помен€лс€
	        }
	      }

	      if (!$userdata['gate_open'])
	      {
  	        $statdata['gate_open'] = self::isGateOpened($userCombid);
	        if ($statdata['gate_open'])
	        {
              $statdata['psum'] += self::$gatePrice;
              $userdata['gate_open']  = 1;
              $was_change = 1;//ставим флаг, что уровень юзера помен€лс€
	        }
	      }

	      if ($userdata['gate_open'] && $userdata['press_build'])
	      {
	        break;
	      }
	    }
	  }

	  //echo "statdata=".var_export($statdata, true)."<br>";
	  self::$common->setDbId(0);
	  //if ($statdata['psum'])
	  if ($was_change) //заносим запись только если помен€лс€ уровень юзера или была оплата
	    self::addRecord($id_user, $statdata);
	}

	static function addRecord($id_user, $statdata)
	{
	  $d = self::$datec;

	  //echo var_export($statdata, true)."\n\n";
	  if ($statdata['first_login']=="''")
	    $statdata['first_login'] = 'NULL';
	  if ($statdata['last_login']=="''")
	    $statdata['last_login'] = 'NULL';

		$sql = "INSERT INTO ds_user_partnerstat(action_date, activated, first_login, last_login, auth_count, press_build, gate_open, psum, id_user, id_partner, paymentdate, paymentsum )
		 VALUES('{$d}', {$statdata['activated']}, {$statdata['first_login']}, {$statdata['last_login']}, '{$statdata['auth_count']}',
		 {$statdata['press_build']}, {$statdata['gate_open']}, {$statdata['psum']}, {$id_user},".self::$partner_id.",{$statdata['paymentdate']}, {$statdata['paymentsum']})";

		//echo $sql."<br>";
		$res = self::$common->executeQuery($sql);
		echo self::$common->errormsg."<br>";
	}

	static function getDate($tstamp)
	{
	  $tm = localtime(intval($tstamp), true);
  	return sprintf( "%04d-%02d-%02d", $tm["tm_year"]+1900, $tm["tm_mon"]+1,
           	       $tm["tm_mday"]);//, $tm["tm_hour"], $tm["tm_min"], $tm["tm_sec"]

	}
}

?>