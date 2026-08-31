<?php
  require_once "../../Main.php";
  require_once('BJ.class.php');

  $cards = initCards();
  $str  = BJ::GetCardsAsStr($cards);
  echo $str."<br><br>".var_export($cards, true);
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


    $cards[0] = 40;
    $cards[1] = 1;
    $cards[2] = 2;
    $cards[3] = 3;
    $cards[4] = 41;
    $cards[5] = 45;
    $cards[6] = 50;
    $cards[5] = 52;
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