<?php $CVS_Version = '$Revision$ ; $Date$';

class MP_GameLeads extends MP_Patch
{
  static public $partner_id = 109;
    static public $activatePrice = 0;
    static public   $pressPrice = 0;
    static public $gatePrice = 0;
    static public $datec;
    static public $curdate;
    static public $userCombid=0;
  static public $pu_arr;
    public function __construct(DS_Common $common)
    {
        self::$common = $common;
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

        $sql = "SELECT to_char(param_date, 'YYYY-MM-DD') as cdate FROM ds_cron_data WHERE param_name='gameleads'";
        $res = self::$common->getResultArray('', $sql, '', '', '');
        echo self::$common->errormsg;
      assert(empty(self::$common->errormsg));

      self::$datec  = $res[0]['cdate'];
      list($year, $mon, $day) = explode('-',self::$datec);
        $starttm   = mktime(0,0,0, $mon, $day, $year);

        self::$curdate = date("Y-m-d");

      list($cur_year, $cur_mon, $cur_day) = explode('-', self::$curdate);
        $enddate =  mktime(0,0,0, $cur_mon, $cur_day, $cur_year);

        self::$activatePrice = self::getParam('gl_activate_price')*1;
        self::$pressPrice = self::getParam('gl_press_price')*1;
        self::$gatePrice = self::getParam('gl_gate_price')*1;

    //echo "activatePrice={$activatePrice},pressPrice={$pressPrice},gatePrice={$gatePrice} datec=$datec, $starttm  curdate=$curdate,$enddate, delta=".($enddate-$starttm)."\n";
      if ($enddate > $starttm)
      {
        $secretkey = 'REDACTED_API_SECRET';
      $data = array();

      $data['pid'] = self::$partner_id;
      $data['sign'] = md5(self::$partner_id.$secretkey);
      $data['sdate'] = self::$datec;
      $data['edate'] = self::$curdate;

     // if (constant("VERSION_NAME")=="LOCAL")
       // $url = "http://billing/gate/moneystat.php";
      //else
        $url = "https://billing.destinysphere.ru/gate/moneystat.php";
      $params = '';

      foreach ($data as $key=>$val)
      {
       if ($i==0)
        $params .= "{$key}=$val";
       else
        $params .= "&{$key}=$val";

       $i++;
      }

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

      self::$pu_arr = array();
      if (strlen($pu)>0)
        self::$pu_arr = explode(',', $pu);

      //die(var_export(self::$pu_arr, true));
      $users = DS_Partner::getPartnerRegStatSimple(self::$partner_id);
      //echo 'users='.var_export($users, true)."<hr>";
      foreach ($users as $u)
      {
         self::checkUser($u['id_user']);
      }

        $stat = true;
        if ($stat)
        {
            $sql = "UPDATE ds_cron_data SET param_date='".self::$curdate."' WHERE param_name='gameleads'";
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


    static function getUserStat($tm) {
        $date = self::getDate($tm);

        $sql = "select id_user, activated, psum, date_part('epoch', action_date) as action_date, date_part('epoch', first_login) as first_login, date_part('epoch', last_login) as last_login,
        press_build, gate_open, auth_count, was_payment from ds_user_actionstat where action_date='$date' order by id_stat";
        $stat = self::$common->getResultArray('', $sql, '', '', 'NO_CACHE');

        foreach ($stat as $r)
        {
            $id_user = $r['id_user'];
            $userstat[$id_user]['id'] = $r['id_user'];
            $userstat[$id_user]['dl'] = 0;
            if (!$userstat[$id_user]['auth_price'])
            {
               $userstat[$id_user]['auth_price'] = $r['was_payment'];
            }


            if (!$userstat[$id_user]['first_login'])
              $userstat[$id_user]['first_login'] = $r['first_login'];

            if ($userstat[$id_user]['eff_reg']<1)
              $userstat[$id_user]['eff_reg'] += $r['activated'];

            if (!$r['gate_open'] && !$r['press_build'])
              $userstat[$id_user]['level'] = 0;
            else if ($r['press_build']==1 && $r['gate_open']==0)
              $userstat[$id_user]['level'] = 1;
            else if ($r['gate_open']==1)
              $userstat[$id_user]['level'] = 2;

            $userstat[$id_user]['auth_count'] +=  $r['auth_count'];
            $userstat[$id_user]['time_game'] = 0;

            if ($r['last_login'])
              $userstat[$id_user]['last_login'] = $r['last_login'];

            $userstat[$id_user]['price'] += $r['psum'];
            $userstat[$id_user]['currency'] = 'RUR';

            if ($r['psum'])
              $userstat[$id_user]['date'] = $r['action_date'];
        }
        return $userstat;
    }


    static function getAgregatedStat($tm1, $tm2)
    {
        $userstat1 = self::getUserStat($tm1);
        $userstat2 = self::getUserStat($tm2);

        foreach ($userstat2 as $key=>$val) {
            $old = $userstat1[$key];
            if ($old['auth_count']==$val['auth_count'] &&
                $old['eff_reg']==$val['eff_reg'] &&
                $old['level']==$val['level'] &&
                $old['price']==$val['price'] &&
                $old['auth_price']==$val['auth_price'])
                    unset($userstat2[$key]);
        }

        return $userstat2;
    }

    static function CollectUserInfo($id_user)
    {
      $sql = "SELECT * FROM ds_user_actionstat WHERE id_user={$id_user}";
        $res = self::$common->getResultArray('', $sql, '', '', 'NO_CACHE');
      //assert(empty(self::$common->errormsg));
      $userdata = array('activated'=>0,'press_build'=>0,'gate_open'=>0, 'psum' => 0);

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
      $statdata = array('activated' => 0,'press_build' => 0, 'gate_open' => 0, 'psum' =>0, 'last_login'=>'', 'auth_count'=>0, 'first_login'=>'');

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
      if (!$userdata['activated'])
      {
        $statdata['activated'] = self::isActivated($id_user);
        if ($statdata['activated'])
          $statdata['psum'] += self::$activatePrice;
      }

      $statdata['was_payment'] =  0;
      if (self::$pu_arr)
      {
        if (in_array($id_user, self::$pu_arr))
        {
          $statdata['was_payment'] =  1;
        }
      }


    $statdata['first_login'] = self::getFirstLogin($id_user);
    $statdata['last_login']  = self::getLastLogin($id_user);
    $statdata['auth_count']  = self::getAuthCount($id_user);
    $statdata['press_build'] = $userdata['press_build']; //если один раз построили пресс или ворота
    $statdata['gate_open']   = $userdata['gate_open']; //то далее эта инфа так и тянется


      GE_SearchStart::SetCommon(self::$common);

    $userworlds = GE_SearchStart::GetWorldsWhereUserIsRegistered(intval($id_user));
    //echo 'userworlds='.var_export($userworlds, true)."<hr>";
    //проверяем   на открытость ворот и постройку пресса только если хоть что-то из этого
    //не построено ранее
    if (!$userdata['press_build'] || !$userdata['gate_open'])
    {
      //хрен знает в каком мире игрок по факту начнёт играть - приходится проверять все, где зарегался
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
            }
          }

          if (!$userdata['gate_open'])
          {
          $statdata['gate_open'] = self::isGateOpened($userCombid);
            if ($statdata['gate_open'])
            {
            $statdata['psum'] += self::$gatePrice;
            $userdata['gate_open']  = 1;
            }
          }

          if ($userdata['gate_open'] && $userdata['press_build'])
          {
            break;
          }
        }
      }

      self::$common->setDbId(0);
      //if ($statdata['psum'])
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

        $sql = "INSERT INTO ds_user_actionstat(action_date, activated, first_login, last_login, auth_count, press_build, gate_open, psum, id_user, was_payment )
         VALUES('{$d}', {$statdata['activated']}, {$statdata['first_login']}, {$statdata['last_login']}, '{$statdata['auth_count']}',
         {$statdata['press_build']}, {$statdata['gate_open']}, {$statdata['psum']}, {$id_user}, {$statdata['was_payment']})";

        //echo $sql."<br>";
        $res = self::$common->executeQuery($sql);
        echo self::$common->errormsg."<br>";
    }

    static function getDate($tstamp)
    {
      $tm = localtime(intval($tstamp-86400), true);
    return sprintf( "%04d-%02d-%02d", $tm["tm_year"]+1900, $tm["tm_mon"]+1,
                   $tm["tm_mday"]);//, $tm["tm_hour"], $tm["tm_min"], $tm["tm_sec"]

    }
}

?>