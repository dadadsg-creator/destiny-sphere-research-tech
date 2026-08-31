<?php $CVS_Version = '$Revision$ ; $Date$';
 
 /**
  * Класс "пользователя"
  */
 class DE_UserInstance extends DE_Base
 {
        
    /**ID юзера */
    private $userID ;
    private $raceID;
    private $worldID; 
    private $lastRefresh;
    
    /**
     * Enter description here...
     *
     * @var DE_BattleXP
     */
    private $battleXP;
    
    /**
     * Enter description here...
     *
     * @var DE_User
     */
    private $user;
    
    /**
     * Enter description here...
     *
     * @var DE_Alliance
     */
    private $aliance;
    
    /**
     * Enter description here...
     *
     * @var DE_Race
     */
    private $race;
    
    /**
     * Enter description here...
     *
     * @var DE_ReligionStatus
     */
    private $religionStatus;
    
    /**
     * Дата окончания действия защиты новичков
     *
     * @var GB_Date
     */
    private $newbDefendEndDate;
    
    /**
     * Дата окончания действия рандомной защиты(после повторного открытия ворот)
     *
     * @var GB_Date
     */
    private $randomDefendEndDate;
    
    /**
     * Максимально возможное окончание действия рандомной защиты 
     *
     * @var GB_Date
     */
    private $randomDefendEndDateMax;
    
    /**
     * Enter description here...
     *
     * @var DC_UserInstanceCombs
     */
    private $userInstanceCombs;
    
    /**
     * Enter description here...
     *
     * @var DC_UserInstanceCombs
     */
    private $nubhelplevel;
    
    /**
     * Enter description here...
     *
     * @var DC_UserInstanceCombs
     */
    private $help_var;
    
    /**
     * Лига игрока
     *
     * @var int
     */
    private $leagueId;

    public $buildSpeedupEndDate;
    
    
    function __construct($id, $data = '')
    {
        assert(is_int($id) && $id>=0);
        parent::__construct($id);                       

        if(!is_array($data))
        {
            $data = DS_UserInstance::GetConstantPartByID($id);
        }               
        assert(is_array($data) && sizeof($data)>0);     
        
        assert (key_exists ( 'id_user', $data ) );
        $this->userID = (int)$data['id_user'];
        assert(is_int($this->userID) && $this->userID>=0);
        
        assert(key_exists('id_race', $data));       
        $this->raceID = (int)$data['id_race'];
        assert(is_int($this->raceID) && $this->raceID>=0);
        
        assert(key_exists('id_world', $data));      
        $this->worldID = (int)$data['id_world'];
        assert(is_int($this->worldID) && $this->worldID>=0);
        
        // Защита новичка
        assert(key_exists('newb_defend_enddate', $data));       
        $newbDefendEndDate = $data['newb_defend_enddate'];
        if(!$newbDefendEndDate || is_null($newbDefendEndDate))
        {
            $newbDefendEndDate = 1;
        }
        $this->newbDefendEndDate = GB_TypeFactory::CreateConstant('GB_Date', $newbDefendEndDate);
        
        // Рандомная защита
        assert(key_exists('random_defend_enddate', $data));     
        $randDefendEndDate = $data['random_defend_enddate'];
        if(!$randDefendEndDate || is_null($randDefendEndDate))
        {
            $randDefendEndDate = 1;
        }
        $this->randomDefendEndDate = GB_TypeFactory::CreateConstant('GB_Date', $randDefendEndDate);
        
        assert(key_exists('random_defend_enddate_max', $data));     
        $randDefendEndDateMax = $data['random_defend_enddate_max'];
        if(!$randDefendEndDateMax || is_null($randDefendEndDateMax))
        {
            $randDefendEndDateMax = 1;
        }
        $this->randomDefendEndDateMax = GB_TypeFactory::CreateConstant('GB_Date', $randDefendEndDateMax);
        
        assert(key_exists('lastrefresh', $data));       
        if(!$data['lastrefresh'] || is_null($data['lastrefresh']))
        {
            $data['lastrefresh'] = 1;
        }
        $this->lastRefresh = GB_TypeFactory::CreateConstant('GB_Date', $data['lastrefresh']);
        
        assert(key_exists('nubhelplevel', $data));      
        $this->nubhelplevel = (int)$data['nubhelplevel'];
        
        assert(key_exists('help_var', $data));      
        $this->help_var = $data['help_var'];
        
        assert(key_exists('id_league', $data));     
        $this->leagueId = $data['id_league'];

        $this->buildSpeedupEndTime = strtotime($data['build_speedup_enddate']);
         
    }//END OF METHOD        
    
    /**
     * Enter description here...
     *
     * @param unknown_type $id
     * @return DE_Userinstance
     */
    static public function CreateFromID($id)
    {
        assert(is_int($id) && $id>=0);      
            
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
    }
    
    public function getLastRefresh()
    {
        return $this->lastRefresh;
    }
    
    public function setLastRefresh(GB_Date $date)
    {               
        $this->lastRefresh = $date;
        $this->isModifyed = true;
    }
    
    public function getUserInstanceID()
    {
        return $this->id;
    }
    
    public function getRaceID()
    {
        return $this->raceID;
    }
    
    public function getWorldID()
    {
        return $this->worldID;
    }
    
    /**
     * Enter description here...
     *
     * @return DE_World
     */
    public function getWorld()
    {
        return DE_World::CreateFromID($this->worldID);
    }
    
    public function getUserID()
    {           
        return $this->userID;   
    }
    
    /**
     * Enter description here...
     *
     * @return DE_BattleXP
     */
    public function getBattleXP()
    {           
        if(!($this->battleXP instanceof DE_BattleXP))
        {
            $this->battleXP = DE_BattleXP::CreateFromID($this->getID());
        }
        
        return $this->battleXP;
    }
    
    /**
     * Enter description here...
     *
     * @return DE_User
     */
    public function getUser()
    {       
        if(!($this->user instanceof DE_User))
        {
            $this->user = DE_User::CreateFromID($this->userID);
        }   
        
        return $this->user; 
    }
    
    /**
     * Enter description here...
     *
     * @return DE_Alliance
     */
    public function getAlliance()
    {               
        if(!($this->aliance instanceof DE_Alliance))
        {
            $this->aliance = DE_Alliance::CreateFromUserInstanceID($this->id);
        }
        
        return $this->aliance;      
    }
    
    /**
     * Enter description here...
     *
     * @return DE_Race
     */
    public function getRace()
    {               
        if(!($this->race instanceof DE_Race))
        {
            $this->race = DE_Race::CreateFromUserInstanceID($this->id);
        }
        
        return $this->race;     
    }

    /**
     * Enter description here...
     *
     * @return DE_Religion
     */
    public function getReligionStatus()
    {       
        if(!($this->religionStatus instanceof DE_Religion))
        {
            $this->religionStatus = DE_ReligionStatus::CreateFromUserInstanceID($this->id);
        }
        
        return $this->religionStatus;
    }
    
    
    public function getCombsCollection()
    {       
        if(!($this->userInstanceCombs instanceof DC_UserInstanceCombs))
        {
            $this->userInstanceCombs = DC_UserInstanceCombs::CreateFromUserInstanceID($this->getID());
        }
        
        return $this->userInstanceCombs;
    }
    
    public function haveNewbDefend()
    {
        if($this->newbDefendEndDate->value > time() && DE_WorldSettings::CreateForCurentWorld()->get('DISABLE_NEWB_DEFEND') == 0)
        {
            return true;
        }
        return false;
    }
    
    public function haveRandomDefend()
    {
        if($this->randomDefendEndDate->value > time())
        {
            return true;
        }
        return false;
    }
    
    public function getNubhelplevel()
    {
        return $this->nubhelplevel;
    }
    
    public function setNubhelplevel($level)
    {
        $this->nubhelplevel = $level;
        $this->isModifyed = true;
    }
    
    public function getHelp_var()
    {
        return $this->help_var;
    }
    
    public function setHelp_var($var)
    {
        $this->help_var = $var;
        $this->isModifyed = true;
    }
    
    public function getNewbDefendEndDate()
    {
        return $this->newbDefendEndDate;
    }
    
    public function getRandomDefendEndDate()
    {
        return $this->randomDefendEndDate;
    }
    
    public function getRandomDefendEndDateMax()
    {
        return $this->randomDefendEndDateMax;
    }
    
    public function getLeagueId()
    {
        return $this->leagueId;
    }
    
    public function setNewbDefendEndDate(GB_Date $defendEndDate)
    {
        $this->newbDefendEndDate = $defendEndDate;
        $this->isModifyed = true;
    }
    
    public function setRandomDefendEndDate(GB_Date $defendEndDate)
    {
        $this->randomDefendEndDate = $defendEndDate;
        $this->isModifyed = true;
    }
    
    public function setRandomDefendEndDateMax(GB_Date $defendEndDate)
    {
        $this->randomDefendEndDateMax = $defendEndDate;
        $this->isModifyed = true;
    }
 
    public function setLeagueId($leagueId)
    {
        // Если игрок npc, то лигу не меняем
        if ($this->getUser()->getIsUserNpc() == 't') {
            return;
        }
        
        assert($leagueId > 0);
        
        $this->leagueId = $leagueId;
        $this->isModifyed = true;
    }
    
    public function clearNewbDefend()
    {
        $this->setNewbDefendEndDate(GB_TypeFactory::CreateConstant('GB_Date', 1));
    }
    
    public function commit()
    {
        parent::commit();
        
        if($this->isModifyed == true)
        {
            if(DS_UserInstance::UpdateUserInstance($this))
            {
                $this->isModifyed = false;
            }           
        }
    }
 }//END OF CLASS


?>