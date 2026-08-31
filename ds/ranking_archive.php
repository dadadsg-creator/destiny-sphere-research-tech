<?
ini_set('memory_limit', '256M');
require_once ( 'Main.php' );
SU_GameSessionWork::SessionContinue();

require_once 'include/Smarty/Smarty.class.php';
require_once('include/GameDown.inc.php');

include ("language/".constant('DEFAULT_LANGUAGE_SHORT')."/ranking.lang");
include ("ranking_cmp_func.php");


if (GameDown())
{
    Header("Status: 302 Moved Temporaly");
    Header("Location: index_down.html");
    exit();
}
$curYear = date('Y');
$months = array('января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря');
$titles = array(
    'total' => 'Рейтинг',
    'totalDelta' => 'Изменение рейтинга',
    'totalplace' => 'Место',
    'totalplaceDelta' => 'Изменение места',
    'science' => 'Наука',
    'scienceDelta' => 'Изменение науки',
    'scienceplace' => 'Место по науке',
    'scienceplaceDelta' => 'Изменение места по науке',
    'arch' => 'Археология',
    'archDelta' => 'Изменение археологии',
    'archplace' => 'Место по археологии',
    'archplaceDelta' => 'Изменение места по археологии',
    'build' => 'Строительство',
    'buildDelta' => 'Изменение строительства',
    'buildplace' => 'Место по строительству',
    'buildplaceDelta' => 'Изменение места по строительству',
    'battle' => 'Боевой рейтинг',
    'battleDelta' => 'Изменение боевого рейтинга',
    'battleplace' => 'Место по боевому рейтингу',
    'battleplaceDelta' => 'Изменение места по боевому рейтингу',
    'npc' => 'NPC рейтинг',
    'npcDelta' => 'Изменение NPC рейтинга',
    'npcplace' => 'Место по NPC рейтингу',
    'npcplaceDelta' => 'Изменение места по NPC рейтингу'
);

$common = DS_Common::CreateForCurrentDB(0);
$sql = "select dbname, worldname from ds_worldcollection";
$worlds = $common->getResultArray( "", $sql, "", "", "");
$shortQuery = '?world='.$_REQUEST['world'].'&day1='.$_REQUEST['day1'].'&month1='.$_REQUEST['month1'].'&year1='.$_REQUEST['year1'].'&day2='.$_REQUEST['day2'].'&month2='.$_REQUEST['month2'].'&year2='.$_REQUEST['year2'].'&limit='.$_REQUEST['limit'].'&limitSort='.$_REQUEST['limitSort'];
$date1 = sprintf("%04d-%02d-%02d", $_REQUEST['year1'], $_REQUEST['month1'], $_REQUEST['day1']);
$date2 = sprintf("%04d-%02d-%02d", $_REQUEST['year2'], $_REQUEST['month2'], $_REQUEST['day2']);
if (!$_REQUEST['sort']) {
    $_REQUEST['sort'] = 'total_desc';
}
$_REQUEST['limit'] = intval($_REQUEST['limit']);

foreach ($GLOBALS['dbWorldAccess'] as $dbID=>$db) {
    if ($db['server']==$_REQUEST['world']) {
        $common->setDbId($dbID);
        break;
    }
}
$players = array();
$sql = "select u.username, l.text as race,
    a.date, a.total, a.level, a.science, a.archeology as arch, a.building as build, a.battle, a.npc,
    p.total as totalplace, p.science as scienceplace, p.archeology as archplace, p.building as buildplace, p.battle as battleplace, p.npc as npcplace
    from ds_user u, ds_ranking_archive a, ds_ranking_archive_places p, ds_race rac, ds_localizedtextlanguage l, ds_userinstance ui
    where u.id_user=a.id_user and a.id_user=p.id_user and a.date=p.date and a.date='$date1'
    and ui.id_user=u.id_user and rac.id_race=ui.id_race and l.id_localizedtext = rac.id_localizedname and l.id_language = 3";
$players = $common->getResultArray( "", $sql, "", "", "");

$sql = "select u.username,
    a.date, a.total, a.level, a.science, a.archeology as arch, a.building as build, a.battle, a.npc,
    p.total as totalplace, p.science as scienceplace, p.archeology as archplace, p.building as buildplace, p.battle as battleplace, p.npc as npcplace
    from ds_user u, ds_ranking_archive a, ds_ranking_archive_places p
    where u.id_user=a.id_user and a.id_user=p.id_user and a.date=p.date and a.date='$date2'";
if ($_REQUEST['limit']) $sql .= "order by a.".$_REQUEST['limitSort']." desc limit ".$_REQUEST['limit'];
$res = $common->getResultArray( "", $sql, "", "", "");
foreach ($res as $row)
    $old[$row['username']] = $row;
unset($res);

if (count($old)) {
    $toDel = array();
    foreach ($players as $key=>$p) {
        if (isset($old[$p['username']])) {
            $players[$key]['totalDelta'] = $p['total'] - $old[$p['username']]['total'];
            $players[$key]['totalplaceDelta'] = $p['totalplace'] - $old[$p['username']]['totalplace'];
            $players[$key]['scienceDelta'] = $p['science'] - $old[$p['username']]['science'];
            $players[$key]['scienceplaceDelta'] = $p['scienceplace'] - $old[$p['username']]['scienceplace'];
            $players[$key]['archDelta'] = $p['arch'] - $old[$p['username']]['arch'];
            $players[$key]['archplaceDelta'] = $p['archplace'] - $old[$p['username']]['archplace'];
            $players[$key]['buildDelta'] = $p['build'] - $old[$p['username']]['build'];
            $players[$key]['buildplaceDelta'] = $p['buildplace'] - $old[$p['username']]['buildplace'];
            $players[$key]['battleDelta'] = $p['battle'] - $old[$p['username']]['battle'];
            $players[$key]['battleplaceDelta'] = $p['battleplace'] - $old[$p['username']]['battleplace'];
            $players[$key]['npcDelta'] = $p['npc'] - $old[$p['username']]['npc'];
            $players[$key]['npcplaceDelta'] = $p['npcplace'] - $old[$p['username']]['npcplace'];
        } else $toDel[] = $key;
    }
    foreach ($toDel as $key) unset($players[$key]);
    unset($toDel);
}
unset($old);
usort ($players, "sort_".$_REQUEST['sort']);


// =====================================================================
?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<meta http-equiv="Content-Style-Type" content="text/css">
<title>DestinySphere :: Ranking Archive</title>
<link rel="stylesheet" href="../dsstyle.css" type="text/css">
</head>
<body>

<table height="86" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td width="500">
            <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="500" height="86" id="header_1" align="middle">
                <param name="allowScriptAccess" value="sameDomain">
                <param name="movie" value="../img/flash/header.swf">
                <param name="quality" value="high">
                <param name="menu" value="false">
                <param name="settings" value="false">
                <param name="wmode" value="opaque">
                <param name="bgcolor" value="#ffffff">
                <embed src="../img/flash/header.swf" quality="high" wmode="opaque" bgcolor="#ffffff" width="500" height="86" name="header_1" align="middle" allowscriptaccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">
            </object></td>
        <td width="265"><img src="../img/background/header_bg.jpg" border="0"></td>
        <td width="100%" background="../img/background/header_bg2.gif"></td>  
    </tr>
</table>

<div style="margin:10px">
<form method="get">
<table><tr>
    <td>Мир:</td>
    <td><select name="world" class="select_gen">
    <? foreach ($worlds as $w) { ?>
        <option value="<?=$w['dbname']?>"<?=$w['dbname']==$_REQUEST['world']?' selected':''?>><?=$w['worldname']?></option>
    <? } ?>
    </select></td>
</tr><tr>
    <td>Дата 1 (новая, актуальная):</td>
    <td><select name="day1" class="select_gen">
    <? for ($day=1; $day<=31; $day++) { ?>
        <option value="<?=$day?>"<?=$day==$_REQUEST['day1']?' selected':''?>><?=$day?></option>
    <? } ?>
    </select>
    <select name="month1" class="select_gen">
    <? for ($month=1; $month<=12; $month++) { ?>
        <option value="<?=$month?>"<?=$month==$_REQUEST['month1']?' selected':''?>><?=$months[$month-1]?></option>
    <? } ?>
    </select>
    <select name="year1" class="select_gen">
    <? for ($year=2012; $year<=$curYear; $year++) { ?>
        <option value="<?=$year?>"<?=$year==$_REQUEST['year1']?' selected':''?>><?=$year?></option>
    <? } ?>
    </select></td>
</tr><tr>
    <td>Дата 2 (старая, для сравнения):</td>
    <td><select name="day2" class="select_gen">
    <? for ($day=1; $day<=31; $day++) { ?>
        <option value="<?=$day?>"<?=$day==$_REQUEST['day2']?' selected':''?>><?=$day?></option>
    <? } ?>
    </select>
    <select name="month2" class="select_gen">
    <? for ($month=1; $month<=12; $month++) { ?>
        <option value="<?=$month?>"<?=$month==$_REQUEST['month2']?' selected':''?>><?=$months[$month-1]?></option>
    <? } ?>
    </select>
    <select name="year2" class="select_gen">
    <? for ($year=2012; $year<=$curYear; $year++) { ?>
        <option value="<?=$year?>"<?=$year==$_REQUEST['year2']?' selected':''?>><?=$year?></option>
    <? } ?>
    </select></td>
</tr>
<tr><td colspan="2">
Кол-во игроков (Top на начало замеров):
<input name="limit" value="<?=$_REQUEST['limit']?>" style="width: 40px">
</td></tr>
<tr><td colspan="2">
По какому рейтингу брать этот Top игроков:
<select name="limitSort">
    <option value="total"<?=$_REQUEST['limitSort']=='total'?' selected':''?>>Общий</option>
    <option value="science"<?=$_REQUEST['limitSort']=='science'?' selected':''?>>Наука</option>
    <option value="archeology"<?=$_REQUEST['limitSort']=='archeology'?' selected':''?>>Археология</option>
    <option value="building"<?=$_REQUEST['limitSort']=='building'?' selected':''?>>Строительство</option>
    <option value="battle"<?=$_REQUEST['limitSort']=='battle'?' selected':''?>>Боевой</option>
    <option value="npc"<?=$_REQUEST['limitSort']=='npc'?' selected':''?>>NPC</option>
</td></tr>
</table>

<input type="hidden" name="sort" value="<?=$_REQUEST['sort']?>">
<input type="hidden" name="dir" value="<?=$_REQUEST['dir']?>">
<input type="submit" class="input_button" value="Показать">
</form>


<table class="ranking_table">
    <th style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; background-color: #364A7A; color: #FDD600; font-weight: bold">Имя</th>
    <th style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; background-color: #364A7A; color: #FDD600; font-weight: bold">Раса</th>
    <? foreach ($titles as $key=>$val) { ?>
    <th style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; background-color: #364A7A; color: #FDD600; font-weight: bold" valign="bottom">
        <?=$val?><br>
        <a style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; background-color: #364A7A; color: #FDD600; font-weight: bold; text-decoration:none" href="<?=$shortQuery.'&sort='.$key.'_desc'?>">&darr;</a>
        <a style="font-family: Arial, Helvetica, sans-serif; font-size: 12px; background-color: #364A7A; color: #FDD600; font-weight: bold; text-decoration:none" href="<?=$shortQuery.'&sort='.$key.'_asc'?>">&uarr;</a>
    </th>
    <? } ?>

<? $i = 2;
   foreach ($players as $p) {
      $i = 3 - $i;
?>
<tr class="ranking_table_0<?=$i?>">
    <td class="wnd_gen_ranking_large"><?=$p['username']?></td>
    <td class="wnd_gen_ranking_large"><?=$p['race']?></td>
    <td class="wnd_gen_ranking_large">
        <?=$p['total']?>
    </td>
    <td class="wnd_gen_ranking_large">
        <? if ($p['totalDelta'] > 0) { ?>
            <b style="color:green">+<?=$p['totalDelta']?></b>
        <? } else if ($p['totalDelta'] < 0) { ?>
            <b style="color:red"><?=$p['totalDelta']?></b>
        <? } ?>
    </td>
    <td class="wnd_gen_ranking_large">
        <?=$p['totalplace']?>
    </td>
    <td class="wnd_gen_ranking_large">
        <? if ($p['totalplaceDelta'] > 0) { ?>
            <b style="color:red">&darr;<?=$p['totalplaceDelta']?></b>
        <? } else if ($p['totalplaceDelta'] < 0) { ?>
            <b style="color:green">&uarr;<?=-$p['totalplaceDelta']?></b>
        <? } ?>
    </td>
    <td class="wnd_gen_ranking_large">
        <?=$p['science']?>
    </td>
    <td class="wnd_gen_ranking_large">
        <? if ($p['scienceDelta'] > 0) { ?>
            <b style="color:green">+<?=$p['scienceDelta']?></b>
        <? } else if ($p['scienceDelta'] < 0) { ?>
            <b style="color:red"><?=$p['scienceDelta']?></b>
        <? } ?>
    </td>
    <td class="wnd_gen_ranking_large">
        <?=$p['scienceplace']?>
    </td>
    <td class="wnd_gen_ranking_large">
        <? if ($p['scienceplaceDelta'] > 0) { ?>
            <b style="color:red">&darr;<?=$p['scienceplaceDelta']?></b>
        <? } else if ($p['scienceplaceDelta'] < 0) { ?>
            <b style="color:green">&uarr;<?=-$p['scienceplaceDelta']?></b>
        <? } ?>
    </td>
    <td class="wnd_gen_ranking_large">
        <?=$p['arch']?>
    </td>
    <td class="wnd_gen_ranking_large">
        <? if ($p['archDelta'] > 0) { ?>
            <b style="color:green">+<?=$p['archDelta']?></b>
        <? } else if ($p['archDelta'] < 0) { ?>
            <b style="color:red"><?=$p['archDelta']?></b>
        <? } ?>
    </td>
    <td class="wnd_gen_ranking_large">
        <?=$p['archplace']?>
    </td>
    <td class="wnd_gen_ranking_large">
        <? if ($p['archplaceDelta'] > 0) { ?>
            <b style="color:red">&darr;<?=$p['archplaceDelta']?></b>
        <? } else if ($p['archplaceDelta'] < 0) { ?>
            <b style="color:green">&uarr;<?=-$p['archplaceDelta']?></b>
        <? } ?>
    </td>
    <td class="wnd_gen_ranking_large">
        <?=$p['build']?>
    </td>
    <td class="wnd_gen_ranking_large">
        <? if ($p['buildDelta'] > 0) { ?>
            <b style="color:green">+<?=$p['buildDelta']?></b>
        <? } else if ($p['buildDelta'] < 0) { ?>
            <b style="color:red"><?=$p['buildDelta']?></b>
        <? } ?>
    </td>
    <td class="wnd_gen_ranking_large">
        <?=$p['buildplace']?>
    </td>
    <td class="wnd_gen_ranking_large">
        <? if ($p['buildplaceDelta'] > 0) { ?>
            <b style="color:red">&darr;<?=$p['buildplaceDelta']?></b>
        <? } else if ($p['buildplaceDelta'] < 0) { ?>
            <b style="color:green">&uarr;<?=-$p['buildplaceDelta']?></b>
        <? } ?>
    </td>
    <td class="wnd_gen_ranking_large">
        <?printf("%.1f", $p['battle'])?>
    </td>
    <td class="wnd_gen_ranking_large">
        <? if ($p['battleDelta'] > 0) { ?>
            <b style="color:green">+<?=printf("%.1f", $p['battleDelta'])?></b>
        <? } else if ($p['battleDelta'] < 0) { ?>
            <b style="color:red"><?=printf("%.1f", $p['battleDelta'])?></b>
        <? } ?>
    </td>
    <td class="wnd_gen_ranking_large">
        <?=$p['battleplace']?>
    </td>
    <td class="wnd_gen_ranking_large">
        <? if ($p['battleplaceDelta'] > 0) { ?>
            <b style="color:red">&darr;<?=$p['battleplaceDelta']?></b>
        <? } else if ($p['battleplaceDelta'] < 0) { ?>
            <b style="color:green">&uarr;<?=-$p['battleplaceDelta']?></b>
        <? } ?>
    </td>
    <td class="wnd_gen_ranking_large">
        <?printf("%.1f", $p['npc'])?>
    </td>
    <td class="wnd_gen_ranking_large">
        <? if ($p['npcDelta'] > 0) { ?>
            <b style="color:green">+<?=printf("%.1f", $p['npcDelta'])?></b>
        <? } else if ($p['npcDelta'] < 0) { ?>
            <b style="color:red"><?=printf("%.1f", $p['npcDelta'])?></b>
        <? } ?>
    </td>
    <td class="wnd_gen_ranking_large">
        <?=$p['npcplace']?>
    </td>
    <td class="wnd_gen_ranking_large">
        <? if ($p['npcplaceDelta'] > 0) { ?>
            <b style="color:red">&darr;<?=$p['npcplaceDelta']?></b>
        <? } else if ($p['npcplaceDelta'] < 0) { ?>
            <b style="color:green">&uarr;<?=-$p['npcplaceDelta']?></b>
        <? } ?>
    </td>
</tr>
<? } ?>
</table>
</div>
</body>
</html>