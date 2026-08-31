<?php $CVS_Version = '$Revision$ ; $Date$';

class MP_RamblerStat extends MP_Patch
{
	public function __construct(DS_Common $common)
	{
		self::$common = $common;
	}

	public function patch()
	{

		$sql = "SELECT to_char(param_date, 'YYYY-MM-DD') as cdate FROM ds_cron_data WHERE param_name='rambler'";
		$res = self::$common->getResultArray('', $sql, '', '', '');
		echo self::$common->errormsg;
	  assert(empty(self::$common->errormsg));

	  $datec  = $res[0]['cdate'];
	  list($year, $mon, $day) = explode('-',$datec);
		$starttm   = mktime(0,0,0, $mon, $day, $year);

		$curdate = date("Y-m-d");

	  list($cur_year, $cur_mon, $cur_day) = explode('-',$curdate);
		$enddate =  mktime(0,0,0, $cur_mon, $cur_day, $cur_year);

    //echo "datec=$datec, $starttm  curdate=$curdate,$enddate, delta=".($enddate-$starttm)."\n";
	  if ($enddate > $starttm)
	  {

	  	$month1 = date("Y-{$mon}-01");
	  	//получим id все юзеров, которые с 1 числа этого мес€ца логинились
		  $sql = "select distinct lh.id_user from ds_loginhistory lh
inner join ds_user u on lh.id_user=u.id_user
where (lastvisit>='{$month1}') AND (lastvisit<'{$datec}')
and u.from_partner=6";

		  $res = self::$common->getResultArray('', $sql, '', '', '');
		  assert(empty(self::$common->errormsg));

		  $total  = 0;
		  $was = array();

		  if (($datec != '2010-01-01') && ($datec != '2010-03-01'))
		  {
		   foreach ($res as $r)
		   {
		  	 $total++;
		     array_push($was, $r['id_user']);
		   }
		  }

		  $nsec = 60*60*24;
		  $i=0;
		  $startdate = $datec;
		  $stat = '';

		  while (1)
		  {
  			$i++;
			  if ($starttm + $nsec*$i > $enddate)
			  {
  			  break;
			  }

	      $d = self::getDate($starttm + $nsec*$i);
		    list($year, $mon, $day) = explode('-',$d);
		     $day *= 1;

        list($year2, $mon2, $day2) = explode('-',$startdate);
		    $statdate = sprintf("%02d.%02d.%04d", $day2, $mon2, $year2);

  		  $sql = "select lh.id_user from ds_loginhistory lh
inner join ds_user u on lh.id_user=u.id_user
where (lastvisit>='{$startdate}') AND (lastvisit<'{$d}')
and u.from_partner=6";

		    $res = self::$common->getResultArray('', $sql, '', '', '');
		    assert(empty(self::$common->errormsg));

		    $sql = "select count(id_user) as cnt from ds_user WHERE registrationdate>='{$startdate}' AND
		    registrationdate <'{$d}' and activate_mail=1 AND from_partner=6";
		    $res2 = self::$common->getResultArray('', $sql, '', '', '');
		    assert(empty(self::$common->errormsg));
		    $regcnt = $res2[0]['cnt'];

		    $curc = 0;
		    foreach ($res as $r)
		    {
		      if (!in_array($r['id_user'],$was))
		      {
  		    	$curc++;
	  	    	array_push($was, $r['id_user']);
		      	$total++;
		      }
		    }
	      echo "{$statdate} {$regcnt} $total<br>\n";
        $stat .= "{$statdate} {$regcnt} {$total}\n";
		    $startdate = $d;

		    if ($day==1)
		    {
		      $total = 0;
		      $was = array();
		    }
		  }

	    if ($stat)
	    {
		    $sql = "UPDATE ds_cron_data SET param_date='{$curdate}' WHERE param_name='rambler'";
		    $res = self::$common->executeQuery($sql);
  	    assert(empty(self::$common->errormsg));

	      $fh = fopen('files/rstat.csv','a');
	      fwrite($fh, $stat);
	      fclose($fh);
	    }
	  }
	}

	static function getDate($tstamp)
	{
	  $tm = localtime($tstamp, true);
  	return sprintf( "%04d-%02d-%02d", $tm["tm_year"]+1900, $tm["tm_mon"]+1,
           	       $tm["tm_mday"]);//, $tm["tm_hour"], $tm["tm_min"], $tm["tm_sec"]

	}
}

?>