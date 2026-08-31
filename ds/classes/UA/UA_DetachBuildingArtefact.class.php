<?php $CVS_Version = '$Revision$ ; $Date$';

// команда извлеч артефакт из здания



class UA_DetachBuildingArtefact extends UA_Building {

    public $id_artefactinstance;
    public $buildingInUpgrade;
    private $collection;

    function UA_DetachBuildingArtefact () {
        UA_Action::UA_Action ();
    }

    // проверяем параметры
    function checkLocalParams () {

        // id_artefactinstance
        $this->id_artefactinstance = (int)$this->root->get_attribute ( "id" );
        assert($this->id_artefactinstance > 0);

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

        $this->collection = (int)$this->root->get_attribute("tabNum");

        return true;
    }

    // провереям допустимость действия
    function checkAction () {

        global $common;
        global $params;

        // есть ли такой артефак у здания
        $sql = sprintf ( "
                SELECT 
                    aab.id_artefact,
                    ab.inupgrade
                FROM 
                    ds_artefactattachedbuilding aab,
                    ds_attachedbuilding ab
                WHERE 
                    aab.id_attachedbuilding = %d
                    AND 
                    aab.id_attachedbuilding = ab.id_attachedbuilding
                    AND 
                    aab.id_artefact = %d
                    AND 
                    ab.id_building = %d
                    AND 
                    ab.id_cell = %d",
                    $this->attachedbid,
                    $this->id_artefactinstance,
                    $this->bid,
                    $this->id_cell 
            );

        $res = $common->getResultArray ( "id_artefactinstance_id_attachedbuilding_id_artefactinstance_".$this->attachedbid."_".$this->id_artefactinstance, $sql, "", $params["uiid"], "LOCAL_CACHE" );
        if(!$res) 
        {
            $this->errormsg .= " SYSTEM ERROR UA_DetachBuildingArtefact::checkAction1";
            return false;
        }
        
        $this->buildingInUpgrade = false;
        if($res[0]['inupgrade'] == 't')
        {
            $this->buildingInUpgrade = true;
        }

        return true;
    }

    // выполняем действие
    function performAction () {

        global $params;
        global $common;

        if ($this->collection == 2) {
            $res = $common->getResultArray ("", "select * from ds_unitcontainer
                where id_basecomb=".$this->comb." and use_artefacts and inaction");
            if (count($res)) return true;
        }

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

        // название соты
        $messparams[0] = DE_Comb::CreateFromID((int)$this->comb)->getName();

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
            $this->errormsg .= " SYSTEM ERROR UA_DetachBuildingArtefact::performAction2";
            
            return false;
        }

        $messparams[1] = $res[0]['artefactname'];

        // название здания      
        $raceBuilding = DE_Building::CreateFromID((int)$this->bid)->getRaceBuilding((int)$params["race"]);          
        $messparams[2] = $raceBuilding->getLocalizedName();

        // удаляем артефакт из здания
        $sql = sprintf ( "delete from ds_artefactattachedbuilding 
                where id_artefact = %d 
                and id_attachedbuilding = %d",
                $this->id_artefactinstance, 
                $this->attachedbid );

        $res = $common->executeQuery ( $sql );
        if ( !$res ) {
            $this->errormsg .= " SYSTEM ERROR UA_DetachBuildingArtefact::performAction4";
            
            return false;
        }

        // помещаем артефакт в хранилище
        $sql = sprintf ( "insert into ds_combhasartefact 
            ( id_comb, id_artefact ) values ( %d, %d )",
            $this->comb,
            $this->id_artefactinstance );

        $res = $common->executeQuery ( $sql );
        if ( !$res ) {
            $this->errormsg .= " SYSTEM ERROR UA_DetachBuildingArtefact::performAction5";
            
            return false;
        }

        // снимаем бонусы
        $sql = sprintf ( "select ars.scopename
                from ds_artefactscope ars,
                ds_artefact ar,
                ds_artefactinstance ai
                where ars.id_artefactscope = ar.id_artefactscope
                and ar.id_artefact = ai.id_artefact
                and ai.id_artefactinstance = %d",
                $this->id_artefactinstance );

        $res = $common->getResultArray ( "scopename_id_artefactinstance_".$this->id_artefactinstance, $sql, "", "", "WORLD_CACHE" );
        if ( !$res ) {
            $this->errormsg .= " SYSTEM ERROR UA_DetachBuildingArtefact::performAction6";
            
            return false;
        }

        $artefactAttributes = DE_Artefact::CreateFromArtefactInstanceID((int)$this->id_artefactinstance)->getAttributes();
        $combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);

        switch ( $res[0]['scopename'] )
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
                                $combAttributes->deltaAttribute($attributeTypeName, $attributeName, -$delta);
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
                                $combAttributes->deltaAttribute($attributeTypeName, $attributeName, -$delta);
                            }
                        }
                    }   
            break;

            case 'COMB':

                $sql = sprintf ( "delete from ds_artefactattachedcomb 
                        where id_comb = %d
                        and id_artefact = %d",
                        $this->comb,
                        $this->id_artefactinstance);

                $res = $common->executeQuery ( $sql );
                if ( !$res ) {
                    $this->errormsg .= " SYSTEM ERROR UA_DetachBuildingArtefact::performAction8";
                    
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
                                $combAttributes->deltaAttribute($attributeTypeName, $attributeName, -$delta);
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
                                $combAttributes->deltaAttribute($attributeTypeName, $attributeName, -$delta);
                            }
                        }
                    }   
            break;

            case 'COMBCOMPLEX':

                $sql = sprintf ( "delete from ds_artefactattachedcomplex 
                        where id_combcomplex = %d
                        and  id_artefact = %d",
                        $this->combcomplex,
                        $this->id_artefactinstance );

                $res = $common->executeQuery ( $sql );
                if ( !$res ) {
                    $this->errormsg .= " SYSTEM ERROR UA_DetachBuildingArtefact::performAction10";
                    
                    return false;
                }
        
            break;

            case 'ALLIANCE':

                $sql = sprintf ( "delete from ds_artefactattachedalliance
                        where id_artefact = %d",
                        $this->id_artefactinstance );

                $res = $common->executeQuery ( $sql );
                if ( !$res ) {
                    $this->errormsg .= " SYSTEM ERROR UA_DetachBuildingArtefact::performAction11";
                    
                    return false;
                }

            break;
            
            case 'WAR':

                $sql = sprintf ( "delete from ds_artefactattachedcomb
                        where id_comb = %d
                        and  id_artefact = %d",
                        $this->comb,
                        $this->id_artefactinstance);

                $res = $common->executeQuery ( $sql );
                if ( !$res ) {
                    $this->errormsg .= " SYSTEM ERROR UA_DetachBuildingArtefact::performAction12";
                    
                    return false;
                }

            break;
        }

        // системное сообщение
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "DETACH_ARTEFACT_MESS", $messparams );

        $res = "";
        $sql = "";
        
        //if($_SESSION['profi_game'])
        //{
            $noCellData = true;
        //} else {
        //  $noCellData = false;
        //}
        
        // минимальный рефреш
        if(!$this->refresh(2, "", "", "", $noCellData)) 
        {
            return false;
        }
        
        /* HELPER */
        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><artbuild />";
        $doc = domxml_open_mem ( $xml );
        $root = $doc->document_element ();

        $ch = $root->new_child ( "detach" );
        $ch->set_attribute ( "id", $this->id_artefactinstance );
        $ch->set_attribute ( "allow", 0 );
        $root->append_child ( $ch );

        SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'helper' );

        return true;
    }
}

?>