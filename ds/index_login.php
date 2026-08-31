<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2009/01/30 10:36:43 $';
// включаем убийство сессии в случае assert или ds_error
$GLOBALS['HI_SECURITY_FLAG'] = 1;

require_once( 'Main.php' );

if ((constant('DEFAULT_LANGUAGE_SHORT')=='G') && ((stripos($_SERVER['HTTP_HOST'], 'destinysphere.de')===0) || (stripos($_SERVER['HTTP_HOST'], 'destinysphere.net')===0) || (stripos($_SERVER['HTTP_HOST'], 'www.destinysphere.net')===0)))
{
  header("Location: http://www.destinysphere.de/ds/index_login.php");
  exit;
}

if (stripos($_SERVER['HTTP_HOST'], 'ds.klase.lt')!==false)
{
  require_once('../ds/config/klasecfg.php' );
  require_once("../sys/OAuth.php");
}
$metaua_registered = 0;
if (stripos($_SERVER['HTTP_HOST'], 'ds.meta.ua')!==false)
{
  require '../sys/auth.class.php';
  //ini_set('display_errors', 1);
//error_reporting(E_ALL);
  $metaua_user = new GlobalClassAuth("hGVixZtDwdNKr1SPn8mt6meW","destinysphere");
  //die(var_export($metaua_user, true));
  //echo $u->sUserName;
  if ($metaua_user->sUserName)
     $metaua_registered = 1;
}

$idp = intval(@$_REQUEST['idp']);
$bid = @$_REQUEST['bid'];
if ($idp > 0)
{
  setcookie("partner", $idp, time()+3600*12, "/");//кука действует 12 часов
  setcookie("idp", $idp, time()+3600*12, "/");//кука действует 12 часов - дл€ reg_wizard'a

  if ($bid)
  {
    setcookie("bid", $bid, time()+3600*12, "/");//кука действует 12 часов - дл€ reg_wizard'a
  }
  
  if (@$_REQUEST['ids'])
  {
    setcookie('ids', $_REQUEST['ids'], time()+3600*12, "/");
  }  
}

if (!isset($_COOKIE['ref']))
{
  $refenc = urlencode(@$_SERVER['HTTP_REFERER']);
  setcookie("ref", $refenc, time()+3600*12, "/");
}

//ѕровер€м не закрыта ли игра
require_once(constant('INCLUDE_PATH').'GameDown.inc.php');
if (GameDown())
{
    Header("Status: 302 Moved Temporaly");
    Header("Location: index_down.html");
    exit();
}

//require_once( "IT.php" );
import('Validator');
//≈сли мы пришли с рамблера, то поставим соотв. флаг

$referer = @$_SERVER['HTTP_REFERER'];

if (stripos($referer, 'onlineguru.ru') !== false) 
{
    setcookie("partner", 11, time()+3600*12, "/");//кука действует 12 часов
}

if (!isset($_COOKIE['ref']))
{
  $refenc = urlencode($referer);
  setcookie("ref", $refenc, time()+3600*24*14, "/");
}
$log = new GE_Login;

if($log->errormsg)
{
    echo $log->errormsg;
}


?>