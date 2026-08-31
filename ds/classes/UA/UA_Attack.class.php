<?php $CVS_Version = '$Revision$ ; $Date$';

// команда на атаку


class UA_Attack extends UA_Action
{

    private $id_userinstance_to;
    private $id_race_to;
    private $id_ring_to;
    private $interring;
    private $wrongtarget;
    private $allycomb;
    private $id_basecomb;
    private $nogate;
    private $noringgate;
    private $nogondola;
    private $war_ethics;
    private $gate;
    private $arrnistice;
    private $sameRaceComb;
    private $needs_energy;

    /**
     * Проверка возможности нападаения
     *
     * @var GL_CanAttackComb
     */
    private $canAttackComb;

    private $resource;
    private $speed;

    /** Классы сот откуда и куда */
    private $combTo;

    private $combFrom;

    private $userInstanceTo;

    /** Класс армии для атакующей*/
    private $army;

    // флаг о необходимости перестройки меню в случае ошибки
    private $needRebuildMenu = false;

    function UA_Attack () {
        $this->UA_Action ();
        $this->canAttackComb = new GL_CanAttackComb();
    }

    function checkLocalParams ()
    {

        $this->combFrom = DE_Comb::CreateFromID($this->comb);

        // координаты атакуемой соты
        $this->destination = $this->root->get_attribute("destination");
        assert(!empty($this->destination) && strlen($this->destination) <= 50);
        assert(preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $this->destination));

        $dest = explode(".", $this->destination);
        $dest_ring = (int)$dest[0];
        $dest_combcomplex = (int)$dest[1];
        $dest_comb = (int)$dest[2];
        assert($dest_ring>0 && $dest_combcomplex>0 && $dest_comb);

        $this->speed = GB_TypeFactory::CreateBase('GB_MoveSpeed', (float)($this->root->get_attribute("speed") / 100));

        $this->resource = (string)$this->root->get_attribute("res");
        assert($this->resource == 'ALL_RES' || in_array($this->resource, GB_ResourcesList::$stealingResources));

        $this->combTo = DE_Comb::CreateFromCoordinates($this->world, $dest_ring, $dest_combcomplex, $dest_comb);

        // id атакующей армии
        $id_army = (int)$this->root->get_attribute("id_army");
        assert($id_army>0);

        $sql = "update ds_unitcontainer set lastcoord='".$this->destination."', speed=".(int)$this->root->get_attribute("speed")." where id_unitcontainer=$id_army";
        $this->common->executeQuery($sql);

        $data = DS_Army::GetByID($id_army);
        if(count($data) == 0)
        {
            $this->needRebuildMenu = true;
            return true;
        }
        $this->army = DE_Army::CreateFromID($id_army, $data);

        return true;
    }

    // проверяем допустимость действия
    function checkAction ()
    {
        if($this->needRebuildMenu)
        {
            return true;
        }

        assert($this->army->getUserInstance()->equals($this->combFrom->getUserInstance()) );

        if($this->army->getInAction())
        {
            $this->needRebuildMenu = true;
            return true;
        }

        assert($this->army->isDefault() == 'f');

        $this->canAttackComb->canAttack($this->combFrom, $this->combTo, GB_TypeFactory::CreateBase('GB_Date', time()));
        return true;
    }

    // выполняем команду
    function performAction ($confirm = false)
    {
        // если в меню устаревшие данные, то перестраиваем
        if($this->needRebuildMenu)
        {
            $menu = new PN_ArmyOverview();
            assert($menu->getPanel());
            return true;
        }

        //if($_SESSION['profi_game'])
        //{
        $noCellData = true;
        //}else{
        //  $noCellData = false;
        //}

        // Проверка отпуска
        if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
        {
            $this->_vocationModeAlert();
            return true;
        }

        // Щит
        if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
        {
            $this->_shildModeAlert();
            return true;
        }

        // Наличие юнита
        if (DS_Army::IsArmyHasQuetsUnit($this->army->getId())) {
            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'CANT_MOOVE_ARMY_WITH_QUEST_UNIT', array(), 1, 'RED');
            return true;
        }
        
        // Проверям нет ли неоконченных боев на этой соте 
        $res = DS_Battle::NotEndedBattlesOnComb($this->combFrom);
        if(count($res) > 0 )
        {
            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'CANT_TRANSFER_UNITS_WHEN_BATTLE_IN_PROGRESS', array(), 1, 'RED');
            return true;
        }
        
        // Если включены лиги и мы нападаем на большую лигу
        if(DE_WorldSettings::CreateForCurentWorld()->get('LEAGUE_ENABLED') == 1)
        {
            // Если лига атакуемого больше лиги атакера
            if ($this->combTo->getUserInstance()->getLeagueId() > $this->combFrom->getUserInstance()->getLeagueId()) {

                $attackerLeague = $this->combFrom->getUserInstance()->getLeagueId();
                $defenderLeague = $this->combTo->getUserInstance()->getLeagueId();

                // Если лига атакера не равна 1 или лига атакуемого не равна 3 (в этом случае нападать нельзя)
                if ($attackerLeague != 1 || $defenderLeague != 3) {
                    // Если есть запрет на полет между этими лигами, но стоит модифаер, который разрешает переходить в большею лигу для атаки
                    if (DE_WorldSettings::CreateForCurentWorld()->get('LEAGUE_CAN_ATTACK_'.$attackerLeague.'_'.$defenderLeague.'_LEAGUE') == 0
                    && DE_WorldSettings::CreateForCurentWorld()->get('LEAGUE_CAN_ATTACK_BIGGER_LEAGUE') == 1) {

                        if ($confirm)
                        {
                            // Меняем лигу
                            $this->combFrom->getUserInstance()->setLeagueId($this->combTo->getUserInstance()->getLeagueId());

                            $messparams = array();
                            $messparams[0] = DE_League::CreateFromID((int)$this->combTo->getUserInstance()->getLeagueId())->getLeagueName();
                            $messparams[1] = $this->combTo->getUserInstance()->getUser()->getName();

                            // Пишем сообщение
                            // Ваша лига изменена на %, так как вы атаковали игрока % из этой лиги, которая была выше вашей!
                            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'LEAGUE_CHANGE_ATTACK_USER_WITH_BIGGER_LEAGUE', $messparams, 1, 'RED');
                        } else {
                            $messparams = array();
                            $messparams[0] = $this->combTo->getUserInstance()->getUser()->getName();
                            $messparams[1] = DE_League::CreateFromID((int)$this->combTo->getUserInstance()->getLeagueId())->getLeagueName();
                            $title = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NEXT_LEAGUE', array(), null, null, false);
                            // Внимание! Вы собираетесь атаковать игрока % из старшей лиги %. Если вы не отмените атаку, вы перейдете в лигу этого игрока!
                            $alertText = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('ATTACK_USER_WITH_BIGGER_LEAGUE', $messparams, null, null, false);

                            //Высылаем предупреждение
                            $alert = 'outside=<?xml version="1.0" encoding="utf-8"?>
                <swfloader override="1" url="flash/dialog_alert.swf" title="'.$title.'" res="'.$this->resource.'" speed="'.$this->root->get_attribute("speed").'" destination="'.$this->destination.'" id_army="'.$this->army->getId().'"><txt><![CDATA['.$alertText.']]></txt></swloader>';
                            SU_OutputHandler::Singleton()->addReadyString($alert);
                            return true;
                        }
                    }
                }
            }
        }

        if ($this->combTo->getIsCapture() && DE_WorldSettings::CreateForCurentWorld()->get('MELSION_ATTACK_STEP') > 0) {
            $maxCombComplex = ($this->combFrom->getIsCapture() ? $this->combFrom->getComplexNumber() : DE_WorldSettings::CreateForCurentWorld()->get('MELSION_ATTACK_START')) + DE_WorldSettings::CreateForCurentWorld()->get('MELSION_ATTACK_STEP');
            if ($maxCombComplex < $this->combTo->getComplexNumber()) {
                DE_SystemMessages::AddMessageFromAction(__CLASS__, 'COMPLEX_TOO_FAR', array(), 1, 'RED');
                return true;
            }
        }

        // послать сообщение и выйти, только если это не сообщение о тихом часе
        if ($this->canAttackComb->getErrorID() && $this->canAttackComb->getErrorID() != 'COLONY_PEACEFUL_TIME')
        {
            $messparams = array();
            switch ($this->canAttackComb->getErrorID())
            {
                case 'CLOSED_CENTRAL_GATES':
                    $messparams[0] = "{b} ".$this->combTo->getFormatedAddress()."{/b}";
                    break;

                case 'WRONG_DESTINATION':
                    $messparams[0] = "{b} ".$this->combTo->getFormatedAddress()."{/b}";
                    break;

                case 'DENIED_ATTACK_COMB_ON_YOUR_DEFEND':
                    $messparams[0] = 0;
                    break;

                case 'CANT_ATTACK_USER_IN_VOCATION_MODE':
                    $messparams = array();
                    break;

                case 'CANT_MOOVE_ARMY_AFTER_FREEZ':
                    $period = GB_TypeFactory::CreateBase('GB_TimePeriod', GL_CanMooveArmysAfterFreez::GetDelta());
                    $messparams = array(
                    0 => $period->getDSFormat()
                    );
                    break;

                case 'PEACE_PERIOD':
                    $peacePeriod = DS_Battle::GetCurrentPeacePeriod();

                    $tmp = $peacePeriod['END_DATE']->value - time();

                    $timeToEnd = GB_TypeFactory::CreateBase('GB_TimePeriod', $tmp);

                    $messparams[0] = "{b} ".$timeToEnd->getDSFormat()."{/b}";
                    break;

                case 'DENIED_ATTACK_USER_WITH_SMALLER_LEAGUE':
                    // {b}Внимание!{/b} Атака игрока младшей лиги запрещено. Ваша лига - %, лига игрока % - %.
                    $messparams[0] = DE_League::CreateFromID((int)$this->combFrom->getUserInstance()->getLeagueId())->getLeagueName();
                    $messparams[1] = $this->combTo->getUserInstance()->getUser()->getName();
                    $messparams[2] = DE_League::CreateFromID((int)$this->combTo->getUserInstance()->getLeagueId())->getLeagueName();
                    break;

                case 'DENIED_ATTACK_USER_WITH_BIGGER_LEAGUE':
                    // {b}Внимание!{/b} Атака игрока старшей лиги запрещено. Ваша лига - %, лига игрока % - %.
                    $messparams[0] = DE_League::CreateFromID((int)$this->combFrom->getUserInstance()->getLeagueId())->getLeagueName();
                    $messparams[1] = $this->combTo->getUserInstance()->getUser()->getName();
                    $messparams[2] = DE_League::CreateFromID((int)$this->combTo->getUserInstance()->getLeagueId())->getLeagueName();
                    break;

                default:
                    $messparams[0] = 0;
                    break;
            }

            DE_SystemMessages::AddMessageFromAction(__CLASS__, $this->canAttackComb->getErrorID(), $messparams, 1, 'RED');

            if(!$this->refresh ( 2, "", "", "", $noCellData))
            {
                return false;
            }
            return true;
            // проверяем лимит армий на соте
        } elseif(!GL_CheckInActionArmiesLimit::Check($this->uiid, DE_Comb::CreateFromID($this->comb), $this->combTo)) {

            DE_SystemMessages::AddMessageFromAction(__CLASS__, GL_CheckInActionArmiesLimit::GetErrorCode(), GL_CheckInActionArmiesLimit::GetMessparams(), 1, 'RED');

            if(!$this->refresh ( 2, "", "", "", $noCellData))
            {
                return false;
            }
            return true;
        }

        // Альянсовые и личные миры
        $peace = DS_Peace::getPeaceByUiid($this->combTo->getUserInstanceID());
        $privatepeace = false;
        if ($peace)
        {
            $privatepeace = true;

            if ($peace['psec'] && (time()>$peace['psec']))
            $privatepeace = false;
        }
        //SU_DebugTools::LogAll('peace='.var_export($peace, true).',privatepeace='.$privatepeace,'peace.log');
        if ($privatepeace)
        {
            if ((!$peace['psec']) && ($peace['status'])) //не расторгали с ним мир
            {
                $alertmsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PRIVATE_PEACE_CANNOT_ATTACK', array($this->combTo->getUserInstance()->getUser()->getName()), null, null, false);
                //$alertmsg  = str_replace(array('{','}'),array('<','>'), $alertmsg);

                $alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$alertmsg.']]></txt></swloader>';

                SU_OutputHandler::Singleton()->addReadyString($alertText);
                return true;

            } else {
                if ($peace['psec'] && !$peace['status'])
                {
                    //мир расторгали, надо проверить, можно уже атаковать или нельзя
                    if (time() < $peace['psec']) //время окончания мира ещё не наступило
                    {
                        $alertmsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PRIVATE_PEACE_CANNOT_ATTACK2', array($this->combTo->getUserInstance()->getUser()->getName(), $peace['peace_end_date']), null, null, false);
                        //$alertmsg  = str_replace(array('{','}'),array('<','>'), $alertmsg);

                        $alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
<swfloader url="flash/alert.swf"  override="1" title=""><txt><![CDATA['.$alertmsg.']]></txt></swloader>';

                        SU_OutputHandler::Singleton()->addReadyString($alertText);
                        return true;
                    }
                }
                //Если же только предложено заключить мир, но он не заключён, то
                //можем нападать на игрока
            }
        } else //Проверка на альянсовый мир (личный мир сильнее, поэтому вначале проверяется он)
        {
            $my_alliance = DS_Peace::getUserAlliance($_SESSION["uiid"]);//Альянс атакующего

            if ($my_alliance)
            {
                $he_alliance = DS_Peace::getUserAlliance($this->combTo->getUserInstanceID()); //альянс атакуемого
                if ($he_alliance)
                {
                    $he_user = DE_UserInstance::CreateFromID($this->combTo->getUserInstanceID())->getUser();
                    $peace = DS_PeaceAlliance::getAlliancePeace($my_alliance['id_alliance'],$he_alliance['id_alliance']);

                    if ($peace && ($peace['psec']>time() && ($peace['status']>0)))
                    {
                        $confirm = $this->root->get_attribute("confirm");

                        if (!$confirm)//Нет подтверждения - шлём запрос на него
                        {
                            $id_army = $this->root->get_attribute("id_army");
                            $xmlstr = "<attack res=\"{$this->resource}\" speed=\"".$this->root->get_attribute("speed")."\" destination=\"{$this->destination}\" id_army=\"{$id_army}\" confirm=\"1\" />";
                            $alertmsg = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('ATTACK_ON_PEACE_USER_CONFIRM', array($he_alliance['alliancename']), null, null, false);

                            $alertText = 'outside=<?xml version="1.0" encoding="utf-8"?>
                    <swfloader url="flash/alert.swf" override="1" title="" action_id="1" >
                    <txt><![CDATA['.$alertmsg.']]></txt>
                    <answer><![CDATA['.$xmlstr.']]></answer>
                    </swloader>';

                            SU_OutputHandler::Singleton()->addReadyString($alertText);
                            return true;
                        } else //есть подтверждение - рассылаем оповещение о расторжении мира
                        {
                            $my_leader = DS_Alliance::GetAllianceLeader($my_alliance['id_alliance']);
                            $he_leader = DS_Alliance::GetAllianceLeader($he_alliance['id_alliance']);

                            $alPeace = new UA_AlliancePeace();
                            if ($he_alliance['id_alliance'] > $my_alliance['id_alliance']  )
                            {
                                $my_n = 1; $he_n = 2; //меньший ID имеет N 1
                            } else {
                                $my_n = 2; $he_n = 1; //меньший ID имеет N 1
                            }

                            $params = array($_SESSION['s_login'], date("Y-m-d H:i"), $he_user->getName(),
                            $he_alliance['alliancename'], $he_alliance['alliancename'], $peace['price_silver_'.$my_n], $peace['price_kad_'.$my_n],
                            $peace['price_nano_'.$my_n], $peace['price_prodium_'.$my_n],
                            $peace['price_energy_'.$my_n], $peace['price_food_'.$my_n]);

                            //Отправляем письмо лидеру альянса, откуда атакующий
                            $alPeace->sendMessage(0, 'ATTACK_ON_PEACE_USER_SUBJECT', 'ATTACK_ON_PEACE_USER_MSG1', $params, 'PRIVATE', intval($my_leader['id_userinstance']));

                            $curTax = DE_WorldSettings::CreateForCurentWorld()->get('ALLIANCE_PEACE_TAX');
                            $tax = 1 - 0.01*$curTax;

                            $params = array($_SESSION['s_login'], $my_alliance['alliancename'],  date("Y-m-d H:i"), $he_user->getName(), $he_alliance['alliancename'],
                            intval(($peace['price_silver_'.$he_n]+$peace['price_silver_'.$my_n])*$tax), intval(($peace['price_kad_'.$he_n]+$peace['price_kad_'.$my_n])*$tax),
                            intval(($peace['price_nano_'.$he_n]+$peace['price_nano_'.$my_n])*$tax), intval(($peace['price_prodium_'.$he_n]+$peace['price_prodium_'.$my_n])*$tax),
                            intval(($peace['price_energy_'.$he_n]+$peace['price_energy_'.$my_n])*$tax), intval(($peace['price_food_'.$he_n]+$peace['price_food_'.$my_n])*$tax)
                            );

                            //Отправляем письмо лидеру альянса, где атакованный
                            $alPeace->sendMessage(0, 'ATTACK_ON_PEACE_USER_SUBJECT', 'ATTACK_ON_PEACE_USER_MSG2', $params, 'PRIVATE', intval($he_leader['id_userinstance']));

                            $uaAl = new UA_Alliance();

                            //Отправляем письмо членам нашего альянса
                            $uaAl->sendAllianceMessage($my_alliance['id_alliance'], $this->langId, 0, 'ATTACK_ON_PEACE_USER_BREAK_GLOBAL_SUBJECT', 'ATTACK_ON_PEACE_USER_BREAK_GLOBAL_MSG',
                            array($he_alliance['alliancename'], $_SESSION['s_login'], $he_user->getName(), $he_alliance['alliancename']));

                            //Отправляем письмо членам другого альянса
                            $uaAl->sendAllianceMessage($he_alliance['id_alliance'], $this->langId, 0, 'ATTACK_ON_PEACE_USER_BREAK_GLOBAL_SUBJECT', 'ATTACK_ON_PEACE_USER_BREAK_GLOBAL_MSG',
                            array($my_alliance['alliancename'], $_SESSION['s_login'], $he_user->getName(), $he_alliance['alliancename']));

                            //Лидеру атакуемого альянса возвращаем все ресурсы
                            $my_comb = DE_CombAttributes::CreateFromCombID(intval($peace['id_comb_'.$he_n]));

                            //Чтоб за один раз прибавить атакуемому все ресурсы, сложим их
                            $peace['price_silver_'.$he_n]  += $peace['price_silver_'.$my_n];
                            $peace['price_kad_'.$he_n]     += $peace['price_kad_'.$my_n];
                            $peace['price_nano_'.$he_n]    += $peace['price_nano_'.$my_n];
                            $peace['price_prodium_'.$he_n] += $peace['price_prodium_'.$my_n];
                            $peace['price_energy_'.$he_n]  += $peace['price_energy_'.$my_n];
                            $peace['price_food_'.$he_n]    += $peace['price_food_'.$my_n];

                            $alPeace->changeRes($my_comb, $peace, $he_n, $he_leader['id_userinstance'],  +1);
                            //$alPeace->changeRes($my_comb, $peace, $my_n, $he_leader['id_userinstance'],  +1);

                            //Удаляем мир
                            DS_PeaceAlliance::deletePeace($peace['id_peace']);
                        }
                    }
                }
                //return false;
            }
        }

        // получаем время окончания эвента
        if ( !$this->getEventEndTime () ) {
            return false;
        }

        // Если летим на захватываемую колонию
        if($this->combTo->getIsCapture() == 1)
        {
            // Получаем время, когда бои запрещены
            $peaceStart = DE_WorldSettings::CreateForCurentWorld()->get('PEACEFUL_TIME_START');
            $peaceStartMin = DE_WorldSettings::CreateForCurentWorld()->get('PEACEFUL_TIME_START_MINUTES');
            $peaceEnd = DE_WorldSettings::CreateForCurentWorld()->get('PEACEFUL_TIME_END');
            $peaceEndMin = DE_WorldSettings::CreateForCurentWorld()->get('PEACEFUL_TIME_END_MINUTES');

            if ($peaceStart != $peaceEnd || $peaceStartMin != $peaceEndMin) {
                //if (date('H', $this->endtime) >= $peaceStart && date('H', $this->endtime) < $peaceEnd) {
                if ($this->endtime >= mktime($peaceStart, $peaceStartMin, 0, date("m", $this->endtime)  , date("d", $this->endtime), date("Y", $this->endtime)) && $this->endtime < mktime($peaceEnd, $peaceEndMin, 0, date("m", $this->endtime)  , date("d", $this->endtime), date("Y", $this->endtime))) {
                    // корректировка времени
                    $delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();

                    $messparams[0] = "{b} ".($peaceStart+$delta).":$peaceStartMin{/b}";
                    $messparams[1] = "{b} ".($peaceEnd+$delta).":$peaceEndMin{/b}";

                    // Добавить поправку на часовой пояс!!!
                    // Вы не можете сейчас отправить армию на захватываемую соту, так как с % до % часов на колонии проходит нерест ихтиандров и ваши войска могут помешать этому процессу.
                    DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANT_ATTACK_CAPTURE_COLONY", $messparams, 1, 'GREEN' );
                    return true;
                }
            }
        }

        // снимаем защиту нового игрока (Если игрок не летит на нпс соту)
        $userInstance = DE_UserInstance::CreateFromID((int)$_SESSION["uiid"]);
        if($userInstance->haveNewbDefend() && $this->combTo->getUserInstance()->getUser()->getIsUserNpc() == 'f')
        {
            if ($confirm)
            {
                // Снимаем защиту
                $userInstance->clearNewbDefend();
                $messparams = array();
                DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_NEWB_DEFEND", $messparams, 0, 'YELLOW');
            } else {
                $title = "";
                $alertText = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('ALERT_TEXT1', array(), null, null, false);

                //Высылаем предупреждение
                $alert = 'outside=<?xml version="1.0" encoding="utf-8"?>
        <swfloader override="1" url="flash/dialog_alert.swf" title="'.$title.'" res="'.$this->resource.'" speed="'.$this->root->get_attribute("speed").'" destination="'.$this->destination.'" id_army="'.$this->army->getId().'"><txt><![CDATA['.$alertText.']]></txt></swloader>';
                SU_OutputHandler::Singleton()->addReadyString($alert);
                return true;
            }
        }


        $travelTimePeriod = GB_TypeFactory::CreateConstant('GB_TimePeriod', (int)$this->traveltime);

        //Рассчитали сколько всего надо ресурсов на перемещение
        $needResources = GL_FullUnitsMovementCost::CountAttack($this->army, $this->combFrom, $this->combTo, $this->speed);

        //Сняли ресурсы за перемещение
        if(!ME_GetUpkeepResourcesFromComb::GetMoovUpkeep($this->combFrom, $needResources))
        {
            if(!$this->refresh(2))
            {
                return false;
            }

            return true;
        }

        //Указываем в атрибутаъх армии, ее сокрость и приоритетный ресурс.
        $armyAttributes = DE_ArmyAttributes::CreateFromArmyID($this->army->getID());

        $armyAttributes->setAttribute('CURRENT_AMOUNT', 'MOVE_SPEED', $this->speed->value);
        $armyAttributes->setAttribute('CURRENT_AMOUNT', 'RESOURCE_FOR_STEALING', $this->resource);


        //Создаем ивент
        $dateFrom = GB_TypeFactory::CreateBase('GB_Date', $this->starttime);
        $dateTo = GB_TypeFactory::CreateBase('GB_Date', $this->endtime);

        $datePeriod = new GB_DatePeriod($dateFrom, $dateTo);

        $eventParticipants = new GB_EventParticipants;
        $eventParticipants->setParticipant($this->combTo->getUserInstance());
        $eventParticipants->setParticipant($this->combFrom->getUserInstance());

        $actionData = new GB_ArmyActionCreationData('MOVE_TO_ATTACK');

        $actionData->setArmy($this->army);
        $actionData->setDatePeriod($datePeriod);
        $actionData->setParticipants($eventParticipants );
        $actionData->setPath(new GB_TravelPath($this->combFrom, $this->combTo));

        $this->eqid = ME_CreateArmyAction::CreateAction($actionData);

        $combToName = $this->combTo->getFullName();
        $messparams[0] = "{b} ".$this->army->getName()."{/b}";
        $messparams[1] = "{b} ".$combToName."{/b}";
        $messparams[2] = "{b} ".$dateTo->getSystemMessageFormat()."{/b}";
        $messparams[3] = "";
        $messparams[4] = "{b} ".$needResources->getShortPayList()."{/b}";


        // посылаем системное сообщение
        DE_SystemMessages::AddMessageFromAction(__CLASS__, "ATTACK_MESS4", $messparams, 1, 'GREEN' );
        $clearMessage = DE_SystemMessages::$clearMessage;

        // снимаем временную защиту с захватываемой колонии
        if ($this->combFrom->getIsCapture() == 1) {
            // Проверяем временную защиту соты
            $defend = DE_CombProtectiveField::CreateFromCombID($this->combFrom->getCombID());
            if ($defend->getTemporaryDefend()->value > time())
            {
                $defend->setTemporaryDefend(GB_TypeFactory::CreateConstant('GB_Date', 1));

                $messparams = array();
                DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_TEMPORARY_DEFEND", $messparams, 0, 'YELLOW');
            }
        }
        
        // Если летим на захватываемую колонию
        if ($this->combTo->getIsCapture() == 1 && !$confirm) {
            $canCaptureComb = new GL_CaptureColonies();
            if (!$canCaptureComb->canCaptureColony($this->combFrom))
            {
                $messparams = array();
                DE_SystemMessages::AddMessageFromAction(__CLASS__, $canCaptureComb->getErrorID(), $messparams, 1, 'RED');
            }
        }

        if(!$this->refresh (2, "", "", 1, $noCellData))
        {
            return false;
        }

        if(isset($_SESSION['profi_game']) && ($_SESSION['profi_game'] == true))
        {
            $this->_sendHelper();
        }

        if ((constant('DEFAULT_LANGUAGE_SHORT') == 'L') /* && (stripos(php_sapi_name(), 'apache')===false) */)
        {
            $token = DS_User::GetOAuthInfoByUserinstanceId($this->combTo->getUserInstanceID());
            if ($token)
            {
                /*
                $params = array();
                $params[0] = $this->army->getName();
                $params[1] = $_SESSION['s_login'];
                $params[2] = $combToName;
                $params[3] = $this->combTo->getUserInstance()->getUser()->getName();

                $timeToEnd = strtotime($dateTo->getPgFormat()) - time();
                $delta =  GB_TypeFactory::CreateConstant('GB_TimePeriod', (int)$timeToEnd);
                $params[4] = $delta->getDSFormat();

                $sys_msg = new DE_SystemMessages((int)$this->uiid);
                $msg = $sys_msg->getFormatedMessage("ATTACK_MESS1", $params, 'black');
                $msg = DE_SystemMessages::$clearMessage;

                str_replace(array('{b}','{/b}'), array('',''),  $msg);


                if ($_SESSION['lang']==5)
                //$msg = iconv("windows-1257","UTF-8", $msg);
                $msg = mb_convert_encoding($msg, "UTF-8", "windows-1257");
                else
                $msg = DE_SystemMessages::win2utf($msg);
                */
                $params = array('type' => 'attack-victim-sent', 'attacker' => $_SESSION['s_login'],
                'time'=> strtotime($dateTo->getPgFormat())/*, 'text' => $msg, */);

                $odata = MakeOAuthNotification($token['open_key'], $token['secret_key'], $params );
            }
        }
        return true;
    }

    // выдает время окончания эвента
    function getEventEndTime ()
    {
        $this->traveltime = GL_TravelTime::Count($this->combFrom, $this->combTo, $this->speed)->value;
        $this->endtime = time() + $this->traveltime;
        $this->starttime = time();
        return true;
    }//END OF METHOD getEventEndTime()


    private function _sendHelper()
    {
        $xml = '<?xml version="1.0" encoding="utf-8"'."?"."><army />";
        $doc = domxml_open_mem($xml);
        $root = $doc->document_element();

        $root->set_attribute("id_army", $this->army->getID());
        $root->set_attribute("inaction", 1);
        $root->set_attribute("lastcoord", $this->army->lastCoord);
        $root->set_attribute("lastspeed", $this->army->speed);
        if ($this->army->useArtefacts==1)
            $root->set_attribute ("useArtefacts", $this->army->useArtefacts);
        $root->set_attribute("res", $this->resource);

        $ch = $root->new_child("transfers");

        $ch = $root->new_child("actions");
        $action = DS_Army::GetActionByName('CANCEL_ATTACK', $this->langId);
        $ch2 = $ch->new_child("possible");
        $ch2->set_attribute("id_action", $action['actiontypename']);
        $ch2->set_attribute("actionname", urlencode($action['localizedtypename']));

        // корректировка времени
        $delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();
        $dateTo = GB_TypeFactory::CreateBase('GB_Date', $this->endtime);

        $messageparams = array(
            $this->combTo->getName(),
            $this->combTo->getUserInstance()->getUser()->getName(),
            $dateTo->getFormatedDate($delta),
            $dateTo->getFormatedTime($delta)
        );

        $currentaction = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('ARMY_ATTACK', $messageparams, null, null, false);

        $elem = $doc->create_text_node($currentaction);
        $ch->append_child($elem);

        SU_OutputHandler::Singleton()->addString($doc->dump_mem(), 'helper');
    }//END OF METHOD _sendHelper
}
?>