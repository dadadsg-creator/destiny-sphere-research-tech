<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

require_once $_SERVER['DOCUMENT_ROOT']."/ds/minigames/bj/BJ.class.php";

  echo "<script src='/Admin/js/pager.js'></script>";
  echo "<link href='/Admin/styles/pager.css' rel='stylesheet' type='text/css' />";
echo "<style type='text/css'>
     .tbl  {
           border: 1px solid black;
           border-collapse: collapse;
           color:white
         }
     .tbl td {padding-left:5px;}
</style>";

echo "<b>Статистика по БлэкДжеку</b>";

$date1   = @$_REQUEST['date1'];
$date2   = @$_REQUEST['date2'];
$act     = @$_REQUEST['act'];
if (!$act)
{
  $act = 'global';
}

$plrnick = @$_REQUEST['plrnick'];

if (!$date1)
{
   $date1 = date('Y-m-01');
   //$date1 = '2008-06-01';
}

if (!$date2)
{
   $date2 = date('Y-m-d');
   //$date2 = '2008-06-10';
}

$checked = array();
$act_values = array('global','detail','players','one_plr');
foreach ($act_values as $v)
{
  if ($v==$act)
    $checked[$v] = 'checked';
  else
    $checked[$v] = '';
}

echo "<form action='index.php' method=POST>
Выборка по играм, сделанным с <input type='text' name='date1' id='date1' value='{$date1}' size=10> по
<input type='text' name='date2'  id='date2' value='{$date2}' size=10>
<input type='submit' value='OK'><br>
<span style='color:white'>
Только приход/расход: <input type='radio' name='act'  value='global' {$checked['global']}>&nbsp;
Детальная: <input type='radio' name='act'  value='detail'  {$checked['detail']}>&nbsp;
По игрокам: <input type='radio' name='act'  value='players'  {$checked['players']}>&nbsp;
По игроку: <input type='radio' name='act'  value='one_plr'  {$checked['one_plr']}>&nbsp;
</span>
<input type='text' name='plrnick' value='{$plrnick}'>
</form>";

switch($act)
{
   case 'global':
   {
     ShowGlobalStat($date1, $date2);
     break;
   }
   case 'detail':
   {
     ShowDetailStat($date1, $date2);
     break;
   }
   case 'players':
   {
     ShowPlayersStat($date1, $date2);
     break;
   }
   case 'one_plr':
   {
     ShowOnePlayerStat($date1, $date2);
     break;
   }
}

function ShowOnePlayerStat($date1, $date2)
{
  $pg = $_REQUEST['pg'];
  if (!$pg)
    $pg = 1;


  $date2 .= ' 23:59:59';
  $plrnick = trim($_REQUEST['plrnick']);
  if (!$plrnick)
  {
    echo "Не введён ник игрока<br>"; 
    return;
  }

  $res = BJ::getGlobalStat($date1, $date2, 'id_bj_game', 'DESC', $plrnick);
  if (!$res)
  {
    echo "За заданный период игрок с ником ".$_REQUEST['plrnick']." в 21 не играл<br>"; 
    return;
  }
  $PaginatorURL = "?act=one_plr&plrnick={$plrnick}&date1={$date1}&date2={$date2}&pg=";

  foreach ($res as $r)
  {
    $cnt++;
    $winsum += $r['winsum'];
    $bet   += $r['bet'];
  }
  $mindate = $r['game_date'];
  $maxdate = $res[0]['game_date'];
  $midbet  = sprintf('%0.1f', $bet/$cnt);

  $totalcnt = count($res);
  $items_num = 50;
  $cnt_pages = ceil($totalcnt/$items_num);
  //echo "res1=".var_export($res, true)."<br>";
  //echo "pg=$pg, items_num={$items_num}<br>";
  $res = array_slice($res, ($pg-1)*$items_num, $items_num);
  //echo "res2=".var_export($res, true)."<br>";

  echo "<p style='color:yellow'>";
  echo "Всего сыграно партий - <b style='color:red'>$cnt</b><br>";
  echo "Первая и последняя игра за период - <b style='color:red'>{$mindate} - {$maxdate}</b><br>";
  echo "Выигрыш игрока за период - <b style='color:red'>$winsum</b><br>";
  echo "Средняя ставка игрока за период - <b style='color:red'>$midbet</b><br><br>";
  echo "</p>";

  echo "<table class='tbl'>";
  echo "<tr><td class='tbl'>Дата</td><td class='tbl'>Игрок</td><td class='tbl'>Ставка</td><td class='tbl'>Выигрыш</td></tr>";
  foreach ($res as $r)
  {
    list ($dt1, $dt2) = explode('.', $r['game_date']);
    echo "<tr>";
    echo "<td class='tbl'>{$dt1}</td><td class='tbl'>{$r['username']}</td><td class='tbl'>{$r['bet']}</td><td class='tbl'>{$r['winsum']}</td>";
    echo "</tr>\n";
  }
  echo "</table>";
  echo "<div class='paginator' id='paginator_games'></div>
<script type='text/javascript'>
	p = new Paginator('paginator_games', $cnt_pages, 10 ,$pg, '$PaginatorURL');
</script>";
}

function ShowPlayersStat($date1, $date2)
{
  $pg = $_REQUEST['pg'];
  if (!$pg)
    $pg = 1;
  $PaginatorURL = "?act=players&date1={$date1}&date2={$date2}&pg=";

  $date2 .= ' 23:59:59';
  $res = BJ::getGlobalStat($date1, $date2, 'username, id_bj_game', 'ASC');
  //var_export($res);
  $users = array();
  foreach ($res as $r)
  {
    $uname  = $r['username'];
    if (!array_key_exists($uname, $users))
    {
       $users[$uname]['min_date'] = $r['game_date'];
       $users[$uname]['name'] = $uname;
       $users[$uname]['cnt'] = 0;
       $users[$uname]['bet'] = 0;
       $users[$uname]['winsum'] = 0;
    }
    $users[$uname]['max_date'] = $r['game_date'];
    $users[$uname]['cnt']++;
    $users[$uname]['winsum'] += $r['winsum'];
    $users[$uname]['bet'] += $r['bet'];

    list ($dt1, $dt2) = explode('.', $r['game_date']);
  }

  $totalcnt = count($users);
  $items_num = 50;
  $cnt_pages = ceil($totalcnt/$items_num);

  $users = array_slice($users, ($pg-1)*$items_num, $items_num);

  echo "<table class='tbl'>";
  echo "<tr><td class='tbl'>Игрок</td><td class='tbl'>Дата первой игры</td><td class='tbl'>Дата последней игры</td><td class='tbl'>Всего игр</td><td class='tbl'>Итоговый выигрыш</td>
  <td class='tbl'>Средняя ставка</td>
  </tr>";

  foreach($users as $u)
  {
    list($u['min_date'], $r) = explode('.',$u['min_date']);
    list($u['max_date'], $r) = explode('.',$u['max_date']);
    $midbet = sprintf("%0.1f",$u['bet']/$u['cnt']);
    echo "<tr>";
    echo "<td class='tbl'>{$u['name']}</td><td class='tbl'>{$u['min_date']}</td>
    <td class='tbl'>{$u['max_date']}</td><td class='tbl'>{$u['cnt']}</td><td class='tbl'>{$u['winsum']}</td><td class='tbl'>{$midbet}</td>";
    echo "</tr>\n";
  }

  echo "</table>";

  echo "<div class='paginator' id='paginator_games'></div>
<script type='text/javascript'>
	p = new Paginator('paginator_games', $cnt_pages, 10 ,$pg, '$PaginatorURL');
</script>";
}

function ShowDetailStat($date1, $date2)
{
  $pg = $_REQUEST['pg'];
  if (!$pg)
    $pg = 1;
  $PaginatorURL = "?act=detail&date1={$date1}&date2={$date2}&pg=";

  $date2 .= ' 23:59:59';
  $res = BJ::getGlobalStat($date1, $date2);

  $totalcnt = count($res);
  $items_num = 50;
  $cnt_pages = ceil($totalcnt/$items_num);
  $res = array_slice($res, ($pg-1)*$items_num, $items_num);
  echo "<table class='tbl'>";
  echo "<tr><td class='tbl'>Дата</td><td class='tbl'>Игрок</td><td class='tbl'>Ставка</td><td class='tbl'>Выигрыш</td></tr>";
  foreach ($res as $r)
  {
    list ($dt1, $dt2) = explode('.', $r['game_date']);
    echo "<tr>";
    echo "<td class='tbl'>{$dt1}</td><td class='tbl'>{$r['username']}</td><td class='tbl'>{$r['bet']}</td><td class='tbl'>{$r['winsum']}</td>";
    echo "</tr>\n";
  }
  echo "</table>";
  echo "<div class='paginator' id='paginator_games'></div>
<script type='text/javascript'>
	p = new Paginator('paginator_games', $cnt_pages, 10 ,$pg, '$PaginatorURL');
</script>";
}


function ShowGlobalStat($date1, $date2)
{
  $date2 .= ' 23:59:59';
  $res = BJ::getGlobalStat($date1, $date2);

  $draw = $loss =  $win = 0;
  $draw_cnt = $loss_cnt = $win_cnt = 0;
  $sumbet =  0;
  //var_export($res);
  $PlrTotal = 0;
  $users = array();
  foreach($res as $r)
  {
    if (!array_key_exists($r['username'], $users ))
    {
      $users[$r['username']] = 1;
      $PlrTotal++;
    }
    $ws = $r['winsum'];
    $sumbet += $r['bet'];
    //echo "{$r['id_bj_game']}- $ws<br>";
    if ($ws == 0)
    {
      $draw_cnt++;
    }
    else if ($ws < 0)
    {
      $loss += $ws;
      $loss_cnt++;
    }
    else if ($ws > 0)
    {
      $win += $ws;
      $win_cnt++;
    }
  }
  $total = count($res);
  $betstr = 0;
  
  if ($total > 0)
    $betstr = sprintf("%0.1f", $sumbet / $total);

  $loss = abs($loss);
  $balance = $loss - $win;
  echo "<p style='color:white'>";
  echo "Всего сыграно партий  - <b style='color:red'>{$total}</b><br>";
  echo "Всего играло игроков - <b style='color:red'>{$PlrTotal}</b><br>";
  echo "Из них игроки выиграли  - <b style='color:red'>{$win_cnt}</b>, на сумму <b style='color:red'>$win</b><br>";
  echo "Из них игроки проиграли - <b style='color:red'>{$loss_cnt}</b>, на сумму <b style='color:red'>$loss</b><br>";
  echo "Из них ничьих - <b style='color:red'>{$draw_cnt}</b><br>";
  echo "Средний размер ставки = <b style='color:red'>".$betstr."</b><br><br>";
  echo "ИТОГО БАЛАНС за заданный период(проигрыш игроков - выигрыш игроков) = <b style='color:red'>{$balance}</b>
  (положительный баланс означает прибыль казино)<br><br>";

  $res = BJ::checkTotalWinsum();
  echo "ИТОГО прибыль казино с момента запуска БлэкДжека: <b style='color:red'>".(-1*$res['total'])."</b><br>";
  echo '</a>';
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>