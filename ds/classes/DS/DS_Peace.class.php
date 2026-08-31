<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_Peace extends DS_Base
{
  public static $endDateStr; //время окончания перемирия после расторжения
  
    public static function getPrivatePeace($id_userinstance)
    {
        $common = self::getCommon();

    $sql = "select pu.id_userinstance1,pu.id_userinstance2, pu.status, pu.peace_date,  
    pu.who_offer, pu.id_peace,  date_part('epoch', pu.peace_end_date) as psec,  u1.username as username1,u2.username as username2   from ds_peace_user  pu
INNER JOIN ds_userinstance  ui1 ON  ui1.id_userinstance=
id_userinstance1 INNER JOIN ds_user u1 ON ui1.id_user=u1.id_user
INNER JOIN ds_userinstance  ui2 ON  ui2.id_userinstance=
id_userinstance2 INNER JOIN ds_user u2 ON ui2.id_user=u2.id_user
   WHERE id_userinstance1={$id_userinstance} or id_userinstance2={$id_userinstance}   ";
        
    $res = $common->getResultArray( "", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));

        if(!$res){
            return false;
        }else{
            return $res;
        }
    }

    function delPrivatePeace($id_peace)
    {
        $common = self::getCommon();        

    $common->executeQuery("DELETE FROM ds_peace_user WHERE id_peace={$id_peace}");
        assert(empty($common->errormsg));
    }
        
    public static function getPeaceByUiid($id_userinstance_with_whom)
    {
        $common = self::getCommon();
                
        $player_uid = $_SESSION['uiid'];
        if ($player_uid < $id_userinstance_with_whom)
        {
           $plr_n = 1;
           $with_whom_n = 2;
        }
        else 
        {
           $plr_n = 2;
           $with_whom_n = 1;
        }
        
    $sql = "SELECT id_peace, status, peace_date, who_offer,  peace_end_date, date_part('epoch', peace_end_date) as psec 
    FROM ds_peace_user WHERE id_userinstance{$plr_n} = {$player_uid} AND
        id_userinstance{$with_whom_n} = {$id_userinstance_with_whom}";
    $res = $common->getResultArray( "", $sql, "", "", "NO_CACHE");
        assert(empty($common->errormsg));
    //SU_DebugTools::LogAll("sql = $sql",'peace.log');  

        if ($res)
        return $res[0];
      else 
        return 0;  
    }

    function addPeace($id_userinstance_with_whom)
    {
        $common = self::getCommon();
                
        $player_uid = $_SESSION['uiid'];
        if ($player_uid < $id_userinstance_with_whom)
        {
           $plr1 = $player_uid;
           $plr2 = $id_userinstance_with_whom;
        }
        else 
        {
           $plr1 = $id_userinstance_with_whom;
           $plr2 = $player_uid;
        }
        
    $sql = "INSERT INTO ds_peace_user (id_userinstance1, id_userinstance2, status, peace_date, who_offer)
    VALUES($plr1, $plr2, 0, now(), {$player_uid})";
    $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
    
        return true;        
    }
        
    function checkUserExistsInWorld($nick, $world)
    {
       $sql = "select * from ds_userinstance ui inner join ds_user u
on ui.id_user = u.id_user WHERE u.username = '{$nick}' and ui.id_world = {$world}";

       $common = self::getCommon();
     $res = $common->getResultArray( "", $sql, "", "", "NO_CACHE");
         assert(empty($common->errormsg));
         if ($res)
           return $res[0];
         else 
           return false;  
    }  
    
    function SetPeace($uiid, $mode)
    {
        $common = self::getCommon();
        
        $result = 0;        
        $peace = self::getPeaceByUiid($uiid);
        //SU_DebugTools::LogAll("peace=".var_export($peace, true),'peace.log');  
        //SU_DebugTools::LogAll("sql=$sql, res=".var_export($res, true), 'peace.log');
        if ($peace)
        {
          if ($mode == 'PEACE_CONFIRMATION')//заключаем мир
          {
            if ($peace['status']==0)//на данный момент мир действительно не заключен
            {
                $sql =  "UPDATE ds_peace_user SET status=1 WHERE id_peace={$peace['id_peace']}";
            //SU_DebugTools::LogAll("sql=$sql", 'peace.log');
                
                $common->executeQuery($sql);
            assert(empty($common->errormsg));
            $result = 1;
            }
          }
          else if ($mode == 'PEACE_REJECTING')//расторгаем мир
          {
          if ($peace['status']==0) //отклоняем предложение о мире
          {      
                   $sql =  "DELETE FROM ds_peace_user WHERE id_peace={$peace['id_peace']}";
            //SU_DebugTools::LogAll("sql=$sql", 'peace.log');
                 $common->executeQuery($sql);
             $result = 1;
          }  
          }
          else if ($mode == 'LIST_REQUEST_REJECT_PEACE') //расторжение существующего мира
          {    
          if ((!$peace['peace_end_date']) && ($peace['status']))//расторгаем существующий мир - при условии, что он уже
                                   //не расторгнут
          {
             $delay = DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_PEACE_DELAY');
             $turn_time = DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME'); //в минутах
             
             $peace_end = localtime(time()+60*$turn_time*$delay, true);
             
             $tm_mday = sprintf("%02d",$peace_end['tm_mday']); 
             $tm_mon  = sprintf("%02d",$peace_end['tm_mon'] + 1); 
             $tm_year = $peace_end['tm_year'] + 1900; 
             $tm_sec  = sprintf("%02d",$peace_end['tm_sec']); 
             $tm_min  = sprintf("%02d",$peace_end['tm_min']); 
             $tm_hour = sprintf("%02d",$peace_end['tm_hour']); 
             
             self::$endDateStr = "{$tm_year}-{$tm_mon}-{$tm_mday} {$tm_hour}:{$tm_min}:{$tm_sec}"; 
             $sql = "UPDATE ds_peace_user SET status=0, peace_end_date='".self::$endDateStr."' WHERE id_peace={$peace['id_peace']}";
             $common->executeQuery($sql);
             //SU_DebugTools::LogAll("date=".date("H:i:s").",delay=$delay, turn_ime={$turn_time}, sql=$sql\n",'peace.log');
             
             assert(empty($common->errormsg));
             $result = 1;             
          }
          }
          else if ($mode == 'LIST_REQUEST_CANCEL_REQUEST') //отклонение запроса на мир, который
                                                           //игрок сам предлагал
          {
          if (!$peace['status']) //мир не был подтверждён другой стороной
          {
             $sql = "DELETE FROM ds_peace_user WHERE id_peace={$peace['id_peace']}";
             $common->executeQuery($sql);
             //SU_DebugTools::LogAll("sql=$sql\n",'peace.log');
             
             assert(empty($common->errormsg));
             $result = 1;             
          }
          
          }
        }
        
        return $result;
    }

        
    public static function getUserAlliance($id_userinstance)
    {

        $common = self::getCommon();

    $sql = "select al.id_alliance, al.alliancetag, al.alliancename from ds_userhasalliance ua
inner join ds_alliance al ON ua.id_alliance=al.id_alliance
where id_userinstance={$id_userinstance}";
    
        //SU_DebugTools::LogAll("getPrivatePeace sql=$sql",'peace.log');
    $res = $common->getResultArray( "", $sql, "", "", "NO_CACHE");

        assert(empty($common->errormsg));

        if(!$res){
            return false;
        }else{
            return $res[0];
        }
      
    }

    public static function StopArmy($from, $to)
    {
      $common = self::getCommon();
      
    $sql = "SELECT * FROM ds_unitcontaineraction ua
                WHERE 
                ua.id_userinstance_from = {$from}
                AND ua.id_userinstance_to = {$to} and id_unitcontaineractiontype in(7,8)";

    $armys = $common->getResultArray( "", $sql, "", "", "NO_CACHE");

        assert(empty($common->errormsg));
        $r  = 0;
        if ($armys)
        {
      foreach ($armys as $a)
      {
        $sql = "SELECT  return_army({$a['id_unitcontainer']})";
        $common->getResultArray( "", $sql, "", "", "NO_CACHE");
      }    
        $r  = 1;
        }
        
        return $r;
    }
}

?>