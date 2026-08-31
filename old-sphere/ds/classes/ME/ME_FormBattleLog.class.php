<?php $CVS_Version = '$Revision: 1.17 $ ; $Date: 2011/04/13 16:14:11 $';
/**
 * Менеджер формирования лога боя.
 * 
 * Ниже деьально описанно причина появления одного бага на который бдут сылаться в коментарии к затыке в этом классе
 * 
 *  ROUND = 2
 *  UNIT =  Yooo2 => Ядозавр
 *  losesTmp = 0.540773
 *  amountTmp = 3.81198
 *  amount = 5
 *  loses = 0 
 * 
 *  ROUND = 3
 *   UNIT =  Yooo2 => Ядозавр
 *   losesTmp = 0.449645
 *   amountTmp = 3.36233
 *   amount = 4
 *   loses = 0
 *  
 *  Собсвтенно отсуда все и видно. Между райндами как бы пропал один юнитю  
 */
class ME_FormBattleLog
{

    /**
     * Сведения о поетрях юнтов между раундами
     * Для того что бы не получалось такой ситуаци когда пишеться что потерь 
     * у юнита нет а колво юнитов между раундами юнта уменьшилась на единицу.
     * Это связанно с тем что потерь перед выводом и занчение текущее юнитов 
     * окргуляються. Описание проблемы в заголовке файла.
     * 
     * InterRoundUnitsGroupData = array('ARMY_NAME_UNIT_NAME' => array('ROUND_NUMBER' => array('LOSES' => ...., 'AMOUNT' => ....)))
     * @var array
     */
    static private $InterRoundUnitsGroupData = array();

    static private $retreatStarted = false;

    static private $prevRetreatSide;

    static private $fullReport;



    /**
     * Основной метод
     *
     * @param DE_Battle $battle
     */
    static public function Exec(DE_Battle $battle, $capture = false)
    {
        // если у атакующего будет полный лог, то не будем делать копию для защитника
        if(!self::_formLog($battle, $battle->getAttacker(), $capture))
        {
            self::_formLog($battle, $battle->getDefender(), $capture);
        }
    }

    /**
     * Формирование лога
     *
     * @param DE_Battle $battle
     * @param DE_BattleParticipant $participant
     * @return bool был полный лог или нет
     */
    static private function _formLog(DE_Battle $battle, DE_BattleParticipant $participant, $capture = false)
    {
        self::$fullReport = true;

        //Ресетим внутренний буффер
        self::$InterRoundUnitsGroupData = array();
        self::$retreatStarted = false;
        self::$prevRetreatSide = null;

        $battleAction = DE_BattleAction::CreateFromLangID($participant->getUserInstance()->getUser()->getLanguageID());
        //$flag = $participant->getParticipantFlag('PG');

        // если шпионаж выключен, то все видят одно и то же
        // модифаер убрали из шпионажа, сделали отдельной настройкой
        $fullLog = (bool)DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_ONE_ROUND_DEATH_LOG');

        // атакующий не видит состава армии противника, если он умер в первом раунде
        if(self::$fullReport)
        {
            $flag = '';
        } else {
            $flag = $participant->getParticipantFlag('PG');
        }

        if(!$fullLog && $participant->getParticipantFlag('PG') == 'A')
        {
            $armyCollection = $participant->getArmiesColletion();
            $armyCollection->resetNext();
            $army = $armyCollection->next();
            if($army->getDieRoundNumber() == 1)
            {
                $flag = 'A';
                self::$fullReport = false;
            }
        }

        //$report = DE_BattleReport::CreateFromBattleIDAndParticipantFlag($battle->getID(), $flag);
        $report = DE_BattleReport::CreateFromID($battle->getID().'_'.$flag);

        self::_addHead($report, $battleAction, $battle);

        if(self::$fullReport)
        {
            $battle->getBattleRoundCollection()->resetNext();
            while($round = $battle->getBattleRoundCollection()->next())
            {
                $key = $battle->getBattleRoundCollection()->getCurrentPosition();
                self::_addRound($report, $battleAction, $battle, $round);
                $battle->getBattleRoundCollection()->setPointerToPosition($key);
            }

            $multyLang = DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_LOG_MULTY_LANG');
            if ($multyLang == 1) {
                // Записываем системный лог для админки
                $battleLog = '';
                $reportArr = $report->getReport();
                foreach ($reportArr as $key => $d)
                {
                    if (empty($d)) {
                        $battleLog .= '
';
                    } else {
                        $battleLog .= $battleAction->formAction($d['NAME'], $d['PARAMS'], false);
                        $battleLog .= '
';
                    }
                }
            } else {
                $battleLog = $report->getReport();
            }

            DE_BattleSystemLog::Insert("\n\n------ BATTLE LOG ------\n".$battleLog);
        }

        self::_addFooter($report, $battleAction, $battle, $capture);

        return self::$fullReport;
    }

    /**
     * Хидер лога боя
     *
     * @param DE_BattleReport $report
     * @param De_BattleAction $battleAction
     * @param DE_Battle $battle
     */
    static private function _addHead(DE_BattleReport $report, De_BattleAction $battleAction, DE_Battle $battle)
    {
        //---------------- Запис о начале боя ----------------------------
        $params = array(
        'COMB_NAME' => $battle->getComb()->getFullName(),
        'USER_NAME' => $battle->getComb()->getUserInstance()->getUser()->getName(),
        'START_DATE' => $battle->getStartDate()->getFormatedFull(),
        );
        $report->addString($battleAction->formAction('BATTLE_BEGIN', $params), 'LATTER');

        $report->addString('', 'LATTER');

        $params = array();
        $report->addString($battleAction->formAction('ATTACKERS_LIST', $params), 'LATTER');

        //---------------- Запис о атакующем ----------------------------
        $userInstance = $battle->getAttacker()->getUserInstance();
        if ($battle->getDefender()->getUserInstance()->getUser()->getIsUserNpc() != 't') {
            $currentXP = $userInstance->getBattleXP()->getXP();
        } else {
            $currentXP = $userInstance->getBattleXP()->getNpcXP();
        }
        $deltaXP = $battle->getAttacker()->getBattleXP();

        $aliance = $userInstance->getAlliance();
        if($aliance->isEmpty())
        {
            $alianceTag = '-';
        }else{
            $alianceTag = $aliance->getShortName();
        }

        $params = array(
        'USER_NAME' => $userInstance->getUser()->getName(),
        'RELIGION' => $userInstance->getReligionStatus()->getReligion()->getID(),
        'BATTLE_XP' => round($currentXP - $deltaXP, 5),
        'DELTA_BATTLE_XP' => round($deltaXP, 5),
        'RACE_NAME' => $userInstance->getRace()->getID(),
        'ALLIANCE_TAG' => $alianceTag
        );
        $report->addString($battleAction->formAction('BATTLE_PARTICIPANT', $params), 'LATTER');

        $armyCollection = $battle->getAttacker()->getArmiesColletion();
        $armyCollection->resetNext();
        $armyID = $armyCollection->next()->getArmyID();
        $common = DS_Common::CreateForCurentDB();
        $res = $common->getResultArray("", "select use_artefacts from ds_unitcontainer where id_unitcontainer=$armyID");
        $useArtefacts = $res[0]['use_artefacts']=='t'?2:0;

        //Юонусы военные
        $battleBonus = GL_BattleBonuses::GetStaticBonus($battle->getFromComb(), $userInstance, $useArtefacts);
        $params = array(
            'ATTACK' => round($battleBonus->getAttackBonus()->getSummRelativeBonus() * 100, 2),
            'DEFENCE' => round($battleBonus->getDefendBonus()->getSummRelativeBonus() * 100, 2),
            'DAMAGE' => round($battleBonus->getDemageBonus()->getSummRelativeBonus() * 100, 2),
            'HITPOINTS' => round($battleBonus->getHitpointBonus()->getSummRelativeBonus() * 100, 2)
        );
        $report->addString($battleAction->formAction('WAR_BONUS', $params));

        $report->addString('', 'LATTER');

        //---------------- Запис о обороняющихся ----------------------------
        $params = array();
        $report->addString($battleAction->formAction('DEFENDERS_LIST', $params), 'LATTER');

        //Записи об обороняющихся
        $battle->getDefendersCollection()->resetNext();

        while($defender = $battle->getDefendersCollection()->next())
        {
            // Для захватываемых колоний
            if ($defender->getUserInstance()->getUserInstanceID() == 0) {
                $params = array(
                'USER_NAME' => 'DestinySphere',
                'RELIGION' => 100,
                'BATTLE_XP' => 0,
                'DELTA_BATTLE_XP' => 0,
                'RACE_NAME' => 1,
                'ALLIANCE_TAG' => '-'
                );
                $report->addString($battleAction->formAction('BATTLE_PARTICIPANT', $params), 'LATTER');

                $params = array(
                'ATTACK' => 0,
                'DEFENCE' => 0,
                'DAMAGE' => 0,
                'HITPOINTS' => 0,
                );
                $report->addString($battleAction->formAction('WAR_BONUS', $params));

                $report->addString('', 'LATTER');
                continue;
            }

            $userInstance = $defender->getUserInstance();
            if ($battle->getAttacker()->getUserInstance()->getUser()->getIsUserNpc() != 't') {
                $currentXP = $userInstance->getBattleXP()->getXP();
            } else {
                $currentXP = $userInstance->getBattleXP()->getNpcXP();
            }                
            $deltaXP = $defender->getBattleXP();

            $aliance = $userInstance->getAlliance();
            if($aliance->isEmpty())
            {
                $alianceTag = '-';
            }else{
                $alianceTag = $aliance->getShortName();
            }

            $params = array(
            'USER_NAME' => $userInstance->getUser()->getName(),
            'RELIGION' => $userInstance->getReligionStatus()->getReligion()->getID(),
            'BATTLE_XP' => round($currentXP - $deltaXP, 5),
            'DELTA_BATTLE_XP' => round($deltaXP, 5),
            'RACE_NAME' => $userInstance->getRace()->getID(),
            'ALLIANCE_TAG' => $alianceTag
            );
            $report->addString($battleAction->formAction('BATTLE_PARTICIPANT', $params), 'LATTER');

            //Юонусы военные
            $battleBonus = GL_BattleBonuses::GetStaticBonus($battle->getComb(), $userInstance);
            $params = array(
            'ATTACK' => round($battleBonus->getAttackBonus()->getSummRelativeBonus() * 100, 2),
            'DEFENCE' => round($battleBonus->getDefendBonus()->getSummRelativeBonus() * 100, 2),
            'DAMAGE' => round($battleBonus->getDemageBonus()->getSummRelativeBonus() * 100, 2),
            'HITPOINTS' => round($battleBonus->getHitpointBonus()->getSummRelativeBonus() * 100, 2)
            );
            $report->addString($battleAction->formAction('WAR_BONUS', $params));

            $report->addString('', 'LATTER');
        }
    }

    /**
     * фоотер лога боя
     *
     * @param DE_BattleReport $report
     * @param DE_BattleAction $battleAction
     * @param DE_Battle $battle
     */
    static private function _addFooter(DE_BattleReport $report, DE_BattleAction $battleAction, DE_Battle $battle, $capture = false)
    {

        $params = array(
        'COMB_NAME' => $battle->getComb()->getFullName(),
        'USER_NAME' => $battle->getComb()->getUserInstance()->getUser()->getName(),
        'END_DATE' => $battle->getEndDate()->getFormatedFull(),
        );
        $report->addString($battleAction->formAction('BATTLE_END', $params), 'LATTER');

        $report->addString('', 'LATTER');

        switch($battle->getWinnerCode()->value)
        {
            case 'A':
                $params = array(
                'USER_NAME' => $battle->getAttacker()->getUserInstance()->getUser()->getName(),
                );
                $report->addString($battleAction->formAction('ATTACKER_WIN', $params), 'LATTER');
                break;
            case 'D':
                $params = array(
                'USER_NAME' => $battle->getDefender()->getUserInstance()->getUser()->getName(),
                );
                $report->addString($battleAction->formAction('DEFENDER_WIN', $params), 'LATTER');
                break;
            default:
                ds_error('Unknown winner code');
        }

        $report->addString('', 'LATTER');

        $params = array(
        'END_REASON' => $battle->getBattleEndReason()->getID(),
        );
        $report->addString($battleAction->formAction('END_REASON', $params), 'LATTER');


        if($battle->getWinnerCode()->value == 'A')
        {
            if (!$capture) {
                $report->addString('', 'LATTER');

                $params = array(
                'WINNER_NAME' => $battle->getAttacker()->getUserInstance()->getUser()->getName(),
                'COMB_NAME' => $battle->getComb()->getName()
                );
                $report->addString($battleAction->formAction('ATTACKER_RAID_COMB', $params), 'LATTER');

                $resources = array('KADERIUM', 'NANO_CRYSTAL', 'PRODIUM', 'FOOD', 'STASIS_ENERGY');
                $foundRes = false;

                // Проверяем, был ли вывоз ресов
                foreach($resources as $k=>$resourceName)
                {
                    if ($battle->getAttributes()->haveAttribute('CURRENT_AMOUNT', $resourceName))
                    {
                        $foundRes = true;
                    }
                }

                // Если мы вывезли ресы
                if($foundRes == true)
                {
                    // Если у нас была вывезена только часть ресурсов из-за разницы в уровнях
                    $newbStealing = $battle->getAttributes()->getAttribute('CURRENT_AMOUNT', 'STEALING_NEWB_TAX');
                    if ($newbStealing != 100) {

                        $params = array
                        (
                        'PERSENT' => $newbStealing
                        );

                        $report->addString('', 'LATTER');

                        // Разница между уровнем захватчика и защитника позволяет вывезти только <b>{PERSENT}</b> процентов от максимально доступного количества ресурсов на соте.
                        $report->addString($battleAction->formAction('STEALING_NEWB_TAX', $params), 'LATTER');
                    }

                    // Если у нас была потеря вывезенного из-за разницы в уровнях
                    $newbStealingLose = $battle->getAttributes()->getAttribute('CURRENT_AMOUNT', 'STEALING_LOSE_NEWB_TAX');
                    if ($newbStealingLose != 0) {

                        $params = array
                        (
                        'PERSENT' => $newbStealingLose
                        );

                        $report->addString('', 'LATTER');

                        // Был удержан налог  <b>{PERSENT}</b> процентов.
                        $report->addString($battleAction->formAction('STEALING_LOSE_NEWB_TAX', $params), 'LATTER');
                    }
                }

                $report->addString('', 'LATTER');

                foreach($resources as $k=>$resourceName)
                {
                    if ($battle->getAttributes()->haveAttribute('CURRENT_AMOUNT', $resourceName))
                    {
                        $foundRes = true;

                        $params = array(
                        'RESOURCE_NAME' => $resourceName,
                        'AMOUNT' => number_format($battle->getAttributes()->getAttribute('CURRENT_AMOUNT', $resourceName))
                        );

                        $report->addString($battleAction->formAction('ATTACKER_GET_RESOURCE', $params), 'LATTER');
                    }
                }

                if($foundRes == false)
                {
                    $params = array();

                    // Если ресурсы не обнаружены, так как игрок находиться под защитой
                    if($battle->getComb()->getRingNumber() != 4 && $battle->getDefender()->getUserInstance()->haveRandomDefend())
                    {
                        $report->addString($battleAction->formAction('ATTACKER_NOT_FOUND_RESOURCES_DEFEND', $params), 'LATTER');
                    } else {
                        $report->addString($battleAction->formAction('ATTACKER_NOT_FOUND_RESOURCES', $params), 'LATTER');
                    }
                }
            }

            // Если сота захватываемая
            if($battle->getComb()->getIsCapture())
            {
                // Если соту захватили
                if ($capture) {
                    $params = array
                    (
                    'NAME' => $battle->getAttacker()->getUserInstance()->getUser()->getName(),
                    'COMB' => $battle->getComb()->getName()
                    );

                    // Игрок {NAME} захватил колонию {COMB}.
                    $report->addString();
                    $report->addString($battleAction->formAction('COLONY_CAPTURED', $params), 'LATTER');
                } else {
                    // Если просто уменьшили процент
                    $field = DE_CombProtectiveField::CreateFromCombID($battle->getComb()->getCombID());
                    $fieldVal = $field->getProtectiveField();
                    $fieldDelta = DE_WorldSettings::CreateForCurentWorld()->get('DELTA_PROTECTIVE_FIELD_IN_BATTLE');

                    // Если у нас защитное поле меньше того значения, на которое мы его умеьшаем
                    if ($fieldVal == 0 || $fieldVal < $fieldDelta) {
                        $fieldDelta = $fieldVal;
                        $fieldVal = 0;
                    } else {
                        $fieldVal = $fieldVal - $fieldDelta;
                    }

                    $field->deltaProtectiveField(-(int)$fieldDelta);

                    $params = array
                    (
                    'LOST' => $fieldDelta,
                    'START_FIELD' => $fieldVal
                    );

                    // Защитное поле на соте было уменьшено на % процентов из %
                    $report->addString();
                    $report->addString($battleAction->formAction('DEFEND_FIELD_LOST', $params), 'LATTER');
                }
            }
        }

        // Вознаграждение

        $attInstance = $battle->getFromComb()->getUserInstance();
        $defInstance = $battle->getComb()->getUserInstance();
        $attIsNPC = $attInstance->getUser()->getIsUserNpc() == 't';
        $defIsNPC = $defInstance->getUser()->getIsUserNpc() == 't';
        $ataker = $attInstance->getID();
        $defender = $defInstance->getID();

        // Если включено вознаграждение за грабеж старшего уровня и мы не воюем с игрокрм Destiny Sphere
        if (DE_WorldSettings::CreateForCurentWorld()->get('REWARD_NEWB_ENABLED') == 1 && $ataker != 0 && $defender != 0) {

            $atakerLevel = DE_Ranking::CreateFromID($ataker)->getLevel();
            $defenderLevel = DE_Ranking::CreateFromID($defender)->getLevel();

            // Если уровни равны
            if (!$attIsNPC && !$defIsNPC && $atakerLevel == $defenderLevel) {
                return;
            }

            $heading = 0;

            foreach(GB_ResourcesList::$allResources as $k => $resourceName)
            {
                if (!$attIsNPC && !$defIsNPC && $atakerLevel > $defenderLevel) {
                    // Пока отключаем
                    return;
                }
                $rewardRes = $battle->getAttributes()->getAttribute('CURRENT_AMOUNT', 'STEALING_REWARD_'.$resourceName);

                if ($rewardRes != 0) {
                    // Если больший уровень у защитника
                    if (!$attIsNPC && !$defIsNPC && $defenderLevel > $atakerLevel || $defIsNPC) {
                        // Заголовок
                        if ($heading == 0) {
                            $report->addString('', 'LATTER');
                            // Так как уровень защитника превышает уровень захватчика, захватчик получаете бонус:
                            $report->addString($battleAction->formAction('STEALING_REWARD'.($defIsNPC?'_NPC':''), array()), 'LATTER');
                            $heading = 1;
                        }
                    // Если больший уровень у атакера
                    } elseif (!$attIsNPC && !$defIsNPC && $atakerLevel > $defenderLevel || $attIsNPC) {
                        // Заголовок
                        if ($heading == 0) {
                            $report->addString('', 'LATTER');
                            // Так как уровень захватчика превышает уровень защитника, защитник получил бонус:
                            $report->addString($battleAction->formAction('STEALING_REWARD'.($attIsNPC?'_NPC':'_DEFENDER'), array()), 'LATTER');
                            $heading = 1;
                        }
                    }

                    $params = array(
                    'RESOURCE_NAME' => $resourceName,
                    'AMOUNT' => number_format($rewardRes)
                    );

                    // <b>{RESOURCE_NAME}</b> <b> - {AMOUNT}</b>
                    $report->addString($battleAction->formAction('STEALING_REWARD_RES', $params), 'LATTER');
                }
            }
        }

    }

    /**
     * Описание райнда боя
     *
     * @param DE_BattleReport $report
     * @param DE_BattleAction $battleAction
     * @param DE_Battle $battle
     * @param DE_BattleRound $round
     */
    static private function _addRound(DE_BattleReport $report, DE_BattleAction $battleAction, DE_Battle $battle, DE_BattleRound $round)
    {
        //Нулевой ранд не вносим в список, так как он описываетсостояние перед боем.
        if($round->getRoundNumber() == 0)
        {
            return;
        }

        $params = array
        (
        'ROUND_NUMBER' => $round->getRoundNumber(),
        'START_DATE' => $round->getStartDate()->getFormatedFull(),
        'END_DATE' => $round->getEndDate()->getFormatedFull(),
        );
        $report->addString($battleAction->formAction('ROUND_BEGIN', $params));

        $deletedArmys = array();

        $armysCollection = DC_BattleParticipantArmys::CreateFromParticipantID($battle->getAttacker()->getID());
        self::_addArmyLoses($report, $battleAction, $round, $armysCollection, $deletedArmys);

        $battle->getDefendersCollection()->resetNext();
        while($defender = $battle->getDefendersCollection()->next())
        {
            $armysCollection = DC_BattleParticipantArmys::CreateFromParticipantID($defender->getID());
            self::_addArmyLoses($report, $battleAction, $round, $armysCollection, $deletedArmys);
        }

        if($round->getAdds() > 0)
        {
            $report->addString();

            $params = array
            (
            'STARS' => GL_ArmyPowerToStars::CountForMenu($round->getAdds())
            );

            $report->addString($battleAction->formAction('REINFORCMENTS_HAVE_ARRIVED', $params));

            $report->addString();
        }

        if(count($deletedArmys) > 0)
        {

            foreach($deletedArmys as $k=>$deletedArmy)
            {
                $params = array(
                'ARMY_NAME' => $deletedArmy->getArmyName(),
                'USER_NAME' => $deletedArmy->getBattleParticipant()->getUserInstance()->getUser()->getName()
                );
                $report->addString($battleAction->formAction('ARMY_DESTROY', $params));
            }

            $report->addString();
        }

        $sides = array('A' => 'ATTACKER', 'D' => 'DEFENDER');

        if($round->getRetreatSide() instanceof GB_BattleParticipantTypeCode)
        {
            $params = array();

            if($round->isRetreatStarted())
            {
                if(self::$retreatStarted && self::$prevRetreatSide != $round->getRetreatSide()->value)
                {
                    assert(key_exists(self::$prevRetreatSide, $sides));
                    $report->addString($battleAction->formAction($sides[self::$prevRetreatSide].'_CANCEL_RETREAT', $params));
                    $report->addString();
                }
                $status = 'START';
                self::$retreatStarted = true;
                self::$prevRetreatSide = $round->getRetreatSide()->value;
            } else {
                $status = 'CANCEL';
                self::$retreatStarted = false;
            }

            assert(key_exists($round->getRetreatSide()->value, $sides));
            $report->addString($battleAction->formAction($sides[$round->getRetreatSide()->value].'_'.$status.'_RETREAT', $params));
            $report->addString();
        }

        $common = DS_Common::CreateForCurentDB();
        $sql = "select l.text from ds_localizedtextlanguage l, ds_artefact a, ds_battleround_burned_arts ba
            where l.id_localizedtext=a.id_localizedname
            and a.id_artefact=ba.id_artefact
            and ba.id_battleround=".$round->getID()."
            and l.id_language=".$_SESSION['lang'];
        $res = $common->getResultArray("", $sql);
        if (count($res)) {
            $report->addString($battleAction->formAction('ARTEFACT_BURNT', array('ARTEFACTNAME'=>$res[0]['text'])));
            $report->addString();
        }
    }

    /**
     * Описание потерь армий во время боя
     *
     * @param DE_BattleReport $report
     * @param DE_BattleAction $battleAction
     * @param DE_BattleRound $round
     * @param DC_BattleParticipantArmys $armysCollection
     * @param unknown_type $deletedArmys
     */
    static private function _addArmyLoses(DE_BattleReport $report, DE_BattleAction $battleAction, DE_BattleRound $round, DC_BattleParticipantArmys $armysCollection, &$deletedArmys)
    {

        assert(is_array($deletedArmys));

        $out = array();

        $armysCollection->resetNext();
        while($army = $armysCollection->next())
        {

            if(($army->getDieRoundNumber() < $round->getRoundNumber()) && ($army->getDieRoundNumber()!= 0))
            {
                continue;
            }

            $groupsData = array();

            $resultsCollection = $round->getBattleResultCollection();
            $resultsCollection->resetNext();
            while($result = $resultsCollection->next())
            {
                if($army->getArmyID() != $result->getArmyID())
                {
                    continue;
                }

                $losesData = self::_countOutLoses($result->getLoses(), $result->getAmount());

                $recivedDamage = 0;
                $outputedDamage = 0;

                $outLoses = $losesData['LOSES'];
                $outAmount = $losesData['AMOUNT'];

                //ЗАтыка доля показывания того чтолько всетаки осталось народу в группе юнитов после раунда
                $afterAmmount = $outAmount - $outLoses;
                $params = array
                (
                'UNIT_NAME' => $result->getUnit()->getName(),
                //'RECIVED_DAMAGE' => $recivedDamage,
                //'OUTPUTED_DAMAGE' => $outputedDamage,
                'RECIVED_DAMAGE' => GB_Integer::Format($afterAmmount),
                'OUTPUTED_DAMAGE' => GB_Integer::Format($afterAmmount),
                'LOSES' => GB_Integer::Format($outLoses),
                'AMOUNT' => GB_Integer::Format($outAmount)
                );

                $groupsData[] = $params;
            }

            if(count($groupsData) > 0)
            {
                $params = array(
                'ARMY_NAME' => $army->getArmyName(),
                'USER_NAME' => $army->getBattleParticipant()->getUserInstance()->getUser()->getName(),
                );
                $report->addString($battleAction->formAction('ARMY_NAME', $params));

                foreach ($groupsData as $k=>$params)
                {

                    $report->addString($battleAction->formAction('UNITS_GROUP_LOSES', $params));
                }

                $report->addString();
            }

            if($army->getDieRoundNumber() == $round->getRoundNumber())
            {
                $deletedArmys[] = $army;
            }

        }
    }

    /**
     * Борьба с пропадающими между раундами юнитами Описание проблемы в заголовке файла.
     *
     * @param unknown_type $losesReal
     * @param unknown_type $amountAfterReal
     * @return unknown
     */
    static private function _countOutLoses($losesReal, $amountAfterReal)
    {
        $amounBeforeReal = $losesReal + $amountAfterReal;

        $amountAfterCeil = ceil($amountAfterReal);
        $amounBeforeCeil = ceil($amounBeforeReal);
        $losesFloor = floor($losesReal);

        $delta = $amounBeforeCeil - $amountAfterCeil;
        if($delta > $losesFloor)
        {
            $losesFloor += 1;
        }

        return array('LOSES' => $losesFloor,  'AMOUNT' => $amounBeforeCeil);
    }


}

?>