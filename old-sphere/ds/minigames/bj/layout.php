<?php
  require_once "../../Main.php";
  require_once('BJ.class.php');
  SU_GameSessionWork::SessionContinue();


  $cfg = array();
  $cfg['minBet'] = 10;
  $cfg['maxBet'] = 1000;
  $cfg['canSplit'] = 1;
  $cfg['maxWinSum'] = 1000000; //максимальная сумма, которую игроки могут выиграть у казино,
                               //после чего игра останавливается

  $cfg['adminsList'] = 'divanov@destinysphere.com,maxim@destinysphere.com,dmitri@destinysphere.com';

 // SU_DebugTools::LogAll("POST=".var_export($_REQUEST, true),'bj.log');
  $uid = $_SESSION['uid'];
  if (!$uid)
  {
    sendInfo('',0, '',0,0,666,$user['sc_amount'],'Сессия истекла. Перелогиньтесь в "Сферу Судьбы".');
    exit;
  }
 
  if (array_key_exists('cmd', $_REQUEST))
  {
    $cmd = $_REQUEST['cmd'];
  }

  $user = BJ::getUserInfo($uid);

  switch($cmd)
  {
     case 'init':
     case 'again':
     {
        if ($cmd=='again')
        {
          if (($_SESSION['hand']==2) && ($_SESSION['split']==1))
          {
            NextCard();
            break;
          }
        } 
        Init($cmd);
        break;
     }
     case 'start':
     {
        Start();
        break;
     }
     case 'accept':
     {
        Accept();
        break;
     }
     case 'nextcard':
     {
        NextCard();
        break;
     }
     case 'enough':
     {
        Enough();
        break;
     }
     case 'split':
     {
        CardsSplit();
        break;
     }
     case 'retry':
     {
       $last_game = BJ::getLastUserGame($_SESSION['uid']);
       if ($last_game)
       {
          Retry($last_game);
       }
       else
       {
         sendInfo('',0, '',0,0,666,$user['sc_amount'],'АХТУНГ!');
         exit;
       }
     }
     case 'shift':
     {
       ShiftDeck();
       exit;
     }
  }
  
  function ShiftDeck()
  {
    global $cfg, $user;
    $shift_num = intval($_REQUEST['num']);
    
    $last_game = BJ::getLastUserGame($_SESSION['uid']);
    if (!$last_game || ($last_game['bet']>0))
    {
      sendInfo('',0, '',0,0,666,$user['sc_amount'],'Читеры-срезчики не пройдут!');
      exit;
    }
   // SU_DebugTools::LogAll('ShiftDeck last_game'.var_export($last_game, true),'bj.log',0);    
    //SU_DebugTools::LogAll('ShiftDeck shift_num='.$shift_num,'bj.log',0);

    $deck = explode(',', $last_game['deck']);
    if (($shift_num > 0) and ($shift_num <= 311) and ($last_game['shift_num']==-1))
    {
      $begin_deck = array_splice($deck, 0, $shift_num); //Удаляем карты от 0 до срезки и возвращаем их
      $deck = array_merge($deck, $begin_deck);
      $deck_str   = implode(',',$deck);
      BJ::ShiftDeck($_SESSION['bj_idgame'], $deck_str, $shift_num);
    }
    Retry($last_game);
    //echo "OK";
  }
  
  function GenerateNewDeck()
  {
    global $cfg, $user;

    $last_game = BJ::getLastUserGame($_SESSION['uid']);
    if ($last_game && ($last_game['bet']==0))
    {
       $_SESSION['bj_idgame'] = $last_game['id_bj_game'];
       $deck = explode(',',$last_game['deck']);
       return md5(BJ::GetCardsAsStr($deck));
    }
    
    $deck = initCards();
    $deck_str   = implode(',',$deck);

    $bj_idgame = $_SESSION['bj_idgame'] = BJ::AddGame($user['id_user'], 0, '', '', $deck_str);
    $deckAsStr  = BJ::GetCardsAsStr($deck);
    $md5s = md5($deckAsStr);
    //SU_DebugTools::LogAll("GenerateNewDeck deckAsStr=$deckAsStr\n\n md5=$md5s",'bj.log');

    return $md5s;
  }
  
  function CardsSplit()
  {
    global $cfg, $user;

    $bj_idgame = $_SESSION['bj_idgame'];
    if (array_key_exists('hand', $_SESSION))
      $hand = $_SESSION['hand'];
    else
      $hand = 1;

    if (!$bj_idgame)
    {
      sendInfo('',0, '',0,0,666,$user['sc_amount'],'Сессия истекла. Перелогиньтесь в "Сферу Судьбы".');
      exit;
    }
    $game = BJ::getGame($bj_idgame);

    $usercards = explode(',', $game['cardlist1_plr']);
    $dealercards = explode(',', $game['cardlist_dlr']);

    $v1 = ceil($usercards[0]/4);

    if ($v1 <=9)
      $v1++;
    else if (($v1 >9) and ($v1 <=12 ))
      $v1 = 10;
    else if ($v1==13)
      $v1 = 11;
    
    $v2 = $v1;

    $split = 0;

    if (($v1!=$v2) || (count($usercards)>2) || ($game["result{$hand}"] >=0))
    {
       sendInfo('',0,'',0,0,666,$user['sc_amount'],'Читеры-сплиттеры не пройдут!');
       exit;
    }
    //SU_DebugTools::LogAll('game='.var_export($game, true)."\n user=".var_export($user, true), 'bj.log'); 
    if ( $user['sc_amount'] < $game['bet'])
    {
       sendInfo('',0,'',0,0,666,$user['sc_amount'],'Недостаточно денег на счёте для сплита');
       exit;
    }

    //сплитуем
    BJ::CardSplit($bj_idgame, $usercards, $game['bet']);

    $sc_amount = BJ::changeSilverAcc($game['id_user'], -1*$game['bet']);

    $dlrScore = calcScore($dealercards);
    sendInfo($usercards[0], $game['bet'], $game['cardlist_dlr'], $v1, $dlrScore, 1, $sc_amount, '', -1, 0, $usercards[1], $v2);
    $_SESSION['split'] = 1;
    $_SESSION['hand'] = 1;
  }
  
  function Enough()
  {
    global $cfg, $user;

    $bj_idgame = $_SESSION['bj_idgame'];
    if (array_key_exists('hand', $_SESSION))
      $hand = $_SESSION['hand'];
    else
      $hand = 1;

    if (!$bj_idgame)
    {
      sendInfo('',0, '',0,0,666,$user['sc_amount'],'Сессия истекла. Перелогиньтесь в "Сферу Судьбы".');
      exit;
    }
    $game = BJ::getGame($bj_idgame);

    if ( $game["result{$hand}"] >=0 )
    {
       sendInfo('',0, '',0,0,666,$user['sc_amount'],'Читеры не пройдут!');
       exit;
    }

/*
    $n = rand(0, 15);

    if ( $n<10 )
    {
       sendInfo('','',0,0,'',$user['sc_amount'],'');
       exit;
    }
*/

    EndGame($game, $hand, $user['sc_amount']);
  }

  function EndGame($game, $hand, $sc_amount) 
  {
    $bj_idgame = $_SESSION['bj_idgame'];
    //SU_DebugTools::LogAll("EndGame hand=$hand, game=".var_export($game, true),'bj.log');
    if (($_SESSION['split'] && $hand==2) || (!$_SESSION['split']))
    {
      $userCards = explode(',',$game["cardlist{$hand}_plr"]);
      $dlrCards = explode(',',$game["cardlist_dlr"]);
      
      $offset = count($userCards)+count($dlrCards);
      if ($hand==2)
        $offset += count(explode(',',$game["cardlist1_plr"])); //учитываем карты с первой "руки"
    
      if (($game['result1']==-1) || (($game['result2']==-1) && $_SESSION['split']))
      {
        list($dlrCards, $dlrScore) = getDealerCards(explode(',',$game['deck']), $dlrCards, $offset);
        $game['cardlist_dlr'] =  $dlrcrd_str = implode(',',$dlrCards);
        BJ::updateCards($bj_idgame, $dlrcrd_str, 'cardlist_dlr');
      }

      list($result1, $msg1, $userScore, $sc_amount) = GetGameResult(1, $game, $dlrScore, $sc_amount);
      $splitScore = 0;
      if ($_SESSION['split'])
      {
        list($result2, $msg2, $splitScore, $sc_amount) = GetGameResult(2, $game, $dlrScore, $sc_amount);
        $result = $result2;
        $msg = "$msg1<br>$msg2";
      }
      else
      {
        $msg  = $msg1;
        $result = $result1;
      }
      $_SESSION['split'] = 0;
      $_SESSION['hand'] = 1;
      $appstatus = 666;

      //Сгенерим тэг <coloda>
      $n = 20;
      //Получим первые $n карт после срезки
      //SU_DebugTools::LogAll('ENDGAME game='.var_export($game, true),'bj.log');
      list($coloda, $colodafull, $md5) = GetDeckAsStr($game);

      sendInfo($game['cardlist1_plr'], $game['bet'], $game['cardlist_dlr'], $userScore, $dlrScore, $appstatus, $sc_amount, $msg,  $result, 0, $game['cardlist2_plr'], $splitScore, '', $md5, $coloda, $colodafull);

       //function sendInfo($ucrd_str, $bet, $dlrcrd_str, $userScore, $dlrScore, $appstatus, $sc_amount, //$msg='', $result=-1, $split=0, $splitcrd_str='', $splitScore='0', $initStr='', $md5='')

    }
    else if ($_SESSION['split'] && ($_SESSION['hand']==1))
    {
      $_SESSION['hand']++;
      NextCard();
      exit;
    }
  }

  function GetDeckAsStr($game)
  {
      
      $n = 20;
      $deck = explode(',', $game['deck']);
      $first2 = implode(',',array_slice($deck, 0, $n));
      //Получим первые 20 до срезки
      if ($game['shift_num']>0)
      {
         $shifted = array_splice($deck, count($deck)-$game['shift_num'], $game['shift_num']);
         $original_deck = array_merge($shifted, $deck);
         $first1 = implode(',',array_slice($original_deck, 0, $n));
      }
      else
      {
         $original_deck = $deck;
         $first1 = $first2;
      }
      $cardsAsStr  = BJ::GetCardsAsStr($original_deck);
      $md5 = md5($cardsAsStr);

      $coloda = "<coloda>
<![CDATA[{$first1}/{$first2}]]>
</coloda>";

      $colodafull = "
<colodafull>
<![CDATA[{$cardsAsStr}]]>
</colodafull>";
     return array($coloda, $colodafull, $md5);
  }

  function GetGameResult($hand, $game, $dlrScore, $sc_amount)
  {
    $bj_idgame = $_SESSION['bj_idgame'];

    $userCards = explode(',', $game["cardlist{$hand}_plr"]);
    $dlrCards  = explode(',', $game['cardlist_dlr']);

    $userScore  = calcScore($userCards);
    $result = $game["result{$hand}"];
    $calculated = false;
    if ($result == - 1)
    {
       $result = CalcGameResult($userScore, $dlrScore, 1);
    }
    else
    {
      $calculated = true;
      $userScore = $game["score{$hand}"];
    }

    if ($_SESSION['split'])
      $hstr = " на {$hand}-ой руке";
    else
      $hstr = "";

    if ($result==2) //юзер выиграл - ему возвращается его двойная ставка
    {
      $delta = $game['bet']*2;
      $msg = "Вы выиграли{$hstr}. Вам выплачивается ".$delta." серебра";
    }
    else if ($result==0) //ничья - ему возвращается его ставка
    {
      $delta = $game['bet'];
      $msg = "Ничья{$hstr}. Вам возвращается ваша ставка";
    }
    else
    {
      $delta = 0;
      $msg = "Вы проиграли{$hstr}.";
    }

    if (!$calculated)
    {
      if ($result != 1) //Если проигрыш - то деньги и так уже списаны перед игрой
         $sc_amount = BJ::changeSilverAcc($game['id_user'], $delta);

      BJ::setGameResult($bj_idgame, $result,  $userScore, $dlrScore, $hand, $delta);
      $appstatus = 666;
    }

    return array($result, $msg, $userScore, $sc_amount);
  }


  function CalcGameResult($userScore, $dlrScore, $mode)
  {
     if ($userScore>21)
       $result = 1; //юзер проиграл
     else
     {
       if ($dlrScore>21)
         $result = 2; //юзер выиграл
       else
       {
          if ($mode) //из EndGame
          {
            if ($userScore==$dlrScore)
            {
              $result = 0; //ничья
            }
            else
            {
              if ($userScore < $dlrScore)
                $result = 1; //юзер проиграл
              else
               $result = 2; //юзер выиграл
            }
          }
          else //из NextCard
          {
            if ($userScore==21) //набрали ровно 21 -  автоматически выигрыш
              $result = 2;
            else
              $result = -1;
          }
       }

     }
     return $result;
  }

  function getDealerCards($deck, $dlrCards, $offset)
  {
    $cur   = $offset;

    while(1)
    {
      $score = calcScore($dlrCards);
      if ($score>=17)
      {
        break;
      }
      $dlrCards[] = $deck[$cur++];
    }

    return array($dlrCards, $score);
  }

  function NextCard()
  {
    global $cfg, $user;
    $msg = '';
    $bj_idgame = $_SESSION['bj_idgame'];
    if (!$bj_idgame)
    {
      sendInfo('',0, '',0,0,666,$user['sc_amount'],'Сессия истекла. Перелогиньтесь в "Сферу Судьбы".');
      exit;
    }

    $game = BJ::getGame($bj_idgame);

    if (array_key_exists('hand', $_SESSION))
      $hand = $_SESSION['hand'];
    else
      $hand = 1;

    if ( $game["result{$hand}"] >=0 )
    {
       sendInfo('',0, '',0,0,666,$user['sc_amount'],'Читеры не пройдут!');
       exit;
    }

    $userCards = explode(',', $game["cardlist{$hand}_plr"]);
    $dlrCards  = explode(',', $game['cardlist_dlr']);

    $offset = count($userCards)+count($dlrCards);
    if ($_SESSION['split'] && $hand == 1)
    {
      $offset += 1; //Учитываем 1 отсплитованную карту
    }
    else if ($hand == 2)
      $offset += count(explode(',',$game["cardlist1_plr"])); //учитываем карты с первой "руки"

    //SU_DebugTools::LogAll("NextCard, offset=$offset", 'bj.log',0);

    $userCards  = getCards(explode(',',$game['deck']), $userCards, 1, $offset);
    $ucrd_str   = implode(',', $userCards);
    //SU_DebugTools::LogAll('2 userCards='.var_export($userCards,true).',ucrd_str='.$ucrd_str,'bj.log');
    $userScore  = calcScore($userCards);
    $dlrScore   = calcScore($dlrCards);
    BJ::updateCards($bj_idgame, $ucrd_str, "cardlist{$hand}_plr");

    $result = CalcGameResult($userScore, $dlrScore, 0);
    $sc_amount = $user['sc_amount'];
    if ($result == 1) //Перебор
    {
       $msg = 'Вы проиграли';
       BJ::setGameResult($bj_idgame, $result,  $userScore, $dlrScore, $hand, 0);
       $game["score{$hand}"] = $userScore;
       $game["result{$hand}"] = $result;
       $appstatus = 666;
       if (($_SESSION['split']==1) && ($_SESSION['hand']==2))
       {
         $game["cardlist2_plr"] = $ucrd_str;
         EndGame($game, $_SESSION['hand'], $sc_amount);
         exit;
       }
       else
       {
         $_SESSION['hand']++;
       }
    }
    else if ($result == 2) //21 - выиграли
    {
       /*Выиграть то выиграли, но даём возможность дилеру сдать карты */

       $game["score{$hand}"] = $userScore;
       $game["cardlist{$hand}_plr"] = $ucrd_str;
       
       /*
       $msg = 'Вы выиграли. Вам выплачивается '.(2*$game['bet']).' серебра';
       BJ::setGameResult($bj_idgame, $result,  $userScore, $dlrScore, $hand, 2*$game['bet']);

       $sc_amount = BJ::changeSilverAcc($user['id_user'], 2*$game['bet']);
       $appstatus = 666;
       */
       $appstatus = 1;

       if ((@$_SESSION['split']==1) && ($_SESSION['hand']==2))
       {
         $game["cardlist2_plr"] = $ucrd_str;
         EndGame($game, $_SESSION['hand'], $sc_amount);
         exit;
       }
       else if (((@$_SESSION['split']==1) && ($_SESSION['hand']==1)) || (!$_SESSION['split']))
       {
         //$_SESSION['hand']++;
         //NextCard();
         $userScore1 = calcScore(explode(',', $game['cardlist1_plr']));
         $u2 = calcScore(explode(',', $game['cardlist2_plr']));

         sendInfo($game['cardlist1_plr'],$game['bet'], $game['cardlist_dlr'], $userScore1, $dlrScore, $appstatus,  $sc_amount, $msg, $result, 0, $game['cardlist2_plr'], $u2);
         exit;
       }
       else if (@$_SESSION['split']==0)
       {
         EndGame($game, $_SESSION['hand'], $sc_amount);
         exit;
       }
    }
    else
      $appstatus = 1;

    if ($hand==1)
    {
      if (($appstatus==666) && !$_SESSION['split'])
      {
         list($coloda, $colodafull, $md5) = GetDeckAsStr($game);
      }
      sendInfo($ucrd_str, $game['bet'], $game['cardlist_dlr'], $userScore, $dlrScore, $appstatus, $sc_amount, $msg, $result, 0, '', 0,'',$md5, $coloda, $colodafull);
    }
    else
    {
      $userScore1 = calcScore(explode(',', $game['cardlist1_plr']));
      sendInfo($game['cardlist1_plr'],$game['bet'], $game['cardlist_dlr'], $userScore1, $dlrScore, $appstatus, $sc_amount, $msg, $result, 0, $ucrd_str, $userScore);
    }
  }

  function sendInfo($ucrd_str, $bet, $dlrcrd_str, $userScore, $dlrScore, $appstatus, $sc_amount, $msg='', $result=-1, $split=0, $splitcrd_str='', $splitScore='0', $initStr='', $md5='', $coloda='', $colodafull='')
  {
    global $cfg, $user;
    $md5str = '';
    if ($md5)
    {
       $md5str =  'md5="'.$md5.'"';
    }
    
    $str =  'jack=<?xml version="1.0" encoding="utf-8"?>
<main mynick="'.$user['username'].'" partnernick="Destiny Sphere" allowsplit="'.$cfg['canSplit'].'" credit="'.$sc_amount.'" min="'.$cfg['minBet'].'" max="'.$cfg['maxBet'].'" appstatus="'.$appstatus.'"  result="'.$result.'" bet="'.$bet.'"'.$md5str.'>';
    $str .= '<mycard ids="'.$ucrd_str.'" score="'.$userScore.'"  split="'.$split.'"/>';
    $str .= '<partnercard ids="'.$dlrcrd_str.'" score="'.$dlrScore.'"/>';
    if ($splitcrd_str)
    {
      $str .= '<mysplit ids="'.$splitcrd_str.'" score="'.$splitScore.'"/>';
    }
    
    $str .= $initStr;
    if ($msg)
    {
      $str .= "<msg><![CDATA[$msg]]></msg>";
    }

    if ($coloda)
    {
      $str .= $coloda;
    }

    if ($colodafull)
    {
      $str .= $colodafull;
    }
    $str .= '</main>';
    $str = iconv("cp1251", "UTF-8", $str);
    echo $str;
    //SU_DebugTools::LogAll($str,'bj.log');
  }

  function Accept()
  {
    global $cfg, $user;
    $bet = intval($_REQUEST['set']);
    if ($bet < $cfg['minBet'])
    {
       sendInfo('',0, '',0,0,666,$user['sc_amount'],'Ставка меньше минимальной');
       exit;
    }
    if ($bet > $cfg['maxBet'])
    {
       sendInfo('',0, '',0,0,666,$user['sc_amount'],'Ставка больше максимальной');
       exit;
    }
    else if ( $user['sc_amount'] < $bet)
    {
       sendInfo('',0, '',0,0,9,$user['sc_amount'],'Недостаточно денег на счёте. Вы можете купить золотые кредиты в биллинге, после чего обменять их в магазине на серебро.');
       exit;
    }
    $res = BJ::checkTotalWinsum();
    if ($res['stopped']=='t')
    {
      sendInfo('',0, '',0,0,666,$user['sc_amount'],'Извините, игра временно приостановлена');
      exit;
    }

    //Не допускаем ситуации, когда мы выиграли у игроков 5 млн, а тут
    //нашлась дырка, и мы ещё дадим возможность вытащить у нас 6 млн (5+1),
    //пока игра автоматически не закроется. Для этого сумму нашего выигрыша - 
    //она же сумма потерь игроков (losssum) надо периодически занулять.
    //На величину lossum уменьшается и winsum при этом
    if ($res['losssum'] > $cfg['maxWinSum'])
    {
       BJ::updateTotalWinsum('losssum', 0);
       $dt = $res['winsum'] - $res['losssum'];
       if ($dt > 0)
         BJ::updateTotalWinsum('winsum', $dt);
       else
         BJ::updateTotalWinsum('winsum', 0);
    }

    //SU_DebugTools::LogAll(var_export($res['letter_send'], true), 'bj.log',0);

    if ( ($res['winsum'] - $res['losssum']) > $cfg['maxWinSum'])
    {
       if ($res['letter_send']=='f')
       {
				 $eol = "\r\n";
 				 $headers = 'From: noreply <noreply@destinysphere.com>'.$eol;
				 $headers .= "Message-ID: <".time()." TheSystem@".$_SERVER['SERVER_NAME'].">".$eol;
				 $headers .= "X-Mailer: PHP v".phpversion().$eol;
				 $headers .= "Content-Type: text/plain; charset=windows-1251".$eol;

         $msg = 'Игра в БлэкДжек приостановлена, т.к. суммарный выигрыш пользователей составил '.($res['winsum'] - $res['losssum']).$eol; 
         $msg .= 'Для возобновления игры обнулите значения в таблице ds_bj_winsum.';
         mail($cfg['adminsList'],'BJ STOP GAME', $msg, $headers);
         BJ::setLetterSend('true');
       }
       sendInfo('',0, '',0,0,666,$user['sc_amount'],'Извините, игра временно приостановлена');
       exit;
    }
      
    $_SESSION['hand'] = 1;
    $_SESSION['split'] = 0;

/*
    if (array_key_exists('hand', $_SESSION))
      $hand = $_SESSION['hand'];
    else
      $hand = 1;
*/


    $hand = 1;
    
    $game = BJ::getLastUserGame($_SESSION['uid']);
    //$last_game = 0;
    if (!$game || ($game['bet']>0)) //Вообще-то это нештатный вариант
    {
      $deck = initCards();
    }
    else
    {
      $deck = explode(',', $game['deck']);
      $_SESSION['bj_idgame'] = $game['id_bj_game'];
    }

    $usercards = getCards($deck, array(), 2, 0);
    $dealercards = getCards($deck, array(), 1, 2);

    $v1 = ceil($usercards[0]/4);
    $v2 = ceil($usercards[1]/4);
    $split = 0;
    if ($v1==$v2)
       $split = 1;

    $userScore = calcScore($usercards);
    $dlrScore  = calcScore($dealercards);


    $deck_str   = implode(',',$deck);
    $ucrd_str   = implode(',',$usercards);
    $dlrcrd_str = implode(',',$dealercards);
    
    if ($game)
    {
        BJ::UpdateGame($game['id_bj_game'], $bet, $ucrd_str, $dlrcrd_str);
    }
    else //нештатный вариант
    {
       $bj_idgame = $_SESSION['bj_idgame'] = BJ::AddGame($user['id_user'], $bet, $ucrd_str, $dlrcrd_str, $deck_str);
    }
    $sc_amount = BJ::changeSilverAcc($user['id_user'], -1*$bet);

    $result = CalcGameResult($userScore, $dlrScore, 0);
    $msg = '';
    if ($result == 2) //21 сходу
    {
       /*
       $msg = 'Вы выиграли. Вам выплачивается '. 2*$bet.' серебра';
       BJ::setGameResult($bj_idgame, $result,  $userScore, $dlrScore, $hand, 2*$bet);
       $sc_amount = BJ::changeSilverAcc($user['id_user'], 2*$bet);

       $appstatus = 666;
       */
       $appstatus = 1;
    }
    else
       $appstatus = 1;

/*
    $n = rand(0, 15);

    if ( $n<16 )
    {
       sendInfo('','',0,0,'',$user['sc_amount'],'');
       exit;
    }
*/

    sendInfo($ucrd_str, $bet, $dlrcrd_str, $userScore, $dlrScore, $appstatus, $sc_amount, $msg, $result, $split);
  }


  function calcScore($cards)
  {
    $sum = 0;
    $wasAce = 0;
    $aceFixed = 0;
    foreach($cards as $c)
    {
      $v = ceil($c/4);
      if ($v<=9)
      {
        $sum += $v + 1;
      }
      else if (($v >9) and ($v <=12 ))
      {
        $sum += 10;
      }
      else if ($v==13)
      {
        $sum += 11;
        if ($sum > 21)
        {
          $sum -= 10;
          $aceFixed++;
        }
        $wasAce++;
      }
    }
    if (($sum>21) && ($aceFixed<$wasAce))
    {
       $sum -= 10; //Если перебор и был туз, считаем за 1
    }

    return $sum;
  }

  function Start()
  {
    global $cfg, $user;
    $res = BJ::checkTotalWinsum();
    if ($res['stopped']=='t')
    {
      sendInfo('',0, '',0,0,666,$user['sc_amount'],'Извините, игра временно приостановлена');
      exit;
    }
    $game = BJ::getLastUserGame($_SESSION['uid']);
    //$last_game = 0;
    if ($game)
    {
       $_SESSION['bj_idgame'] = $game['id_bj_game'];
       if (($game['bet'] >0) && (($game['result1'] == -1) || (($game['result2']==-1) && ($game['cardlist2_plr']))) )
       {
         Retry($game);
       }
    }
    $_SESSION['split'] = 0;
    $_SESSION['hand'] = 1;

    sendInfo('', 0, '', 0, 0, 0, $user['sc_amount'], '');
  }

  function Retry($game)
  {
     global $cfg, $user;
     $usercards = explode(',', $game['cardlist1_plr']);
     $userScore1 = calcScore($usercards);
     $dlrScore   = calcScore(explode(',', $game['cardlist_dlr']));
     $appstatus = $game['bet']>0?1:0;

     if (!$game['cardlist2_plr']) //Без сплита
     {
       $_SESSION['split'] = 0;
       $_SESSION['hand'] = 1;

       $cansplit = 0;
       if (count($usercards) == 2) //Если только две карты, то может быть это будущий сплит
       {
         $v1 = ceil($usercards[0]/4);
         $v2 = ceil($usercards[1]/4);
         if ($v1==$v2)
            $cansplit = 1;
       }

       sendInfo($game['cardlist1_plr'], $game['bet'], $game['cardlist_dlr'], $userScore1, $dlrScore, $appstatus,     $user['sc_amount'], '', -1, $cansplit);
       exit;
     }
     else
     {
       $splitcards = explode(',', $game['cardlist2_plr']);
       $_SESSION['split'] = 1;
       if (($game['result1'] == -1) && (count($splitcards)==1))
         $_SESSION['hand'] = 1;
       else
         $_SESSION['hand'] = 2;

       $userScore2 = calcScore($splitcards);
       sendInfo($game['cardlist1_plr'], $game['bet'],  $game['cardlist_dlr'], $userScore1, $dlrScore, $appstatus,  $user['sc_amount'], '', -1, 0, $game['cardlist2_plr'], $userScore2);
       exit;
     }
  }
  
  function Init($cmd)
  {
   global $cfg, $user;

   if ($cmd=='init')
      $appstatus = 100;
   else if ($cmd=='again')
      $appstatus = 0;

   $initStr = '';
   if ($appstatus==100)
   {
     $initStr = '
<buttons>
	<btn1 desc="СТАРТ"/>
	<btn2 desc="ПОМОЩЬ"/>
	<btn3 desc="ПРИНЯТЬ"/>
	<btn4 desc="СНЯТЬ СТАВКУ"/>
	<btn5 desc="МАКС.СТАВКА"/>
	<btn6 desc="ЕЩЕ КАРТУ"/>
	<btn7 desc="ДОСТАТОЧНО"/>
	<btn8 desc="ВЫХОД"/>
	<btn9 desc="ЗАКРЫТЬ"/>
	<btn10 desc="ПРОДОЛЖИТЬ"/>
	<btn11 desc="СПЛИТ"/>
  <btn12 desc="ПОПОЛНИТЬ" url="/ds/billing.php?mode=gold"/>
  <btn20 desc="СРЕЗАТЬ"/>
  <btn21 desc="MD5"/>
  <btn22 desc="Колода"/>
</buttons>

<titles>
     <txt1 desc="Очки дилера:"/>
     <txt2 desc="Ваша ставка:"/>
     <txt3 desc="Очки игрока:"/>
     <txt4 desc="Ваши кредиты:"/>
     <txt5 desc="Макс.ставка:"/>
     <txt6 desc="Срезать колоду (1..311)"/>
     <txt7 desc="Сервер занят"/>
     <txt8 desc="Код колоды - MD5"/>

</titles>

<hlp>
<![CDATA[<b>Помощь.</b><br/>Игра ведется шестью колодами по 52 карты (это значит, что, теоретически, вам, например, может выпасть 6 двоек пик). ВНИМАНИЕ: игра ведётся на СЕРЕБРО из кабинета. Перед началом каждой игры набор карт полон.<p>В начале игры Вы получаете две карты, одну карту получает Дилер. Вы можете брать еще карты, пока не сочтете нужным остановиться, либо сумма очков не станет больше либо равной двадцати одному.<p>Если Вы превысили 21, Вы проигрываете. Если нет, ход переходит к дилеру, который берет карты, пока сумма очков не станет больше либо равна семнадцати.<br/>Если сумма Ваших очков совпадет с суммой очков дилера, и составит не более двадцати одного, то Вы вернете свою ставку. Вы выиграете, если сумма ваших очков составит больше, чем сумма очков дилера и меньше двадцати одного. <br/>В случае выигрыша, вы получите назад сумму в два раза большую вашей ставки. В остальных случаях Вы проиграете.<p>Стоимость младших карт совпадает с их номиналом, стоимость «картинок» 10, туз стоит 11 или 1 в зависимости от того, как выгоднее играющему.<br/>В случае, если с первой раздачи вам пришло две одинаковых карты, то вы можете сделать сплит - разделить их, и играть независимо с двух "рук". Это будет стоить вам ещё одной ставки, которая снимется автоматически, если вы нажмёте кнопку "Сплит".<br/> В случае, если у вас пропала связь с интернетом, завис компьютер и т.д. - при следующем заходе в игру после нажатия кнопки "Старт" будет возобновлена последняя недоигранная игра.<br/><br/> <b>КОНТРОЛЬ ЧЕСТНОСТИ</b>. Чтобы вы не сомневались, что мы не сдаём специально дилеру хорошие карты, а игроку - плохие, у нас реализован контроль честности через MD5 и срезку. Работает это следующим образом. Перед началом каждой сдачи вы можете получить MD5-сумму колоды(что такое MD5, вы можете <b><a href="http://www.google.ru/search?q=md5%26ie=utf-8%26oe=utf-8%26aq=t"target="_blank">узнать в гугле</a></b>). Затем, при желании, вы можете её срезать, как это делается в настоящих казино, указав номер карты, на которой происходит срезка. <br/>После окончания игры при нажатии на кнопку "Колода" вы увидите начало колоды до срезки, после срезки, а также полный код колоды до срезки. Скопировав его в буфер и поместив в любой независимый сервис вычисления суммы MD5 (например, <b><a href="http://even-odd.ru"  target="_blank">even-odd.ru</a></b> или <b><a href="http://md5-hash-online.waraxe.us/"  target="_blank">md5-hash-online.waraxe.us</a></b> )и сравнив полученный MD5 с тем, который был выдан вам перед игрой, вы сможете убедиться, что вы срезали действительно ту колоду, MD5 от которой вам был первоначально выдан, и тем самым вы сами повлияли на расклад карт.]]>
</hlp>

<hint>
<![CDATA[Для того, чтобы сделать ставку, нажмите на фишки]]>
</hint>
';
   }
   if ( $user['sc_amount'] < $cfg['minBet'])
   {
      sendInfo('',0, '',0,0,9,$user['sc_amount'],'Количество серебра на вашем счёте меньше минимальной ставки. Вы можете купить золотые кредиты в биллинге, после чего обменять их в магазине на серебро.',-1, 0, '', 0, $initStr );
      exit;
   }

   $md5 = GenerateNewDeck();
   sendInfo('', 0, '', 0, 0, $appstatus, $user['sc_amount'], '',-1, 0, '', 0, $initStr, $md5 );
  }


  function getCards($deck, $res, $n, $offset)
  {
    $str = '';
    for($i=$offset; $i< $offset+$n;$i++)
    {
       $res[] = $deck[$i];
       //$str .= "$i={$deck[$i]},";
    }

    return $res;
  }

  function initCards()
  {
    $cards = array();
    for($i=0;$i<=5;$i++)
    {
       for($k=0;$k <= 51;$k++)
       {
         $cards[($i*52)+$k] = $k+1;
       }
    }

    $csz = count($cards);

    for ($k=0;$k<5;$k++)
    {
      for($i=0; $i < $csz; $i++)
      {
        $v = rand(0, $csz - 1);
        $t = $cards[$i];
        $cards[$i] = $cards[$v];
        $cards[$v] = $t;
      }
    }

/*
    $cards[0] = 1;
   $cards[1] = 2;
*/
/*
    $cards[0] = 36;
    $cards[1] = 24;
    $cards[2] = 22;
    $cards[3] = 2;
    $cards[4] = 3;
    $cards[5] = 51;
    $cards[6] = 36;
*/
    return $cards;
  }
?>
