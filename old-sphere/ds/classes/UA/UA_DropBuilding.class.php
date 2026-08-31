<?php $CVS_Version = '$Revision$ ; $Date$';

// команда снести здание
class UA_DropBuilding extends UA_Building
{

    function UA_DropBuilding ()
    {
        UA_Building::UA_Building ();
    }

    // проверяем параметры
    function checkLocalParams ()
    {
        $this->cellnumber = (int)$this->root->get_attribute("c");
        assert($this->cellnumber > 0);

        $cellData = ME_CombViewGetCellIDAndBuildigIDByCell::Exec($this->cellnumber, true);

        $this->id_cell = $cellData['CELL_ID'];
        assert(is_int($this->id_cell) && $this->id_cell > 0);

        $this->groundid = $cellData['GROUND_TYPE_ID'];
        assert(is_int($this->groundid) && $this->groundid > 0);


        $this->attachedbid = $cellData['ATTACHED_BUILDING_ID'];
        assert(is_int($this->attachedbid) && $this->attachedbid >= 0);

        $this->bid = $cellData['BUILDING_ID'];
        assert(is_int($this->bid) && $this->bid >= 0);

        return true;
    }

    // проверяем допустимость действия
    function checkAction ()
    {
        global $common;
        global $params;

        // можно ли его сносить
        $sql = "
                SELECT *
                FROM
                    ds_attachedbuildinghasattribute AS atbha                    
                WHERE
                    atbha.id_attachedbuilding = $this->attachedbid
                    AND
                    atbha.id_attribute = 43 -- 'CURRENT_FLAG' => 'DESTROYABLE'";
        $res = $common->getResultArray ("", $sql, "", "", "");
        assert(empty($common->erromsg));

        if(count($res) != 1)
        {
            ds_error("This building can't be deleted", false, true, true);
        }

        return true;
    }

    // выполняем действие
    function performAction ()
    {
        global $params;
        global $common;

        $combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);

        $buildingAttributes = DE_BuildingAttributes::CreateFromBuildingID((int)$this->bid);

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

        if(!DE_WorldSettings::CreateForCurentWorld()->get('CAN_DROP_BUILDINGS'))
        {
            ds_error("Sorry. This action disabled.");
        }

        //Логируем удаление игровых объектов
        SU_LogDeleteCombObjects::LogForCurrentUserAndComb('BUILDING', (int)$this->bid, (int)$this->id_cell);

        // вынимаем артефакты и отправляем их в хранилище

        // активно ли здание
        $sql = sprintf ( "select active
                from ds_attachedbuilding
                where id_attachedbuilding = %d",
        $this->attachedbid );

        $res = $common->getResultArray ( "is_active_id_attachedbuilding_".$this->attachedbid, $sql, "", $params["uiid"], "LOCAL_CACHE" );
        if ( !$res ) {
            $this->errormsg = "SYSTEM ERROR - UA_DropBuilding::performAction1";

            return false;
        }

        $active = $res[0]['active'];

        //если активно - снимаем бонусы артефактов
        if ( $res[0]['active'] == 't' )
        {
            ME_AddBuildingAttributesToComb::AddArtefactAttributes($this->attachedbid, $combAttributes, 'minus');
        }

        // артефакты в хранилище
        $sql = sprintf ( "insert into ds_combhasartefact
                ( id_comb, id_artefact ) select %d, aab.id_artefact
                from ds_artefactattachedbuilding aab,
                ds_attachedbuilding ab
                where aab.id_attachedbuilding = ab.id_attachedbuilding
                and ab.id_attachedbuilding = %d",
        $this->comb,
        $this->attachedbid );

        $res = $common->executeQuery ( $sql );
        if ( $res === false ) {
            $this->errormsg = "SYSTEM ERROR - UA_DropBuilding::performAction3";

            return false;
        }

        // вынимаем их из здания
        $sql = sprintf ( "delete from ds_artefactattachedbuilding
                where id_attachedbuilding = %d",
        $this->attachedbid );

        $res = $common->executeQuery ( $sql );
        if ( $res === false ) {
            $this->errormsg = "SYSTEM ERROR - UA_DropBuilding::performAction4";

            return false;
        }

        $this->deact = new UA_DeactivateBuilding;
        if ( !$this->deact ) {
            $this->errormsg = "SYSTEM ERROR - UA_DropBuilding::performAction5";

            return false;
        }

        // деактивируем зависимые здания
        $this->deact->sysmsg = &$this->sysmsg;

        //$this->deact->destr_flag = 1;
        if ( !$this->deact->getBuildingToDeact ( $this->bid, $this->comb ) ) {
            $this->errormsg = $this->deact->errormsg;
            return false;
        }

        if ( sizeof ( $this->deact->todeact > 0 ) )
        {
            if ( !$this->deact->deactivate ( $this->deact->todeact, $this->comb, $params["uiid"] , $params["lang"] ) ) {
                $this->errormsg = $this->deact->errormsg;
                return false;
            }
        }

        // прибиваем эвенты
        $sql = "SELECT id_eventqueue
                FROM ds_attachedbuildingbindeventqueue
                WHERE id_attachedbuilding = ".$this->attachedbid;
        $res = $common->getResultArray('', $sql);
        assert(empty($common->errormsg));

        if(count($res) > 0)
        {
            DE_EventQueue::CreateFromID((int)$res[0]['id_eventqueue'])->setIsTrash(true);
        }

        // стираем здание
        $sql = sprintf ( "delete from DS_AttachedBuildingHasAttribute
                where ID_AttachedBuilding = %d",
        $this->attachedbid );

        $res = $common->executeQuery ( $sql );
        if ( $res === false ) {
            $this->errormsg = "SYSTEM ERROR - UA_DropBuilding::performAction8";

            return false;
        }

        $sql = sprintf ( "delete from DS_AttachedBuilding
                where ID_AttachedBuilding = %d",
        $this->attachedbid );

        $res = $common->executeQuery ( $sql );
        if ( $res === false ) {
            $this->errormsg = "SYSTEM ERROR - UA_DropBuilding::performAction9";

            return false;
        }

        // Если здание Генератор защитного поля
        if ($this->bid == 91) {
            // Выставляем защиту 0%
            $defend = DE_CombProtectiveField::CreateFromCombID((int)$this->comb);
            $defend->setProtectiveField(0);
        }

        // если здание активно - уменьшаем значения upkepp\production для соты
        if ( $active == 't' )
        {
            ME_AddBuildingAttributesToComb::AddAttributes($buildingAttributes, $combAttributes, 'minus');
        }

        // название соты
        $messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

        // название здания
        $raceBuilding = DE_Building::CreateFromID((int)$this->bid)->getRaceBuilding((int)$params["race"]);
        $messparams[1] = "{b} ".$raceBuilding->getLocalizedName()."{/b}";

        // системное сообщение
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "DROP_BUILDING_MESS", $messparams );

        //Рестартим вид соты
        CR_CombView::CreateFromCombID((int)$this->comb)->resetBuildings(DE_Cell::CreateFromID($this->id_cell)->getCellTypeID());

        // Выбираем очки за здание
        $buildinScore = DS_Building::GetBuildingScore((int)$this->bid);
            
        // уменьшаем рейтинг соты
        DE_Comb::CreateFromID($this->comb)->deltaCombScore(-$buildinScore);
        
        // Уменьшаем строительный рейтинг игрока
        $buildinScore2 = DS_Building::GetBuildingScoreNew((int)$this->bid);
        DE_Ranking::CreateFromID($params["uiid"])->deltaBuildingRanking(-$buildinScore2);

        // рефреш с актуализацией соты и ресурсов
        if ( !$this->refresh ( 2, "", 1, 1 ) ) {
            return false;
        }

        return true;
    }
}

?>