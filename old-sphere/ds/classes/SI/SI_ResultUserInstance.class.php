<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Сохраненный инстанс пользователя
 *
 */
class SI_ResultUserInstance
{
    /**
     * ID пользователя чей это инстанс
     *
     * @var integer
     */
    private $userID;
    
    /**
     * ДАмп сведений из Таблицы пользователя на случай если его опять придеться создавать
     *
     * @var array
     */
    public $userData = array();
    
    /**
     * ID мира откуда сохранен инстан
     *
     * @var unknown_type
     */
    private $worldID;
    
    /**
     * ID инстанса
     *
     * @var integer
     */
    private $UIID;
    
    /**
     * EMAIL
     *
     * @var string
     */
    private $email;
    
    /**
     * ID рассы инстанса
     *
     * @var integer
     */
    private $raceID;
    
    /**
     * науки пользователя
     *
     * @var array()
     */
    private $sciences = array();
        
    /**
     * защита нубов
     *
     * @var timestamp
     */
    private $newbDefend;
    
    /**
     *религии елси она етсь у пользователя
     *
     * @var integer
     */
    private $religion = array();
    
    /**
     * Военный опыт 
     *
     * @var integer
     */
    private $battleXP = array();    
    
    /**
     * Сотыв игрока
     *
     * @var array()
     */
    private $combs = array();
    
    /**
     * Ивенты которые надо перенести вместе с юзверем
     *   
     * @var array
     */
    public $events = array();
    
    /**
     * Список способностей юзверя
     *
     * @var array
     */
    private $userAbilitys = array();
    
    /**
     * Сдвигн времени последнего рефреша от даты сохзранения
     *
     * @var string
     */
    private $lastRefreshDelta;
    
    /**
     * Цепочка колонизации
     *
     * @var array
     */
    private $colonizeChain = array();
    
    /**
     * Список колнизационных модулей
     *
     * @var array
     */
    private $colonizators = array();
    
    /**
     * Полное имя мира откуда взят инстанс
     *
     * @var string
     */
    private $sourceWorldName = '';
    
    /**
     * Короткое имя мира откуда взят инстанст
     *
     * @var string
     */
    private $sourceWorldShortName = '';
    
    /**
     * Имя пользователя кому прндлежал этот инстант
     *
     * @var string
     */
    private $username = '';
    
    /**
     * Дата сохранения инстанса
     *
     * @var GB_Date
     */
    private $storeDate;
        
    /**
     * Время турна в скундах в мире откуда.
     *
     * @var integer
     */
    private $worldTurnTime;
    
    /**
     * Информация о режиме отпуска
     *
     * @var array
     */
    private $vocationModeData;
    
    /**
     * Информация о доме отдыха
     *
     * @var array
     */
    private $grandBuilding;
    
    /**
     * Информация о бонусах игрока
     *
     * @var array
     */
    private $userBonuses;
    
    /**
     * Продовес построенной и потерянной армии
     *
     * @var array
     */
    private $armyWeight;
    
    /**
     * Рейтинг игрока
     *
     * @var array
     */
    private $ranking;
    
    /**
     * Лига игрока
     *
     * @var int
     */
    private $league;
    
    /**
     * Подсказки игрока
     *
     * @var int
     */
    private $nubhelplevel;
    
    public function __construct($UIID)
    {       
        assert((int)$UIID > 0);
        $this->UIID = (int)$UIID;
                
        $userData = SI_SearchUserInstance::GetUserDataByUIID($UIID);
        
        $this->userID = (int)$userData['id_user'];
        assert($this->userID > 0);
        
        $this->email = $userData['email'];
        assert(!empty($this->email));
        
        $this->raceID = (int)$userData['id_race'];
        assert($this->raceID > 0);
        
        $this->worldID = (int)$userData['id_world'];        
        assert($this->worldID > 0);
        
        $this->sourceWorldName = $userData['worldname'];
        assert(!empty($this->sourceWorldName));
        
        $this->sourceWorldShortName = $userData['world_identifier'];
        assert(!empty($this->sourceWorldShortName));
        
        $this->username = $userData['username'];
        assert(!empty($this->username));
        
        $this->grandBuilding = (int)$userData['id_building'];

        assert(key_exists('newb_defend_enddate', $userData));
        $this->newbDefend = GB_TypeFactory::CreateBase('GB_Date', $userData['newb_defend_enddate']);
        
        $this->userData = $userData;
        
        $this->storeDate = GB_TypeFactory::CreateBase('GB_Date', time());
        
        $this->worldTurnTime = (int)$userData['turn_time'];
        assert($this->worldTurnTime > 0);
        
        $lastRefreshTimeObject = GB_TypeFactory::CreateBase('GB_Date', $userData['lastrefresh']);
        $this->lastRefreshDelta = $lastRefreshTimeObject->value - time();
        
        $this->vocationModeData['START_DATE'] = $userData['startdate'];
        $this->vocationModeData['END_DATE'] = $userData['enddate'];
        $this->vocationModeData['PASSWORD'] = $userData['password'];    
        
        $this->league = $userData['id_league'];
        
        $this->nubhelplevel = $userData['nubhelplevel'];
    }   
    
    public function getTurnTime()
    {
        return $this->worldTurnTime;        
    }
    
    public function getEmail()
    {
        return $this->email;
    }
        
    public function getUserID()
    {
        return $this->userID;       
    }
    
    public function setUserID($userID)
    {
        settype($userID, 'integer');
        assert($userID > 0);
        $this->userID = $userID;        
    }
    
    public function getUserName()
    {
        return $this->username;     
    }

    public function setUserName($userName)
    {
        assert(is_string($userName) && strlen($userName));
        $this->username = $userName;
    }
    
    public function getNewbDefend()
    {
        return $this->newbDefend;
    }
    
    public function getUserData()
    {
        return $this->userData;     
    }
    
    public function setColonizators($colonizatorsData)
    {       
        assert(is_array($colonizatorsData));
        
        $this->colonizators = $colonizatorsData;
    }
    
    public function getColonizators()
    {                       
        return $this->colonizators;
    }
    
    public function setColonizeChain($colonizeChain)
    {       
        assert(is_array($colonizeChain));
        
        $this->colonizeChain = $colonizeChain;
    }
    
    public function getColonizeChain()
    {
        return $this->colonizeChain;
    }
    
    public function getRaceID()
    {
        return $this->raceID;
    }
    
    public function getUIID()
    {
        return $this->UIID;
    }
    
    public function getLastRefreshDelta()
    {
        $this->lastRefreshDelta;
    }
    
    public function getWorldID()
    {
        return $this->worldID;
    }
    
    public function getShortWorldName()
    {
        return $this->sourceWorldShortName;
    }
    
    public function setScience($sciences)
    {
        assert(is_array($sciences));        
        
        $this->sciences = $sciences;                    
    }
    
    public function getSciences()
    {
        return $this->sciences;
    }
    
    public function setReligion($religionData)
    {       
        assert(is_array($religionData) || is_null($religionData));
        $this->religion = $religionData;    
    }
    
    public function getReligion()
    {               
        return $this->religion; 
    }
        
    public function setUserBonuses($bonuses)
    {       
        assert(is_array($bonuses) || is_null($religionData));
        $this->userBonuses = $bonuses;  
    }
        
    public function setArmyWeight($armyWeight)
    {       
        assert(is_array($armyWeight) || is_null($armyWeight));
        $this->armyWeight = $armyWeight;    
    }
        
    public function setRanking($ranking)
    {       
        assert(is_array($ranking) || is_null($ranking));
        $this->ranking = $ranking;  
    }
    
    public function getUserBonuses()
    {               
        return $this->userBonuses;  
    }
    
    public function getArmyWeight()
    {               
        return $this->armyWeight;   
    }
    
    public function getRanking()
    {               
        return $this->ranking;  
    }
    
    public function setBattleXP($battleXP)
    {
        assert(is_array($battleXP));
        $this->battleXP = $battleXP;
    }   
    
    public function getBattleXP()
    {       
        return $this->battleXP;     
    }   
    
    public function setComb(SI_ResultComb $combStore)
    {
        $this->combs[$combStore->getCombName()] = $combStore;
    }
    
    public function getComb()
    {
        return $this->combs;
    }
    
    public function getCombByName($combName)    
    {
        assert(!empty($combName));
        assert(key_exists($combName, $this->combs));
        
        return $this->combs[$combName];     
    }
    
    public function getGrandBuilding()
    {
        return $this->grandBuilding;
    }
    
    public function getVocationMode()
    {
        return $this->vocationModeData;
    }
    
    public function getLeague()
    {
        return $this->league;       
    }
    
    public function getNubhelplevel()
    {
        return $this->nubhelplevel;     
    }
    
    private function _setNewCombName($oldName, $newName)
    {
        assert(!empty($oldName));
        assert(!empty($newName));
        
        $combResult = $this->getCombByName($oldName);
        unset($this->combs[$oldName]);
        
        $combResult->setCombName($newName);
        $this->combs[$newName] = $combResult;
    }
    
    public function setNewCombNames($newCombNames)
    {
        assert(is_array($newCombNames) && count($newCombNames) == count($this->combs));
        
        foreach($newCombNames as $combOldName => $combNewName)
        {
            assert(key_exists($combOldName, $this->combs));
            $this->_setNewCombName($combOldName, $combNewName);
        }
    }
    
    public function setEvent($eventData, $eventOptions)
    {
        assert(is_array($eventData));
        assert(is_array($eventOptions));
        
        $eventData['options'] = $eventOptions;
        
        $this->events[] = $eventData;
    }
    
    public function getEvents()
    {
        return $this->events;
    }
    
    public function setAbility($abilityID)
    {
        assert((int)$abilityID > 0);
        $this->userAbilitys[] = $abilityID;     
    }
    
    public function getAbilityes()
    {
        return $this->userAbilitys;
    }
}

?>