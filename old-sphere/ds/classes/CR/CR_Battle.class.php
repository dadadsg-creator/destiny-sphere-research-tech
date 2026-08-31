<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Контролер управления протеканием боя боя
 *
 */
class CR_Battle extends CR_Base
{
    
    /**
     * Объект боя которым управляем
     *
     * @var DE_Battle
     */
    private $battle;
    
    /**
     * Армия аттакующего
     *
     * @var DE_Army
     */
    private $army;
    
    /**
     * Сота откуда атака
     *
     * @var DE_Comb
     */
    private $combFrom;
    
    /**
     * Сота на которую атака
     *
     * @var DE_Comb
     */
    private $combTo;
    
    /**
     * Время кончания текущего ивента в котором работаем
     *
     * @var GB_Date
     */
    private $currentEventEndTime;
    
    /**
     * Армия защитника
     *
     * @var DE_CombDefendArmy
     */
    private $combDefendArmy;
    
    /**
     * Флаг того что все данные по бою устоновленны без этого флага нельза рабоать
     *
     * @var boolean
     */
    private $battleDataSeted = false;
    
    /**
     * Коснтрутор
     *
     * @param integer $id ID боя
     */
    public function __construct($id)
    {       
        assert(is_integer($id) && $id>0);                       
        
        parent::__construct($id);
        
        $this->battle = DE_Battle::CreateFromID($id);               
    }
    
    /**
     * задаем данные по бою кто с кем и когда
     *
     * @param array $data
     */
    
    public function setBattleData($data)
    {
        assert(is_array($data));    
        
        assert(key_exists('ARMY', $data));
        $this->army = $data['ARMY'];
        assert($this->army instanceof DE_Army);
        
        assert(key_exists('COMB_FROM', $data));
        $this->combFrom = $data['COMB_FROM']; 
        assert($this->combFrom instanceof DE_Comb);
        
        assert(key_exists('COMB_TO', $data));
        $this->combTo = $data['COMB_TO'];
        assert($this->combTo instanceof DE_Comb);
        
        assert(key_exists('EVENT_END_TIME', $data));
        $this->currentEventEndTime = $data['EVENT_END_TIME'];
        assert($this->currentEventEndTime instanceof GB_Date);      
        
        $this->combDefendArmy = DE_CombDefendArmy::CreateFromCombID($this->combTo->getID());
        
        $this->battleDataSeted = true;
    }
    
    /**
     * Создание боя и экземпляра контроллера
     *
     * @param DE_Army $army
     * @param DE_Comb $combFrom
     * @param DE_CombTo $combTo
     * @param GB_Date $currentEventEndTime
     * @return CR_Battle
     */
    static public function CreateWithBattle(DE_Army $army, DE_Comb $combFrom, DE_Comb $combTo, GB_Date $currentEventEndTime)
    {
        $battle = ME_StartBattle::Exec($army, $combFrom, $combTo, $currentEventEndTime);
        
        $data = array 
        (
            'ARMY' => $army,
            'COMB_FROM' => $combFrom,
            'COMB_TO' => $combTo,
            'EVENT_END_TIME' => $currentEventEndTime,
        );
            
        $controller = GB_SingletonObjectStorage::GetSingleton(__CLASS__, $battle->getID());
        $controller->setBattleData($data);
        return $controller;
    }       
    

    /**
     * Возвращаем объект боя
     *
     * @return DS_Battle
     */
    public function getBattle()
    {
        return $this->battle;
    }
    
    /**
     * Создание контролерра по бою.
     *
     * @param DE_Battle $battle  
     * @param DE_Army $army
     * @param DE_Comb $combFrom
     * @param DE_CombTo $combTo
     * @param GB_Date $currentEventEndTime
     * @return CR_Battle
     */
    static public function CreateFromBattle(DE_Battle $battle, DE_Army $army, DE_Comb $combFrom, DE_Comb $combTo, GB_Date $currentEventEndTime)
    {       
        $data = array 
        (
            'ARMY' => $army,
            'COMB_FROM' => $combFrom,
            'COMB_TO' => $combTo,
            'EVENT_END_TIME' => $currentEventEndTime,
        );
        
        $controller = GB_SingletonObjectStorage::GetSingleton(__CLASS__, $battle->getID());
        $controller->setBattleData($data);
        return $controller;
    }
    
    public function startRound($battleRoundNumber)
    {
        DE_BattleSystemLog::Insert("\n======== START CREATE ROUND $battleRoundNumber\n");
        assert($this->battleDataSeted);
        assert(is_int($battleRoundNumber) && $battleRoundNumber > 0);
        
        //Расчитали время рануда для этих восйк и создали райнд
        $roundDuration = GL_BattleRoundDuration::Count($this->army, $this->combTo);         
        $round = DE_BattleRound::CreateInDB($this->battle, $battleRoundNumber, $this->currentEventEndTime, $roundDuration);
        
        DE_BattleSystemLog::Insert("\n======== END CREATE ROUND $battleRoundNumber\n");
        return $roundDuration;
    }
    
    /**
     * Удар армий друг о друга
     *
     * @param integer $battleRoundNumber
     * @return GB_TimePeriod
     */
    public function armysHit (DE_BattleRound $round)
    {

        DE_BattleSystemLog::Insert("\n======== START ARMYS HIT\n");
        assert($this->battleDataSeted);             
        assert($round->getRoundNumber() > 0);
        
        //Ударили армии дру о друга
        $battleSidesHits = GL_SidesHaveHitInBattle::Count($this->battle);
        ME_ArmysHit::Exec($this->army, $this->combDefendArmy, $battleSidesHits['ATTACKER'], $battleSidesHits['DEFENDER']);
            
        //Даобавли в него армии после этого
        ME_AddRoundResults::Exec($round, $this->army, $this->combDefendArmy);

        //Вычистили потери у армий
        ME_CleanArmyLooses::Exec($this->army, $this->combDefendArmy);
            
        //Чистим урмии пустые поле удара
        ME_CleanEmptyArmysAfterBattle::exec($round, $this->army, $this->combDefendArmy);
        
        // Проверяем не остались ли одни транспорта в армии
        ME_CheckBattleNullDamage::Check($this->army, $this->combDefendArmy, $round);
        
        //Вносим изменения в апкипы на основание потерь                     
        DE_LiveUpkeepPoints::GetInstance()->addPoint($this->combTo, $this->currentEventEndTime);
        
        DE_BattleSystemLog::Insert("\n======== END ARMYS HIT\n");       
    }
    
    /**
     * Удар армий друг о друга
     * Попытка улучшить бой
     *
     * @param integer $battleRoundNumber
     * @return GB_TimePeriod
     */
    public function armysHitNew (DE_BattleRound $round)
    {

        DE_BattleSystemLog::Insert("\n======== START ARMYS HIT\n");
        assert($this->battleDataSeted);             
        assert($round->getRoundNumber() > 0);
        
        //Ударили армии дру о друга
        $battleSidesHits = GL_SidesHaveHitInBattle::Count($this->battle);
        ME_ArmysHitNew::Exec($this->army, $this->combDefendArmy, $battleSidesHits['ATTACKER'], $battleSidesHits['DEFENDER']);
            
        //Даобавли в него армии после этого
        ME_AddRoundResults::Exec($round, $this->army, $this->combDefendArmy);

        //Вычистили потери у армий
        ME_CleanArmyLooses::Exec($this->army, $this->combDefendArmy);
            
        //Чистим урмии пустые поле удара
        ME_CleanEmptyArmysAfterBattle::exec($round, $this->army, $this->combDefendArmy);
        
        // Проверяем не остались ли одни транспорта в армии
        ME_CheckBattleNullDamage::Check($this->army, $this->combDefendArmy, $round);
        
        //Вносим изменения в апкипы на основание потерь                     
        DE_LiveUpkeepPoints::GetInstance()->addPoint($this->combTo, $this->currentEventEndTime);
        
        DE_BattleSystemLog::Insert("\n======== END ARMYS HIT\n");       
    }
    
    /**
     * Завершение боя
     *
     * @param DE_EndBattleTestResult $reason
     */
    public function battleEnd(DE_EndBattleTestResult $reason)
    {
        DE_BattleSystemLog::Insert("\n======== START END BATTLE\n");
        assert($this->battleDataSeted);
        
        // Смотрим, захватили ли мы колонию
        $capture = false;
        if($reason->getWinnerCode()->value == 'A')
        {
            // Если бой был на захватываемой колонии
            if ($this->battle->getComb()->getIsCapture()) {
                // Смотрим, захватили ли мы колонию
                $field = DE_CombProtectiveField::CreateFromCombID($this->battle->getComb()->getCombID());
                $fieldVal = $field->getProtectiveField();
                assert($fieldVal >= 0 && $fieldVal <= 100);

                // Смотрим, есть ли на соте юнит (не в укрытии!)
                //$userHasUnit = DS_Unit::IsUnitOnComb($this->battle->getComb()->getID());
                $userHasUnit = false;
                
                $this->combDefendArmy->getCombDefendUnitGroupsCollection()->resetNext();
                while($combDefendGroup = $this->combDefendArmy->getCombDefendUnitGroupsCollection()->next())
                {
                    if ($combDefendGroup->getUnit()->getId() == 98 || $combDefendGroup->getUnit()->getId() == 99 || $combDefendGroup->getUnit()->getId() == 93) {
                        $userHasUnit = true;
                        break;
                    }
                }   

                $canCapture = new GL_CaptureColonies();
                if ($fieldVal == 0 && $canCapture->canCaptureColony($this->battle->getFromComb())) {
                    // Смотрим, есть ли на соте юнит
                    if (!$userHasUnit) {
                        $capture = true;
                    }
                } 
            }
        }
        
        
        //Совершаем действия необходимые для завершени ябоя
        ME_EndBattle::Exec($this->battle, $reason, $this->currentEventEndTime, $capture);           
            
        //Расчитали опыт
        ME_BattleXP::Add($this->battle);

        //Сформировали логбоя
        ME_FormBattleLog::Exec($this->battle, $capture);

        if (DE_WorldSettings::CreateForCurentWorld()->get('ACTIVE_NPC')) {
            $isAttackerNPC = $this->combFrom->getUserInstance()->getUser()->getIsUserNpc() == 't';
            $isDefenderNPC = $this->combTo->getUserInstance()->getUser()->getIsUserNpc() == 't';
            if ($isAttackerNPC || $isDefenderNPC) {
                ME_LogNPCBattle::Log($this->battle, $isDefenderNPC);
            }
        }

        // Захватываем соту, если надо
        if ($capture) {
            ME_CaprureColony::Capture($this->battle->getComb(), $this->battle->getAttacker()->getUserInstance(), $this->currentEventEndTime);
        }

        //Починили юниыт посл боя       
        ME_RepairUnitsAfterBattle::Exec($this->army, $this->combDefendArmy);


        //Вносим изменения в апкипы на основание потерь                     
        DE_LiveUpkeepPoints::GetInstance()->addPoint($this->combTo, $this->currentEventEndTime);
        
        DE_BattleSystemLog::Insert("\n======== END END BATTLE\n");
    }
}



?>
