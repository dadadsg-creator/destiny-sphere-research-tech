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

$act = @$_REQUEST['act'];
switch($act)
{
  case 'update_site':
  {
    UpdateSite();
    break;
  }
  default:
  {
    ShowForm('');
    break;
  }
}

function UpdateSite()
{
  global $act;
  $pD = array();

  $pD['name']           = str_replace(array('\"',"\'"),array('"', "'"), trim($_REQUEST['pname']));
  $pD['name']           = str_replace("'","\'", trim($_REQUEST['pname']));
  $pD['site_list']      = trim($_REQUEST['sites']);
  $pD['contact_person'] = trim($_REQUEST['person']);
  $pD['email']          = trim($_REQUEST['email']);
  $pD['id_partner']     = @$_REQUEST['partner_id'];
  $pD['login']          = trim($_REQUEST['login']);

  if (intval($_REQUEST['idp'])>0)
  {
    $pD['id_partner'] = intval($_REQUEST['idp']);
  }

  if (isset($_REQUEST['active']))
    $pD['active'] = 'true';
  else
    $pD['active'] = 'false';

  $pD['procent']  = floatval($_REQUEST['proc']);

  if (trim($_REQUEST['passw']))
     $pD['password'] = md5(trim($_REQUEST['passw']));

  $errmsg = '';
  if (!$pD['name'])
     $errmsg = 'Не введено название компании<br>';

  if (!$pD['login'])
     $errmsg .= 'Не введён логин<br>';

  if (($pD['procent']<=0) || ($pD['procent']>50))
     $errmsg .= 'Партнёрский процент меньше 0 или больше 50';

  if ((!$pD['password']) && !$pD['id_partner'])
    $errmsg .= 'Не введён пароль';

  if ($errmsg && !isset($_REQUEST['delb']))
  {
    ShowForm($errmsg);
    return;
  }

  $bill_act = '';
  if (isset($_REQUEST['delb']))
  {
    $errmsg = '';
    $act = '';
    $bill_act = 'del';
    $_REQUEST['partner_id'] = 0;
    DS_Partner::DeletePartner($pD['id_partner']);
  }
  else
  {
    if ($pD['id_partner'] && !$_REQUEST['idp'])
    {
      $bill_act = 'upd';
      DS_Partner::UpdatePartner($pD);
    }
    else
    {
      $pD['id_partner'] = DS_Partner::AddPartner($pD);
      $bill_act = 'add';
    }
  }

  if (stripos($_SERVER['HTTP_HOST'], 'destinysphere.lt')===false)
  {
    if ($_SERVER['HTTP_HOST'] =='unlim')
     $url = "http://billing/gate/sitepartners.php";
    else
     $url = "https://billing.destinysphere.ru/gate/sitepartners.php";


    if ($act=='del')
      $params = 'act=$bill_act&id_partner='.$pD['id_partner'];
    else
    {
      $params = "act=$bill_act";
      foreach ($pD as $key=>$val)
      {
        $params .= "&{$key}=$val";
      }
    }

	  $ch = curl_init ($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	  curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
 	  curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
	  curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
  
  	ob_start();
	  $result1 = curl_exec($ch);
	  ob_end_clean();
	  curl_close($ch);
  
    if (((stripos($_SERVER['HTTP_HOST'], 'dsgame')===false) && stripos($_SERVER['HTTP_HOST'], 'ds-game')===false))
    {
      $url = "http://dsgame.rambler.ru/ds/gate/sitepartners.php";
      $ch = curl_init ($url);  
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
	    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);


	    ob_start();
	    $result2 = curl_exec($ch);
	    ob_end_clean();
	    curl_close($ch);
    }  
  }
  
  ShowForm($errmsg, $result1." ".$result2);
}

function ShowForm($msg, $httpmsg = '')
{
  global $act;

  echo "<a href='index.php' style='color:lightgreen'><<НАЗАД</a><br><br>";
  $IDStr = '';
  $delb = $dateReg = '';
  if (@$_REQUEST['partner_id'])
  {
    $header = 'Редактирование данных о партнёре';

    $delb = "<input type=submit name='delb' value='Удалить партнёра' onClick='return confirm(\"Вы действительно хотите удалить этого партнёра?\")'>";
    $partner_id = $_REQUEST['partner_id'];
    if ($act == 'edit')
    {
      $pinfo = DS_Partner::getPartner($partner_id);

      $pname          = $pinfo['name'];
      $contact_person = $pinfo['contact_person'];
      $sites          = $pinfo['site_list'];
      $email          = $pinfo['email'];
      $checkedstr     = $pinfo['active']=='t'?'checked':'';
      $proc           = $pinfo['procent'];
      $dateReg = "<tr><td class=tbl>Дата регистрации</td><td class=tbl>{$pinfo['reg_date']}</td></tr>";
      $IDStr = "<tr><td class=tbl>ID</td><td class=tbl>{$partner_id}</td></tr>";
      $loginStr = "<tr><td class=tbl>Логин</td><td class=tbl><input type='hidden' name='login' value='{$pinfo['login']}'>{$pinfo['login']}</td></tr>";
    }
    else
    {
      if ($msg)
      {
        $pname          = trim($_REQUEST['pname']);
        $contact_person = trim($_REQUEST['person']);
        $sites          = trim($_REQUEST['sites']);
        $email          = trim($_REQUEST['email']);
        $checkedstr     = isset($_REQUEST['active'])?'checked':'';
        $proc           = intval($_REQUEST['proc']);
        $login          = trim($_REQUEST['login']);
      }
      $loginStr = "<tr><td class=tbl>Логин</td><td class=tbl><input type='text' size=20 name='login' value='{$login}'></td></tr>";
    }
  }
  else
  {
    $partner_id = 0;
    $checkedstr = 'checked';
    $pname          = '';
    $contact_person = '';
    $sites          = '';
    $email          = '';
    $proc           = '50';
    $login = '';
    $header = 'Добавление нового партнёра';
    $loginStr = "<tr><td class=tbl>Логин</td><td class=tbl><input type='text' size=20 name='login' value='{$login}'></td></tr>";
     $IDStr = "<tr><td class=tbl>ID</td><td class=tbl><input type=text name='idp' value='' size=4><br>Если вы не хотите сами присваивать партнёру ID, оставьте это поле пустым. Обычно это поле нужно заполнять, только если вы добавляете партнёра на DS Unlimited</td></tr>";

  }

  if (($partner_d==0) && (stripos($_SERVER['HTTP_HOST'], 'destinysphere.lt')===false))      
  {
    echo "<span style='color:white'>ПОСЛЕ ДОБАВЛЕНИЯ НА КЛАССИКЕ, ПРОВЕРЬТЕ, ДОБАВИЛСЯ ЛИ ПАРТНЁР НА АНЛИМЕ</span><br>";
  } 

/*  
  echo "<span style='color:white'>{$header}</span><br>
  <span style='color:yellow'>{$msg}</span>{$httpmsg} 
  <form action='' method=POST>
  <input type=hidden name='act' value='update_site'>
  <input type=hidden name='partner_id' value='$partner_id'>
  <table class=tbl>
   {$IDStr}
   <tr><td class=tbl>Активен</td><td class=tbl><input type='checkbox' name='active'  value=1 $checkedstr></td></tr>

   {$dateReg}
   <tr><td class=tbl>Название компании</td><td class=tbl><input type='text' name='pname' value='{$pname}' size=30></td></tr>
   {$loginStr}
   <tr><td class=tbl>Контактное лицо</td><td class=tbl><input type='text' name='person' value='{$contact_person}'></td></tr>
   <tr><td class=tbl>e-mail</td><td class=tbl><input type='text' name='email' value='{$email}'></td></tr>
   <tr><td class=tbl>Сайты (каждый на новой строчке)</td><td class=tbl><textarea name='sites' cols=30 rows=4>{$sites}</textarea></td>
   <tr><td class=tbl>Реферальный %</td><td class=tbl><input type='text' name='proc' value='{$proc}' size=2></td></tr>
   <tr><td class=tbl>Пароль <br>(оставьте пустым, чтобы не менять)</td><td class=tbl><input type='password' name='passw'></td></tr>
  </table>
  <input type=submit value='OK'>
  $delb

  </form>";
*/
  $partners  = DS_Partner::getPartner();
  //var_export($partners);
  echo "<span style='color:yellow'>Существующие партнёры</span>";
  echo "<table class='tbl'>";
  echo "<tr><td class='tbl'>ID</td><td class='tbl'>Логин</td><td class='tbl'>Название</td><td class='tbl'>Дата добавления</td><td class='tbl'>Контактное лицо</td><td class='tbl'>Партнёрский %</td><td class='tbl'>Активен</td><td class='tbl'>Статистика</td></tr>";
  $n = 1;
  foreach($partners as $p)
  {
    if ($p['active']=='t')
     $active = '<span style="lightgreen">Да</span>';
    else
     $active = '<span style="red">Нет</span>';

    $dt = explode('.',$p['reg_date']);

    if ($_SERVER['HTTP_HOST'] =='unlim')
     $url = "http://billing/partners/stat.php?pid={$p['id_partner']}&phash={$p['password']}&mode=dsa";
    else
    {
       if (stripos($_SERVER['HTTP_HOST'], 'destinysphere.lt') !== false)      
       {
         $url = "http://www.destinysphere.lt/partners/stat.php?pid={$p['id_partner']}&phash={$p['password']}&mode=dsa";
       }
       else 
       {          
         $url = "https://billing.destinysphere.ru/partners/stat.php?pid={$p['id_partner']}&phash={$p['password']}&mode=dsa";
       }
    }
    
    echo "<tr>
    <td class='tbl'>{$p['id_partner']}</td>
    <td class='tbl'>{$p['login']}</td>
    <td class='tbl'><a href='?act=edit&partner_id={$p['id_partner']}'>{$p['name']}</a></td>
    <td class='tbl'>{$dt[0]}</td>
    <td class='tbl'>{$p['contact_person']}</td>
    <td class='tbl'>{$p['procent']}</td>
    <td class='tbl'>{$active}</td>
    <td class='tbl'><a href='$url' target='_blank'>биллинг</a></td>

    </tr>";
    $n++;
  }
  echo '</table>';
}


require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/adminbottom.php";
?>