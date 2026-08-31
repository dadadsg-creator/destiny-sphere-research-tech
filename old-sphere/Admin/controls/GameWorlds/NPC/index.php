<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";
session_start();

$common = DS_Common::CreateForCurentDB(0);
$conds = array('1|1', '1|3','1|12','1|25', '1|50', '1|100','2|1', '2|2', '2|4','2|8', '2|12', '2|18','3|0','3|1','3|2','3|4','3|10','3|30','4|1','4|0','5|0','6|1','7|1','6|0','7|0');

$worlds = array();
$sql = "SELECT worldname, id_world, dbname FROM ds_worldcollection";
$res = $common->getResultArray("", $sql);
foreach ($res as $row) {
    $worlds[$row['dbname'].'_'.$row['id_world']] = $row['worldname'];
}

if ($_REQUEST['act']=="selectWorld") {
    $pos = strrpos($_POST['world'], '_');
    $server = substr($_POST['world'], 0, $pos);
    foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db) {
        if ($db['server']==$server) {
            $_SESSION['dbId'] = $dbId;
            break;
        }
    }
    $_SESSION['worldFull'] = $_POST['world'];
    $_SESSION['world'] = substr($_POST['world'], $pos+1);
}

if (isset($_SESSION['dbId'])) {
    $common->setDbId($_SESSION['dbId']);
    if ($_REQUEST['act'] == 'insertNewType') {
        $league = (int) $_POST['league'];
        $type = (int) $_POST['type'];
        $sql = "insert into ds_npc_army_settings (league, type) values ($league, $type)";
        $common->executeQuery($sql);
        foreach ($conds as $key) {
            $parts = explode("|", $key);
            $sql = "insert into ds_npc_attack_chance (league, type, condType, condValue, probability) values ($league, $type, {$parts[0]}, {$parts[1]}, 0)";
            $common->executeQuery($sql);
        }
    }

    $sql = "select ui.id_userinstance, u.username, r.thefinal, r3.id_level, bx.value, ui.id_league, l.text as race, nt.type
    from ds_localizedtextlanguage l, ds_race r2, ds_userinstance ui
    left join ds_npchastype nt on nt.id_userinstance=ui.id_userinstance
    left join ds_battlexp bx on bx.id_userinstance = ui.id_userinstance
    left join ds_ranking r3 on r3.id_userinstance = ui.id_userinstance
    inner join ds_user u on u.id_user = ui.id_user
    left join ds_finalranking r on u.id_user = r.id_user
    where u.npc and ui.id_world={$_SESSION['world']}
    and r2.id_race = ui.id_race
    and l.id_localizedtext = r2.id_localizedname and l.id_language = 3";

    $npcList = $common->getResultArray("", $sql);

    $npcIds = array();
    foreach ($npcList as $npc) $npcIds[] = $npc['id_userinstance'];

    $coords = array();
    $sql = "SELECT co.id_userinstance, ri.ringnumber, cc.complexnumber, co.combnumber
        FROM ds_comb co, ds_combcomplex cc, ds_ring ri
        WHERE ri.id_ring = cc.id_ring
        AND cc.id_combcomplex = co.id_combcomplex
        AND co.id_userinstance in (".implode(',', $npcIds).")";
    $res = $common->getResultArray("", $sql);
    foreach ($res as $row)
        $coords[$row['id_userinstance']][] = $row['ringnumber'].'.'.$row['complexnumber'].'.'.$row['combnumber'];
    for ($i=0; $i < count($npcList); $i++)
        $npcList[$i]['coords'] = implode('; ', $coords[$npcList[$i]['id_userinstance']]);

    $attackCountDay = array();
    $attackCountWeek = array();
    $defenceCountDay = array();
    $defenceCountWeek = array();
    if (count($npcIds)) {
        $sql = "select c.id_userinstance, count(*) as cnt from ds_comb c, ds_battle b
            where c.id_comb=b.id_attackercomb and c.id_userinstance in (".implode(',',$npcIds).")
            and battlestartdate > now() - interval '1 day'
            group by c.id_userinstance";
        $res = $common->getResultArray("", $sql);
        foreach ($res as $row) $attackCountDay[$row['id_userinstance']] = $row['cnt'];

        $sql = "select c.id_userinstance, count(*) as cnt from ds_comb c, ds_battle b
            where c.id_comb=b.id_attackercomb and c.id_userinstance in (".implode(',',$npcIds).")
            and battlestartdate > now() - interval '7 days'
            group by c.id_userinstance";
        $res = $common->getResultArray("", $sql);
        foreach ($res as $row) $attackCountWeek[$row['id_userinstance']] = $row['cnt'];

        foreach ($npcList as $npc) $npcIds[] = $npc['id_userinstance'];
        $sql = "select c.id_userinstance, count(*) as cnt from ds_comb c, ds_battle b
            where c.id_comb=b.id_battlecomb and c.id_userinstance in (".implode(',',$npcIds).")
            and battlestartdate > now() - interval '1 day'
            group by c.id_userinstance";
        $res = $common->getResultArray("", $sql);
        foreach ($res as $row) $defenceCountDay[$row['id_userinstance']] = $row['cnt'];

        foreach ($npcList as $npc) $npcIds[] = $npc['id_userinstance'];
        $sql = "select c.id_userinstance, count(*) as cnt from ds_comb c, ds_battle b
            where c.id_comb=b.id_battlecomb and c.id_userinstance in (".implode(',',$npcIds).")
            and battlestartdate > now() - interval '7 days'
            group by c.id_userinstance";
        $res = $common->getResultArray("", $sql);
        foreach ($res as $row) $defenceCountWeek[$row['id_userinstance']] = $row['cnt'];
    }

    $sql = "select * from ds_npc_army_settings order by league, type";
    $npcSettings = $common->getResultArray("", $sql);

    $probs = array();
    $sql = "select * from ds_npc_attack_chance order by league, type";
    $res = $common->getResultArray("", $sql);
    foreach ($res as $row) {
        $probs[$row['league']."|".$row['type']][$row['condtype']."|".$row['condvalue']] = $row['probability'];
    }

    $sql = "select * from ds_npc_repeat_attack order by first_fight desc, pillager desc, winner desc, have_losses desc, have_spoils desc";
    $repSettings = $common->getResultArray("", $sql);

    $sql = "select username from ds_user where id_user in (select id_user from ds_userinstance where id_userinstance in (select distinct bl.id_userinstance from ds_npc_battle_log bl, ds_battle b where bl.defender and bl.id_battle=b.id_battle and b.battlestartdate > now() - interval '2 days'))";
    $attackers = $common->getResultArray("", $sql);
}


//----------------------------------------------------------------
?>
<script src="/Admin/js/jquery-2.1.4.min.js"></script>
<script>
function saveValue(el, key) {
    $.ajax({
        type: 'POST',
        url: 'save.php',
        data: 'key=' + key + '&val=' + el.value,
        success: function(data) {
            el.parentNode.onclick = function() {makeInput (this, key);};
            el.parentNode.innerHTML = data;
        }
    });
}

function makeInput(el, key) {
    el.onclick = null;
    el.innerHTML = '<input onBlur="saveValue(this, \''+key+'\')" value="'+el.innerHTML+'">';
    el.children[0].focus();
}
</script>

Мир:
<form method="post">
<input type="hidden" name="act" value="selectWorld">
<select name="world">
<? foreach ($worlds as $key=>$val) { ?>
    <option value="<?=$key?>"<?=$_SESSION['worldFull']==$key?' selected':''?>><?=$val?></option>
<? } ?>
</select>
<input type="submit" value="Выбрать">
</form>

<?if (isset($npcList)) { ?>

<table border="0" bordercolor="black" cellspacing="1" cellpadding="4">
    <th class="th_3">Лига</th>
    <th class="th_3">Тип</th>
    <th class="th_3">Мин. армия</th>
    <th class="th_3">Макс. армия</th>
    <th class="th_3">Макс. армия атаки</th>
    <th class="th_3">Доля ударных</th>
    <th class="th_3">Доля транспорта</th>
    <th class="th_3">Доля прослоя</th>
    <th class="th_3">Ударные Воранеров</th>
    <th class="th_3">Ударные Лиенсу</th>
    <th class="th_3">Ударные Псолао</th>
    <th class="th_3">Транспорт Воранеров</th>
    <th class="th_3">Транспорт Лиенсу</th>
    <th class="th_3">Транспорт Псолао</th>
    <th class="th_3">Макс. класс прослоя</th>
    <th class="th_3">Вероятность восстановления прослоя</th>
    <th class="th_3">Вероятность восстановления прослоя в бою</th>
    <th class="th_3">Вероятность восстановления армии</th>
    <th class="th_3">Вероятность восстановления армии в бою</th>
    <th class="th_3">Лимит врисовки в защите</th>
    <th class="th_3">Лимит врисовки в атаке</th>
    <th class="th_3">Мин. зондов</th>
    <th class="th_3">Макс. зондов</th>
    <? foreach ($npcSettings as $s) { ?>
    <tr>
        <td bgcolor="#f0ffff"><?=$s['league']?></td>
        <td bgcolor="#f0ffff"><?=$s['type']?></td>
        <? foreach (array('min_army', 'max_army','max_attack_army','strike_part', 'transport_part', 'layer_part','strike_units_1', 'strike_units_2', 'strike_units_3','transport_units_1', 'transport_units_2', 'transport_units_3','max_layer_class','layer_prob','fight_layer_prob','army_prob','fight_army_prob','defense_spawn_limit','attack_spawn_limit', 'min_zonds', 'max_zonds') as $key) { ?>
        <td bgcolor="#f0ffff" align="center" style="cursor: pointer" onClick="makeInput(this, '<?="settings|{$s['league']}|{$s['type']}|$key"?>')"><?=$s[$key]?></td>
        <? } ?>
    </tr>
    <? } ?>
</table>
<br>
<table border="0" bordercolor="black" cellspacing="1" cellpadding="4" width="1200">
    <th class="th_3">Лига</th>
    <th class="th_3">Тип</th>
    <th class="th_3">Вероятность инициативной атаки</th>
    <th class="th_3">Мин. армия  инициативной атаки</th>
    <th class="th_3">Макс. армия  инициативной атаки</th>
    <th class="th_3">Мин. размер грабли</th>
    <th class="th_3">Макс. размер грабли</th>
    <th class="th_3">Мин. уровень для инициативной атаки</th>
    <th class="th_3">Макс. уровень для инициативной атаки</th>
    <th class="th_3">Выбор цели по топ рейтингу</th>
    <th class="th_3">Выбор цели по изменению рейтинга</th>
    <th class="th_3">Выбор цели из атакующих</th>
    <th class="th_3">Выбор цели из соседей</th>
    <th class="th_3">Множитель размера большой армии</th>
    <? foreach ($npcSettings as $s) { ?>
    <tr>
        <td bgcolor="#f0ffff"><?=$s['league']?></td>
        <td bgcolor="#f0ffff"><?=$s['type']?></td>
        <? foreach (array('init_attack_prob', 'min_init_army', 'max_init_army', 'min_pillage_army', 'max_pillage_army', 'min_level_for_init_attack','max_level_for_init_attack','attack_by_top_rating','attack_by_rating_change', 'attack_by_attackers', 'attack_by_neighbours', 'big_army_multiplier') as $key) { ?>
        <td bgcolor="#f0ffff" align="center" style="cursor: pointer" onClick="makeInput(this, '<?="settings|{$s['league']}|{$s['type']}|$key"?>')"><?=$s[$key]?></td>
        <? } ?>
    </tr>
    <? } ?>
</table>
<br>
<table border="0" bordercolor="black" cellspacing="1" cellpadding="4" width="1200">
    <th class="th_3">Первый бой</th>
    <th class="th_3">Грабля</th>
    <th class="th_3">Победа</th>
    <th class="th_3">Есть потери</th>
    <th class="th_3">Ограблено</th>
    <th class="th_3">Шанс повтора атаки</th>
    <th class="th_3">Шанс грабли</th>
    <th class="th_3">Шанс большой грабли</th>
    <th class="th_3">Шанс рейдера</th>
    <th class="th_3">Шанс большого рейдера</th>
    <th class="th_3">Шанс спама</th>
    <? foreach ($repSettings as $s) { ?>
    <tr>
        <td bgcolor="#f0ffff"><?=$s['first_fight']=='t'?'Да':'Нет'?></td>
        <td bgcolor="#f0ffff"><?=$s['pillager']=='t'?'Да':'Нет'?></td>
        <td bgcolor="#f0ffff"><?=$s['winner']=='t'?'Да':'Нет'?></td>
        <td bgcolor="#f0ffff"><?=$s['have_losses']!=-1?($s['have_losses']==1?'Да':'Нет'):''?></td>
        <td bgcolor="#f0ffff"><?=$s['have_spoils']!=-1?($s['have_spoils']==1?'Да':'Нет'):''?></td>
        <? foreach (array('repeat_chance', 'pillager_chance', 'big_pillager_chance', 'raider_chance', 'big_raider_chance', 'spam_chance') as $key) { ?>
        <td bgcolor="#f0ffff" align="center" style="cursor: pointer" onClick="makeInput(this, '<?="repeat|{$s['first_fight']}|{$s['pillager']}|{$s['winner']}|{$s['have_losses']}|{$s['have_spoils']}|$key"?>')"><?=$s[$key]?></td>
        <? } ?>
    </tr>
    <? } ?>
</table>
<br>
<table border="0" bordercolor="black" cellspacing="1" cellpadding="4">
    <th class="th_3">Лига</th>
    <th class="th_3">Тип</th>
    <th class="th_3">Убито &lt;1%</th>
    <th class="th_3">Убито &lt;3%</th>
    <th class="th_3">Убито &lt;12%</th>
    <th class="th_3">Убито &lt;25%</th>
    <th class="th_3">Убито &lt;50%</th>
    <th class="th_3">Убито &gt;50%</th>
    <th class="th_3">1 бой</th>
    <th class="th_3">2 боя</th>
    <th class="th_3">4 боя</th>
    <th class="th_3">8 боёв</th>
    <th class="th_3">12 боёв</th>
    <th class="th_3">18 боёв</th>
    <th class="th_3">0 вылетов</th>
    <th class="th_3">1 вылет</th>
    <th class="th_3">2 вылета</th>
    <th class="th_3">4 вылета</th>
    <th class="th_3">10 вылетов</th>
    <th class="th_3">30 вылетов</th>
    <th class="th_3">Грабёж был</th>
    <th class="th_3">Грабежа нет</th>
    <th class="th_3">Вероятность атаки 2 соты</th>
    <th class="th_3">Бой выигран, мин. армия</th>
    <th class="th_3">Бой выигран, макс. армия</th>
    <th class="th_3">Бой проигран, мин. армия</th>
    <th class="th_3">Бой проигран, макс. армия</th>
    <? foreach ($probs as $key=>$val) {
        $parts = explode("|", $key);
        $league = $parts[0];
        $type = $parts[1];
    ?>
    <tr>
        <td bgcolor="#f0ffff"><?=$league?></td>
        <td bgcolor="#f0ffff"><?=$type?></td>
        <? foreach ($conds as $key) { ?>
        <td bgcolor="#f0ffff" align="center" style="cursor: pointer" onClick="makeInput(this, '<?="prob|$league|$type|$key"?>')"><?=$val[$key]?></td>
        <? } ?>
    </tr>
    <? } ?>
</table>
<br>
<div style="color: #f0ffff">
<form method="post">
<input type="hidden" name="act" value="insertNewType">
Лига: <input name="league" size="1">
Тип: <input name="type" size="2">
<input type="submit" value="Добавить новый тип NPC">
</form>
</div>
<br>
<table border="0" bordercolor="black" cellspacing="1" cellpadding="4">
    <th class="th_3">Имя</th>
    <th class="th_3">Рейтинг</th>
    <th class="th_3">БО</th>
    <th class="th_3">Лига</th>
    <th class="th_3">Раса</th>
    <th class="th_3">Тип</th>
    <th class="th_3">Уровень</th>
    <th class="th_3">Координаты</th>
    <th class="th_3">Атак за день</th>
    <th class="th_3">Защит за день</th>
    <th class="th_3">Атак за неделю</th>
    <th class="th_3">Защит за неделю</th>
    <? foreach ($npcList as $npc) { ?>
    <tr>
        <td bgcolor="#f0ffff"><?=$npc['username']?></td>
        <td bgcolor="#f0ffff" align="right"><?=$npc['thefinal']?></td>
        <td bgcolor="#f0ffff" align="right"><?=$npc['value']?></td>
        <td bgcolor="#f0ffff" align="center" style="cursor: pointer" onClick="makeInput(this, '<?="lt|{$npc['id_userinstance']}|league"?>')"><?=$npc['id_league']?></td>
        <td bgcolor="#f0ffff" align="center"><?=$npc['race']?></td>
        <td bgcolor="#f0ffff" align="center" style="cursor: pointer" onClick="makeInput(this, '<?="lt|{$npc['id_userinstance']}|type"?>')"><?=$npc['type']?></td>
        <td bgcolor="#f0ffff" align="center" style="cursor: pointer" onClick="makeInput(this, '<?="lt|{$npc['id_userinstance']}|level"?>')"><?=$npc['id_level']?></td>
        <td bgcolor="#f0ffff" align="center"><?=$npc['coords']?></td>
        <td bgcolor="#f0ffff" align="center"><?=$attackCountDay[$npc['id_userinstance']]?></td>
        <td bgcolor="#f0ffff" align="center"><?=$defenceCountDay[$npc['id_userinstance']]?></td>
        <td bgcolor="#f0ffff" align="center"><?=$attackCountWeek[$npc['id_userinstance']]?></td>
        <td bgcolor="#f0ffff" align="center"><?=$defenceCountWeek[$npc['id_userinstance']]?></td>
    </tr>
    <? } ?>
<? } ?>
</table>

<br><font color="lightblue">
Атакующие (<?=count($attackers)?>):<br>
<? foreach ($attackers as $a) echo $a['username'].'<br>'; ?>
</font>