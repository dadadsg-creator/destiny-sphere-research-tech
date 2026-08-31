<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс игровой логики определяющий можно ли в данный момент атаковать соту 
 * или нет
 *
 */

class GL_CanAttackComb extends GameBase
{
    /**
     * ID ошибки
     *
     * @var string
     */
    private $errorID;

    /**
     * Сота откуда идет нападение
     *
     * @var DE_Comb
     */
    private $combFrom;

    /**
     * Сота куда
     *
     * @var DE_Comb
     */
    private $combTo;


    /**
     * НА каой момент времени осуществыляем проверку
     *
     * @var GB_Date
     */
    private $chekDate;

    function __construct()
    {
        $this->settings = DE_WorldSettings::CreateForCurentWorld();
    }

    public function canAttack(DE_Comb $combFrom, DE_CombInterface $combTo, GB_Date $chekDate, $flag = false, $chekPeaceFull = true)
    {
        $this->combFrom = $combFrom;

        $this->combTo = $combTo;

        $this->chekDate = $chekDate;

        if ($this->combFrom->getUserInstanceID() == $this->combTo->getUserInstanceID()) return false;

        try {
            $this->_canMooveAfterFreez();

            //Бан хозяина
            $this->_checkBanUser();

            //Премиум бан хозяина
            $this->_checkPremiumBan();

            //Проверка а есть ли тот, на кого нападаем
            $this->_checkDestination();

            if ($flag === false) {
                //Открыты ли центральные вопрота у атакера
                $this->_checkAttackOpenGate();
            }

            //Есть ли гандола траспортер
            $this->_checkBuildGandola();

            //Если нападаем на другое кольца, проверяем есть ли кольцевые ворота
            $this->_checkRingGate();

            //Проверка перемирия
            $this->_checkPeacePeriods();
            
            //Не из одного ли альянса
            $this->_checkSomeAlliance();
            
            //Проверка, нет ли у нас отпуска
            $this->_checkVocationMode();
            
            //Не из одного ли комплекса
            $this->_checkInOneComplex();
            
            //Премиум бан атакуемого
            $this->_checkDstPremiumBan();
                
            //Проверяем не забанен ли атакуемый игрок
            $this->_checkDstBanUser();
            
            //Проверка военной этики
            $this->_checkOponentIsNewb();
            
            //Не на одном ли кольце игроки (если мир RVR)
            $this->_checkOnOneRing();
            
            //Провка не пытаюсь ли поставить армию на защиту на эту же соту
            $this->_checkDefend();
            
            // Если лиги включены
            if ($this->settings->get('LEAGUE_ENABLED') == 1) {
                //Провка атаки высшей лиги
                $this->_checkBuggerLealue();
            
                //Провка атаки нисшей лиги
                $this->_checkSmallerLealue();
            }
        } catch(Exception $e){
            $this->errorID = $e->getMessage();
            return false;
        }

        // Смотрим, захватываемая это колония или нет
        if (!$combTo->getIsCapture()) {
            // Проверки ТОЛЬКО для обычных сот
            try {
                //Проверка пустая ли сота на которую летим
                $this->checkEmptyComb();

                //Открыты ли центральные вопрота у защитника
                $this->_checkDefendOpenGate();

                //Прверяем военную этику
                $this->_checkWarEthics();

                //Проверка, не включен ли щит
                $this->_checkPowerShild();
            }catch(Exception $e){
                $this->errorID = $e->getMessage();
                return false;
            }
        } else {
            // Проверки ТОЛЬКО для захватываемых колоний
            try {
                //Защита соты
                $this->_checkTempoparyDefend();
                
                if ($chekPeaceFull == true) {
                    //Тихий час
                    $this->_checkPeaceFulTime();
                }

            }catch(Exception $e){
                $this->errorID = $e->getMessage();
                return false;
            }
        }
        return true;
    }

    private function _checkDefend()
    {
        $userArmysCollection = DC_UserInstanceArmys::CreateFromUserInstanceID($this->combFrom->getUserInstanceID());

        $userArmysCollection->resetNext();
        while($army = $userArmysCollection->next())
        {
            if(!$army->getInAction())
            {
                continue;
            }

            $armyAction = $army->getArmyAction();

            if(($armyAction->getActionType() == 'MOVE_TO_DEFEND' || $armyAction->getActionType() == 'IN_DEFEND')
            && $armyAction->getCombToID() == $this->combTo->getID())
            {
                throw new Exception("DENIED_ATTACK_COMB_ON_YOUR_DEFEND");
            }
        }
    }   
    
    private function _checkBuggerLealue()
    {
        $attackerLeague = $this->combFrom->getUserInstance()->getLeagueId();
        $defenderLeague = $this->combTo->getUserInstance()->getLeagueId();
        
        // Если лига атакера больше лиги защитника
        if ($attackerLeague >= $defenderLeague) {
            return;
        }
                
        // Если лига атакера равна 1, а лига атакуемого равна 3
        if ($attackerLeague == 1 && $defenderLeague == 3) {
            throw new Exception("DENIED_ATTACK_USER_WITH_BIGGER_LEAGUE");
        }
        
        // Если стоит модифаер запрет полет между этими лигами
        if(DE_WorldSettings::CreateForCurentWorld()->get('LEAGUE_CAN_ATTACK_'.$attackerLeague.'_'.$defenderLeague.'_LEAGUE') == 0)
        {
            throw new Exception("DENIED_ATTACK_USER_WITH_BIGGER_LEAGUE");
        }
    }
    
    private function _checkSmallerLealue()
    {
        $attackerLeague = $this->combFrom->getUserInstance()->getLeagueId();
        $defenderLeague = $this->combTo->getUserInstance()->getLeagueId();
                
        // Если лига атакера меньше лиги защитника
        if ($attackerLeague <= $defenderLeague) {
            return;
        }
                
        // Если лига атакера равна 1, а лига атакуемого равна 3
        if ($attackerLeague == 3 && $defenderLeague == 1) {
            throw new Exception("DENIED_ATTACK_USER_WITH_SMALLER_LEAGUE");
        }
        
        // Если стоит модифаер запрет полет между этими лигами
        if(DE_WorldSettings::CreateForCurentWorld()->get('LEAGUE_CAN_ATTACK_'.$defenderLeague.'_'.$attackerLeague.'_LEAGUE') == 0)
        {
            throw new Exception("DENIED_ATTACK_USER_WITH_SMALLER_LEAGUE");
        }
    }

    /**
     * Проверка не пустая ли сота куда мы хотим литеть
     *
     * @return True/False
     */
    private function checkEmptyComb()
    {
        //$combName = $this->combTo->getName();
        if($this->combTo instanceof DE_CombNull)
        {
            throw new Exception("DENIED_ATTACK_EMPTY_COMB_MESS");
        }

        $userInstanceID = $this->combTo->getUserInstanceID();
        if(empty($userInstanceID))
        {
            throw new Exception("DENIED_ATTACK_EMPTY_COMB_MESS");
        }
    }


    /**
     * Прверка военной этики. По силе армии.
     *
     * @return True/False
     */
    private function _checkWarEthics()
    {
        if($this->settings->get('POSSIBLE_ATTACK_SCOPE') > 0)
        {
            //Проверка но венную этику
            $armyPowerFrom = DE_CombDefendArmy::CreateFromCombID($this->combFrom->getID())->getArmyPower();
            $armyPowerTo = DE_CombDefendArmy::CreateFromCombID($this->combTo->getID())->getArmyPower();
            if ( (($armyPowerFrom/$armyPowerTo)/100) > $this->settings->get('POSSIBLE_ATTACK_SCOPE') )
            {
                throw new Exception("WAR_ETHICS");
            }
        }
    }//END OF METHOD

    private function _checkPeacePeriods()
    {
        $chekDate = $this->chekDate->getPgFormat();
        $peacePeriod = DS_Battle::GetCurrentPeacePeriod($chekDate);

        if(is_array($peacePeriod))
        {
            throw new Exception("PEACE_PERIOD");
        }
    }//END OF METHOD

    /**
    * Прверка на наличие Такого комба
    *
    * @return True/False
    */
    private function _checkDestination()
    {

        // Проверка на существование на кого нападаем
        if ($this->combTo->isNull())
        {
            throw new Exception("WRONG_DESTINATION");
        }

        if ($this->combTo->getName() == '')
        {
            throw new Exception("WRONG_DESTINATION");
        }

        if ($this->combFrom->getID() == $this->combTo->getID())
        {
            throw new Exception("WRONG_SOME_ATTACK");
        }

    }//END OF METHOD

    /**
    * Прверка на наличие гандолы траспортера
    *
    * @return True/False
    */
    private function _checkBuildGandola()
    {
        // имеется ли в наличии на соте Гондола Транспортер
        if (!$this->combFrom->haveGandolaTransporter())
        {
            throw new Exception("NO_GONDOLA_TRANSPORTER");
        } //END IF
    }//END OF METHOD

    /**
     * Прверка на открытость ворот атакером
     *
     * @return True/False
     */
    private function _checkAttackOpenGate()
    {
        // открыты ли ЦВ
        if (!$this->combFrom->haveOpenCentralGate())
        {
            throw new Exception("CLOSED_SELF_CENTRAL_GATES"); //у Себя на соте
        }
    }//END OF METHOD

    /**
     * Прверка на открытость ворот защитником
     *
     * @return True/False
     */
    private function _checkDefendOpenGate()
    {
        // открыты ли ЦВ
        if (!$this->combTo->haveOpenCentralGate())
        {
            throw new Exception("CLOSED_CENTRAL_GATES"); //у противника
        }
    }//END OF METHOD


    /**
     * Прверка на заты ворот захватываемой колонии
     *
     * @return True/False
     */
    private function _checkTempoparyDefend()
    {
        // Есть ли временная защита
        $defend = DE_CombProtectiveField::CreateFromCombID($this->combTo->getCombID());         

        if ($defend->getTemporaryDefend()->value > $this->chekDate->value)
        {
            throw new Exception("TEMPORARY_DEFEND_COLONY"); //у противника
        }
    }//END OF METHOD

    /**
     * Прверка на Тихий час
     *
     * @return True/False
     */
    private function _checkPeaceFulTime()
    {
        $peaceStart = DE_WorldSettings::CreateForCurentWorld()->get('PEACEFUL_TIME_START');
        $peaceEnd = DE_WorldSettings::CreateForCurentWorld()->get('PEACEFUL_TIME_END');
        if ($peaceStart != $peaceEnd) {
            //if (date('H', $this->chekDate->value) >= $peaceStart && date('H', $this->chekDate->value) < $peaceEnd) {
            if (constant('VERSION_NAME') == 'VKONTAKTE') {
                $minutes = 0;
            } else {
                $minutes = 30;
            }
            
            if ($this->chekDate->value >= mktime($peaceStart, $minutes, 0, date("m")  , date("d"), date("Y")) && $this->chekDate->value < mktime($peaceEnd, $minutes, 0, date("m")  , date("d"), date("Y"))) {
                    throw new Exception("COLONY_PEACEFUL_TIME"); // Затыка
            }
        }
    }//END OF METHOD


    /**
     * Проверка на наличие межкольцевых ворот
     *
     * @return True/False
     */
    private function _checkRingGate()
    {
        // проверяем есть ли RING_GATE
        if ( !$this->combFrom->getCombRing()->equals($this->combTo->getCombRing()) )
        {
            if ( !$this->combFrom->haveOpenRingGate() )
            {
                throw new Exception("NO_RING_GATES");
            }
        }
    }//END OF METHOD

    /**
     * Проверка не состоят ли оба хозяева сот в одном альянсе.
     *
     * @return True/False
     */
    private function _checkSomeAlliance()
    {
        $uiidFrom = $this->combFrom->getUserInstance()->getID();
        $uiidTo = $this->combTo->getUserInstance()->getID();
        
        // Если летим на игрока DestinySphere
        if ($uiidTo == 0) {
            return;
        }

        if($uiidFrom == $uiidTo)
        {
            throw new Exception("ATTACK_YOURSELF");
        }

        $this->allianceFrom = DE_Alliance::CreateFromUserInstanceID($uiidFrom);
        $this->allianceTo = DE_Alliance::CreateFromUserInstanceID($uiidTo);

        if($this->allianceFrom->isEmpty() || $this->allianceTo->isEmpty())
        {
            return;
        }

        if($this->allianceFrom->equals($this->allianceTo))
        {
            throw new Exception("IN_SOME_ALLIANCE");
        }
    }//END OF METHOD

    /**
     * Проверка не в одном ли комплексе одного кольца живут нападающий и защищающийся
     *
     * @return True/False
     */
    private function _checkInOneComplex()
    {
        if($this->settings->get('ALLOW_COMBCOMPLEX_ATTACK') == 0)
        {
            if(($this->combFrom->getRingNumber() == $this->combTo->getRingNumber()) && ($this->combFrom->getComplexNumber() == $this->combTo->getComplexNumber()))
            {
                throw new Exception("IN_ONE_COMPLEX");
            } //END IF
        }//END IF
    }//END OF METHOD

    /**
     * Проверка не на одном ли кольце опоненты
     *
     * @return True/False
     */
    private function _checkOnOneRing()
    {
        // Если сота принадлежит игроку ДС выходим
        if ($this->combTo->getUserInstance()->getID() == 0) {
            return;
        }
        
        //Если тип мира RvR то проверяем не одной ли рассы с атакуещем жертва
        if(key_exists('worldtype',$_SESSION) && $_SESSION["worldtype"] == 'RACE_VS_RACE')
        {
            if ($this->combFrom->getUserInstance()->getRaceID() == $this->combTo->getUserInstance()->getRaceID())
            {
                throw new Exception("WRONG_RVR_ATTACK_TARGET");
            }

        }//END OF IF
    }//END OF METHOD


    private function _checkVocationMode()
    {
        $chekDate = $this->chekDate->getPgFormat();
        $res = GL_IsUserInVocationMode::Test($this->combTo->getUserInstance(), $chekDate);

        if(count($res) > 0)
        {
            throw new Exception("CANT_ATTACK_USER_IN_VOCATION_MODE");
        }

    }

    private function _checkPowerShild()
    {
        $powerShild = DE_PowerShild::CreateFromUserInstance($this->combTo->getUserInstance()->getID());

        if($powerShild->isWorks($this->chekDate))
        {
            throw new Exception("CANT_ATTACK_USER_IN_POWER_SHILD");
        }

    }

    private function _canMooveAfterFreez()
    {
        if(GL_CanMooveArmysAfterFreez::Test($this->combFrom->getUserInstance(), $this->chekDate))
        {
            throw new Exception("CANT_MOOVE_ARMY_AFTER_FREEZ");
        }
    }

    private function _checkOponentIsNewb()
    {
        if($this->combTo->getUserInstance()->haveNewbDefend())
        {
            throw new Exception("CANT_ATTACK_NEWB");
        }
    }

    /**
     * Проверка на забаненность данного пользователя
     *
     * @return True/False
     */
    private function _checkBanUser()
    {
        //Установлен флаг активности true / false (t/f)
        if ($this->combFrom->getUserInstance()->getUser()->getAccountActive() == 'f')
        {
            throw new Exception("ARMY_ACTION_BAN");
            //throw new Exception("DENIED_ATTACK_BANNED_USER_MESS");
        }
    }

    private function _checkPremiumBan()
    {
        if($this->combFrom->getUserInstance()->getUser()->getPremiumBan()->dateInPeriod($this->chekDate))
        {
            //throw new Exception("ARMY_ACTION_PREMIUM_BAN");
            throw new Exception("ARMY_ACTION_BAN");
        }
    }

    /**
     * Проверка на забаненность пользователя, которого хотим атаковать
     *
     * @return True/False
     */
    private function _checkDstBanUser()
    {
        //Установлен флаг активности true / false (t/f)
        if ($this->combTo->getUserInstance()->getUser()->getAccountActive() == 'f')
        {
            throw new Exception("DENIED_ATTACK_BANNED_USER_MESS");
        }
    }

    private function _checkDstPremiumBan()
    {
        if($this->combTo->getUserInstance()->getUser()->getPremiumBan()->dateInPeriod($this->chekDate))
        {
            throw new Exception("DENIED_ATTACK_BANNED_USER_MESS");
        }
    }

    public function getError()
    {
        return $this->errormsg;
    }

    public function getErrorID()
    {
        return $this->errorID;
    }



}//END OF CLASS

?>