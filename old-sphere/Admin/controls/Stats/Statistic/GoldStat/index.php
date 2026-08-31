<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

require_once "ADM_GoldStat.class.php";

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

echo "<b>Статистика по Вводу золота</b>";

$date1   = @$_REQUEST['date1'];
list($date2, $tail)   = explode(' ',@$_REQUEST['date2']);
$act     = @$_REQUEST['act'];

if (!$act)
{
  $act = 'showstat';
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
$act_values = array('showstat','showdiscounts');
foreach ($act_values as $v)
{
  if ($v==$act)
    $checked[$v] = 'checked';
  else
    $checked[$v] = '';
}

if ($act=='sbmdiscount')
  $checked[1] = 'checked';

echo "<form action='index.php' method=POST>
Период: с <input type='text' name='date1' id='date1' value='{$date1}' size=10> по
<input type='text' name='date2'  id='date2' value='{$date2}' size=10><br>
Статистика платежей:<input type='radio' name='act' value='showstat' {$checked['showstat']}>&nbsp;&nbsp;
Скидочные акции:<input type='radio' name='act' value='showdiscounts' {$checked['showdiscounts']}>
<input type='submit' value='OK'><br>
</form>";

switch($act)
{
   case 'showstat':
   {
     ShowStat($date1, $date2);
     break;
   }
   case 'showdiscounts':
   {
     ShowDiscounts($date1, $date2);
     break;
   }
   case 'sbmdiscount':
   {
     SubmitDiscount();
     break;
   }

}


function SubmitDiscount()
{
  $start_date = trim($_REQUEST['start_date']); 
  $end_date = trim($_REQUEST['end_date']); 
  $proc = trim($_REQUEST['proc']);
  $curdisc_id = $_REQUEST['disc_id'];
  $errstr = '';
  if (!$start_date)
    $err_str = 'Не введена начальная дата<br>';
  if (!$end_date)
    $err_str .= 'Не введена конечная дата<br>';
  if (!$proc)
    $err_str .= 'Не введены проценты<br>';
    
  $end_date .= ' 23:59:59';
  if ($err_str)
  {
    ShowDiscounts($start_date, $end_date, $err_str);
  }
  else
  {
    if ($curdisc_id)
      ADM_GoldStat::updateDiscount($curdisc_id, $start_date, $end_date, $proc);
    else
      ADM_GoldStat::addDiscount( $start_date, $end_date, $proc);

    ShowDiscounts($start_date, $end_date);
  }
}

function ShowDiscounts($date1, $date2, $msg='')
{
  $curdisc =   UC_PaymentGC::GetCurrentDiscount();
  $start_date = date("Y-m-d");
  $end_date = '';
  $proc = 0;
  $curdisc_id = 0;
  if ($curdisc)
  {
     list($start_date, $tail) = explode(' ',$curdisc['start_date']);
     list($end_date, $tail) = explode(' ',$curdisc['end_date']);
     $proc = $curdisc['discount_size'];
     $curdisc_id = $curdisc['id_discount'];
  }

  echo "<span style='color:yellow'>{$msg}</span>";
  echo "<b style='color:red'>Текущая/новая акция</b><span style='color:white'><br>
  Для досрочной остановки текущей акции укажите дату окончания, равной вчерашнему дню.
  Для добавления новой акции остановите текущую(если она есть) а потом в этой же форме 
  добавьте новую акцию. Даты вводятся в формате ГГГГ-ММ-ДД.<br/> СКИДКА ДЕЙСТВУЕТ до 23:59:59 конечного дня по московскому времени</span><br>
  <form action='index.php' method=POST>
  <input type='hidden' name='act' value='sbmdiscount'>
  Начало: <input type='text' name='start_date' value='{$start_date}' size=10>&nbsp;&nbsp; Окончание:
  <input type='text' name='end_date' value='{$end_date}' size=10>&nbsp;&nbsp;Процент (целое число): 
  <input type='text' name='proc'  value='{$proc}' size=3>
  <input type='submit' value='OK'>
  <input type='hidden' name='disc_id'  value='{$curdisc_id}'>
  </form>
  <br>
  <b style='color:red'>Последние 100 акций:</b>";
  $disc_list =ADM_GoldStat::GetAllDiscounts();

  echo "<table class='tbl'><tr><td class='tbl'>Дата начала</td><td class='tbl'>Дата окончания</td><td class='tbl'>Процент</td></tr>";
  foreach ($disc_list as $d)
  {
    if ($d['id_discount'] != $curdisc_id)
    {
      echo "<tr><td class='tbl'>{$d['start_date']}</td><td class='tbl'>{$d['end_date']}</td><td class='tbl'>{$d['discount_size']}</td>
      </tr>";
    }
  }
  echo "</table>";
}

function ShowStat($date1, $date2)
{
  $pg = $_REQUEST['pg'];
  if (!$pg)
    $pg = 1;


  $date2 .= ' 23:59:59';
  $Stat = ADM_GoldStat::GetAgregatedStat($date1, $date2);
  $PaginatorURL = "?act=showstat&date1={$date1}&date2={$date2}&pg=";
  $items_num  = 20;
  $cnt_pages = ceil($Stat['totalcnt'])/$items_num;
  echo "<span style='color:yellow'>";
  echo "ПОДТВЕРЖДЁННЫХ платежей (количество/сумма золотых кредитов/сумма в LTL) - <b style='color:red'>{$Stat['cnt']}/{$Stat['cnt_gc']}/{$Stat['sum_money']}</b><br>";
  echo "НЕЗАВЕРШЁННЫХ платежей (количество/сумма золотых кредитов/сумма в LTL) - <b style='color:red'>{$Stat['curr_cnt']}/{$Stat['curr_cnt_gc']}/{$Stat['curr_sum_money']}</b><br>";
  echo "ОТКАЗЫ от платежа (количество/сумма золотых кредитов/сумма в LTL) - <b style='color:red'>{$Stat['cancel_cnt']}/{$Stat['cancel_cnt_gc']}/{$Stat['cancel_sum_money']}</b><br>";
  echo "</span>";

  $totalcnt = $Stat['cnt']+$Stat['curr_cnt']+$Stat['curr_cnt'];
  $cnt_pages = ceil($totalcnt/$items_num);
  $DetailStat = ADM_GoldStat::GetDetailedStat($pg, $items_num, $date1, $date2, 1);
  
  echo "<br><b style='color:red'>Детальная статистика по ПОДТВЕРЖДЁННЫМ платежам за указанный период</b><br>";
  echo '<table class="tbl"><tr><td class="tbl">Дата</td><td class="tbl">Ник</td><td class="tbl">Число золотых</td><td class="tbl">Сумма</td><td class="tbl">валюта</td><td class="tbl">Платёжная система</td></tr>'."\n";
  foreach ($DetailStat as $d)
  {
     $d['price'] = str_replace('$','', $d['price']);
     echo "<tr>
  <td class='tbl'>{$d['payment_date']}</td><td class='tbl'>{$d['username']}</td>
 <td class='tbl'>{$d['quantity']}</td><td class='tbl'>{$d['price']}</td>
     <td class='tbl'>{$d['id_currency']}</td><td class='tbl'>{$d['paymenttype']}</td>
     </tr>\n";
  }
  echo "</table>";
  echo "<div class='paginator' id='paginator_games'></div>
<script type='text/javascript'>
	p = new Paginator('paginator_games', $cnt_pages, 10 ,$pg, '$PaginatorURL');
</script>";
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>