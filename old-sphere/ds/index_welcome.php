<?
// включаем убийство сессии в случае assert или ds_error
$GLOBALS['HI_SECURITY_FLAG'] = 1;

require_once( 'Main.php' );

//Проверям не закрыта ли игра
require_once(constant('INCLUDE_PATH').'GameDown.inc.php');
if (GameDown())
{
	Header("Status: 302 Moved Temporaly");
	Header("Location: index_down.html");
	exit();
}

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

if (!isset($_COOKIE['ref']))
{
  $refenc = urlencode($referer);
  setcookie("ref", $refenc, time()+3600*24*14, "/");
}

$log = new GE_Login(true);

?>