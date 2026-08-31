<?php $CVS_Version = '$Revision$ ; $Date$';

// команда реинкарнировать юниты
class UA_ReincarnateUnit extends UA_Action
{

    /**
     * Массивчик со списком юнитов, подлежащих реинкарнации
     *
     * @var array
     */
    private $unitArr;
    
    /**
     * Id юнита
     *
     * @var int
     */
    private $unitId;
    
    /**
     * Количество реинкарнируемых юнитов
     *
     * @var int
     */
    private $counttotrain;
    
    /**
     * Время реинкарнации армии
     *
     * @var int
     */
    private $timetotrainone;    
    
    /**
     * За что покупаем юниты (ресурсы или серебро)
     *
     * @var str
     */
    private $reincFor;
    
    /**
     * Юниты, встающие на реинкарнацию. Мега затыка для того, чтобы они сразу же появлялись в очереди.
     *
     * @var str
     */
    private $unitPanel = array();
    
    /**
     * Информация о восстановленной армии (нужна для вывода алерта)
     *
     * @var array
     */
    private $messageParams = array();   
    
    /**
     * Количество ресурсов, необходимых для реинкарнации всех юнитов
     *
     * @var array
     */
    private $needResourses = array();   
    
    /**
     * Количество продовеса, необходимое для реинкарнации всех юнитов
     *
     * @var int
     */
    private $needProdium = 0;   
    
    /**
     * Флаг о надобности выводить инфу о восстановленной армии
     * (true в том случае, если мы что-то поставили на реинкарнацию)
     *
     * @var boolean
     */
    private $showAlert = false;

    function __construct()
    {
        UA_Action::UA_Action();
    }

    // Проверяем параметры
    function checkLocalParams ()
    {       
        // За что покупаем юниты
        $this->reincFor = $this->root->get_attribute ( "for" );
        assert($this->reincFor == 'Res' || $this->reincFor == 'Sera');
        
        // Проверяем возможность реинкарнации
        if ($this->reincFor == 'Res') {
            assert($this->worldSettings->get('UNIT_REINCARNATION_IN_RESOURCE_ENABLED'));
        } else {
            assert($this->worldSettings->get('UNIT_REINCARNATION_IN_SILVER_ENABLED'));
        }
        
        // Список юнитов для реинкарнации
        $unitList = explode(',',$this->root->get_attribute ( "lst" ));
        assert(count($unitList) > 0);
        
        foreach($unitList as $k=>$unitStr)
        {
            $unitData = explode(':', $unitStr);
            $unitId = (int)$unitData[0];
            $unitAnount = (int)$unitData[1];
            assert($unitId) > 0;
            assert($unitAnount) > 0;
            
            // Если это квестовый юнит
            if ($unitId == 99 || $unitId == 98 || $unitId == 93) {
                $this->errormsg = " SYSTEM ERROR UA_ReincarnateUnit::checkAction0";
                return false;
            }
            
            $this->unitArr[$unitId]['amount'] = $unitAnount;
            
            // хватает ли на юнит науки и зданий
            $canTrainUnit = DS_ArmyWeight::GetUnitListToReincarnation($this->uiid, (int)$this->comb, (int)$_SESSION['race'], $unitId);
            if(count($canTrainUnit) == 0)
            {
                
                $this->errormsg = " SYSTEM ERROR UA_ReincarnateUnit::checkAction1";
                return false;
            }   
            
            // Увеличиваем количество необходимых ресурсов для восстановление армии на стоимоть юнитов
            
            // Ресурсы, необходимые для реинкарнации юнита
            $resCost = DS_ArmyWeight::GetUnitReincarnationPrice($unitId, (int)$_SESSION['world'], DE_UserInstance::CreateFromID($this->uiid)->getLeagueId());
            $resStr = "";
            foreach ($resCost as $reskey => $resdata)
            {
                if ($resdata['price'] == 0) {
                    continue;
                }
    
                // Если мы покупает за серу, игнорируем все цены на ресурсы
                if ($this->reincFor == 'Sera' && $resdata['full_name'] != 'SILVER_CREDIT') {
                    continue;
                }
    
                // Если мы покупает за ресурсы, игнорируем цену за серу
                if ($this->reincFor == 'Res' && $resdata['full_name'] == 'SILVER_CREDIT') {
                    continue;
                }
    
                if (!key_exists($resdata['full_name'], $this->needResourses)) {
                    $this->needResourses[$resdata['full_name']] = 0;
                }
                $this->needResourses[$resdata['full_name']] += $resdata['price']*$unitAnount;
                
                $resStr .= $resdata['price']." ".substr($resdata['full_name'], 0, 3).";";
            }
                
            // Ресурсы. необходимые для ренкарнации этого юнита в одну строчку(для логирования)
            $this->unitArr[$unitId]['res_for'] = $resStr;

            // Вес юнитов
            $this->needProdium += DE_Unit::CreateFromID($unitId)->getUnitPower()/10*$unitAnount;
            
        }
        
        // Если мы продаем за серебро, округляем цену до большего
        if (key_exists('SILVER_CREDIT', $this->needResourses)) {
            $this->needResourses['SILVER_CREDIT'] = ceil($this->needResourses['SILVER_CREDIT']);
        }
        
        // Время реинкарнации армии
        $this->timetotrainone = $this->worldSettings->get('UNIT_REINCARNATION_TIME')*60*60;
        if ($this->reincFor == 'Sera') {
            $this->timetotrainone /= $this->worldSettings->get('UNIT_REINCARNATION_SILVER_TIME_DIVISOR');
        }
        assert($this->timetotrainone) > 0;
        
        return true;
    }

    // Проверяем допустимость действия
    function checkAction()
    {
        // Проверяем допустимость действия
        if (!$this->_checkAction())
        {
            return false;
        }

        // Выполняем действия
        if (!$this->_perfomAction())
        {
            return false;
        }
        
        // Открываем меню заново
        $menu = new PN_ReincarnationPanel();
        assert($menu->getPanel($this->unitPanel, $this->showAlert, $this->messageParams));

        return true;
    }

    // выполняем действие
    function performAction ()
    {
        $noCellData = true;

        // рефреш с актуализацией ресурсов
        if(!$this->refresh( 2, "", "", 1, $noCellData))
        {
            return false;
        }

        return true;
    }

    function _checkAction()
    {
        global $params;
        global $common;

        // Хватает ли ресурсов
        $this->combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);
        foreach($this->needResourses as $resourceName => $resourceValue)
        {
            // Если это серебро
            if ($resourceName == 'SILVER_CREDIT') {
                if (!UC_PaymentServiceMoneyOperation::CheckMoney(DE_User::CreateFromID(DE_UserInstance::CreateFromID($this->uiid)->getUserID()), $resourceValue, 'SC', DE_World::CreateForCurrentWorld()->getShortName())) {
                    $this->errormsg = " SYSTEM ERROR UA_ReincarnateUnit::checkAction2";
                    return false;
                }
            } else {
                // Если это ресурс
                if ($this->combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName, true) < $resourceValue) {
                    $this->errormsg = " SYSTEM ERROR UA_ReincarnateUnit::checkAction3";
                    return false;
                }
            }
        }
        
        // Хватает ли родовеса
        $armyWeight = DE_ArmyWeight::CreateFromUserInstanceID($this->uiid);
        $targetProdium = 0.7*min($armyWeight->getArmyWeightLost(), $armyWeight->getArmyWeightBuilt());      
    
        if ($targetProdium < $this->needProdium) {
            $this->errormsg = " SYSTEM ERROR UA_ReincarnateUnit::checkAction3";
            return false;
        }

        return true;
    }

    function _perfomAction()
    {
        global $params;
        global $common;

        // Проверяем, нет ли у нас уже юниты на очереди в реинкарнацию
        $unitOnReincarnation = DS_ArmyWeight::GetUnitOnReincarnation($this->uiid, (int)$this->comb);
        if (count($unitOnReincarnation) > 0) {
            $messparams = array();
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANT_REINCARNATE_UNIT_MESS", $messparams, 0, 'GREEN');  
            return true;        
        }
        
        
        // Проверяем, нет ли у игрока режима отпуска
        if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
        {
            $this->_vocationModeAlert();            
            return true;
        }

        // Проверяем, нет ли у игрока включенного щита
        if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
        {
            $this->_shildModeAlert();           
            return true;
        }
        
        if($this->haveProcessedErrors)
        {
            return true;
        }
        
        // название соты
        $messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->comb)->getName()."{/b}";

        $prodiumWeightTotal = 0;
        $armyWeight = DE_ArmyWeight::CreateFromUserInstanceID($this->uiid);
        
        // Время начала и окончания ивента
        if(!$this->getEventEndTime())
        {
            return false;
        }
        
        // Для мегазатыки добавления новых юнитов в очередь
        $starttime = GB_TypeFactory::CreateBase('GB_Date', $this->starttime)->getTimeStamp(DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta());
        $endtime = GB_TypeFactory::CreateBase('GB_Date', $this->endtime)->getTimeStamp(DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta());       
        $time = $starttime.'000|'.$endtime.'000';   
        
        // Вычитаем продовес
        $armyWeight->deltaArmyWeightBuilt(-$this->needProdium*0.3);
        $armyWeight->deltaArmyWeightLost(-$this->needProdium);  
        
        // вычитаем ресурсы
        $this->messageParams[1] = "";
        $finalPrice = "";
        $this->combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);
        foreach($this->needResourses as $resourceName => $resourceValue)
        {
            // Если это серебро
            if ($resourceName == 'SILVER_CREDIT') {
                UC_PaymentServiceMoneyOperation::GetMoneyForPaymentOperation(DE_User::CreateFromID(DE_UserInstance::CreateFromID($this->uiid)->getUserID()), 'UNIT_REINCARNATION', (int)$resourceValue, 'SC', DE_World::CreateForCurrentWorld()->getShortName());

                $this->messageParams[1] .= number_format($resourceValue, 0, ",", "'")." ".DE_Money::CreateFromShortName('SC')->getLocolizedName($_SESSION['lang'])."; ";
            } else {
                // Если это ресурс
                $this->combAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, -$resourceValue);
                $this->messageParams[1] .= number_format($resourceValue, 0, ",", "'")." ".DE_Resource::CreateFromName($resourceName)->getLocolizedShortName()."; ";
            }
            
            $finalPrice .= "$resourceValue ".substr($resourceName, 0, 3).";";
        }
        
        // Записываем дату реинкарнирования
        DS_ArmyWeight::InsertArmyReincarnation($this->uiid, (float)$this->needProdium, $this->unitArr, $finalPrice);
        
        // проходимся по каждому юниту
        foreach ($this->unitArr as $key => $value)
        {
            $this->unitId = $key;
            assert($this->unitId > 0);

            $this->counttotrain = $value['amount'];
            assert($this->counttotrain > 0);

            // название юнита
            $messparams[2] = "{b} ".DE_Unit::CreateFromID((int)$this->unitId)->getLocalizedName()."{/b}";

            $eid = DS_Event::GetEventIDByName('REINCARNATE_UNIT');
            assert($eid > 0);

            // создаем эвент
            $this->createEvent ('REINCARNATE_UNIT');
            
            // системное сообщение
            // На соте % началась реинкарнация % %.
            $messparams[1] = "{b} ".$this->counttotrain."{/b}";
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "START_REINCARNATE_UNIT_MESS", $messparams, 0, 'GREEN');
            
            // Добавляем в протокол будущей менющки новые юниты
            // Мега затыка, так как иначе они не появяться в очереди 
            $this->unitPanel[$this->unitId]['unitname'] = DE_Unit::CreateFromID($this->unitId)->getName();
            $this->unitPanel[$this->unitId]['amount'] = $this->counttotrain;
            $this->unitPanel[$this->unitId]['time'] = $time;

        }
    
        $this->messageParams[0] = number_format(round($this->needProdium), 0, ",", "'");
        $this->messageParams[2] = number_format(round($armyWeight->getArmyWeightLost()), 0, ",", "'");
        $this->messageParams[3] = number_format(round($armyWeight->getArmyWeightBuilt()), 0, ",", "'");
        $this->messageParams[4] = number_format(round(0.7*min($armyWeight->getArmyWeightLost(), $armyWeight->getArmyWeightBuilt())), 0, ",", "'");
        $this->messageParams[5] = GB_TypeFactory::CreateBase('GB_Date', $this->endtime)->getFormatedFull(DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta()); 
        
        // Ставим пометку, что нам надо вывести инфу о восстановленной армии
        $this->showAlert = true;

        return true;
    }

    // время окончания
    function getEventEndTime()
    {
        $startDate = GB_TypeFactory::CreateBase('GB_Date', time());
        $endDate = GB_TypeFactory::CreateBase('GB_Date', time() + $this->timetotrainone);

        $this->starttime = $startDate->getPgFormat();
        $this->endtime = $endDate->getPgFormat();

        return true;
    }

    function createEventParams ()
    {
        $this->eventparams["CountToReincarnate"] = $this->counttotrain;
        $this->eventparams["ID_Unit"] = $this->unitId;
        $this->eventparams["ID_Comb"] = $this->comb;    

        return true;
    }

    function _invariant ()
    {
        return true;
    }
}

?>