<?php
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

//require_once "ADM_GoldStat.class.php";

  echo "<script src='/Admin/js/pager.js'></script>";
  echo "<link href='/Admin/styles/pager.css' rel='stylesheet' type='text/css' />";
echo "<style type='text/css'>
     .tbl  {
           border: 1px solid black;
           border-collapse: collapse;
           color:white
         }
     .tbl td {padding-left:5px;}
</style>
    <script src='/Admin/js/pager.js'></script>
    <link href='/Admin/styles/pager.css' rel='stylesheet' type='text/css' />
";

switch($act)
{
  case 'change_param':
  {
    ChangeParam();
    break;
  }
  default:
  {
    ShowForm('');
    break;
  }
}

function ChangeParam()
{
  $refproc = intval($_REQUEST['refproc']);
  if ($refproc<=0)
  {
    ShowForm('Реферальный процент не может быть меньше или равен нулю');
    exit;
  }

  UC_PaymentGC::SetParam('REF_PROC', $refproc);
  UC_PaymentGC::SetParam('GATE_OPEN_SUM', intval($_REQUEST['gateopen']));
  UC_PaymentGC::SetParam('COLONY_OPEN_SUM', intval($_REQUEST['colonyopen']));

  if (stripos($_SERVER['HTTP_HOST'], 'destinysphere.lt')===false)
  {
    if ($_SERVER['HTTP_HOST'] =='unlim')
     $url = "http://billing/gate/setconf.php";
    else
     $url = "https://billing.destinysphere.ru/gate/setconf.php";

    ob_flush();
    $params = 'act=setref&refproc='.$refproc.'&id_partner='.constant('BILLING_PARTNER_ID');

    $ch = curl_init ($url.'?'.$params);

      curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);

      ob_start();
      $result = curl_exec($ch);
      ob_end_clean();
      curl_close($ch);
  }
    ShowForm($result);
}

function ShowForm($msg)
{
  $refproc    = UC_PaymentGC::GetParam('REF_PROC');
  $gateopen   = UC_PaymentGC::GetParam('GATE_OPEN_SUM');
  $colonyopen = UC_PaymentGC::GetParam('COLONY_OPEN_SUM');
  echo "<b style='color:yellow'>Параметры реферальной системы</b>";
  if ($msg)
  {
    echo "<span style='color:red'>{$msg}</span><br>";
  }
  $usernick = @$_REQUEST['usernick'];
  $date1    = @$_REQUEST['date1'];
  $date2    = @$_REQUEST['date2'];
  $nick1 = @$_REQUEST['nick1'];
  $nick2 = @$_REQUEST['nick2'];

  if ($nick1) $usernick = $nick1;

  $noshow = false;
  if (!$date1)
   $noshow = true;

  if (!$date1)
    $date1 = date('Y-m-01');

  if (!$date2)
   $date2 = date('Y-m-d');

  echo "<form action='' method=POST>
  <input type=hidden name='act' value='change_param'>
  <span style='color:white'>Процент отчислений по реферальной программе(только целое число!):</span><input type=text name='refproc' value='{$refproc}' size=3><span style='color:white'>%</span><br>
  <span style='color:white'>Начисление за открытие ворот(только целое число!):</span><input type=text name='gateopen' value='{$gateopen}' size=3><span style='color:white'>SC</span><br>
  <span style='color:white'>Начисление за первую колонию(только целое число!):</span><input type=text name='colonyopen' value='{$colonyopen}' size=3><span style='color:white'>SC</span><br>
  <input type=submit value='Изменить параметры'>
  </form>";

  echo "<span style='color:white'>Показать рефералов игрока:</span>
  <form action='' method=POST>
  <input type=hidden name='act' value='getrefs'>
  <input type=text name='usernick' value='{$usernick}' size=20>
  <input type=submit value='Искать'>
  </form>";

  echo "<span style='color:white'>Добавить реферала:</span>
  <form action='' method=POST>
  <input type=hidden name='act' value='addref'>
  <span style='color:white'>Кому:</span>
  <input type=text name='nick1' size=20>
  <span style='color:white'>Кого:</span>
  <input type=text name='nick2' size=20>
  <input type=submit value='Искать'>
  </form>";


echo "<span style='color:white'>Статистика реф. регистраций:</span><br><form action='index.php' method=POST>
<span style='color:white'>Период: с</span> <input type='text' name='date1' id='date1' value='{$date1}' size=10> <span style='color:white'>по</span>
<input type='text' name='date2'  id='date2' value='{$date2}' size=10>
<input type='submit' value='OK'><br>
</form>";

  if ($nick1 && $nick2)
  {
    $recid1 = DS_User::GetUserIDByName($nick1);
    $recid2 = DS_User::GetUserIDByName($nick2);
    if ($recid1 && $recid2) {
        $common = DS_Common::CreateForCurrentDB(0);
        $sql = "insert into ds_user_registration_recomend values ($recid2, $recid1)";
        $common->executeQuery($sql);
        assert(empty($common->errormsg));
    }
  }

  if ($usernick)
  {
    $recid = DS_User::GetUserIDByName($usernick);
    if (!$recid)
    {
       echo "<span style='color:white'>Игрок <b>$usernick</b> не найден</span>";
    }
    else
    {
      $oldDbId = $_SESSION["dbId"];
      $common = DS_Common::CreateForCurrentDB(0);
        assert(empty($common->errormsg));

      $sql = "SELECT u.* FROM ds_user u WHERE id_user in
      (SELECT id_user FROM ds_user_registration_recomend WHERE id_user_recomend={$recid}) ORDER BY registrationdate DESC";
      $referers = $common->getResultArray('', $sql, '', '', 'NO_CACHE');
      assert(empty($common->errormsg));
      $_SESSION["dbId"] = $oldDbId;

      if (!$referers)
      {
        echo "<span style='color:white'> У игрока <b>$usernick</b> нет рефералов";
      }
      else
      {
        $NickInfo = DS_User::GetByID($recid);
        echo "<span style='color:yellow'>Рефералы игрока <b>{$usernick}</b></span><br><span style='color:white'>IP-адрес регистрации игрока <b>$usernick</b> -  {$NickInfo['ip_addr']}, дата регистрации - {$NickInfo['registrationdate']} </span>";
        echo "<table class=tbl>";
        echo "<tr><td class='tbl'>Игрок</td><td class='tbl'>Дата регистрации</td><td class='tbl'>IP адрес</td><td class='tbl'>Откуда пришёл</td></tr>";
        foreach ($referers as $ref)
        {
           echo "<tr>
           <td class='tbl' style='color:lightgreen'>{$ref['username']}</td><td style='color:white' class='tbl'>{$ref['registrationdate']}</td>
           <td class='tbl' style='color:white'>&nbsp;{$ref['ip_addr']}</td><td style='color:white' class='tbl'><a href=\"{$ref['reg_referer']}\">{$ref['reg_referer']}</a></td>
           </tr>";
        }
        echo "</table>";
      }
    }
  }

  if ($date1 && $date2 && !$noshow)
  {
     ShowRefStat($date1, $date2);
  }
}

function ShowRefStat($date1, $date2)
{
  $num = 50;
  $pg = $_REQUEST['pg'];
  if (!$pg)
    $pg = 1;
  $PaginatorURL = "?date1=$date1&date2=$date2&pg=";

  $date2 .= ' 23:59:59';
  list($refinfo, $cnt) = DS_User::GetReferalsForPeriod($date1, $date2, $pg,  $num);

  if (!$refinfo)
    return 0;

  $cnt_pages = ceil($cnt/$num);
  echo "<span style='color:yellow'>Всего рефералов за период - <b>$cnt</b></span>";
  echo "<br><br><table class='tbl'>";
  echo "<tr>";
  echo "<td class=tbl>N</td><td class=tbl>Ник</td><td class=tbl>Дата регистрации</td><td class=tbl>Чей реферал</td><td class=tbl>Откуда пришёл</td><td class=tbl>IP-адрес</td>";
  echo "</tr>";
  $n = ($pg-1)*$num + 1;


  foreach ($refinfo as $r)
  {
    $dt = explode('.', $r['registrationdate']);
    echo "<tr>
    <td class=tbl>$n</td>
    <td class=tbl><a href='?usernick={$r['recusername']}'>{$r['username']}</a></td>
    <td class=tbl>{$dt[0]}</td>
    <td class=tbl><a href='?usernick={$r['recusername']}'>{$r['recusername']}</a></td>
    <td class=tbl><a href='{$r['reg_referer']}' target='_blank'>{$r['reg_referer']}</a></td>
    <td class=tbl>{$r['ip_addr']}</td>
    </tr>
    ";
    $n++;
  }
  echo "</table>";
  echo "<div class='paginator' id='paginator_users'></div>
<script type='text/javascript'>
    p = new Paginator('paginator_users', $cnt_pages, 10 ,$pg, '$PaginatorURL');
</script>";
}

require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>