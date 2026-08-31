<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";
import('domXML');


function selectScope()
{
    global $common;
    $sql = "
        SELECT
            ap.id_artefactscope as id,
            COALESCE(ltl.text, ap.scopename) AS name
        FROM
            ds_artefactscope AS ap
            LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = ap.id_localizedscope AND ltl.id_language = ".constant('DEFAULT_LANG_ID').")
        ";
    $res = $common->getResultArray('', $sql);
    assert(empty($common->errormsg));

    $out = array('<select id="artScope" name="artScope" onChange="filter1=this.value; filterArts()"><option value="0">Все типы</value>');
    foreach ($res as $row) {
        $out[] = '<option value="'.$row['id'].'"'.($row['id']==$_POST['artScope']?' selected':'').'>'.$row['name'].'</value>';
    }
    $out[] = '</select>';
    
    return implode('', $out);
}

function selectSize()
{
    global $common;
    $sql = "
        SELECT
            apa.id_artefactprobability as id,
            COALESCE(ltl.text, apa.prefix) as name
        FROM
            ds_artefactprobability AS apa
            LEFT JOIN ds_localizedtextlanguage AS ltl ON (apa.id_localizedprefix = ltl.id_localizedtext AND ltl.id_language = ".constant('DEFAULT_LANG_ID').")
        ORDER BY
            apa.id_artefactprobability
        ";
    $res = $common->getResultArray('', $sql);
    assert(empty($common->errormsg));

    $out = array('<select id="artSize" name="artSize" onChange="filter2=this.value; filterArts()"><option value="0">Все размеры</value>');
    foreach ($res as $row) {
        $out[] = '<option value="'.$row['id'].'"'.($row['id']==$_POST['artSize']?' selected':'').'>'.$row['name'].'</value>';
    }
    $out[] = '</select>';
    
    return implode('', $out);
}

function selectBonus()
{
    global $common;
    $sql = "
        SELECT DISTINCT
            att.id_attributename as id,
            COALESCE(ltl.text, an.attributename) as name
        FROM
            ds_artefact AS a
            INNER JOIN ds_artefacthasbonus AS ahb ON (a.id_artefact = ahb.id_artefact)
            INNER JOIN ds_attribute AS att ON (ahb.id_attribute = att.id_attribute)
            INNER JOIN ds_attributename AS an ON (att.id_attributename = an.id_attributename)
            LEFT JOIN ds_localizedtextlanguage AS ltl ON (an.id_localizedname = ltl.id_localizedtext AND ltl.id_language = ".constant('DEFAULT_LANG_ID').")
        ";
    $res = $common->getResultArray('', $sql);
    assert(empty($common->errormsg));
    
    $out = array('<select id="artBonus" name="artBonus" onChange="filter3=this.value; filterArts()"><option value="0">Все ресурсы</value>');
    foreach ($res as $row) {
        $out[] = '<option value="'.$row['id'].'"'.($row['id']==$_POST['artBonus']?' selected':'').'>'.$row['name'].'</value>';
    }
    $out[] = '</select>';
    
    return implode('', $out);
}


function selectUnit()
{
    global $common;
    $sql = sprintf("
                    SELECT
                            un.id_unit,
                            ltl.text as unitname,
                            ucbbbr.id_race,
                            (
                            SELECT
                                    ltl.text
                                FROM
                                    ds_race r,
                                    ds_localizedtext lt,
                                    ds_localizedtextlanguage ltl
                                WHERE
                                    r.id_race = ucbbbr.id_race
                                    AND r.id_localizedname = lt.id_localizedtext
                                    AND lt.id_localizedtext = ltl.id_localizedtext
                                    AND ltl.id_language = %d
                            ) as racename
                        FROM    
                            ds_unit un  
                            LEFT JOIN ds_unitcanbebuildbyrace ucbbbr USING(id_unit)
                            LEFT JOIN ds_unitorcontainertype uct USING(id_unitcontainertype)
                            LEFT JOIN ds_unithasclass uhc USING(id_unit)
                            LEFT JOIN ds_unitclass uc ON (uhc.id_unitclass = uc.id_unitclass)
                            LEFT JOIN ds_localizedtext lot ON (un.id_localizedname = lot.id_localizedtext)
                            LEFT JOIN ds_localizedtextlanguage ltl ON ( lot.id_localizedtext = ltl.id_localizedtext)   
                        WHERE
                            uct.containertypename = 'ARMY'
                            AND ltl.id_language = %d                                                                                                                
                    ORDER BY
                            ucbbbr.id_race, uc.classnumber",
                    constant( 'DEFAULT_LANG_ID' ),
                    constant( 'DEFAULT_LANG_ID' ));

    $res = $common->getResultArray ( "allunits", $sql, "", "", "" );
    if ( !$res ) {
        $this->errormsg = " SYSTEM ERROR - addUnits::getAllUnits !!! ".$sql;
        return false;
    }

    $current_race = 0;
    $selectUnits = "<select name='id_unit'>";
    
    foreach($res as $k => $v) 
    {
        if($current_race != (int)$v['id_race'])
        {
            $selectUnits .= "<option disabled>-------------".
                                    $v['racename'].
                                    "----------------</option>";
            $current_race = (int)$v['id_race'];
        }
        
        $selectUnits .= "<option value='".$v['id_unit']."'".($v['id_unit']==$_POST['id_unit']?' selected':'').">".$v['unitname']."</option>";
    }
    
    $selectUnits .= "</select>";

    return $selectUnits;
}

function selectArtefact()
{
    global $common;
    $sql = "SELECT a.id_artefact, a.id_artefactscope, a.id_artefactprobability, at.id_attributename,
       COALESCE(ltl.text, a.artefactname) AS artefact_name
       FROM ds_artefact AS a
       LEFT JOIN ds_artefacthasbonus AS ahb ON (ahb.id_artefact = a.id_artefact)
       LEFT JOIN ds_attribute AS at ON (at.id_attribute = ahb.id_attribute)
       LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = a.id_localizedname AND ltl.id_language = 3)
       ORDER BY a.id_artefactscope, a.id_artefactprobability, a.id_artefacttype";
    $res = $common->getResultArray ("", $sql);
    $select = array('<select id="id_art" name="id_art">');
    foreach ($res as $row) {
        $select[] = '<option id="art'.$row['id_artefact'].'_'.$row['id_artefactscope'].'_'.$row['id_artefactprobability'].'_'.$row['id_attributename'].'" value="'.$row['id_artefact'].'"'.($_POST['id_art']==$row['id_artefact']?' selected':'').'>'.$row['artefact_name'].'</option>';
    }
    $select[] = '</select>';
    return implode("", $select);
}

function selectWorld() {
    global $common;
    $sql = "select dbname, worldname, id_world from ds_worldcollection";
    $res = $common->getResultArray ("", $sql);
    $worlds = array();
    foreach ($res as $row) {
        if ($row['worldname']=='Кабинет') $row['worldname'] = '--- выберите мир ---';
        $worldIDs[$row['dbname']] = $row['id_world'];
        $worlds[$row['dbname']] = $row['worldname'];
    }
    $select = array('<select name="world">');
    foreach ($GLOBALS['dbWorldAccess'] as $dbId=>$db) {
        $select[] = '<option '.($worlds[$db['server']][0]=='-'?'disabled':'value="'.$dbId.'_'.$worldIDs[$db['server']].'"').($dbId.'_'.$worldIDs[$db['server']]==$_POST['world']?' selected':'').'>'.$worlds[$db['server']].'</option>';
    }
    $select[] = '</select>';
    return implode("", $select);
}

function getUserInstanceID($username) {
    global $common;
    $sql ="select ui.id_userinstance
        from ds_userinstance ui, ds_user u
        where ui.id_user=u.id_user and u.username='$username'";
    $res = $common->getResultArray ("", $sql);
    return $res[0]['id_userinstance'];
}

function getUserID($username) {
    global $common;
    $sql ="select id_user from ds_user where username='$username'";
    $res = $common->getResultArray ("", $sql);
    return $res[0]['id_user'];
}

function addArtefacts($combID, $artID, $num, $lifeTime)
{
    global $common;
    $instanceIds = array();
    
    for ($i=0; $i<$num; $i++) {
        $sql = "SELECT add_artefact_to_comb($combID, $artID, $lifeTime) as id";
        $res = $common->getResultArray ("", $sql);
        assert(empty($common->errormsg));
        $instanceIds[] = $res[0]['id'];
    }
    return $instanceIds;
}



// ===============================================================
if (isset($_POST['world'])) {
    list($dbID, $worldID) = explode('_', $_POST['world']);
} else {
    $dbID = 0;
    $worldID = 0;
}

$common = DS_Common::CreateForCurrentDB($dbID);

if (isset($_POST['type'])) {
    $userInstanceID = getUserInstanceID($_POST['seller']);
    $_SESSION['dbId'] = (int)$dbID;
    $_SESSION['world'] = (int)$worldID;
    $_SESSION['lang'] = 3;
    $_SESSION['uiid'] = $userInstanceID;
    $_SESSION['uid'] = getUserID($_POST['seller']);
    $GLOBALS['params']['uiid'] = $userInstanceID;
    $sql = "SELECT c.id_comb FROM ds_comb AS c, ds_userinstance AS ui, ds_combcomplex cc, ds_ring r
        WHERE ui.id_world = $worldID AND c.id_userinstance = $userInstanceID
        AND c.id_combcomplex=cc.id_combcomplex and cc.id_ring=r.id_ring and r.ringnumber != 4";
    $res = $common->getResultArray ("", $sql);
    $_SESSION['comb'] = $res[0]['id_comb'];
    $num = (int) $_POST['num'];
    $price = (int) $_POST['price'];
    $tradetime = (int) $_POST['tradetime'];
    if ($_POST['type']=='art') {
        $artID = (int) $_POST['id_art'];
        $lifeTime = (int) $_POST['lifetime'];
        $instanceIds = addArtefacts($_SESSION['comb'], $artID, $num, $lifeTime);
        if ($lifeTime>0) {
            $sql = "select extract(epoch from birth_time) as birth, extract(epoch from death_time) as death
                from ds_artefactinstance
                where id_artefactinstance in (".implode(',',$instanceIds).")";
            $res = $common->getResultArray ("", $sql);
            $death = array();
            foreach ($res as $row) {
                $death[] = (int)$row['birth']."|".(int)$row['death'];
            }
        }
        for ($i=0; $i<$num; $i++) {
            $fields = array(
                "id_tradescope" => 3,
                "id_fromcategory" => 3,
                "id_fromarticle" => $artID,
                "amount" => 1,
                "id_tocategory" => 4,
                "id_toarticle" => 1,
                "price" => $price,
                "tradetime" => $tradetime,
                "isauction" => isset($_POST['auction']) ? 1 : 0,
                "nick" => $_POST['nick'],
                "pass" => "",
                "anonymous" => isset($_POST['anonymous']) ? 1 : 0,
                "death" => $lifetime > 0 ? array_pop($death): ""
            );
            $xml = array('<sell');
            foreach ($fields as $key=>$val) {
                $xml[] = ' '.$key.'="'.$val.'"';
            }
            $xml[] = '/>';
            $xml = implode('', $xml);
            $xmldata = '<?xml version="1.0" encoding="ISO-8859-1"'."?".">".$xml;
            $tree = domxml_open_mem ( $xmldata);
            assert($tree);      
            $GLOBALS['params']["root"] = $tree->document_element();
            $action = new UA_Sell;
            assert($action->checkGlobalParams());
            assert($action->checkLocalParams());
            assert($action->checkAction());
            assert($action->performAction());
            echo "Артефакт выставлен на продажу<br>";
        }
    }
    else if ($_POST['type']=='unit') {
        $id_unit = (int) $_POST['id_unit'];
        $sql = "SELECT uchu.id_unitcontainerhasunit
            FROM ds_unitcontainerhasunit uchu, ds_unitcontainer uc
            WHERE uchu.id_unit = $id_unit
            and uchu.id_army=uc.id_unitcontainer
            and uc.id_basecomb=".$_SESSION['comb']." and uc.isdefault";
        $res = $common->getResultArray ("", $sql);
        if (count($res)) {
            $sql = "update ds_unitcontainerhasunit set amount=amount+$num
                where id_unitcontainerhasunit=".$res[0]['id_unitcontainerhasunit'];
        }
        else {
            $sql = "insert into ds_unitcontainerhasunit (id_army, id_unit, amount)
                select id_unitcontainer, $id_unit, $num from ds_unitcontainer
                where id_basecomb=".$_SESSION['comb']." and isdefault";
        }
        $common->executeQuery($sql);
        $fields = array(
            "id_tradescope" => 3,
            "id_fromcategory" => 2,
            "id_fromarticle" => $id_unit,
            "amount" => 1,
            "id_tocategory" => 4,
            "id_toarticle" => 1,
            "price" => $price,
            "tradetime" => $tradetime,
            "isauction" => isset($_POST['auction']) ? 1 : 0,
            "nick" => $_POST['nick'],
            "pass" => "",
            "anonymous" => isset($_POST['anonymous']) ? 1 : 0,
            "death" => ""
        );
        $xml = array('<sell');
        foreach ($fields as $key=>$val) {
            $xml[] = ' '.$key.'="'.$val.'"';
        }
        $xml[] = '/>';
        $xml = implode('', $xml);
        $xmldata = '<?xml version="1.0" encoding="ISO-8859-1"?>'.$xml;
        $tree = domxml_open_mem ( $xmldata);
        assert($tree);      
        $GLOBALS['params']["root"] = $tree->document_element();
        $action = new UA_Sell;
        assert($action->checkGlobalParams());
        assert($action->checkLocalParams());
        assert($action->checkAction());
        assert($action->performAction());
        echo "Юнит выставлен на продажу<br>";
    }
}

?>
<script>
var filter1 = 0;
var filter2 = 0;
var filter3 = 0;
var trash = document.createElement("select");
function filterArts() {
    for (var i=trash.childNodes.length-1; i>=0; i--) {
        var node = trash.childNodes[i];
        tokens = node.id.split('_');
        if ((filter1==0 || tokens[1]==filter1) && (filter2==0 || tokens[2]==filter2) && (filter3==0 || tokens[3]==filter3))
            document.getElementById('id_art').appendChild(node);
    }
    var nodeList = document.getElementById('id_art').childNodes;
    for (var i=nodeList.length-1; i>=0; i--) {
        var node = nodeList[i];
        tokens = node.id.split('_');
        if (filter1!=0 && tokens[1]!=filter1 || filter2!=0 && tokens[2]!=filter2 || filter3!=0 && tokens[3]!=filter3)
            trash.appendChild(node);
    }
}
</script>
<style>
  td {color: #CCCCCC}
</style>
<form method="post">
<table><tr><td>
    Мир
</td><td>
    <?=selectWorld()?>
</td></tr><tr><td>
    Продавец
</td><td>
    <input name="seller" value="<?=$_POST['seller']?$_POST['seller']:'dada'?>">
</td></tr><tr><td>
    <input type="radio" name="type" value="unit"<?=$_POST['type']!='art'?' checked':''?>>
    Юнит
</td><td>
    <?=selectUnit()?>
</td></tr><tr><td>
    <input type="radio" name="type" value="art"<?=$_POST['type']=='art'?' checked':''?>>
    Артефакт
</td><td><br>
    <?=selectScope()?><br>
    <?=selectSize()?><br>
    <?=selectBonus()?><br>
    <?=selectArtefact()?>
    на <input name="lifetime" size="3" value="<?=$_POST['lifetime']?>"> месяцев
</td></tr><tr><td><br>
    Количество
</td><td>
    <input name="num" value="<?=$_POST['num']?$_POST['num']:1?>">
</td></tr><tr><td>
    Цена (в серебре)
</td><td>
    <input name="price" value="<?=$_POST['price']?$_POST['price']:1000?>">
</td></tr><tr><td>
    Длительность (в турнах)
</td><td>
    <input name="tradetime" value="<?=$_POST['tradetime']?$_POST['tradetime']:60?>">
</td></tr><tr><td>
    С аукционом
</td><td>
    <input type="checkbox" name="auction"<?=$_POST['auction']?' checked':''?>>
</td></tr><tr><td>
    Анонимно
</td><td>
    <input type="checkbox" name="anonymous"<?=$_POST['anonymous']?' checked':''?>>
</td></tr><tr><td>
    Персонально для
</td><td>
    <input name="nick"<?=$_POST['nick']?>>
</td></tr><tr><td></td><td><br>
    <input type="submit" value="Выставить на аукцион">
</td></tr></table>
</form>
<script>
filter1 = document.getElementById('artScope').value;
filter2 = document.getElementById('artSize').value;
filter3 = document.getElementById('artBonus').value;
filterArts();
</script>
