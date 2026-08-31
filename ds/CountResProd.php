<?php
//CountResProd.php
require_once ( 'Main.php' );
ini_set('memory_limit', '1240M');
set_time_limit(0);


$common = DS_Common::CreateForCurentDB((int)$_REQUEST['db']);
$sql = "select id_world from ds_world";
$res = $common->getResultArray ("", $sql);

$_SESSION['world'] = $res[0]['id_world'];

$all = array();
$sql = "SELECT aacc.id_alliance, atn.attributename, att.typename, sum(ahb.value) as rs
            FROM
                ds_artefactattachedalliance AS aacc
                INNER JOIN ds_artefactinstance AS ai ON (aacc.id_artefact = ai.id_artefactinstance)
                INNER JOIN ds_artefact AS a ON (ai.id_artefact = a.id_artefact)
                INNER JOIN ds_artefactscope AS ascop ON (a.id_artefactscope = ascop.id_artefactscope)
                INNER JOIN ds_artefacthasbonus AS ahb ON (ahb.id_artefact = a.id_artefact)
                INNER JOIN ds_attribute AS at ON (at.id_attribute = ahb.id_attribute)
                INNER JOIN ds_attributename AS atn ON (atn.id_attributename = at.id_attributename)
                INNER JOIN ds_attributetype AS att ON (att.id_attributetype = at.id_attributetype)
            WHERE
                    atn.attributename in ('KADERIUM', 'NANO_CRYSTAL', 'STASIS_ENERGY','FOOD','PRODIUM')
                    AND att.typename in ('BONUS_ABSOLUTE', 'BONUS_RELATIVE')
            group by aacc.id_alliance, atn.attributename, att.typename";
$res = $common->getResultArray ("", $sql);
foreach ($res as $row) {
    $all[$row['id_alliance'].'_'.$row['attributename'].'_'.$row['typename']] = $row['rs'];
}


// Выбираем все соты
$sql = "SELECT u.username, c.id_comb, uha.id_alliance
 FROM ds_comb c, ds_user u, ds_userinstance ui
 left join ds_userhasalliance uha on uha.id_userinstance = ui.id_userinstance
 WHERE c.id_userinstance = ui.id_userinstance
 and u.id_user = ui.id_user
 and not c.is_capture
 and ui.id_userinstance != 0
 order by c.id_comb";

$res = $common->getResultArray ("", $sql);
//echo "<pre>";

echo "Count:".count($res)."<br>";

$archiolog = DE_Unit::CreateFromName('ARCHEOLOGIST');
$scientist = DE_Unit::CreateFromName('SCIENTIST');
$worker = DE_Unit::CreateFromName('WORKER');

   
foreach ($res as $key => $comb)
{
    echo $userName."<br>";
    
    $id_comb = (int)$comb['id_comb'];
    //assert($id_comb > 0);
    $userName = $comb['username'];
    //assert(!empty($userName));
    $id_alliance = $comb['id_alliance'];

    $comb = DE_Comb::CreateFromID($id_comb);
    $combAttributes = DE_CombAttributes::CreateFromCombID($comb->getID());

    $productionK = GL_CountCombProductionOfResource::Count($comb, 'KADERIUM');
    //$upkeepK = $combAttributes->getAttribute('TURN_UPKEEP', 'KADERIUM');
    /*if ($id_alliance) {
        $baseProd = $combAttributes->getAttribute('TURN_PRODUCTION', 'KADERIUM');
        $bonus = $baseProd * (float)$all[$id_alliance.'_KADERIUM_BONUS_RELATIVE'] + (float)$all[$id_alliance.'_KADERIUM_BONUS_ABSOLUTE'];
        $productionK -= $bonus;
    } */

    $productionNK = GL_CountCombProductionOfResource::Count($comb, 'NANO_CRYSTAL');
    //$upkeepNK = $combAttributes->getAttribute('TURN_UPKEEP', 'NANO_CRYSTAL');
    /*if ($id_alliance) {
        $baseProd = $combAttributes->getAttribute('TURN_PRODUCTION', 'NANO_CRYSTAL');
        $bonus = $baseProd * (float)$all[$id_alliance.'_NANO_CRYSTAL_BONUS_RELATIVE'] + (float)$all[$id_alliance.'_NANO_CRYSTAL_BONUS_ABSOLUTE'];
        $productionNK -= $bonus;
    } */

    $productionSe = GL_CountCombProductionOfResource::Count($comb, 'STASIS_ENERGY');
    //$upkeepSe = $combAttributes->getAttribute('TURN_UPKEEP', 'STASIS_ENERGY');
    /*if ($id_alliance) {
        $baseProd = $combAttributes->getAttribute('TURN_PRODUCTION', 'STASIS_ENERGY');
        $bonus = $baseProd * (float)$all[$id_alliance.'_STASIS_ENERGY_BONUS_RELATIVE'] + (float)$all[$id_alliance.'_STASIS_ENERGY_BONUS_ABSOLUTE'];
        $productionSe -= $bonus;
    } */

    $productionF = GL_CountCombProductionOfResource::Count($comb, 'FOOD');
    //$upkeepF = $combAttributes->getAttribute('TURN_UPKEEP', 'FOOD');
    /*if ($id_alliance) {
        $baseProd = $combAttributes->getAttribute('TURN_PRODUCTION', 'FOOD');
        $bonus = $baseProd * (float)$all[$id_alliance.'_FOOD_BONUS_RELATIVE'] + (float)$all[$id_alliance.'_FOOD_BONUS_ABSOLUTE'];
        $productionF -= $bonus;
    } */

    $productionP = GL_CountCombProductionOfResource::Count($comb, 'PRODIUM');
    //$upkeepP = $combAttributes->getAttribute('TURN_UPKEEP', 'PRODIUM');
    /*if ($id_alliance) {
        $baseProd = $combAttributes->getAttribute('TURN_PRODUCTION', 'PRODIUM');
        $bonus = $baseProd * (float)$all[$id_alliance.'_PRODIUM_BONUS_RELATIVE'] + (float)$all[$id_alliance.'_PRODIUM_BONUS_ABSOLUTE'];
        $productionP -= $bonus;
    } */

/*
    //Инициалиировавлаи массив апкипом армии
    $summaryUpkeep = GL_LiveUpkeep::CountComb($comb);
    


    //Учли потребление энергии здагниями
    $rs = DS_Economical::GetCombBuildingsTurnUpkeep($id_comb);
    if(count($rs)>0)
    {
        $buildingsUpkeepEnergy = $rs[0]['upkeep'];
    }else{
        $buildingsUpkeepEnergy = 0;
    }
    $summaryUpkeep['STASIS_ENERGY'] += $buildingsUpkeepEnergy;

    //Учли потредение еы архами и рабочими
    $foodUsing =
        $combAttributes->getAttribute('CURRENT_AMOUNT', 'WORKER')
        *
        $worker->getAttributes()->getAttribute('TURN_UPKEEP', 'FOOD')
        +
        $combAttributes->getAttribute('CURRENT_AMOUNT', 'SCIENTIST')
        *
        $scientist->getAttributes()->getAttribute('TURN_UPKEEP', 'FOOD')
        +
        (
        $combAttributes->getAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST', true)
        +
        $combAttributes->getAttribute('AWAY_AMOUNT', 'ARCHEOLOGIST', true)
        )
        *
        $archiolog->getAttributes()->getAttribute('TURN_UPKEEP', 'FOOD');
    $summaryUpkeep['FOOD'] += $foodUsing;
    
    
    $upkeepK = $summaryUpkeep['KADERIUM'];
    $upkeepNK = $summaryUpkeep['NANO_CRYSTAL'];
    $upkeepP = $summaryUpkeep['PRODIUM'];
    $upkeepF = $summaryUpkeep['FOOD'];
    $upkeepSe = $summaryUpkeep['STASIS_ENERGY'];

    $sql = "
    INSERT INTO ds_count
    (username, production_k, upkeep_k, production_nk, upkeep_nk, production_se, upkeep_se, production_f, upkeep_f, 
    production_p, upkeep_p, id_comb)
    VALUES
    ('$userName', $productionK, $upkeepK, $productionNK, $upkeepNK, $productionSe, $upkeepSe, $productionF, $upkeepF, 
    $productionP, $upkeepP, $id_comb)";
*/
    $sql = "
    INSERT INTO ds_count
    (username, production_k, production_nk, production_se, production_f, production_p, id_comb)
    VALUES
    ('$userName', $productionK, $productionNK, $productionSe, $productionF, $productionP, $id_comb)";

    $res = $common->executeQuery ($sql);
    assert(empty($common->errormsg));
    //echo "ok<br>";
    GB_SingletonObjectStorage::ResetAll();
}

echo "END<br>";



?>