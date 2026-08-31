<?php $CVS_Version = '$Revision$ ; $Date$';

// команда начать строительство здания
class UA_ConstructBuilding extends UA_Building
{
    private $isResourceEnought = true;

    private $cells = array();

    function __construct()
    {
        parent::UA_Building ();
    }

    // проверяем параметры
    function checkLocalParams()
    {
        $tmpArr = explode(',', $this->root->get_attribute("c"));
        assert(count($tmpArr) > 0);
        
        foreach ($tmpArr as $key => $cell) {
            assert($cell > 0);
            $cellData = ME_CombViewGetCellIDAndBuildigIDByCell::Exec((int)$cell, false);
            $this->cells[] = $cellData['CELL_ID'];
            
        }

        $this->cells = array_reverse($this->cells);
        // id ячейки
        $this->id_cell = $this->cells[0];
        assert(is_int($this->id_cell) && $this->id_cell > 0);

        $this->groundid = $cellData['GROUND_TYPE_ID'];
        assert(is_int($this->groundid) && $this->groundid > 0);

        // id здания
        $this->bid = $this->root->get_attribute ("b");
        if (empty($this->bid))
        {
            $this->errormsg .= " SYSTEM ERROR UA_ConstructBuilding::checkLocalParams2";
            return false;
        }

        // своя ли сота
        if (!$this->isOwnComb($this->id_cell))
        {
            return false;
        }

        return true;
    }

    // проверяем допустимость действия (только для одного здания, на первой попавшейся ячейке)
    // так как при возможности построить хотя бы одно здание, действие будет выполнено
    function checkAction()
    {
        global $common;
        global $params;

        // доступно расе
        assert($this->isBuildingAllowRace($this->bid, $params["race"]));

        // соблюдена уникальность
        assert($this->isUniqueTrue($this->comb, $this->bid));

        // присутствуют необходимые здания
        assert($this->isNeedsBuildingPresent($this->comb, $this->bid));

        // выключено ли здание
        assert(!$this->isDisabledBuilding($this->world, $this->bid));

        // можно строить на этой ячейке
        assert($this->isAllowBuildOn($this->bid, $this->id_cell, $this->groundid));

        // хватает ресурсов
        $lastTestResInfo = "";
        if(!$this->isResourcesEnough($this->comb, $this->bid, $lastTestResInfo))
        {
            $this->isResourceEnought = false;
        }

        // хватает науки
        assert($this->isScienceEnough($params["uiid"], $this->bid));

        // хватает одновременных строек
        assert($this->isSimiltaneousBuildsEnough($this->comb));

        return true;
    }

    // выполняем действие
    function performAction ()
    {
        global $params;
        global $common;

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

        $combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);

        // название соты
        $messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

        // название здания
        $raceBuilding = DE_Building::CreateFromID((int)$this->bid)->getRaceBuilding((int)$params["race"]);
        $messparams[1] = "{b} ".$raceBuilding->getLocalizedName()."{/b}";

        $lastTestResInfo = "";
        
        // получаем время окончания эвента
        if ( !$this->getEventEndTime () ) {
            return false;
        }

        // Идем по всем зданиям
        foreach ($this->cells as $key => $cell) {

            $this->id_cell = (int)$cell;
            assert($this->id_cell > 0);

            // Если кончились ресурсы
            if(!$this->isResourcesEnough($this->comb, $this->bid, $lastTestResInfo))
            {
                $this->processNotEnoughtResources();
                break;
            }
            
            // Соблюдена уникальность 
            if (!$this->isUniqueTrue($this->comb, $this->bid)) {
                break;
            }
        
            // Если кончилось количество одновременно стоящихся зданий на соте
            if (!$this->isSimiltaneousBuildsEnough($this->comb)) {
                break;
            }
            
            $sql = sprintf ( "
                insert into DS_AttachedBuilding 
                (ID_Building, ID_Cell, active, underconstruction, starttime, endtime, inupgrade, id_comb)
                values 
                ( %d, %d, false, true, '%s', '%s', false, %d )",
                $this->bid, $this->id_cell, $this->starttime, $this->endtime, $this->comb );

            $res = $common->executeQuery ( $sql );
            if ( $res === false ) {
                $this->errormsg = "SYSTEM ERROR - UA_ConstructBuilding::performAction3";

                return false;
            }

            // получаем id_attachedbuilding
            $sql  = sprintf ( "
                select id_attachedbuilding
                from ds_attachedbuilding
                where id_cell = %d",
            $this->id_cell );

            $res = $common->getResultArray ( "id_attachedbuilding", $sql, "", "", "" );
            if ( !$res ) {
                $this->errormsg = "SYSTEM ERROR - UA_ConstructBuilding::performAction4";

                return false;
            }

            $this->attachedbid = $res[0]['id_attachedbuilding'];

            // создаем эвент
            $this->createEvent('CONSTRUCT_BUILDING');

            $sql = sprintf ( "insert into DS_AttachedBuildingBindEventQueue
                (ID_AttachedBuilding, ID_EventQueue) 
                values (%d, %d)",
            $this->attachedbid, $this->eqid );

            $res = $common->executeQuery ( $sql );
            if ( $res == false ) {
                $this->errormsg = "SYSTEM ERROR - UA_ConstructBuilding::performAction8";

                return false;
            }

            // увеличиваем значение идущих строек
            $combAttributes->deltaAttribute('CURRENT_AMOUNT', 'SIMILTANEOUS_BUILDS', (float)1);

            // получаем список ресурсов, необходимых дня постройка здания
            $buildingAttributes = DE_BuildingAttributes::CreateFromBuildingID((int)$this->bid);
            $attributeNames = $buildingAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');

            foreach ($attributeNames as $k=>$attributeName)
            {
                if ( $attributeName == 'CONSTRUCTION_TIME')
                {
                    continue;
                }

                if ($attributeName == 'SILVER_CREDIT' || $attributeName == 'GOLD_CREDIT')
                {
                    //Смотрим модифаер
                    $worldSettings = DE_WorldSettings::CreateForCurentWorld();
                    $modifier = $worldSettings->get('BUILDING_CAN_BUIL_BY_MONEY');

                    //Проверяем модифаер
                    assert($modifier == 1 || $modifier == 0);

                    //Если в настройках мира у нас стоит запрет на постройку зданий за деньги
                    if ($modifier == 0)
                    {
                        continue;
                    }

                    //Здесь будет снятие денег
                    continue;
                }

                //Снимам ресурсы за постройку здания
                $value = $buildingAttributes->getAttribute('PRODUCTION_COSTS', $attributeName);
                $combAttributes->deltaAttribute('CURRENT_AMOUNT', $attributeName, -$value);

            }

            // системное сообщение
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "START_BUILDING_MESS", $messparams);
        }


        //Рестартим вид соты
        CR_CombView::CreateFromCombID((int)$this->comb)->resetBuildings(DE_Cell::CreateFromID($this->id_cell)->getCellTypeID());

        // рефреш с актуализацией соты и ресурсов
        if (!$this->refresh(2, "", 1, 1))
        {
            return false;
        }

        $current = $combAttributes->getAttribute('CURRENT_AMOUNT', 'SIMILTANEOUS_BUILDS');
        $maxBuilding = $combAttributes->getAttribute('MAX_AMOUNT', 'SIMILTANEOUS_BUILDS') + $this->worldSettings->get('SIMILTANEOUS_BUILDS_ADDON');

        if ($current == $maxBuilding) {
            $alertmsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('MAX_SYMULTANEOSLY_BUILDINDS', array($maxBuilding), null, null, false);

            //$alertmsg = iconv('utf8', 'cp1257', $alertmsg);
            
            $alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$alertmsg.']]></txt></swloader>';

            SU_OutputHandler::Singleton()->addReadyString($alertText);
        }

        return true;
    }

    function processNotEnoughtResources()
    {
        $messparams = array();
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "BUILD_NOT_ENOUGHT_RESOURCES", $messparams);

        // рефреш с актуализацией соты и ресурсов
        assert($this->refresh(2, "", 1, 1));
    }

    // получить время окончания эвента
    function getEventEndTime()
    {
        global $common;
        global $params;

        $sql = sprintf ( "select now() as starttime,
                now()+(select (text((".$this->worldSettings->get('TURN_TIME')."*60*bha.value
                )))::interval) as endtime
                from ds_building b,
                ds_buildinghasattribute bha,
                ds_attribute a,
                ds_attributename atn,
                ds_attributetype att
                where b.id_building = bha.id_building
                and bha.id_attribute = a.id_attribute
                and a.id_attributename = atn.id_attributename
                and a.id_attributetype = att.id_attributetype
                and att.typename = 'PRODUCTION_COSTS'
                and atn.attributename = 'CONSTRUCTION_TIME'
                and b.id_building = %d",
        $this->bid );

        $res = $common->getResultArray ( "construct_building_start_time_end_time", $sql, "", "", "NO_CACHE" );
        if (!$res)
        {
            $this->errormsg = "SYSTEM ERROR - UA_ConstructBuilding::getEventEndTime1";

            return false;
        }

        $this->endtime = $res[0]['endtime'];
        $this->starttime = $res[0]['starttime'];

        $res = "";

        return true;
    }
}

?>