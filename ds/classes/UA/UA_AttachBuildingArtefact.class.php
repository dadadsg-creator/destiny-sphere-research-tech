<?php $CVS_Version = '$Revision$ ; $Date$';

// команда приделать артефакт к зданию



/**
* class UA_AttachArtefact
*
*
* @version 1.0
* @author Rouslan K. Tairov <rouslan@allieddreams.de>
* @package destiny
*/
class UA_AttachBuildingArtefact extends UA_Building {

    public $id_artefactinstance;
    public $errorname;
    public $id_alliance;
    public $scopename;
    
    public $buildingInUpgrade;

    public $delArtefact = false;

    function UA_AttachBuildingArtefact () {
        UA_Action::UA_Action ();
    }

    // проверяем пришедшие параметры
    function checkLocalParams () 
    {
        if ($this->root->get_attribute("c")=='warbase') {
            $sql = "select c.cellnumber from ds_cell c, ds_attachedbuilding ab
                where c.id_cell=ab.id_cell and c.id_comb=".$this->comb."
                and ab.id_building=26";
            $res = $this->common->getResultArray("", $sql);
            $this->cellnumber = (int)$res[0]['cellnumber'];
        }
        else $this->cellnumber = (int)$this->root->get_attribute("c");
        
        $cellData = ME_CombViewGetCellIDAndBuildigIDByCell::Exec($this->cellnumber, true);
        
        $this->id_cell = $cellData['CELL_ID'];
        assert(is_int($this->id_cell) && $this->id_cell > 0);
        
        $this->attachedbid = $cellData['ATTACHED_BUILDING_ID'];
        assert(is_int($this->attachedbid) && $this->attachedbid >= 0);
            
        $this->bid = $cellData['BUILDING_ID'];
        assert(is_int($this->bid) && $this->bid >= 0);

        $this->id_artefactinstance = (int)$this->root->get_attribute ("id");
        assert($this->id_artefactinstance > 0);

        $this->collection = (int)$this->root->get_attribute("tabNum");
        if ($this->collection==0) $this->collection = 1;

        return true;
    }

    // проверяем допустимость действия
    function checkAction ()
    {
        global $common;
        global $params;

        // смотрим scope артефакта
        $sql = sprintf("
                SELECT 
                    ars.scopename
                FROM
                    ds_artefactscope ars,
                    ds_artefact ar,
                    ds_artefactinstance ai
                WHERE 
                    ars.id_artefactscope = ar.id_artefactscope
                    AND 
                    ar.id_artefact = ai.id_artefact
                    AND 
                    ai.id_artefactinstance = %d",
                $this->id_artefactinstance );

        $res = $common->getResultArray ( "scopename_id_artefactinstance_".$this->id_artefactinstance, $sql, "", "", "WORLD_CACHE" );
        if(!$res)
        {
            $this->errormsg .= " SYSTEM ERROR UA_AttachBuildingArtefact::checkAction1";
            return false;
        }
        $this->scopename = $res[0]['scopename'];
        
        // не вставлен ли уже этот артефакт
        $sql = "
            SELECT
                aab.id_attachedbuilding
            FROM
                ds_artefactattachedbuilding as aab
            WHERE
                id_artefact = ".$this->id_artefactinstance."
            ";
        $res = $common->getResultArray("", $sql, "", "", "" );
        assert(empty($common->errormsg));
        
        if(count($res) > 0)
        {
            $this->errormsg .= " Artefact already attached ";
            return false;
        }

        // подходит ли артефакт к этому зданию
        $sql = sprintf ( "
                SELECT 
                    ai.id_artefactinstance
                FROM 
                    ds_artefact a,
                    ds_artefactprobability ap,
                    ds_artefactscope ars,
                    ds_artefacttype at,
                    ds_artefactinstance ai,
                    ds_combhasartefact cha,
                    ds_artefacttypeforbuilding atfb,
                    ds_attachedbuilding ab
                WHERE 
                    a.id_artefact = ai.id_artefact
                    and 
                    a.id_artefactprobability = ap.id_artefactprobability
                    and 
                    a.id_artefactscope = ars.id_artefactscope
                    and 
                    a.id_artefacttype = at.id_artefacttype
                    and 
                    ai.id_artefactinstance = cha.id_artefact
                    and 
                    cha.id_comb = %d
                    and 
                    ai.id_artefactinstance = %d
                    and 
                    atfb.id_artefactscope = ars.id_artefactscope
                    and 
                    atfb.id_artefacttype = at.id_artefacttype
                    and 
                    atfb.id_building = ab.id_building
                    and 
                    ab.id_attachedbuilding = %d
                    and 
                    ab.id_cell = %d
                    and 
                    ab.id_building = %d
                    ",
                    $this->comb,
                    $this->id_artefactinstance,
                    $this->attachedbid,
                    $this->id_cell,
                    $this->bid );

        $res = $common->getResultArray ( "id_artefactinstance_id_comb_id_artefactinstance_id_building_".$this->comb."_".$this->id_artefactinstance."_".$this->bid, $sql, "", $params["uiid"], "LOCAL_CACHE" );
        if(!$res)
        {
            $this->errormsg .= " SYSTEM ERROR UA_AttachBuildingArtefact::checkAction3";
            return false;
        }

        // подходит ли артефакт к этому зданию
        $sql = sprintf ("
                SELECT
                    (
                        SELECT
                            COUNT( aab.id_artefact )
                        FROM
                            ds_artefactattachedbuilding aab
                        WHERE
                            aab.id_attachedbuilding = ab.id_attachedbuilding
                        and aab.collection = ".$this->collection."
                        GROUP BY
                            aab.id_attachedbuilding
                    ) as curcount,
                    bha.value,
                    ab.inupgrade
                FROM
                    ds_attribute a,
                    ds_attributename an,
                    ds_attributetype at,
                    ds_buildinghasattribute bha,
                    ds_attachedbuilding ab
                WHERE
                    bha.id_building = ab.id_building
                    AND bha.id_attribute = a.id_attribute
                    AND a.id_attributetype = at.id_attributetype
                    AND a.id_attributename = an.id_attributename
                    AND at.typename = 'MAX_AMOUNT'
                    AND an.attributename = 'ARTEFACT_SLOTS'
                    AND ab.id_attachedbuilding = %d
                ",
                $this->attachedbid
            );

        $res = $common->getResultArray("", $sql, "", "", "");
        assert(empty($common->errormsg));
        if(!$res)
        {
            $this->errormsg .= " SYSTEM ERROR Something wrong with artefacts "; // UA_AttachBuildingArtefact::checkAction4
            return false;
        }

        if ($this->collection==2) {
            $slots = DE_WorldSettings::CreateForCurentWorld()->get('OFFENSIVE_ARTEFACTS_SLOTS');
        }
        else {
            $slots = $res[0]['value'];
        }

        if(($slots - $res[0]['curcount']) <= 0)
        {
            $this->errormsg .= " SYSTEM ERROR No free slots in this building for an artefact ";
            return false;
        }
        
        $this->buildingInUpgrade = false;
        if($res[0]['inupgrade'] == 't')
        {
            $this->buildingInUpgrade = true;
        }
        
        $res = "";

        return true;
    }

    // выполняем действие
    function performAction()
    {
        global $params;
        global $common;

        if ($this->collection == 2) {
            $res = $common->getResultArray ("", "select * from ds_unitcontainer
                where id_basecomb=".$this->comb." and use_artefacts and inaction");
            if (count($res)) return true;
        }


        $common->clearLocalCache ( $params["uiid"] );

        if ( $this->errorname ) {

            $messparams = array();
            DE_SystemMessages::AddMessageFromAction(__CLASS__, $this->errorname, $messparams);
        } else {

            if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
            {
                $this->_vocationModeAlert();            
                return true;
            }
            
            if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
            {
                $this->_shildModeAlert();           
                return true;
            }
            
            
            
            $artefactAttributes = DE_Artefact::CreateFromArtefactInstanceID((int)$this->id_artefactinstance)->getAttributes();
            $combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);

            switch ( $this->scopename )
            {

                case 'SPHERE':

                    // если здание апгрейдится, то не изменяем атрибуты соты
                    if($this->buildingInUpgrade)
                    {
                        break;
                    }
                    
                    $attributeTypeNames = $artefactAttributes->getAttributeTypesNames();
                    foreach($attributeTypeNames as $k1=>$attributeTypeName)
                    {
                        $attributeNames = $artefactAttributes->getAttributeNamesByTypeName($attributeTypeName);
                        foreach($attributeNames as $k2=>$attributeName)
                        {
                            if($combAttributes->haveAttribute($attributeTypeName, $attributeName))
                            {
                                $delta = $artefactAttributes->getAttribute($attributeTypeName, $attributeName);
                                $combAttributes->deltaAttribute($attributeTypeName, $attributeName, $delta);
                            }
                        }
                    }

                    break;

                case 'BUILDING':
                    
                    // если здание апгрейдится, то не изменяем атрибуты соты
                    if($this->buildingInUpgrade)
                    {
                        break;
                    }

                    $attributeTypeNames = $artefactAttributes->getAttributeTypesNames();
                    foreach($attributeTypeNames as $k1=>$attributeTypeName)
                    {
                        $attributeNames = $artefactAttributes->getAttributeNamesByTypeName($attributeTypeName);
                        foreach($attributeNames as $k2=>$attributeName)
                        {
                            if($combAttributes->haveAttribute($attributeTypeName, $attributeName))
                            {
                                $delta = $artefactAttributes->getAttribute($attributeTypeName, $attributeName);
                                $combAttributes->deltaAttribute($attributeTypeName, $attributeName, $delta);
                            }
                        }
                    }
                    break;

                case 'COMB':

                    $sql = sprintf ( "insert into ds_artefactattachedcomb ( id_comb, id_artefact, id_attachedbuilding ) values ( %d, %d, %d )",
                        $this->comb,
                        $this->id_artefactinstance,
                        $this->attachedbid );

                    $res = $common->executeQuery ( $sql );
                    if ( $res === false ) {
                        $this->errormsg .= " SYSTEM ERROR Can't insert artefact ".$this->id_artefactinstance." into building ".$this->attachedbid." on comb ".$this->comb;// UA_AttachBuildingArtefact::performAction8
                        return false;
                    }

                    $attributeTypeNames = $artefactAttributes->getAttributeTypesNames();
                    foreach($attributeTypeNames as $k1=>$attributeTypeName)
                    {
                        $attributeNames = $artefactAttributes->getAttributeNamesByTypeName($attributeTypeName);
                        foreach($attributeNames as $k2=>$attributeName)
                        {
                            if($combAttributes->haveAttribute($attributeTypeName, $attributeName))
                            {
                                $delta = $artefactAttributes->getAttribute($attributeTypeName, $attributeName);
                                $combAttributes->deltaAttribute($attributeTypeName, $attributeName, $delta);
                            }
                        }
                    }
    
                    break;
                    
                case 'LOVE':

                    // если здание апгрейдится, то не изменяем атрибуты соты
                    if($this->buildingInUpgrade)
                    {
                        break;
                    }

                    $attributeTypeNames = $artefactAttributes->getAttributeTypesNames();
                    foreach($attributeTypeNames as $k1=>$attributeTypeName)
                    {
                        $attributeNames = $artefactAttributes->getAttributeNamesByTypeName($attributeTypeName);
                        foreach($attributeNames as $k2=>$attributeName)
                        {
                            if($combAttributes->haveAttribute($attributeTypeName, $attributeName))
                            {
                                $delta = $artefactAttributes->getAttribute($attributeTypeName, $attributeName);
                                $combAttributes->deltaAttribute($attributeTypeName, $attributeName, $delta);
                            }
                        }
                    }
                    break;

                case 'COMBCOMPLEX':

                    $sql = sprintf ( "insert into ds_artefactattachedcomplex ( id_combcomplex, id_artefact, id_attachedbuilding )
                                values ( %d, %d, %d )",
                            $this->combcomplex,
                            $this->id_artefactinstance,
                            $this->attachedbid );
                            
                    $res = $common->executeQuery ( $sql );
                    if ( $res === false ) {
                        $this->errormsg .= " SYSTEM ERROR UA_AttachBuildingArtefact::performAction11";
    
                        return false;
                    }

                    break;

                case 'ALLIANCE':

                    $alliance = DE_UserInstance::CreateFromID((int)$params['uiid'])->getAlliance();

                    if ($alliance instanceof DE_AllianceEmpty)
                    {
                        $messparams = array();
                        DE_SystemMessages::AddMessageFromAction(__CLASS__, 'NOT_IN_ALLIANCE', $messparams);
                        return true;
                    } else {
                        $this->id_alliance = $alliance->getID();
                    }

                    assert($this->id_alliance > 0);

                    $sql = sprintf ( "insert into ds_artefactattachedalliance
                                ( id_artefact, id_alliance, id_attachedbuilding )
                                values ( %d, %d, %d )",
                                $this->id_artefactinstance,
                                $this->id_alliance,
                                $this->attachedbid );
                    $res = $common->executeQuery($sql);
                    assert(empty($common->errormsg));
                    break;

                case 'WAR':
                    // Если мы на захватываемой колонии
                    if (DE_Comb::CreateFromID($this->comb)->getIsCapture()) {
                        // Проверяем на ограничение вставки артов
                        $maxArt = DE_WorldSettings::CreateForCurentWorld()->get('MAX_WAR_ARTEFACT_SLOTS_ON_COLONIY');
                        
                        // Если есть ограничение 
                        if ($maxArt > 0) {
                            // Смотрим, сколько сейчас уже есть артефактов в военной базе
                            $sql = sprintf ( "select * from ds_artefactattachedcomb where 
                            id_comb = %d AND id_attachedbuilding = %d", $this->comb, $this->attachedbid);
                            $resArt = $common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
                            assert(empty($common->errormsg));
                             
                            $artCount = count($resArt);
                            assert($artCount >= 0);
                            
                            if ($artCount >= $maxArt) {
                                DE_SystemMessages::AddMessageFromAction(__CLASS__, "MAX_WAR_ARTEFACT_SLOTS_LIMIT", array() );
                                return true;
                            }
                        }
                    }
                    
                    $sql = sprintf ( "insert into ds_artefactattachedcomb ( id_comb, id_artefact, id_attachedbuilding ) values ( %d, %d, %d )",
                                $this->comb,
                                $this->id_artefactinstance,
                                $this->attachedbid );

                    $res = $common->executeQuery ( $sql );
                    if ( $res === false ) {
                        $this->errormsg .= " SYSTEM ERROR UA_AttachBuildingArtefact::performAction14";

                        return false;
                    }
    
                    break;
            }

            if( $this->delArtefact )
            {
                $sql = sprintf (
                "
                    DELETE
                    FROM
                        ds_combhasartefact
                    WHERE
                        id_artefact = %d
                    ",
                $this->id_artefactinstance
                );
                $common->executeQuery( $sql);
                assert( empty( $common->errormsg ) );

                $sql = sprintf (
                "
                    DELETE
                    FROM
                        ds_artefactinstance
                    WHERE
                        id_artefactinstance = %d
                    ",
                $this->id_artefactinstance
                );
                $common->executeQuery( $sql);
                assert( empty( $common->errormsg ) );

                return true;
            }


            // название сотыs
            $messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

            // название артефакта
            $sql = sprintf ( "select text as artefactname
                    from DS_LocalizedTextLanguage ltl,
                    ds_artefact a,
                    ds_artefactinstance ai
                    where a.ID_LocalizedName = ltl.ID_LocalizedText
                    and ltl.ID_Language = %d
                    and a.id_artefact = ai.id_artefact
                    and ai.id_artefactinstance = %d",
            $params["lang"],
            $this->id_artefactinstance );

            $res = $common->getResultArray ( "artefactname_id_language_id_artefactinstance_".$params["lang"]."_".$this->id_artefactinstance, $sql, "", "", "GLOBAL_CACHE" );
            if ( !$res ) {
                $this->errormsg .= " SYSTEM ERROR UA_AttachBuildingArtefact::performAction2";

                return false;
            }

            $messparams[1] = "{b} ".$res[0]['artefactname']."{/b}";

            // название здания
            $raceBuilding = DE_Building::CreateFromID((int)$this->bid)->getRaceBuilding((int)$params["race"]);
            $messparams[2] = "{b} ".$raceBuilding->getLocalizedName()."{/b}";

            $sql = "SELECT *
                    FROM ds_artefactattachedbuilding
                    WHERE
                        id_artefact = ".$this->id_artefactinstance." AND
                        id_attachedbuilding = ".$this->attachedbid;
            $res = $common->getResultArray ( "", $sql, "", "", "" );
            if ( !$res ){
                // приделываем артефакт к зданию
                $sql = sprintf ( "insert into ds_artefactattachedbuilding ( id_artefact, id_attachedbuilding, collection ) values ( %d, %d, %d )",
                $this->id_artefactinstance, $this->attachedbid, $this->collection);

                $res = $common->executeQuery ( $sql );
                if ( $res === false ) {
                    $this->errormsg .= " SYSTEM ERROR UA_AttachBuildingArtefact: Can't insert artefact ".$this->id_artefactinstance." into building ".$this->attachedbid; // UA_AttachBuildingArtefact::performAction4
                    return false;
                }
            }

            // убираем артефакт из хранилища
            $sql = sprintf ( "delete from ds_combhasartefact where id_comb = %d and id_artefact = %d",
            $this->comb, $this->id_artefactinstance );

            $res = $common->executeQuery ( $sql );
            if ( $res === false ) {
                $this->errormsg .= " SYSTEM ERROR Can't remove artefact ".$this->id_artefactinstance." from repository in comb ".$this->comb; // UA_AttachBuildingArtefact::performAction5
                return false;
            }

            if ($this->bid==26) {
                if ($this->collection==2) $messparams[3] = " (в атаке)";
                else $messparams[3] = " (в защите)";
            }
            else $messparams[3] = "";

            // приделываем бонусы

            DE_SystemMessages::AddMessageFromAction(__CLASS__, "ATTACH_ARTEFACT_MESS", $messparams );

            /*      HELPER      */          

            $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><artbuild />";
            $doc = domxml_open_mem ( $xml );
            $root = $doc->document_element ();

            $ch = $root->new_child ( "attach" );
            $ch->set_attribute ( "id", $this->id_artefactinstance );
            $ch->set_attribute ( "allow", 1 );
            $root->append_child ( $ch );

            SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'helper' );

        }



        $res = "";
        $sql = "";
        
        //if($_SESSION['profi_game'])
        //{
            $noCellData = true;
        //} else {
        //  $noCellData = false;
        //}
                
        // рефреш по минимуму
        if(!$this->refresh(2, "", "", "", $noCellData)) 
        {
            return false;
        }

        return true;
    }
}

?>