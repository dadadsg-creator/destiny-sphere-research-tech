<?php

if ($_SERVER['SERVER_NAME']=='ds-game.su' || $_SERVER['SERVER_NAME']=='www.ds-game.su') {
    header ("Location: /ds/index_login.php");
    exit;
}

require_once "./sys/define.php";
require_once "./sys/lib/PageCache.class.php";

$charset = array ('RU' => 'windows-1251', 'DE' => 'iso-8859-1', 'EN' => 'windows-1251');


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

$idp = intval(@$_REQUEST['idp']);

if ($idp > 0)
{
  require_once( 'ds/Main.php' );
  $partnerinfo = DS_Partner::getPartner($idp);

  if (($idp>0) && $partnerinfo && $partnerinfo['active']=='t')
  {
    setcookie("partner", $idp, time()+3600*12, "/");//кука действует 12 часов
  }
}

header("Content-Type: text/html; charset=".$charset[strtoupper(constant('DEFAULT_LANG'))]." ");
header("Last-Modified: ".date("D, d M Y H:i:s")." GMT");

require_once $_SERVER['DOCUMENT_ROOT']."/ds/Main.php";
$time_start = getMicroTime();

// Рамблер ID
$ramblerLogin = new GE_RamblerLogin();
if($ramblerLogin->IsRSID())
{
    $_POST['rambler_login'] = true;
}

$pageCache = new PageCache($default_page);
$strOut = $pageCache->get();
//$strOut = '';
if(!$strOut || ($_REQUEST['nfs'] == 79)){

  ob_start();
    require_once "./sys/lib/engine.php";

    $strOut = ob_get_contents();
    ob_end_clean();
    $strOut = str_replace("{TITLE_END}", @$GLOBALS['TITLE_END'], $strOut);
    $strOut = str_replace('</head>', '<script src="https://unpkg.com/@ruffle-rs/ruffle"></script></head>', $strOut);
    $pageCache->set($strOut);
    echo "<!--REAL PAGE!-->";
}else{
    echo "<!--PAGE FROM CACHE!-->";
}

$time_end = getMicroTime();
$time = $time_end - $time_start;
echo "<!--Generation time ".round($time,3)."sec-->";
echo $strOut;
?>