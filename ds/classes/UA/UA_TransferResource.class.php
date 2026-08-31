<?php $CVS_Version = '$Revision$ ; $Date$';
// команда отправки ресурсов на другую соту
class UA_TransferResource extends UA_Action
{

    public $id_article;
    public $id_category;
    public $amount;
    public $categoryname;
    public $immediate;
    public $tradetime;
    public $tradeby;
    public $startprice;
    public $comb_to;
    public $errorname;

    /**
     * Enter description here...
     *
     * @var DE_Comb
     */
    private $combFrom;

    /**
     * Enter description here...
     *
     * @var DE_Comb
     */
    private $combTo;

    /**
     * Enter description here...
     *
     * @var array
     */
    private $messparams = array();

    /**
     * Время жизни артефакта
     *
     * @var str
     */
    public $artefactTime;


    function UA_TransferResource ()
    {
        UA_Action::UA_Action ();
    }

    // проверить параметры
    function checkLocalParams ()
    {
        // куда отправить
        $this->comb_to = (int)$this->root->get_attribute ( "combto" );
        if($this->comb_to <= 0)
        {
            $this->errorname[] = 'NOT_FOUND_COLONY';
            return true;
        }

        // id товара
        $this->id_article = (int)$this->root->get_attribute ( "id_article" );
        assert($this->id_article > 0);

        // id категории
        $this->id_category = (int)$this->root->get_attribute ( "id_category" );
        assert($this->id_category > 0);

        // колличество
        $this->amount = (float)round($this->root->get_attribute("amount"));
        assert($this->amount > 0);

        $this->tempamount = $this->root->get_attribute("tempamount");

        $this->combFrom = DE_Comb::CreateFromID((int)$this->comb);

        $this->combTo = DE_Comb::CreateFromID($this->comb_to);

        // Время жизни артефакта
        $this->artefactTime = $this->root->get_attribute("death");

        return true;
    }

    // проверяем доступность действия
    function checkAction ()
    {
        if(sizeof($this->errorname) > 0)
        {
            return true;
        }

        assert($this->comb != $this->comb_to);
        assert($this->combTo->getUserinstanceID() == $this->uiid);


        if(!$this->combFrom->haveOpenCentralGate())
        {
            $this->errorname[] = 'TRANSFER_RES_CLOSED_CG';
            return true;
        }

        // открыты ли ЦВ в пункте назначения
        if(!$this->combTo->haveOpenCentralGate())
        {
            $this->errorname[] = 'TRANSFER_RES_CLOSED_DEST_CG';
            return true;
        }

        // название категории
        $this->categoryname = DS_Trade::GetTradeCategoryNameByID($this->id_category);

        $st = ST_Attributes::Create();

        // проверяем наличии отправляемого
        switch($this->categoryname)
        {
            case "RESOURCE":
                $combAttributes = $this->combFrom->getAttributes();

                $attributeName = $st->getAttributeNameByAttributeNameID($this->id_article);
                $chr = $combAttributes->getAttribute('CURRENT_AMOUNT', $attributeName);

                if($chr < $this->amount)
                {
                    $this->amount = $chr;
                }
                break;

            case "ARTEFACT":
                if(!isset($_SESSION['profi_game']) || !$_SESSION['profi_game'])
                {
                    $this->amount = (float)1;
                }

                // Проверяем ограничение на количество артефактов
                if(DS_Archeology::getCountArefacts((int)$this->comb_to) + $this->amount > $this->worldSettings->get('MAX_COMB_ARTEFACT_COUNT'))
                {
                    $messparams = array();
                    $messparams[0] = $this->worldSettings->get('MAX_COMB_ARTEFACT_COUNT');
                    $messparams[1] = $this->combTo->getName();
                    DE_SystemMessages::AddMessageFromAction(__CLASS__, 'ARTEFACT_ON_COMB_TO_LIMIT', $messparams, 0);
                    $this->_sendHelper(0);
                    return true;
                }

                // Если у нас не бессмертный арт
                if (!empty($this->artefactTime)) {
                    $arTpp = explode('|', $this->artefactTime);

                    // Часы и минуты не учитываем, потому что это нам не принципиально
                    $artefactBirthTime = date('Y-m-d', $arTpp[0]);
                    $artefactDeathTime = date('Y-m-d', $arTpp[1]);
                } else {
                    $artefactBirthTime = '';
                    $artefactDeathTime = '';
                }

                $artefacts = DS_Artefact::GetArtefactsFromCombStore($this->combFrom, $this->id_article, $artefactBirthTime, $artefactDeathTime);
                assert(count($artefacts) >= $this->amount);

                break;

            case "UNIT":
                $unit = DE_Unit::CreateFromID((int)$this->id_article);
                
                // Проверяем, что это не запрещенные для транфера юниты
                assert((int)$this->id_article != 99 && (int)$this->id_article != 98 && (int)$this->id_article != 93);

                if(!GL_CanMoveUnitsFromComb::IsCan($this->combFrom))
                {
                    $this->errorname[] = 'CANT_TRANSFER_UNITS_WHEN_BATTLE_IN_PROGRESS';
                    return true;
                }

                $homeArmy = $this->combFrom->GetHomeArmy();
                assert($homeArmy instanceof DE_Army);

                $unitGroupsCollection = $homeArmy->getUnitsGroupCollection();
                assert($unitGroupsCollection instanceof DC_ArmyUnitGroups);

                $unitsGroup = $unitGroupsCollection->GetUnitGroupByUnit($unit);
                assert($unitsGroup instanceof DE_UnitsGroup);

                if(ceil($unitsGroup->getAmount()) < $this->amount)
                {
                    $this->amount = $unitsGroup->getAmount();
                }

                break;
        }
        return true;
    }



    // выполняем действие
    function performAction()
    {
        if(!DE_WorldSettings::CreateForCurentWorld()->get('CAN_TRANSFER_RESOURCES_TO_COLONY') && $this->categoryname == 'RESOURCE')
        {
            ds_error("Sorry. This action disabled.");
        }

        $noCellData = true;

        if(sizeof($this->errorname) > 0)
        {
            $messparams[0] = '';
            DE_SystemMessages::AddMessageFromAction(__CLASS__, $this->errorname[0], $messparams );

            return $this->refresh(2, "", "", "", $noCellData);
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

        // время окончания эвента
        if(!$this->getEventEndTime())
        {
            return false;
        }

        $this->messparams[0] = "{b} ".GB_Integer::Format($this->amount, ' ')."{/b}";

        // вычитаем отправленное с соты
        switch($this->categoryname)
        {
            case "RESOURCE":
                $this->_transferResources();
                break;

            case "ARTEFACT":
                $this->_transferArtefacts();
                break;

            case "UNIT":
                if(!$this->_transferUnits())
                {
                    return $this->refresh(2, "", "", "", $noCellData);
                }
                break;
        }

        // создаем эвент
        $this->createEvent('TRANSFER_RESOURCE');

        // присоединяем артефакты к евенту
        $event = DE_EventQueue::CreateFromID($this->eqid);
        if($this->categoryname == "ARTEFACT")
        {
            // Если у нас не бессмертный арт
            if (!empty($this->artefactTime)) {
                $arTpp = explode('|', $this->artefactTime);

                // Часы и минуты не учитываем, потому что это нам не принципиально
                $artefactBirthTime = date('Y-m-d', $arTpp[0]);
                $artefactDeathTime = date('Y-m-d', $arTpp[1]);
            } else {
                $artefactBirthTime = '';
                $artefactDeathTime = '';
            }

            ME_TransferArtefactWork::AddArtfactsToTransferEventFromComb($event, $this->id_article, $this->amount, $this->combFrom, $artefactBirthTime, $artefactDeathTime);
        }

        // название соты откуда
        $this->messparams[2] = "{b} ".$this->combFrom->getName()."{/b}";

        // название соты куда
        $this->messparams[3] = "{b} ".$this->combTo->getName()."{/b}";

        // системное сообщение
        if($this->categoryname == 'UNIT')
        {
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "START_TRANSFER_RESOURCE_MESS2", $this->messparams );
        } else {
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "START_TRANSFER_RESOURCE_MESS1", $this->messparams );
        }

        // рефреш с актуализацией ресурсов
        if(!$this->refresh(2, "", "", 1, $noCellData))
        {
            return false;
        }

        $this->_sendHelper(1);

        //Логируем
        DS_Transfer::LogTransfer($this->combFrom->getCombID(), $this->combTo->getCombID(), $this->amount, $this->id_article, $this->categoryname, $event);

        return true;
    }

    private function _transferResources()
    {
        $st = ST_Attributes::Create();
        $attributeName = $st->getAttributeNameByAttributeNameID($this->id_article);
        $amount = DE_CombAttributes::CreateFromCombID((int)$this->comb)->getAttribute('CURRENT_AMOUNT', $attributeName);

        DE_CombAttributes::CreateFromCombID((int)$this->comb)->deltaAttribute('CURRENT_AMOUNT', $attributeName, -(float)$this->amount);

        $this->messparams[1] = "{b} ".DE_Localization::CreateForCurentLang()->get((int)$st->getAttributeLocalizedName($attributeName))."{/b}";
    }

    private function _transferUnits()
    {
        $unit = DE_Unit::CreateFromID((int)$this->id_article);

        $moveSpeed = GB_TypeFactory::CreateMaster('GB_MoveSpeed', (float)(1));
        $upkeepValue = GL_FullUnitsMovementCost::CountTransfer($unit, $this->amount, $this->combFrom, $this->combTo, $moveSpeed);

        if(!ME_GetUpkeepResourcesFromComb::GetMoovUpkeep($this->combFrom, $upkeepValue))
        {
            $this->_sendHelperUnitsNeedUpkeep();
            return false;
        }

        $this->messparams[1] = "{b} ".$unit->getLocalizedName()."{/b}";
        $this->messparams[4] = "{b}".$upkeepValue->getShortPayList()."{/b}";

        $homeArmy = $this->combFrom->getHomeArmy();
        $unitsGroup = $homeArmy->getUnitsGroupCollection()->GetUnitGroupByUnit($unit);

        ME_AddUnitsToHomeArmy::GetUnit($this->combFrom, $unit, (float)$this->amount);

        return true;
    }

    private function _transferArtefacts()
    {
        $artefact = DE_Artefact::CreateFromArtefactID($this->id_article);
        $this->messparams[1] = "{b} ".$artefact->getLocalizedName()."{/b}";
    }

    private function _sendHelperUnitsNeedUpkeep()
    {
        if(!isset($_SESSION['profi_game']) || !$_SESSION['profi_game'])
        {
            return;
        }

        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><status />";
        $doc = domxml_open_mem ( $xml );
        $root = $doc->document_element ();
        $elem = $doc->create_text_node ( '0' );
        $root->append_child ( $elem );
        SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'helper' );
    }

    private function _sendHelper($result)
    {
        assert(is_int($result) && ($result == 0 || $result == 1));

        if(isset($_SESSION['profi_game']) && ($_SESSION['profi_game'] == true))
        {
            $str = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<transres id_article=\"".$this->id_article."\" id_category=\"".$this->id_category."\" amount=\"".$this->amount."\" death=\"".$this->artefactTime."\"><keys allow=\"$result\"></transres>";
            SU_OutputHandler::Singleton()->addString($str, 'helper');
        }
    }

    // время окончания эвента
    function getEventEndTime ()
    {
        $dateFrom = GB_TypeFactory::CreateBase('GB_Date', time());
        
        // Если мы трансферим на захватываемую колонию или с нее, время трансфера свое
        if ($this->combFrom->getIsCapture() || $this->combTo->getIsCapture()) {
            $transferTime =  (float)$this->worldSettings->get('TRANSFER_CAPTURE_COLONIES_TIME');
            assert($transferTime > 0);
            $dateTo = GB_TypeFactory::CreateBase('GB_Date', time() + $transferTime*60*$this->worldSettings->get('TURN_TIME'));
        } else {
            $this->traveltime = GL_TravelTime::Count($this->combFrom, $this->combTo, GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)1));
            $dateTo = GB_TypeFactory::CreateBase('GB_Date', time() + $this->traveltime->value);
        }


        $this->starttime = $dateFrom->getPgFormat();
        $this->endtime = $dateTo->getPgFormat();

        return true;
    }

    function createEventParams ()
    {
        $this->eventparams["ID_UserInstance"] = $this->uiid;
        $this->eventparams["ID_Category"] = $this->id_category;
        $this->eventparams["ID_Article"] = $this->id_article;
        $this->eventparams["Amount"] = $this->amount;
        $this->eventparams["ID_Language"] = $this->langId;
        //$this->eventparams["ID_World"] = $this->world;
        $this->eventparams["ID_CombTo"]  = $this->comb_to;
        $this->eventparams["ID_CombFrom"]  = $this->comb;

        return true;
    }
}

?>