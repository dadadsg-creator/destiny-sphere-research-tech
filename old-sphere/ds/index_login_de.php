<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2009/01/30 10:36:43 $';
// включаем убийство сессии в случае assert или ds_error
$GLOBALS['HI_SECURITY_FLAG'] = 1;

require_once( 'Main.php' );

$_SESSION['lang'] = 1;

//Проверям не закрыта ли игра
require_once(constant('INCLUDE_PATH').'GameDown.inc.php');
if (GameDown())
{
    Header("Status: 302 Moved Temporaly");
    Header("Location: index_down.html");
    exit();
}

//require_once( "IT.php" );
import('Validator');
//Если мы пришли с рамблера, то поставим соотв. флаг

$referer = @$_SERVER['HTTP_REFERER'];

if (stripos($referer, 'rambler.ru') !== false)
{
   //Выяснили, что в реферере есть rambler.ru. Теперь надо отсечь поддомены собственно сферы.
   if (!eregi('ds\.|dsgame\.|ds1\.|ds2\.|ds3\.|dsu\.|dsg\.|nova\.|openstat',$referer))
   {
     //действительно пришли с сайта рамблера
     header("Set-Cookie: partner=6;");
   }
}
else if (stripos($referer, 'onlineguru.ru') !== false) 
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