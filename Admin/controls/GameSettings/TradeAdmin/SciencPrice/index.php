<?
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/security.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/set_work_category.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/admintop.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/classfactory.php";
require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/settings.php";

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
        
        $selectUnits .= "<option value='".$v['id_unit']."' $selected>".$v['unitname']."</option>";
    }
    
    $selectUnits .= "</select>";

    return $selectUnits;
}

function selectArtefact()
{
    global $common;

    $sql = "SELECT a.id_artefact,
       COALESCE(ltl.text, a.artefactname) AS artefact_name
       FROM ds_artefact AS a
       LEFT JOIN ds_artefacthasbonus AS ahb ON (ahb.id_artefact = a.id_artefact)
       LEFT JOIN ds_attribute AS at ON (at.id_attribute = ahb.id_attribute)
       LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = a.id_localizedname AND ltl.id_language = 3)
       ORDER BY a.id_artefactscope, a.id_artefactprobability, a.id_artefacttype";
    $res = $common->getResultArray ("", $sql);
    $select = array('<select name="id_art">');
    foreach ($res as $row) {
        $select[] = '<option value="'.$row['id_artefact'].'">'.$row['artefact_name'].'</option>';
    }
    $select[] = '</select>';
    return implode("", $select);
}

// ===============================================================
$common = DS_Common::CreateForCurrentDB();
?>
<form method="post">
<table><tr><td class="th_3">
    Юнит
</td><td>
    <input type="radio" name="type" value="unit" selected>
    <?=selectUnit()?>
</td></tr><tr><td>
    Артефакт
</td><td>
    <input type="radio" name="type" value="art">
    <?=selectArtefact()?>
</td></tr><tr><td>
    Количество
</td><td>
    <input name="num" value="1">
</td></tr><tr><td>
    Цена (в серебре)
</td><td>
    <input name="price" value="1000">
</td></tr><tr><td>
    Длительность (в турнах)
</td><td>
    <input name="num" value="60">
</td></tr><tr><td>
    С аукционом
</td><td>
    <input type="checkbox" name="auction">
</td></tr><tr><td>
    Анонимно
</td><td>
    <input type="checkbox" name="anonymous">
</td></tr><tr><td>
    Персонально для
</td><td>
    <input name="nick">
</td></tr><tr><td></td><td>
    <input type="submit" value="Выставить на аукцион">
</td></tr></table>
</form>