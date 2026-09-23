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
    $strOut = str_replace('</head>', '<script>window.RufflePlayer = window.RufflePlayer || {}; window.RufflePlayer.config = { autoplay: "on", unmuteOverlay: "hidden" };</script><script src="https://unpkg.com/@ruffle-rs/ruffle"></script></head>', $strOut);
    $strOut = str_replace('http://destinysphere.ru/banners/', '/banners/', $strOut);
    $menuStart = strpos($strOut, '<!-- register button begin -->');
    $menuEnd = strpos($strOut, '<!-- premium button begin -->');
    if ($menuStart !== false && $menuEnd !== false) {
        $menuChunk = substr($strOut, $menuStart, $menuEnd - $menuStart);
        $rowOpen = '<tr>\s*<td width=173 height=23 align=left valign=middle background="img/buttons/but_lm_white\.gif" bgcolor=#2A3B6F class=menu_txt_left>';
        $guard = '(?:(?!<tr>\s*<td width=173).)*?';
        $sep = '</tr>\s*<tr>\s*<td width=173 height=5 align=left valign=middle>' . $guard . '</tr>\s*';
        $menuChunk = preg_replace("~$rowOpen$guard" . 'index\.php\?p=main_news"' . "$guard$sep~s", '', $menuChunk);
        $menuChunk = preg_replace("~$rowOpen$guard" . 'beauty\.destinysphere\.ru/index\.php"' . "$guard</tr>\s*~s", '', $menuChunk);
        $menuChunk = preg_replace("~$rowOpen$guard" . 'wiki\.destinysphere\.ru/"' . "$guard</tr>\s*~s", '', $menuChunk);
        $menuChunk = preg_replace("~$rowOpen$guard" . 'destinysphere\.com/partner\.php"' . "$guard</tr>\s*~s", '', $menuChunk);
        $menuChunk = preg_replace("~$rowOpen$guard" . 'sms\.destinysphere\.ru/"' . "$guard$sep~s", '', $menuChunk);
        $menuChunk = preg_replace("~$rowOpen$guard" . 'test\.destinysphere\.ru/"' . "$guard$sep~s", '', $menuChunk);
        $menuChunk = preg_replace("~$rowOpen$guard" . 'index\.php\?p=faq_tournament"' . "$guard$sep~s", '', $menuChunk);
        $menuChunk = preg_replace("~$rowOpen$guard" . 'index\.php\?p=icq_helper"' . "$guard$sep~s", '', $menuChunk);
        $strOut = substr($strOut, 0, $menuStart) . $menuChunk . substr($strOut, $menuEnd);
    }
    $skyStart = strpos($strOut, '<!-- banner skytower begin-->');
    $skyEnd = strpos($strOut, '<!-- banner skytower end-->');
    if ($skyStart !== false && $skyEnd !== false) {
        $skyEnd += strlen('<!-- banner skytower end-->');
        $strOut = substr($strOut, 0, $skyStart) . substr($strOut, $skyEnd);
    }
    $newsEndPos = strpos($strOut, '<!-- news window end -->');
    $detiPos = ($newsEndPos !== false) ? strpos($strOut, 'deti-life.ru', $newsEndPos) : false;
    if ($newsEndPos !== false && $detiPos !== false) {
        $region = substr($strOut, $newsEndPos, $detiPos - $newsEndPos);
        $tdPos = strpos($region, '<td width=187 valign=top>');
        $lastTrPos = strrpos($region, '</tr>');
        if ($tdPos !== false && $lastTrPos !== false && $lastTrPos > $tdPos) {
            $newRegion = substr($region, 0, $tdPos) . substr($region, $lastTrPos);
            $strOut = substr($strOut, 0, $newsEndPos) . $newRegion . substr($strOut, $detiPos);
        }
    }
    $menuAnchors = [
        'chat\.destinysphere\.ru/irc\.cgi',
        'planeta\.rambler\.ru/community/ds/',
        'p=support_user_pages',
        'beauty\.destinysphere\.ru/\'',
        'vozvrat\.destinysphere\.ru',
        'p=deal_page',
        'p=game_statistics',
        'p=ds_job',
    ];
    foreach ($menuAnchors as $menuAnchor) {
        $strOut = preg_replace("~^.*$menuAnchor.*\r?\n~m", '', $strOut);
    }
    $strOut = preg_replace('~ranking_new\.php" class=menu_txt_left target=_blank><font color="#FF3366">.*?</font>~s', 'ranking_new.php" class=menu_txt_left target=_blank>Рейтинг', $strOut);
    $welcomeStart = strpos($strOut, '<!-- welcome window begin -->');
    $welcomeEnd = strpos($strOut, '<!-- welcome window end -->');
    if ($welcomeStart !== false && $welcomeEnd !== false) {
        $welcomeChunk = substr($strOut, $welcomeStart, $welcomeEnd - $welcomeStart);
        $welcomeChunk = preg_replace('~(<img src="img/elements/cap_div\.gif" border=0>).*?(</td>)~s', '$1' . 'Добро пожаловать в Destiny Sphere — первую российскую онлайн-стратегию' . '$2', $welcomeChunk, 1);
        $welcomeChunk = preg_replace('~(<span class=wnd_gen_txt><span class="wnd_gen_txt_large">).*?(</span>\r?\n<div align="right">)~s', '$1' . '<strong>Стратегическая онлайн-игра Destiny Sphere Classic</strong> работает с 2003 года — легенда российского онлайна в жанре MMOG (Massively Multiplayer Online Game). Тысячи игроков одновременно развивают свои базы в фантастическом мире четырёх колец, вращающихся вокруг солнца, выбирая одну из трёх рас — технологичных Воранеров, генетиков Лиенсу или мистиков-археологов Псолао. Игра идёт в реальном времени круглосуточно и без остановки — прямо в браузере, без скачивания и установки какого-либо клиента.<br />
<br />
За эти годы к нам присоединилось более 1,3 млн игроков!<br />' . '$2', $welcomeChunk, 1);
    $welcomeChunk = preg_replace('~(</span>\r?\n<div align="right">)~', '<br /><div align="center"><img src="img/screens/05.jpg" width="335" height="252" border="0" style="margin:4px;" alt=""><img src="img/screens/07.jpg" width="335" height="252" border="0" style="margin:4px;" alt=""><img src="img/screens/09.jpg" width="335" height="252" border="0" style="margin:4px;" alt=""></div>' . '$1', $welcomeChunk, 1);
        $strOut = substr($strOut, 0, $welcomeStart) . $welcomeChunk . substr($strOut, $welcomeEnd);
    }
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