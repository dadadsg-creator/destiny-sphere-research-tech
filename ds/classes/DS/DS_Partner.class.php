<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2008/07/01 08:46:46 $';

/**
 * Статтический Класс контейнер для SQL зпросов свзяанных с миром.
 */
class DS_Partner  extends DS_Base
{
	static public function AddPartner($pD)
	{
		$common = self::getCommon();
		$common->setDbId(0);

    if (!isset($pD['id_partner']) || ($pD['id_partner']==0))
    {
		  $sql = "select  nextval('ds_partner_id_partner_seq'::regclass) as n";
	    $res = $common->getResultArray ( "",$sql, "", "", "");
      assert(empty($common->errormsg));
      $pid = $res[0]['n'];
    }
    else
    {
      $pid = $pD['id_partner'];
    }

		$sql = "INSERT INTO ds_partner(id_partner, name, login,  site_list, contact_person, email, reg_date, active, procent, password)";
    $sql .= "VALUES($pid, '{$pD['name']}', '{$pD['login']}', '{$pD['site_list']}','{$pD['contact_person']}',
    '{$pD['email']}', now(), true, {$pD['procent']}, '{$pD['password']}')";
    SU_DebugTools::LogAll("AddPartner = $sql",'plog.log');
    $res = $common->executeQuery($sql);
    echo $common->errormsg;
    if ($common->errormsg)
       SU_DebugTools::LogAll("err = ".$common->errormsg,'plog.log');
    assert(empty($common->errormsg));
		return $pid;
	}

	static public function UpdatePartner($pD)
	{
		$common = self::getCommon();
		$common->setDbId(0);

		$updpassw  = '';
		if ($pD['password'])
		{
		  $updpassw = ", password = '{$pD['password']}'";
		}
		$sql = "UPDATE ds_partner SET name='{$pD['name']}', site_list='{$pD['site_list']}', contact_person='{$pD['contact_person']}',
		email = '{$pD['email']}', active = {$pD['active']}, procent = {$pD['procent']} $updpassw WHERE id_partner={$pD['id_partner']}";

    $res = $common->executeQuery($sql);
    echo $common->errormsg;
    assert(empty($common->errormsg));
	}

	static public function DeletePartner($id)
	{
		$common = self::getCommon();
		$common->setDbId(0);

		$sql = "DELETE FROM ds_partner WHERE id_partner={$id}";

    $res = $common->executeQuery($sql);
    echo $common->errormsg;
    assert(empty($common->errormsg));
	}

	static public function getPartner($id=0)
	{
		$common = self::getCommon();
		$common->setDbId(0);

		$and = "";
	  if ($id)
	    $and = "and id_partner=$id";
/*
	  if (constant('BILLING_PARTNER_ID')==9)
	    $maxn = 2;
	  else
	    $maxn = 6;
*/
    $maxn = 3;
	  $sql = "SELECT * FROM ds_partner WHERE id_partner  >= $maxn $and ORDER BY id_partner";
	  $res = $common->getResultArray ( "",$sql, "", "", "");

    assert(empty($common->errormsg));

		if(!$res){
			return false;
		}else{
		  if ($id)
			  return $res[0];
			else
			  return $res;
		}
  }

  static public function getPartnerStat($pid, $date1, $date2)
  {
		$common = self::getCommon();
		$common->setDbId(0);
    $pid = intval($pid);
    $sql = "SELECT count(id_user) as cnt FROM ds_user WHERE from_partner=$pid  and activate_mail=1";

	  $res = $common->getResultArray ( "",$sql, "", "", "");
    assert(empty($common->errormsg));

    $data = array();
    $data['user_total'] = $res[0]['cnt'];

    $sql = "SELECT count(id_user) as cnt FROM ds_user WHERE registrationdate>=
    to_timestamp('$date1','YYYY-MM-DD') and registrationdate <= to_timestamp('$date2','YYYY-MM-DD HH24:MI:SS')  and from_partner=$pid  and activate_mail=1";
	  $res = $common->getResultArray ( "",$sql, "", "", "");
    assert(empty($common->errormsg));
    $data['user_period'] = $res[0]['cnt'];
    return $data;
  }

  static public function getPartnerRegStat_old($pid, $date1, $date2)
  {
		$common = self::getCommon();
		$common->setDbId(0);
    $pid = intval($pid);
    $sql = "SELECT from_site_id, extract(epoch from registrationdate)::int as regd FROM ds_user
    WHERE from_partner=$pid";

	  $res = $common->getResultArray ( "",$sql, "", "", "");
    if ($common->errormsg)
    {
       SU_DebugTools::LogAll("sql err=".$common->errormsg,'partner.log');
    }
    assert(empty($common->errormsg));


    $dt = explode(' ', $date1);
    $dt = $dt[0];
    list ($year, $mon, $day) = explode('-',$dt);
    $t1 = mktime(0, 0, 0, $mon, $day, $year);

    $dt = explode(' ', $date2);
    $dt = $dt[0];
    list ($year, $mon, $day) = explode('-',$dt);
    $t2 = mktime(23, 59, 59, $mon, $day, $year);

    $total = count($res);

    $result = array();
    $result['sites'] = array();
    for($i=0; $i < $total; $i++)
    {
       $siteid = $res[$i]['from_site_id'];
       $regd   = $res[$i]['regd'];
       if (!$siteid)
          $siteid = 0;
       if (!array_key_exists($siteid, $result['sites']) )
       {
         $result['sites'][$siteid] = array('total' => 0, 'period' => 0);
       }
       $result['sites'][$siteid]['total']++;

       if (($regd >= $t1) && ($regd <= $t2) )
       {
         $result['sites'][$siteid]['period']++;
       }
    }

    return serialize($result);
  }

  static public function getAllTotalPartnerStat($plist)
  {
		$common = self::getCommon();
		$common->setDbId(0);

    $partners = explode(',', $plist);
    //$resarr = array();
    $str = "";
    foreach($partners as $p)
    {
       if (!$p)
         continue;
       $sql = "SELECT count(id_user) as cnt FROM ds_user WHERE from_partner=$p  and activate_mail=1";
       $res = $common->getResultArray ( "",$sql, "", "", "NO_CACHE");

       //array_push($resarr, array('id' => $p, 'cnt' => $res[0]['cnt']));
       $str .= "$p:".$res[0]['cnt'].",";
    }

//SU_DebugTools::LogAll("resarr= ".serialize($resarr), 'ustat.log');

    return serialize(substr($str, 0, -1));
  }

  static public function getPartnerRegStatSimple($pid)
  {
		$common = self::getCommon();
		$common->setDbId(0);    
    $sql = "SELECT * FROM ds_user WHERE from_partner=$pid order by id_user";
    $res = $common->getResultArray ( "",$sql, "", "", "");
    return $res;
  }
  
  static public function getPartnerRegStat($pid, $date1, $date2)
  {
		$common = self::getCommon();
		$common->setDbId(0);
    $pid = intval($pid);
    $sql = "SELECT distinct from_site_id FROM ds_user
    WHERE from_partner=$pid";

	  $res = $common->getResultArray ( "",$sql, "", "", "");
    if ($common->errormsg)
    {
       SU_DebugTools::LogAll("sql err=".$common->errormsg,'partner.log');
    }
    assert(empty($common->errormsg));
/*

    $dt = explode(' ', $date1);
    $dt = $dt[0];
    list ($year, $mon, $day) = explode('-',$dt);
    $t1 = mktime(0, 0, 0, $mon, $day, $year);

    $dt = explode(' ', $date2);
    $dt = $dt[0];
    list ($year, $mon, $day) = explode('-',$dt);
    $t2 = mktime(23, 59, 59, $mon, $day, $year);
*/
    $total = count($res);

    $result = array();
    $result['sites'] = array();
    for($k=0;$k < $total; $k++)
    {
       $whsid = $siteid  = $res[$k]['from_site_id'];

       if (!$siteid)
       {
          $siteid = 0;
          $whsid = '((from_site_id = 0) or (from_site_id is null))';
       }
       else
       {
          $whsid = "(from_site_id = {$siteid})";
       }
       if (!array_key_exists($siteid, $result['sites']) )
       {
         $result['sites'][$siteid] = array('total' => 0, 'period' => 0);
       }

       $sql = "SELECT count(id_user) as cnt FROM ds_user WHERE $whsid and from_partner=$pid AND
       (registrationdate>='{$date1}' AND registrationdate<='{$date2}') and activate_mail=1";
       //SU_DebugTools::LogAll("1 sql=$sql", 'reg.log');
       $res = $common->getResultArray ( "",$sql, "", "", "");
       $result['sites'][$siteid]['period'] = $res[0]['cnt'];

       $sql = "SELECT count(id_user) as cnt FROM ds_user WHERE $whsid and from_partner=$pid and activate_mail=1";
       //SU_DebugTools::LogAll("2 sql=$sql", 'reg.log');

       $res = $common->getResultArray ( "",$sql, "", "", "");
       //SU_DebugTools::LogAll("3. res=".var_export($res, true), 'reg.log');

       $result['sites'][$siteid]['total'] = $res[0]['cnt'];
    }


    return serialize($result);
  }
}//END OF CLASS


?>