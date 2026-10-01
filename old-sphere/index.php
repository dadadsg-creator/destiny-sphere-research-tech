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
    $welcomeChunk = preg_replace('~(</span>?
<div align="right">)~', '<br /><div align="center" style="white-space:nowrap;"><a href="img/graph/sphere1.png" target="_blank"><img src="img/graph/sphere1_thumb.jpg" width="232" height="174" border="0" style="margin:4px;" alt=""></a><a href="img/graph/sphere2.png" target="_blank"><img src="img/graph/sphere2_thumb.jpg" width="232" height="174" border="0" style="margin:4px;" alt=""></a><a href="img/graph/sphere3.png" target="_blank"><img src="img/graph/sphere3_thumb.jpg" width="232" height="174" border="0" style="margin:4px;" alt=""></a><a href="img/graph/sphere4.png" target="_blank"><img src="img/graph/sphere4_thumb.jpg" width="232" height="174" border="0" style="margin:4px;" alt=""></a></div>' . '$1', $welcomeChunk, 1);

        $strOut = substr($strOut, 0, $welcomeStart) . $welcomeChunk . substr($strOut, $welcomeEnd);
    }
    $blStart = strpos($strOut, '<!--banner & login wnd begin-->');
    $blEnd = strpos($strOut, '<!--banner & login wnd end-->');
    if ($blStart !== false && $blEnd !== false) {
        $blChunk = substr($strOut, $blStart, $blEnd - $blStart);
        $loginFields = '';
        if (preg_match('~class=wnd_inner>(.*?</table>)~s', $blChunk, $m)) {
            $loginFields = $m[1];
        }
        $forgotText = '';
        if (preg_match('~href="ds/remaind_pass\.php" class=wnd_gen_txt>(.*?)</td>~s', $blChunk, $m)) {
            $forgotText = $m[1];
        }
        $bannerContent = '';
        if (preg_match('~class=wnd_inner align=center>(.*?)<td width=4 background="img/border/02/r\.gif">~s', $blChunk, $m)) {
            $bannerContent = $m[1];
        }
        if ($loginFields !== '' && $forgotText !== '' && $bannerContent !== '') {
            $cardStyle = 'background:#F7F8FA;border:1px solid #B5BBC4;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,0.15);overflow:hidden;';
            $newBlock = '<!--banner & login wnd begin-->'
                . '<tr height=22><td align=center class=wnd_gen_txt><table width=100% border=0 cellpadding=0 cellspacing=0><tr>'
                . '<td width=187 valign=top><div style="' . $cardStyle . '">'
                . '<div style="text-align:center;padding:6px 0 4px;"><img src="img/graph/label_enter_in_game.gif" border=0></div>'
                . '<form action="ds/index_login.php" method="POST" name=login_form>'
                . '<div style="padding:0 10px 6px;">' . $loginFields . '</div>'
                . '</form>'
                . '<div style="padding:0 10px 8px;"><a href="ds/remaind_pass.php" class=wnd_gen_txt>' . $forgotText . '</a></div>'
                . '</div></td>'
                . '<td align=center valign=middle><div style="width:728px;padding:4px;box-sizing:border-box;' . $cardStyle . '">' . $bannerContent . '</div></td>'
                . '</tr></table></td></tr>';
            $strOut = substr($strOut, 0, $blStart) . $newBlock . substr($strOut, $blEnd);
        }
    }
    $menuBoxStart = strpos($strOut, '<!-- DS_UNLIM button end -->');
    $premiumStart = strpos($strOut, '<!-- premium button begin -->');
    $premiumEnd = strpos($strOut, '<!-- premium button end -->');
    if ($menuBoxStart !== false && $premiumStart !== false && $premiumEnd !== false) {
        $menuBoxStart += strlen('<!-- DS_UNLIM button end -->');
        $premiumEnd += strlen('<!-- premium button end -->');
        $boxChunk = substr($strOut, $menuBoxStart, $premiumStart - $menuBoxStart);
        $premiumHtml = substr($strOut, $premiumStart, $premiumEnd - $premiumStart);
        $forumLabel = '';
        if (preg_match('~href="http://forum\.destinysphere\.ru/"[^>]*>(.*?)</a>~s', $boxChunk, $m)) {
            $forumLabel = $m[1];
        }
        $rankingLabel = '';
        if (preg_match('~href="http://destinysphere\.ru/ds/ranking_new\.php"[^>]*>(.*?)</a>~s', $boxChunk, $m)) {
            $rankingLabel = $m[1];
        }
        $billingLabel = '';
        if (preg_match('~href="https://billing\.destinysphere\.ru"[^>]*>(.*?)</a>~s', $boxChunk, $m)) {
            $billingLabel = $m[1];
        }
        if ($forumLabel !== '' && $rankingLabel !== '' && $billingLabel !== '') {
            $linkStyle = 'display:block;padding:7px 10px;border-bottom:1px solid rgba(255,255,255,0.18);';
            $lastLinkStyle = 'display:block;padding:7px 10px;';
            $newBox = $premiumHtml
                . '<tr><td colspan=3><div style="background:linear-gradient(180deg,#4B6498 0%,#2B3C6F 55%,#1A2340 100%);border:1px solid #7A8AAC;border-radius:6px;box-shadow:0 1px 2px rgba(0,0,0,0.2);overflow:hidden;">'
                . '<a href="https://forum.destinysphere.ru/" target=_blank class=menu_txt_left style="' . $linkStyle . '">' . $forumLabel . '</a>'
                . '<a href="https://destinysphere.ru/ds/ranking.php" target=_blank class=menu_txt_left style="' . $linkStyle . '">Рейтинг</a>'
                . '<a href="https://destinysphere.ru/ds/ranking_new.php" target=_blank class=menu_txt_left style="' . $linkStyle . '">Новый рейтинг</a>'
                . '<a href="https://billing.destinysphere.ru" target=_blank class=menu_txt_left style="' . $lastLinkStyle . '">' . $billingLabel . '</a>'
                . '</div></td></tr>';
            $strOut = substr($strOut, 0, $menuBoxStart) . $newBox . substr($strOut, $premiumEnd);
        }
    }
    $strOut = str_replace('background="img/background/buttons_bg.gif" align=center class=wnd_gen_txt>', 'style="background:linear-gradient(180deg,#E8E7ED 0%,#131C39 50%,#E8E7ED 100%);" align=center class=wnd_gen_txt>', $strOut);
    $strOut = str_replace('http://game17.destinysphere.ru/http://game7.destinysphere.ru/http://game3.destinysphere.ru/http://game5.destinysphere.ru//img/elements/icon_minipost.gif', 'img/elements/icon_minipost.gif', $strOut);
    $strOut = str_replace('<script src="http://images.rambler.ru/patch_script.js"></script>', '', $strOut);
    $strOut = preg_replace('~<!-- begin of Top100 code -->.*?<!-- end of Top100 code-->~s', '', $strOut);
    $strOut = preg_replace('~<!-- Rating@Mail.ru counter -->.*?<!-- //Rating@Mail.ru counter -->~s', '', $strOut);
    // убираем юр./факт. адрес со страницы ?p=about
    $strOut = str_replace(
        'Юр. адрес: 197341, Фермское шоссе, 36-7-118.<br/>
            г. Санкт-Петербург<br/>
            Россия<br/>
            Факт. адрес: 197101, ул. Мира, 16, оф. 20.<br/>
            г. Санкт-Петербург<br/>
            Россия<br/>
',
        '',
        $strOut
    );
    $strOut = str_replace("http://forum.destinysphere.ru/viewforum.php?f=2", "https://forum.destinysphere.ru/viewforum.php?f=2", $strOut);
    $strOut = str_replace("http://forum.destinysphere.ru/viewforum.php?f=776", "https://forum.destinysphere.ru/viewforum.php?f=776", $strOut);
    $strOut = str_replace("http://destinysphere.ru/ds/tree.html", "https://destinysphere.ru/ds/tree.html", $strOut);
    $strOut = str_replace("http://forum.destinysphere.ru/search.php", "https://forum.destinysphere.ru/search.php", $strOut);
    $strOut = str_replace("http://forum.destinysphere.ru/index.php", "https://forum.destinysphere.ru/index.php", $strOut);
    $strOut = str_replace('mm_menu_42.addMenuItem("ICQ-Консультант","location=\'?p=icq_helper\'");', '', $strOut);
    $strOut = str_replace('ФЕНИКС', 'ПАРУСА', $strOut);
    $strOut = str_replace('ЛЕВ', 'КЕНТАВР', $strOut);
    $strOut = str_replace('<tr>
            <td width="22%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Особенности мира:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">премиум-мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">экспериментальный мир&nbsp; &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">мир для новичков&nbsp; </font><font size="2"><br />
            </font></td>
        ', '<tr>
            <td width="22%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Особенности мира:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">премиум-мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">заморожен &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">экспериментальный мир&nbsp; &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">мир для новичков&nbsp; </font><font size="2"><br />
            </font></td>
        ', $strOut);
    $strOut = str_replace('<tr>
            <td width="22%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Тип игры:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">каждый&nbsp; против&nbsp; каждого</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;</font><font size="2">каждый&nbsp; против&nbsp; каждого</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">каждый&nbsp; против&nbsp; каждого</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">каждый&nbsp; против&nbsp; каждого</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">каждый&nbsp; против&nbsp; каждого</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">каждый&nbsp; против&nbsp; каждого</font></td>
        ', '<tr>
            <td width="22%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Тип игры:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">каждый&nbsp; против&nbsp; каждого</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;</font><font size="2">каждый&nbsp; против&nbsp; каждого</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">раса&nbsp; против&nbsp; расы</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">каждый&nbsp; против&nbsp; каждого</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">каждый&nbsp; против&nbsp; каждого</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">каждый&nbsp; против&nbsp; каждого</font></td>
        ', $strOut);
    $strOut = str_replace('<tr>
            <td width="35%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Тип игры:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
        ', '<tr>
            <td width="35%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Тип игры:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;каждый против каждого</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
        ', $strOut);
    $strOut = str_replace('<tr>
            <td width="35%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Особенности мира:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">мир для новичков &nbsp;</font></td>
        ', '<tr>
            <td width="35%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Особенности мира:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">регистрация закрыта &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">мир для новичков &nbsp;</font></td>
        ', $strOut);
    $strOut = str_replace('<tr>
            <td bgcolor="#ffffff" style="vertical-align: top; font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Вращение колец включено:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font><font size="2">       </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">         <font size="2">нет</font></td>
        ', '<tr>
            <td bgcolor="#ffffff" style="vertical-align: top; font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Вращение колец включено:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font><font size="2">       </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">         <font size="2">нет</font></td>
        ', $strOut);
    $strOut = str_replace('<tr>
            <td bgcolor="#ffffff" style="vertical-align: top; font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Шпионаж включен:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font><font size="2">       </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font><font size="2"></font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">         <font size="2">нет</font></td>
        ', '<tr>
            <td bgcolor="#ffffff" style="vertical-align: top; font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Шпионаж включен:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font><font size="2">       </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font><font size="2"></font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">         <font size="2">нет</font></td>
        ', $strOut);
    $strOut = str_replace('<table width="85%" cellspacing="0" cellpadding="0" border="1" align="center" style="border-collapse: collapse;">
    <tbody>
        <tr>
            <td width="35%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Название Сферы:<br />
            </font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center; font-weight: bold;" bordercolor="#999999"><font size="2"><br />
            ЭРИДАН<br />
            </font><font size="2"><img vspace="5" hspace="5" align="middle" alt="" src="http://ds.rambler.ru/ds/images/world_logo/eridan_1.gif" /></font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center; font-weight: bold;" bordercolor="#999999"><font size="2">&nbsp;<br />
            ПЕГАС<br />
            </font><font size="2"><img vspace="5" hspace="5" align="middle" alt="" src="http://ds.rambler.ru/ds/images/world_logo/pegassus_1.gif" /></font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center; font-weight: bold;" bordercolor="#999999"><font size="2"><br />
            ВОДОЛЕЙ<br />
            </font><font size="2"><img vspace="5" hspace="5" align="middle" alt="" src="http://ds.rambler.ru/ds/images/world_logo/vodolei_2.gif" /></font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center; font-weight: bold;" bordercolor="#999999"><font size="2"><br />
            КЕНТАВР<br />
            </font><font size="2"><img vspace="5" hspace="5" align="middle" alt="" src="http://ds.rambler.ru/ds/images/world_logo/lion_2.gif" /></font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center; font-weight: bold;" bordercolor="#999999"><font size="2"><br />
            ТЕРРА НОВА<br />
            </font><font size="2"><img vspace="5" hspace="5" align="middle" alt="" src="http://ds.rambler.ru/ds/images/world_logo/priemka_6.gif" /></font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Тип игры:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;каждый против каждого</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">День рождения мира:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;13.07.2004</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;27.10.2004</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">26.04.2005&nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">26.09.2006&nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;3.04.2007</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Особенности мира:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">регистрация закрыта &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">мир для новичков &nbsp;</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Уровень развития мира (от 1 до 4):</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">IV</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">III</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">III</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">II</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">I</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Максимальное количество игроков в альянсе:<br />
            </font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">&nbsp;<font size="2">200<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">100</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">175</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">175</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">50</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Максимальное количество колоний в мире:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">4</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">3</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">2</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">2</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">1</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Размер средней армии в продиуме игрока ТОП-100 мира:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">22 682 291 150&nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">5 331 330 871&nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">8 358 699 565&nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">3 853 891 509&nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">62 138 046&nbsp;</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Количество комплексов на Ворании <br />
            </font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">360              </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">360 <br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">320</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">500</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">418 <br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Количество комплексов на </font><font size="2">Лиенсорде</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">360<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">360</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">330</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">520 <br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">418</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Количество комплексов на Псолеоне<br />
            </font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">360</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">360</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">321</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">500 <br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">418</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Количество комплексов на Мельсионе<br />
            </font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">800</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">760</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">630</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">1000 <br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">901</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Одновременное количество строящихся зданий на соте:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">7<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">7<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">7<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">5<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">11<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Время игрового хода в минутах:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">60<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">60<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">60<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">60<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">30<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Бонус на производство ресурсов премиум-игрокам:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">5%<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">5%<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">10%<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">10%<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">10%<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Вращение колец включено:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Коэффициент расчета стоимости проживания юнитов на соте:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000007500<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000008000</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000007500</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000100000</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000010000</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Коэффициент для расчета стоимости движения юнитов (во время боя):</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000050000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000050000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000050000</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000100000</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000050000</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Коэффициент для расчета стоимости не военного перемещения юнитов (торговля, перенос на колонии):</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000010000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000010000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000010000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000100000</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000010000</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Разрешено ли отступление в мире:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Сколько можно одновременно отправить армий с соты:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">200<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">150<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">150<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">100<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">200<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Максимальное количество армий, которое разрешено создавать игроку на одной соте:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">300<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">200<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">250<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">150<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">200<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">&quot;Голые сумки&quot;&nbsp; могут выиграть бой по весу:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Шпионаж включен:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Общее максимальное количество зондов на соте:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">1000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">1000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">1000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">1000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">1000<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Количество зондов за каждый уровень науки:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">20<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">20<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">20<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">20<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">20<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Торговля за серебро:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Возможность торговать в мире раса против расы на межкольце артефактами:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Возможность торговать в мире раса против расы на межкольце ресурсами:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Возможность торговать в мире раса против расы на межкольце юнитами:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Включение персональных торгов:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Можно ли выставить лот анонимно:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Включение налогов на торгах:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Ограничение на максимальное количество выставляемых лотов:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Ограбление соты даже если в атакующей армии не осталось юнитов способных наносить урон:</font><br />
            </div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">                          <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">             Налог на грабеж:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Максимально время работы силового щита в игровых ходах:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">150<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">150<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">150<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">150<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">300<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Минимальное время отпуска в игровых ходах:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">72<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">72<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">72<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">72<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">144<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Сколько дней после открытия ворот будет действовать защита новичков:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">21<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">21<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">21<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">28<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">28<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Максимальная сила армии (кол-во звезд) после которой снимается защита с новичков:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">7<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">9<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">7<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">7<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">6<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Бонус локальных артефактов:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">&nbsp;да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">да</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Бесплатный переезд по домашнему кольцу разрешен:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Через сколько дней можно повторно использовать бесплатный переезд по кольцу:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">90<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">90<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">90<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">60<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">60<br />
            </font></td>
        </tr>
        <tr>
            <td width="35%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Из мира возможен переезд в миры:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center; font-style: italic;"><font size="2">Черная Дыра<br />
            Змееносец</font>             </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center; font-style: italic;"><font size="2">Черная Дыра<br />
            Феникс<br />
            Эридан<br />
            Орион<br />
            Змееносец</font>             </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center; font-style: italic;"><font size="2">Черная Дыра<br />
            Пегас<br />
            Ворон<br />
            Феникс<br />
            Эридан<br />
            Орион<br />
            Змееносец</font>             </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center; font-style: italic;"><font size="2">Черная Дыра<br />
            Пегас<br />
            Водолей<br />
            Ворон<br />
            Феникс<br />
            Эридан<br />
            Орион<br />
            Змееносец</font>             </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center; font-style: italic;"><font size="2">Черная Дыра<br />
            Лев<br />
            Волк<br />
            Пегас<br />
            Водолей<br />
            Ворон<br />
            Феникс<br />
            Эридан<br />
            Орион<br />
            Змееносец</font>             </td>
        </tr>
    </tbody>
', '<table width="85%" cellspacing="0" cellpadding="0" border="1" align="center" style="border-collapse: collapse;">
    <tbody>
        <tr>
            <td width="22%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Название Сферы:<br />
            </font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center; font-weight: bold;" bordercolor="#999999"><font size="2"><br />
            ЭРИДАН<br />
            </font><font size="2"><img vspace="5" hspace="5" align="middle" alt="" src="http://ds.rambler.ru/ds/images/world_logo/eridan_1.gif" /></font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center; font-weight: bold;" bordercolor="#999999"><font size="2">&nbsp;<br />
            ПЕГАС<br />
            </font><font size="2"><img vspace="5" hspace="5" align="middle" alt="" src="http://ds.rambler.ru/ds/images/world_logo/pegassus_1.gif" /></font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center; font-weight: bold;" bordercolor="#999999"><font size="2"><br />
            ВОДОЛЕЙ<br />
            </font><font size="2"><img vspace="5" hspace="5" align="middle" alt="" src="http://ds.rambler.ru/ds/images/world_logo/vodolei_2.gif" /></font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center; font-weight: bold;" bordercolor="#999999"><font size="2"><br />
            КЕНТАВР<br />
            </font><font size="2"><img vspace="5" hspace="5" align="middle" alt="" src="http://ds.rambler.ru/ds/images/world_logo/lion_2.gif" /></font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center; font-weight: bold;" bordercolor="#999999"><font size="2"><br />
            ТЕРРА НОВА<br />
            </font><font size="2"><img vspace="5" hspace="5" align="middle" alt="" src="http://ds.rambler.ru/ds/images/world_logo/priemka_6.gif" /></font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center; font-weight: bold;" bordercolor="#999999"><font size="2"><br />
            СИРИУС<br />
            </font><font size="2"><img vspace="5" hspace="5" align="middle" alt="" src="http://ds.rambler.ru/ds/images/world_logo/edinorog_15.gif" /></font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Тип игры:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;каждый против каждого</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;раса против расы</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;каждый против каждого</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">День рождения мира:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;13.07.2004</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;27.10.2004</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">26.04.2005&nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">26.09.2006&nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&nbsp;3.04.2007</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">&mdash;</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" style="font-style: italic;" bordercolor="#999999">
            <div style="margin-left: 40px;"><font size="2">Особенности мира:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">регистрация закрыта &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">мир для новичков &nbsp;</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" style="text-align: center;" bordercolor="#999999"><font size="2">обычный мир &nbsp;</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Уровень развития мира (от 1 до 4):</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">IV</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">III</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">III</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">II</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">I</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">&mdash;</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Максимальное количество игроков в альянсе:<br />
            </font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">&nbsp;<font size="2">200<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">100</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">175</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">175</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">50</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">50</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Максимальное количество колоний в мире:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">4</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">3</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">2</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">2</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">1</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">2</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Размер средней армии в продиуме игрока ТОП-100 мира:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">22 682 291 150&nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">5 331 330 871&nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">8 358 699 565&nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">3 853 891 509&nbsp;</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">62 138 046&nbsp;</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">&mdash;</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Количество комплексов на Ворании <br />
            </font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">360              </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">360 <br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">320</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">500</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">418 <br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">&mdash;</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Количество комплексов на </font><font size="2">Лиенсорде</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">360<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">360</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">330</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">520 <br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">418</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">&mdash;</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Количество комплексов на Псолеоне<br />
            </font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">360</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">360</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">321</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">500 <br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">418</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">&mdash;</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Количество комплексов на Мельсионе<br />
            </font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">800</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">760</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">630</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">1000 <br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">901</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">&mdash;</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Одновременное количество строящихся зданий на соте:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">7<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">7<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">7<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">5<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">11<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">&mdash;</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Время игрового хода в минутах:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">60<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">60<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">60<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">60<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">30<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">10<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Бонус на производство ресурсов премиум-игрокам:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">5%<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">5%<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">10%<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">10%<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">10%<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">&asymp;55%<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Вращение колец включено:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Коэффициент расчета стоимости проживания юнитов на соте:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000007500<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000008000</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000007500</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000100000</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000010000</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000000000</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Коэффициент для расчета стоимости движения юнитов (во время боя):</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000050000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000050000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000050000</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000100000</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000050000</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000005000</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Коэффициент для расчета стоимости не военного перемещения юнитов (торговля, перенос на колонии):</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000010000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000010000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000010000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000100000</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000010000</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">0.0000003300</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Разрешено ли отступление в мире:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Сколько можно одновременно отправить армий с соты:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">200<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">150<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">150<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">100<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">200<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">50<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Максимальное количество армий, которое разрешено создавать игроку на одной соте:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">300<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">200<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">250<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">150<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">200<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">100<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">&quot;Голые сумки&quot;&nbsp; могут выиграть бой по весу:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Шпионаж включен:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Общее максимальное количество зондов на соте:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">1000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">1000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">1000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">1000<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">1000<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">2000<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Количество зондов за каждый уровень науки:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">20<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">20<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">20<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">20<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">20<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">20<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Торговля за серебро:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Возможность торговать в мире раса против расы на межкольце артефактами:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Возможность торговать в мире раса против расы на межкольце ресурсами:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Возможность торговать в мире раса против расы на межкольце юнитами:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Включение персональных торгов:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Можно ли выставить лот анонимно:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Включение налогов на торгах:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Ограничение на максимальное количество выставляемых лотов:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">600</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Ограбление соты даже если в атакующей армии не осталось юнитов способных наносить урон:</font><br />
            </div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">                          <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">             Налог на грабеж:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">нет</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">&asymp;5%</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Максимально время работы силового щита в игровых ходах:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">150<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">150<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">150<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">150<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">300<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">288<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Минимальное время отпуска в игровых ходах:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">72<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">72<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">72<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">72<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">144<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">288<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Сколько дней после открытия ворот будет действовать защита новичков:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">21<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">21<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">21<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">28<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">28<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">&asymp;84<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Максимальная сила армии (кол-во звезд) после которой снимается защита с новичков:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">7<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">9<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">7<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">7<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">6<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">8<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Бонус локальных артефактов:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">&nbsp;да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">да</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;"><font size="2">нет</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Бесплатный переезд по домашнему кольцу разрешен:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">да</font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Через сколько дней можно повторно использовать бесплатный переезд по кольцу:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">90<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">90<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">90<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">60<br />
            </font></td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">60<br />
            </font></td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center;">             <font size="2">90<br />
            </font></td>
        </tr>
        <tr>
            <td width="22%" bgcolor="#ffffff" bordercolor="#999999" style="vertical-align: top; font-style: italic;">
            <div style="margin-left: 40px;"><font size="2">Из мира возможен переезд в миры:</font></div>
            </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center; font-style: italic;"><font size="2">Черная Дыра<br />
            Змееносец</font>             </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center; font-style: italic;"><font size="2">Черная Дыра<br />
            Феникс<br />
            Эридан<br />
            Орион<br />
            Змееносец</font>             </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center; font-style: italic;"><font size="2">Черная Дыра<br />
            Пегас<br />
            Ворон<br />
            Феникс<br />
            Эридан<br />
            Орион<br />
            Змееносец</font>             </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center; font-style: italic;"><font size="2">Черная Дыра<br />
            Пегас<br />
            Водолей<br />
            Ворон<br />
            Феникс<br />
            Эридан<br />
            Орион<br />
            Змееносец</font>             </td>
            <td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center; font-style: italic;"><font size="2">Черная Дыра<br />
            Лев<br />
            Волк<br />
            Пегас<br />
            Водолей<br />
            Ворон<br />
            Феникс<br />
            Эридан<br />
            Орион<br />
            Змееносец</font>             </td><td width="13%" valign="middle" bgcolor="#ffffff" align="center" bordercolor="#999999" style="vertical-align: top; text-align: center; font-style: italic;"><font size="2">&mdash;</font>             </td>
        </tr>
    </tbody>
', $strOut);
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