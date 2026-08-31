<?php $CVS_Version = '$Revision$ ; $Date$';

class MP_Popmog extends MP_Patch
{
  static public $partner_id = 109;
	static public $activatePrice = 0;
	static public	$pressPrice = 0;
	static public $gatePrice = 0;
	static public $datec;
	static public $curdate;	
	static public $userCombid=0;
		  
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
    global $popmogcfg;
    //set_time_limit(600);
		$sql = "SELECT date_part('epoch', param_date) as cdate FROM ds_cron_data WHERE param_name='popmog'";
		$res = self::$common->getResultArray('', $sql, '', '', '');
		//echo self::$common->errormsg;
	  assert(empty(self::$common->errormsg));

	  self::$datec  = $res[0]['cdate'];

		//echo "datec=".self::$datec.',time='.time().',delta='.(time() - self::$datec).'<br>';
    ///echo "activatePrice={$activatePrice},pressPrice={$pressPrice},gatePrice={$gatePrice} datec=$datec, $starttm  curdate=$curdate,$enddate, delta=".($enddate-$starttm)."\n";
	  if (time() - self::$datec  > 3600*24)//прошло больше суток
	  {
      $client   = new PopmogRestClient ( $popmogcfg['key'], $popmogcfg['secret'] ); 	    
      $users = self::getPopmogUsers();

      
      $n = 0;  
      $rtgarr = array();
      foreach ($users as $u)
      {
         $rtg = self::getUserRating($u);
         //$rtgarr[] = array('id_user_popmog'=>$u['id_user_popmog'],'username'=>$u['username'],'rtg'=>$rtg); 
         
         $r =  $client->player_set_score($rtg, $u['id_user_popmog']);
//         echo "id_user_popmog={$u['id_user_popmog']}, name=username={$u['username']}, rtg=$rtg, result=".var_export($r, true)."<br>";
         //$n++;
         //if ($n>10)
          //break;
      }
      
      /*
      usort($rtgarr, 'rtgsort');
      foreach($rtgarr as $u)
      {
         $r =  $client->player_set_score($rtg, $u['id_user_popmog']);
         echo "id_user_popmog={$u['id_user_popmog']}, name=username={$u['username']}, rtg={$u['rtg']}, result=".var_export($r, true)."<br>";
         $n++;
         if ($n>10)
         break;
      }
      */
	    $stat = true;
	    if ($stat)
	    {
		    $sql = "UPDATE ds_cron_data SET param_date=NOW() WHERE param_name='popmog'";
		    $res = self::$common->executeQuery($sql);
	    }
	  }
	}

  static function getUserRating($u)
	{
	  //echo "0";
	  $dbid = DS_Common::GetServerID($u['dbname']);
	  self::$common->setDbId($dbid);
		$_SESSION['dbId'] = $dbid;
		$_SESSION['world'] = $u['id_world'];		
	  //echo "1";
		$worldSettigs = DE_WorldSettings::CreateForCurentWorld();
		
		$sciensK = $worldSettigs->get('RANKING_SCIENCE_MULTIPLIER');
		$artK = $worldSettigs->get('RANKING_ARCHEOLOGY_MULTIPLIER');
		$buildK = $worldSettigs->get('RANKING_BUILDING_MULTIPLIER');
		$battleK = $worldSettigs->get('RANKING_BATTLE_MULTIPLIER');	  
		
		$ranking = "COALESCE(
			                (r.science_ranking*$sciensK 
			                + 
			                r.artefact_ranking*$artK 
			                + 
			                r.building_ranking*$buildK
			                + 
			                COALESCE(b.absolute_value*$battleK,0)
			                ), 0) as ranking";
		
		$sql = "SELECT 
						$ranking,	ui.id_userinstance
					FROM 
						ds_userinstance ui	LEFT JOIN ds_ranking r ON (r.id_userinstance = ui.id_userinstance)
						LEFT JOIN ds_battlexp b ON (b.id_userinstance = ui.id_userinstance)
            WHERE 
            ui.id_world = ".$u['id_world']." AND ui.id_userinstance=".$u['id_userinstance'];
	  $rank = self::$common->getResultArray('', $sql, '', '', 'NO_CACHE');
	 // echo "$sql, err=".$common->errormsg."<br>";
    assert(empty($common->errormsg));
		if ($rank)
		  return $rank[0]['ranking'];
		else 
		  return 0;  
	}
	
	static function getPopmogUsers()
	{
	  $sql = "select u.id_user, u.username,  u.id_user_popmog, uic.id_userinstance,  id_world, dbname  from ds_user u inner join ds_userinstancecollection uic ON 
	  u.id_user = uic.id_user  where u.id_user_popmog is not NULL order by id_user";
	  $users = self::$common->getResultArray('', $sql, '', '', 'NO_CACHE');
    assert(empty($common->errormsg));
    return $users;
	}
	

	static function getDate($tstamp)
	{
	  $tm = localtime(intval($tstamp), true);
  	return sprintf( "%04d-%02d-%02d", $tm["tm_year"]+1900, $tm["tm_mon"]+1,
           	       $tm["tm_mday"]);//, $tm["tm_hour"], $tm["tm_min"], $tm["tm_sec"]

	}
}

function rtgsort($a, $b)
{
    if ($a['rtg'] == $b['rtg']) 
    {
        return 0;
    }
    return ($a['rtg'] > $b['rtg']) ? -1 : 1;  
}

?>