<?php

class DS_Botlist  extends DS_Base
{
  public static function AddBot($userid)
  {
    $common = self::getCommon();
    $sql  = "SELECT user_id from ds_antibot_botlist where user_id={$userid}";
 		$res = $common->getResultArray( "", $sql, "", "", "NO_CACHE");
  	assert(empty($common->errormsg));

    if (!$res)
    {
      $sql = "INSERT INTO ds_antibot_botlist(user_id, adddate) VALUES($userid, NOW())";
      $common->executeQuery($sql);
      assert(empty($common->errormsg));
    }
  }

  public static function RemoveBot($userid)
  {
     $common = self::getCommon();
     $userid = intval($userid);
     $sql = "DELETE FROM ds_antibot_botlist where user_id = $userid";
     $common->executeQuery($sql);
     assert(empty($common->errormsg));
  }

  public static function GetStat($from, $to, $idw,  $dirs)
  {
    global $filter;
    $common = self::getCommon();
    $to .= " 23:59:59";
    $sql  = "SELECT id, user_id, qdate, status, username from ds_antibot_stat, ds_user where (qdate>='$from') and (qdate<='$to') and (id_world = $idw) and (ds_user.id_user=ds_antibot_stat.user_id) order by user_id";
    $res = $common->getResultArray( "", $sql, "", "", "NO_CACHE");
    echo $common->errormsg;
    assert(empty($common->errormsg));

    $userarr = array();
    $oldid = 0;
    $k = -1;
    $totalgood = 0;

    $totalansw = count($res);
    for($i=0; $i < $totalansw;$i++)
    {
      $id     = $res[$i]['user_id'];
      $status = $res[$i]['status'];
      $username = $res[$i]['username'];
      if ($id != $oldid)  $k++;
      $oldid = $id;

      if (array_key_exists($k, $userarr))
      {
        $el = $userarr[$k];
        $total = $el['total'];
        $good = $el['good'];
        $proc = $el['proc'];
      }
      else
      {
        $bad = $total = $good = $proc = 0;
      }

      $total++;
      $good += $status;
      $proc = ($total-$good)/$total;
      $userarr[$k] = array('id'=>$id, 'total'=>$total, 'good'=>$good, 'proc'=>$proc, 'username'=>$username);

      $totalgood += $status;
    }
    $totalusers = $k+1;


    if ($filter=='username')
    {
      if ($dirs=='DESC')
       usort($userarr, "BotCmpStrDESC");
      else
       usort($userarr, "BotCmpStrASC");
    }
    else
    {
      if ($dirs=='DESC')
       usort($userarr, "BotCmpDESC");
     else
       usort($userarr, "BotCmpASC");
    }

    return array($userarr, $totalgood, $totalusers, $totalansw);
  }
}

function BotCmpDESC($a, $b)
{
   global $filter;
   if ($a[$filter] == $b[$filter])
        $r = 0;
   else
      $r = ($a[$filter] < $b[$filter]) ? 1 : -1;

   return $r;
}


function BotCmpASC($a, $b)
{
   global $filter;
   if ($a[$filter] == $b[$filter])
        $r = 0;
   else
      $r = ($a[$filter] > $b[$filter]) ? 1 : -1;

   return $r;
}

function BotCmpStrDESC($a, $b)
{
   global $filter;
   $r = strcasecmp($a[$filter], $b[$filter]);
   if (!$r)
     return 0;

   if ($r>0)
     return -1;
   else
     return 1;
}


function BotCmpStrASC($a, $b)
{
   global $filter;
   $r = strcasecmp($a[$filter], $b[$filter]);
   if (!$r)
     return 0;

   if ($r>0)
     return 1;
   else
     return -1;
}
?>