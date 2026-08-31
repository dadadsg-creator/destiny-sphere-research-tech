<?php $CVS_Version = '$Revision: 1.10 $ ; $Date: 2008/07/01 08:56:38 $';

// рефреш соты
class UA_Refresh extends UA_Action
{
    public $full;

    public $cell;

    public $combres;

    public $messages;

    private $notCellInfo;

    function UA_Refresh()
    {
        UA_Action::UA_Action ();
    }

    // проверка локальных параметров
    function checkLocalParams()
    {
        // если cell=1 берем из базы инфу по всем ячейкам и отправляем клиенту
        $this->cell = $this->root->get_attribute("cell");

        if(isset($_SESSION['cells']))
        {
            if($_SESSION['cells'] == 1)
            {
                $this->cell = 1;
            }

            unset($_SESSION['cells']);
        }

        $this->combres = 1;

        return true;
    }

    // проверка допустимости действия
    function checkAction()
    {
        // если нет
        if(!DS_UserInstance::isAllowRefresh($this->uiid))
        {
            $mparams[0] = $this->worldSettings->get('REFRESH_RATE');
            // системное сообщение
            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TO_FAST_REFRESH_MESS', $mparams );
            // Дублируем сообщение в красную кнопку
            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TO_FAST_REFRESH_MESS', $mparams, 1, 'RED' );
            
            $this->full = 0;
        } else {
            // прошло достаточно времени
            $this->full = 1;
            // профи игра
            if(isset($_SESSION['profi_game']))
            {
                // убиваем из сессии данные о всех сотах игрока и будем брать заново из базы
                unset($_SESSION["combres"]);
            }
        }

        //Ресетим кеш списка боев
        $_SESSION['BATTLE_OVERVIEW'] = array();

        return true;
    }

    /**
     * выполняем действие
     *
     * @param bool $notCellInfo к рефрешу не будут добавлены сведения с ячейкуами соты и сведения со зданяими
     * @return bool
     */
    function performAction($notCellInfo = false)
    {
        //Елси работаем из командной чстроки то всегда полнй рефреш
        if(RD_AdeptUserActionToConsole::$commandLineFlag)
        {
            $this->full = 1;
            unset($_SESSION["combres"]);
        }

        //////////////////////////////////////////////////////////////////////////////////////////////////////////
        $this->notCellInfo = (bool)$notCellInfo;
        $this->_refreshUserInstance();
        $this->_createCombView();
        $this->_updateCombRes();
        
        // Проверяем, не вышел ди игрок из своей лиги по времени жизни ака
        if (DE_WorldSettings::CreateForCurentWorld()->get('LEAGUE_ENABLED') == 1 && DE_WorldSettings::CreateForCurentWorld()->get('LEAGUE_ALLOW_PASS_NEXT_LEAGUE_LIFE_TIME') == 1) {
            $this->_checkLeague();
        }
        //////////////////////////////////////////////////////////////////////////////////////////////////////////

        $userInstance = DE_UserInstance::CreateFromID($this->uiid);

        // проверяем есть ли новые сообщения
        if(!($this->noscan == 1 || $this->noscan == 2))
        {
            if(DS_Message::UserHaveNewMessages($userInstance))
            {
                SU_OutputHandler::Singleton()->addString('1' , 'newmessage');
            }
        }

        if(($userInstance->getLastRefresh()->value + (int)$this->worldSettings->get('REFRESH_RATE')) > time())
        {
            $delta = ($userInstance->getLastRefresh()->value + (int)$this->worldSettings->get('REFRESH_RATE')) - time();

            //ВНИМАНИЕ!!!! ЭТО ЗАТЫКА!!! Люди жаловались что во время боя время рефреша увеличиваеться до 20-30 минут
            //правдо после перезахода вс енормлаьно становиться
            
            if($delta > (int)$this->worldSettings->get('REFRESH_RATE'))
            {
                $delta = (int)$this->worldSettings->get('REFRESH_RATE');
            }
            SU_OutputHandler::Singleton()->addString($delta , 'refresh_rate' );
        } else {
            SU_OutputHandler::Singleton()->addString( '0' , 'refresh_rate' );
        }
        $endDate = 0;

        $premiumDays = GE_SearchStart::GetPremiumDaysLeft((int)$_SESSION['uid']);
        if ($premiumDays)
        {
            $endDate = explode('.',$premiumDays['enddate']);
            if (is_array($endDate))
                $endDate =  $endDate[0] - 3600 * 3;
             
            //list($endDate, $tail) = 
            if ($endDate*1 < time())
            {
                $endDate =0;
            }
            else
            $endDate = $endDate.'000';

            SU_OutputHandler::Singleton()->addString($endDate, 'mode' );
        } 
        
        if (!$endDate)
        {
            SU_OutputHandler::Singleton()->addString($endDate, 'mode' );

            $mode = GL_HaveTrialPremium::GetMode(DE_User::CreateFromID((int)$_SESSION['uid']), (int)$_SESSION['dbId']);
            
            if ($mode)
            {
                //Проверяем триал по миру
                if(key_exists($_SESSION['dbId'], $GLOBALS['WORLDS_WITH_PREMIUM']))
                {
                    $endDate = GB_TypeFactory::CreateBase('GB_Date', $GLOBALS['WORLDS_WITH_PREMIUM'][$_SESSION['dbId']]['TO'])->value;
                } else {
                    $premiumDays = GE_SearchStart::GetPremiumDaysLeft((int)$_SESSION['uid']);
                    list($endDate, $tail) = explode('.',$premiumDays['enddate']);
                }
                $endDate -= 3600 * 3;
                
                if ($endDate*1 < time())
                {
                    $endDate = 0;
                }
                else $endDate = $endDate.'000';

                SU_OutputHandler::Singleton()->addString($endDate, 'mode' );
            }
            else
            {
                SU_OutputHandler::Singleton()->addString(0, 'mode' );
            }
        }

        //Помощь новичкам
        $nubHelpText = GH_NubHelper::getHelpMessage($this->uiid, $this->world);
        if(!empty($nubHelpText)) {
            SU_OutputHandler::Singleton()->addString($nubHelpText, "outside");
        }

        //Оповещения о том что твариться во время рефреша при режиме отпуска
        $res = GL_IsUserInVocationMode::Test($userInstance);
        if(count($res) > 0)
        {
            // Если мир в паузе
            if (key_exists('pause', $res)) {
                // ВНИМАНИЕ! Мир заморожен до %.
                $messparams[0] = GB_TypeFactory::CreateBase('GB_Date', $res['pause'])->getSystemMessageFormat();
                DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TURNIR_PAUSE_MESS', $messparams, 0);
            } else {
                // Если человек в бессрочнном отпуске
                $messparams = array();
                DE_SystemMessages::AddMessageFromAction(__CLASS__, 'VACATION_REFRESH_NOTICE', $messparams, 0);
            }
        }

        //Оповещение о том что рефреш прошел под силовым щитом и нихрена не делаеться.
        if(DE_PowerShild::CreateFromUserInstance($userInstance->getID())->isWorks())
        {
            $powerShild = DE_PowerShild::CreateFromUserInstance((int)$this->uiid);

            $timeDelta = DE_UserInstance::CreateFromID((int)$this->uiid)->getUser()->getTimeDelta();

            $messparams = array(
            0 => $powerShild->getEndDate()->getFormatedFull($timeDelta)
            );

            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'PS_REFRESH_NOTICE', $messparams, 0);
        }

        if(!GL_NewbDefendTestArmyPower::isStillHaveDefend())
        {
            $userInstance->clearNewbDefend();
            $messparams = array();//DE_WorldSettings::CreateForCurentWorld()->get('MAX_NEWB_ARMY_POWER');
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "CLEAR_NEWB_DEFEND_MAX_ARMY", $messparams, 0, 'YELLOW');
        }

        if($this->full == 1 && $userInstance->haveNewbDefend() && DE_Comb::CreateFromID((int)$this->comb)->haveOpenCentralGate())
        {
            $newbDefendLeft = GB_TypeFactory::CreateBase('GB_TimePeriod', $userInstance->getNewbDefendEndDate()->value - time());
            $messparams = array($newbDefendLeft->getDSFormat());
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "NEWB_DEFEND_LEFT", $messparams, 0);
        }

        if($this->full == 1 && $userInstance->haveRandomDefend())
        {
            $messparams = array($userInstance->getRandomDefendEndDateMax()->getFormatedFull(true));
            DE_SystemMessages::AddMessageFromAction(__CLASS__, "RANDOM_DEFEND_MESS", $messparams, 0);
        }
            
        return true;
    }

    private function _refreshUserInstance()
    {
        if($this->notCellInfo)
        {
            return;
        }

        $userInstanceObject = DE_UserInstance::CreateFromID((int)$this->uiid);

        $refreshCommand = new CR_RefreshUserInstanceCommand($userInstanceObject);

        if($this->noscan == 1 || $this->noscan == 2 || $this->full == 0)
        {
            $withEvents = false;
        } else {
            $withEvents = true;
        }

        if($this->noscan != 2 && $this->full == 1)
        {
            $withTurnCalc = true;
        } else {
            $withTurnCalc = false;
        }

        $refreshCommand->setRefreshType($withEvents, $withTurnCalc);

        $refreshController = new CR_RefreshUserInstance($refreshCommand);
        $refreshController->exec();
    }

    private function _createCombView()
    {
        if($this->notCellInfo)
        {
            return;
        }

        $controlCombView = CR_CombView::CreateFromCombID((int)$this->comb);

        $controlCombView->setWithBuildings(true);

        if($this->notCellInfo && $this->cell)
        {
            $controlCombView->setWithCells(false);
        }else{
            $controlCombView->setWithCells(true);
        }

        $controlCombView->createView((int)$this->celltype);
    }

    private function _updateCombRes()
    {
        // данные о ресурсах на соте
        if(key_exists("combres", $_SESSION))
        {
            $combres = $_SESSION["combres"];
        }else{
            $combres = array();
        }

        if(is_array($combres) && key_exists($this->comb, $combres) && $this->combres == 0)
        {
            SU_OutputHandler::Singleton()->addReadyString($combres[$this->comb]);
            return;
        }

        // актуализируем
        $xmldoc = domxml_new_xmldoc("1.0");
        $root = $xmldoc->add_root("combres");

        $combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);

        foreach(GB_ResourcesList::$allCombResources as $k=>$resourceName)
        {
            $haveMax = $combAttributes->haveAttribute('MAX_AMOUNT', $resourceName);
            $haveCurrent = $combAttributes->haveAttribute('CURRENT_AMOUNT', $resourceName);

            if($haveMax || $haveCurrent)
            {
                $ch = $root->new_child($resourceName);

                if($haveCurrent)
                {
                    $current = $combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName);
                    $ch->set_attribute ( "current", GB_Integer::Format(floor($current)));
                }

                if($haveMax)
                {
                    $max = $combAttributes->getAttribute('MAX_AMOUNT', $resourceName);
                    $ch->set_attribute ( "max",  GB_Integer::Format(floor($max)) );
                }
            }
        }

        //Добавлеям бабло отображение
        $member = new MU_EntityOperationMember(
        DE_UserInstance::CreateFromID((int)$this->uiid)->getUser(),
        DE_WorldFromCollection::CreateFromID(DE_World::CreateForCurrentWorld()->getShortName())
        );
        $moneyData = MU_SearchOperations::GetAccountData($member);
        foreach($moneyData as $moneyName => $moneyAmount)
        {
            if(!key_exists($moneyName, MU_EntityMoneySumm::$allowCreditName))
            {
                continue;
            }

            $money = DE_Money::CreateFromShortName($moneyName);

            $ch = $root->new_child($money->getName());
            $ch->set_attribute("current", round($moneyAmount, 2));
        }

        $combres[$this->comb] = SU_OutputHandler::Singleton()->addString( $xmldoc->dump_mem() , 'combres' );

        $_SESSION["combres"] = $combres;
    }
    
    private function _checkLeague()
    {
        // Текущая лига
        $CurLeague = DE_UserInstance::CreateFromID($this->uiid)->getLeagueId();
        
        // Если лига равна максимальной, то нет смысла что-то считать
        if ($CurLeague == DS_League::GetMaxLeague()) {
            return;
        } 
        
        // Лига, соответствующая времени жизни аккаунта
        $league = DS_League::GetLeagueLife((int)$this->uiid);
        
        // Если есть соответствующая времени жизни лига и она больше от текущей
        if ($league && ($league['id_league'] > $CurLeague)) {
            // Меняем лигу
            DE_UserInstance::CreateFromID($this->uiid)->setLeagueId((int)$league['id_league']);
            
            // Сообщение
            $alertmsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NEXT_LEAGUE_LIFE_TIME', array($league['league_name']), null, null, false);

            $alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$alertmsg.']]></txt></swloader>';

            SU_OutputHandler::Singleton()->addReadyString($alertText);
        }
    }
}

?>