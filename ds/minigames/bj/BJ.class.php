<?php

   class BJ extends DS_Base
   {
      static public function getUserInfo($uid)
      {
         $oldDbId = $_SESSION["dbId"];
         $common = DS_Common::CreateForCurrentDB(0);

     		 if (!empty($common->errormsg)) {
           SU_DebugTools::LogAll(',errormsg='.$common->errormsg,'bj.log');
           exit;
		     }

         $sql = "select u.username, u.id_user, s.sc_amount from ds_user u
inner join  ds_money_account  s on s.id_user=u.id_user
where u.id_user=$uid and s.world_identifier='UC'";

        $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
         //echo $common->errormsg;
        if ($common->errormsg)
          SU_DebugTools::LogAll("after getResultArray($sql) errormsg=".$common->errormsg,'bj.log');
        assert(empty($common->errormsg));
        $_SESSION["dbId"] = $oldDbId;
        return $res[0];
      }

      static public function changeSilverAcc($id_user, $delta)
      {
         $oldDbId = $_SESSION["dbId"];
         $_SESSION["dbId"] = 0;
         $id_user = intval($id_user);
         assert($id_user > 0);
         $common = DS_Common::CreateForCurrentDB(0);


         if (!empty($common->errormsg)) {
           SU_DebugTools::LogAll(',errormsg='.$common->errormsg,'bj.log');
           exit;
		     }
/*
         $sql = "UPDATE ds_money_account SET sc_amount = sc_amount + $delta where id_user=$id_user and
         world_identifier='UC'";

        $res = $common->executeQuery($sql);
         //echo $common->errormsg;
        assert(empty($common->errormsg));

        $sql = "SELECT sc_amount FROM ds_money_account where id_user=$id_user and
         world_identifier='UC'";
        $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
        assert(empty($common->errormsg));
*/

        $user = DE_User::CreateFromId($id_user);
        $actionType = DE_MoneyActionType::CreateFromName('BLACKJACK_BET');
        $world = DE_WorldFromCollection::CreateFromID('UC');
        $memberFrom = new MU_EntityOperationMember($user, $world);

        $DSUser =  DE_User::CreateFromName('DestinySphere');
        $memberTo = new MU_EntityOperationMember($DSUser, $world);
        if ($delta > 0)
        {
          $t  = $memberTo;
          $memberTo  = $memberFrom;
          $memberFrom = $t;
        }
        $summ = new MU_EntityMoneySumm(abs($delta), 'SC');

        $operation = new MU_EntityOperation($actionType, $summ, $memberFrom, $memberTo);
        //SU_DebugTools::LogAll('operation='.var_export($operation,true),'bj.log');
        MU_ControlExecOperations::ExecOperation($operation, '');
        //SU_DebugTools::LogAll('after ExecOperation','bj.log');

        $sql = "SELECT sc_amount FROM ds_money_account where id_user=$id_user and
         world_identifier='UC'";
        $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
        assert(empty($common->errormsg));
        if ($delta != 0)
        {
          if ($delta > 0)
            $sql = "UPDATE ds_bj_winsum set total = total+{$delta}, winsum = winsum + {$delta} where id=1";
          else
            $sql = "UPDATE ds_bj_winsum set total = total+{$delta}, losssum = losssum + ".abs($delta)."  where id=1";

          $common->executeQuery($sql);
          assert(empty($common->errormsg));
        }

        $_SESSION["dbId"] = $oldDbId;
        return $res[0]['sc_amount'];
      }

      static public function checkTotalWinsum()
      {
        $oldDbId = $_SESSION["dbId"];
        $common = DS_Common::CreateForCurrentDB(0);

        $sql = "SELECT winsum, losssum, total, letter_send, stopped from ds_bj_winsum where id=1";
        $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
        assert(empty($common->errormsg));
        $_SESSION["dbId"] = $oldDbId;
        return $res[0];
      }

      static public function updateTotalWinsum($field, $value)
      {
        $oldDbId = $_SESSION["dbId"];
        $common = DS_Common::CreateForCurrentDB(0);

        $sql = "UPDATE ds_bj_winsum set $field = $value where id=1";
        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
        $_SESSION["dbId"] = $oldDbId;
        return $res[0];
      }

      static public function setLetterSend($status)
      {
        $oldDbId = $_SESSION["dbId"];
        $common = DS_Common::CreateForCurrentDB(0);
        //SU_DebugTools::LogAll('setLetterSend '.$common->errormsg,'bj.log');
        assert(empty($common->errormsg));
        $sql = "UPDATE ds_bj_winsum SET letter_send = {$status} WHERE id=1";
        $res = $common->executeQuery($sql);
        //SU_DebugTools::LogAll('setLetterSend '.$common->errormsg,'bj.log', 0);
        assert(empty($common->errormsg));
        $_SESSION["dbId"] = $oldDbId;
      }

      static public function setGameResult($bj_idgame, $result, $userScore, $dlrScore, $hand, $winsum)
      {
         $oldDbId = $_SESSION["dbId"];
         $common = DS_Common::CreateForCurrentDB(0);
     		 if (!empty($common->errormsg)) {
           SU_DebugTools::LogAll(',errormsg='.$common->errormsg,'bj.log');
           exit;
		     }

         $sql = "UPDATE ds_bj_game SET result{$hand}=$result, score{$hand}_plr=$userScore, score_dlr=$dlrScore, winsum = winsum+{$winsum}, winsum{$hand}=winsum{$hand}+{$winsum} WHERE id_bj_game = $bj_idgame";

         $res = $common->executeQuery($sql);
         if ($common->errormsg)
          SU_DebugTools::LogAll("setGameResult($sql) errormsg=".$common->errormsg,'bj.log');
         assert(empty($common->errormsg));

         //SU_DebugTools::LogAll('getGame sql='.$sql."\n res=".var_export($res, true),'bj.log');

         $_SESSION["dbId"] = $oldDbId;
      }

      static public function ShiftDeck($id_bj_game, $deck,  $shift_num)
      {
         $oldDbId = $_SESSION["dbId"];
         $common = DS_Common::CreateForCurrentDB(0);

         //SU_DebugTools::LogAll('ShiftDeck deck='.$deck.", shift_num=".$shift_num,'bj.log');

         if (!empty($common->errormsg)) {
           SU_DebugTools::LogAll(',errormsg='.$common->errormsg,'bj.log');
           exit;
		     }

         $sql = "UPDATE ds_bj_game SET deck='$deck', shift_num=$shift_num WHERE id_bj_game = {$id_bj_game}";

         $res = $common->executeQuery($sql);
         //echo $common->errormsg;
         if ($common->errormsg)
          SU_DebugTools::LogAll("ShiftDeck($sql) errormsg=".$common->errormsg,'bj.log');
         assert(empty($common->errormsg));

         //SU_DebugTools::LogAll('ShiftDeck sql='.$sql."\n res=".var_export($res, true),'bj.log');

         $_SESSION["dbId"] = $oldDbId;
         return $res[0];
      }

      static public function getGame($id_bj_game)
      {
         $oldDbId = $_SESSION["dbId"];
         $common = DS_Common::CreateForCurrentDB(0);
     		 if (!empty($common->errormsg)) {
           SU_DebugTools::LogAll(',errormsg='.$common->errormsg,'bj.log');
           exit;
		     }

         $sql = "SELECT * FROM ds_bj_game WHERE id_bj_game = $id_bj_game";

         $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
         //echo $common->errormsg;
         if ($common->errormsg)
          SU_DebugTools::LogAll("after getResultArray($sql) errormsg=".$common->errormsg,'bj.log');
         assert(empty($common->errormsg));

         //SU_DebugTools::LogAll('getGame sql='.$sql."\n res=".var_export($res, true),'bj.log');

         $_SESSION["dbId"] = $oldDbId;
         return $res[0];
      }

      static public function getLastUserGame($uid)
      {
         $oldDbId = $_SESSION["dbId"];
         $common = DS_Common::CreateForCurrentDB(0);
     		 if (!empty($common->errormsg)) {
           SU_DebugTools::LogAll(',errormsg='.$common->errormsg,'bj.log');
           exit;
		     }

         $sql = "SELECT id_bj_game, bet, deck, shift_num FROM ds_bj_game WHERE id_user = $uid order by id_bj_game DESC LIMIT 1";

         $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
         //echo $common->errormsg;
         if ($common->errormsg)
          SU_DebugTools::LogAll("after getResultArray($sql) errormsg=".$common->errormsg,'bj.log');
         assert(empty($common->errormsg));

         $_SESSION["dbId"] = $oldDbId;
         return $res[0];
      }

      static public function AddGame($id_user, $bet, $usercards, $dealercards, $deck, $shift_num = -1)
      {
         $oldDbId = $_SESSION["dbId"];
         $common = DS_Common::CreateForCurrentDB(0);
     		 if (!empty($common->errormsg)) {
           SU_DebugTools::LogAll(',errormsg='.$common->errormsg,'bj.log');
           exit;
		     }

         $sql = "insert into ds_bj_game(id_user, game_date, bet, winsum, cardlist1_plr, winsum1,  cardlist_dlr, deck,shift_num ) ";
         $sql .= "values($id_user, now(), $bet,".(-1*$bet).", '$usercards',".(-1*$bet).", '$dealercards', '$deck',$shift_num)";

        $res = $common->executeQuery($sql);
         //echo $common->errormsg;
        if ($common->errormsg)
          SU_DebugTools::LogAll("after getResultArray($sql) errormsg=".$common->errormsg,'bj.log');
        assert(empty($common->errormsg));

        $sql = "SELECT id_bj_game from ds_bj_game where id_user=$id_user order by id_bj_game DESC LIMIT 1";
        $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
        assert(empty($common->errormsg));
        $_SESSION["dbId"] = $oldDbId;
        return $res[0]['id_bj_game'];
      }

      static public function CardSplit($bj_idgame, $usercards, $bet)
      {
         $oldDbId = $_SESSION["dbId"];
         $common = DS_Common::CreateForCurrentDB(0);
     		 if (!empty($common->errormsg)) {
           SU_DebugTools::LogAll(',errormsg='.$common->errormsg,'bj.log');
           exit;
		     }

         $sql = "UPDATE ds_bj_game SET cardlist1_plr={$usercards[0]},
         cardlist2_plr = {$usercards[1]}, winsum2 = winsum2-$bet, winsum=winsum-$bet WHERE id_bj_game=$bj_idgame";

        $res = $common->executeQuery($sql);
        assert(empty($common->errormsg));
        $_SESSION["dbId"] = $oldDbId;

      }

      static public function updateCards($bj_idgame, $crd_str, $field)
      {
         $oldDbId = $_SESSION["dbId"];
         $common = DS_Common::CreateForCurrentDB(0);
     		 if (!empty($common->errormsg)) {
           SU_DebugTools::LogAll(',errormsg='.$common->errormsg,'bj.log');
           exit;
		     }

         $sql = "UPDATE ds_bj_game SET $field = '$crd_str' WHERE id_bj_game=$bj_idgame";

        $res = $common->executeQuery($sql);
         //echo $common->errormsg;
        if ($common->errormsg)
          SU_DebugTools::LogAll("after getResultArray($sql) errormsg=".$common->errormsg,'bj.log');
        assert(empty($common->errormsg));
        $_SESSION["dbId"] = $oldDbId;
      }

      static public function getGlobalStat($date1, $date2, $pg, $order='id_bj_game', $dir='DESC', $uname='')
      {
        $oldDbId = $_SESSION["dbId"];
        $common = DS_Common::CreateForCurrentDB(0);
        if ($uname)
        {
          $namefilter = "and u.username='$uname'";
        }

        $offset = 20;
        $LIMIT = '';
        if ($pg)
        {
          $LIMIT  = 'LIMIT '.$offset.' OFFSET '.($pg-1)*$offset;
        }

        $sql  = "SELECT bj.id_user, bj.bet, bj.winsum, bj.game_date, u.username FROM ds_bj_game bj INNER JOIN  ds_user u on bj.id_user=u.id_user where game_date >= to_timestamp('$date1','YYYY-MM-DD') and
        game_date  <= to_timestamp('$date2','YYYY-MM-DD HH24:MI:SS') {$namefilter} ORDER BY {$order} {$dir} $LIMIT";
        //echo $sql;
        $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
        echo $common->errormsg;
        assert(empty($common->errormsg));
        $_SESSION["dbId"] = $oldDbId;
        return $res;
      }

      static public function getGameCnt($date1, $date2, $uname='')
      {
        $oldDbId = $_SESSION["dbId"];
        $common = DS_Common::CreateForCurrentDB(0);

        if ($uname)
        {
          $namefilter = "and u.username='$uname'";
        }
        $Stat = array();
        $sql  = "SELECT count(id_bj_game) as cnt, min(game_date) as mindate, max(game_date) as maxdate FROM ds_bj_game bj INNER JOIN
        ds_user u on bj.id_user=u.id_user where game_date >= to_timestamp('$date1','YYYY-MM-DD') and
        game_date  <= to_timestamp('$date2','YYYY-MM-DD HH24:MI:SS') AND result1 <> -2 {$namefilter} ";
        //echo $sql;
        $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
        assert(empty($common->errormsg));
        $Stat['totalcnt'] = $res[0]['cnt'];
        $Stat['mindate'] = $res[0]['mindate'];
        $Stat['maxdate'] = $res[0]['maxdate'];

        $_SESSION["dbId"] = $oldDbId;
        return $Stat;
      }


      static public function getAgregatedStat($date1, $date2,  $uname='')
      {
        $oldDbId = $_SESSION["dbId"];
        $common = DS_Common::CreateForCurrentDB(0);
        if ($uname)
        {
          $namefilter = "and u.username='$uname'";
        }
        $Stat = array();

        //============================
        //посчитаем общее число игр
        //=============================
        $Stat = self::getGameCnt($date1, $date2, $uname);

        //==========================================
        //посчитаем число выигрышей и сумму
        //==========================================
        $sql  = "SELECT sum(winsum) as ws, count(id_bj_game) as cnt FROM ds_bj_game bj INNER JOIN
        ds_user u on bj.id_user=u.id_user where winsum>0  AND game_date >= to_timestamp('$date1','YYYY-MM-DD') and game_date  <= to_timestamp('$date2','YYYY-MM-DD HH24:MI:SS')  AND result1 <> -2 {$namefilter}";
        //echo $sql;
        $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
        echo $common->errormsg;
        assert(empty($common->errormsg));

        $Stat['totalwin'] = $res[0]['ws'];
        $Stat['cntwin']  =  $res[0]['cnt'];
        //посчитаем число проигрышей и сумму
        $sql  = "SELECT sum(winsum) as ws, count(id_bj_game) as cnt FROM ds_bj_game bj INNER JOIN
        ds_user u on bj.id_user=u.id_user where winsum<0 AND game_date >= to_timestamp('$date1','YYYY-MM-DD') and game_date  <= to_timestamp('$date2','YYYY-MM-DD HH24:MI:SS')  AND result1 <> -2 {$namefilter}";

        $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
        assert(empty($common->errormsg));
        $Stat['totalloss'] = abs($res[0]['ws']);
        $Stat['cntloss'] = $res[0]['cnt'];

        //посчитаем число ничьих
        $Stat['cntdraw'] = $Stat['totalcnt'] - $Stat['cntwin'] - $Stat['cntloss'];
        $Stat['balance'] = $Stat['totalloss'] - $Stat['totalwin'];

        //посчитаем средний размер ставки
        $sql  = "SELECT sum(bet) as bs FROM ds_bj_game bj INNER JOIN
        ds_user u on bj.id_user=u.id_user where game_date >= to_timestamp('$date1','YYYY-MM-DD') and game_date  <= to_timestamp('$date2','YYYY-MM-DD HH24:MI:SS')  AND result1 <> -2 {$namefilter}";
        //echo $sql;
        $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
        assert(empty($common->errormsg));
        $Stat['betstr']  = '0';

        if ($Stat['totalcnt'] > 0)
          $Stat['betstr'] =  sprintf("%0.1f", $res[0]['bs'] / $Stat['totalcnt']);

        //посчитаем, сколько сыграло игроков
        if (!$uname)
        {
          $sql  = "SELECT count(DISTINCT id_user) as cnt FROM ds_bj_game bj where game_date >= to_timestamp('$date1','YYYY-MM-DD') and game_date  <= to_timestamp('$date2','YYYY-MM-DD HH24:MI:SS') AND result1 <> -2 ";
          //echo $sql;
          $res = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
          assert(empty($common->errormsg));
          $Stat['playerscnt'] = $res[0]['cnt'];
        }

        $_SESSION["dbId"] = $oldDbId;
        return $Stat;
      }

      static public function UpdateGame($id, $bet, $ucrd_str, $dlrcrd_str)
      {
        $oldDbId = $_SESSION["dbId"];
        $common = DS_Common::CreateForCurrentDB(0);
        if ($common->errormsg)
          SU_DebugTools::LogAll("UpdateGame ($sql) errormsg=".$common->errormsg,'bj.log');
        assert(empty($common->errormsg));

        $sql = "UPDATE ds_bj_game SET bet={$bet}, winsum=".(-1*$bet).",cardlist1_plr=
        '$ucrd_str',cardlist_dlr='$dlrcrd_str'  WHERE id_bj_game={$id}";
        $res = $common->executeQuery($sql);
        if ($common->errormsg)
          SU_DebugTools::LogAll("UpdateGame ($sql) errormsg=".$common->errormsg,'bj.log');
        assert(empty($common->errormsg));

        $_SESSION["dbId"] = $oldDbId;
        return $Stat;
      }

      static  public function GetCardsAsStr($cardlist)
      {
         $CVStr = array(1=>'2',2=>'3',3=>'4',4=>'5',5=>'6',6=>'7',7=>'8',8=>'9',9=>'10', 10=>'J',
           11=>'D', 12=>'Q', 13=>'K', 14=>'A');

         $CSstr = array(0=>'c',1=>'k',2=>'p',3=>'b');

         $str = '';
         foreach ($cardlist as $c)
         {

            if ($c<=44) //44
            {
              $v = ceil($c/4);
            }
            else
              $v = ceil($c/4)+1;

            $suite = $c % 4;
            $str .= $CVStr[$v].$CSstr[$suite].',';
         }
         $str = substr($str, 0, -1);
         return $str;
      }
   }
?>