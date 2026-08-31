<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Сущность армии
 */

class DE_Army extends DE_Base implements DE_ArmyInterface
{

    private $id_basecomb;

    private $userInstanceID;

    private $moveable;

    private $inAction;

    private $unitContainerName;

    private $isDefault;

    private $currentCombID;

    private $bindEventID = null;

    private $hitedWarPowers = array();

    private $armyBonus;

    public $useArtefacts;
    
    
    /**
     * Enter description here...
     *
     * @var DE_ArmyAttributes
     */
    private $armyAttributes;
    
    /**
     * Enter description here...
     *
     * @var DE_UserInstance
     */
    private $userInstance;
    
    function __construct($id, $data = null)
    {       
        assert(is_integer($id) && $id>0);
        
        parent::__construct($id);
        
        //Это для того что бы засветитьс яна кладбище объектов и др объекты могли пнять что рмия удалена была
        $this->needAdditionalDeleteProcess = true;
        
        if(is_null($data))
        {
            $data = DS_Army::getByID($id);      
        }
        
        if(!(is_array($data) && count($data)>0))
        {
            ds_error('Try to create not existed army. '.$id);   
        }
        
        assert(key_exists('id_basecomb', $data));
        $this->id_basecomb = (int)$data['id_basecomb'];

        assert(key_exists('id_userinstance', $data));
        $this->userInstanceID = (int)$data['id_userinstance'];

        assert(key_exists('moveable', $data));
        $this->moveable = $data['moveable'];

        assert(key_exists('inaction', $data));
        $this->inAction = $data['inaction'];

        assert(key_exists('unitcontainername', $data));
        $this->unitContainerName = $data['unitcontainername'];

        assert(key_exists('isdefault', $data));
        $this->isDefault = $data['isdefault'];

        assert(key_exists('id_currentcomb', $data));
        $this->currentCombID = (int)$data['id_currentcomb'];

        $this->lastCoord = $data['lastcoord'];
        $this->speed = $data['speed'];
        $this->useArtefacts = $data['use_artefacts']=='t'?1:0;

        $this->_invariant('construct');
    }

    /**
     * Создание на основание ID
     *
     * @param unknown_type $id
     * @return DE_Army
     */
    static public function CreateFromID($id, $data = null)
    {
        assert((int)$id>0);
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, (int)$id, $data);
    }


    /**
     * Enter description here...
     *
     * @param unknown_type $combID
     * @return DE_Army
     */
    static public function CreateHomeArmyByCombID($combID)
    {
        assert(is_int($combID) && $combID>0);

        $armyID = DS_Army::GetHomeArmyIDByCombID($combID);

        return self::CreateFromID($armyID);
    }
    
    public function isDefault()
    {
        $this->_invariant();
    
        return $this->isDefault;
    }
    
    /**
     * Возвращает экземпляр текущего экшена армии
     *
     * @return DE_ArmyAction
     */
    public function getArmyAction()
    {
         
        return DE_ArmyAction::CreateFromArmyID($this->getID());
    }

    /**
     * коллекция с группами армии
     *
     * @return DC_ArmyUnitGroups
     */
    public function getUnitsGroupCollection($sort = false)
    {
        $data = null;
        
        if($sort)
        {
            $data = array('sort_by' => 1);
        }
        
        return DC_ArmyUnitGroups::CreateFromArmyID($this->id, $data);
    }

    /**
     * Армия имеет сейчас нулевой демедж
     *
     * @return bool
     */
    function haveNullDamage()
    {
        $unitsGroupCollection = $this->getUnitsGroupCollection();

        $unitsGroupCollection->resetNext();

        while ($unitsGroup = $unitsGroupCollection->next())
        {
            if(!$unitsGroup->haveNullDamage())
            {
                return false;
            }
        }

        return true;

    }

    /**
     * Возвращает текущает еккость армии для перевозки награбленного
     *
     * @return unknown
     */
    public function getCapacity()
    {
        $capacity = (float)0;

        $army = $this->getUnitsGroupCollection();
        $army->resetNext();
        while ($unitcollection = $army->next())
        {
            $capacity = $capacity + ($unitcollection->getUnit()->getCapacity() * $unitcollection->getAmount() );
        }

        return (float)$capacity;
    }

    /**
     * Чиним потери. Елси не добит югнит то его приводим к целому числу.
     *
     */
    public function repair()
    {
        $this->getUnitsGroupCollection()->resetNext();
        while ($unitsGroup = $this->getUnitsGroupCollection()->next())
        {
            $unitsGroup->repair();
        }
    }

    /**
     * Возвращает силу армии в стазисе
     *
     * @param bool $calcUnitsWithNullDamage считать ли юнитов с нулевым весом
     * @return float
     */
    public function getArmyPower($calcUnitsWithNullDamage = true)
    {
        assert(is_bool($calcUnitsWithNullDamage));

        $armyPower = (float)0;

        if(count($this->getUnitsGroupCollection()->countCollection())==0)
        {
            return $armyPower;
        };

        $collection = $this->getUnitsGroupCollection();
        
        $key = $collection->getCurrentPosition();       
        $collection->resetNext();
        while($unitsGroup = $collection->next())
        {
            $armyPower = $armyPower + $unitsGroup->getGroupPower($calcUnitsWithNullDamage);
        }
        $collection->setPointerToPosition($key);
        
        assert($armyPower >= 0);
        return (float)$armyPower;
    }

    /**
     * Возвращает колличество звезд в армии
     *
     * @return unknown
     */
    public function getArmyStars()
    {
        return GL_ArmyPowerToStars::Count($this->getArmyPower());
    }

    /**
     * Возвращает экземпляр обекта с старибутами армии
     *
     * @return DE_ArmyAttributes
     */
    public function getAttributes()
    {
        if(!($this->armyAttributes instanceof DE_ArmyAttributes))
        {
            $this->armyAttributes = DE_ArmyAttributes::CreateFromArmyID($this->getID());
        }
        
        return $this->armyAttributes;
    }

    /**
     * Возвращет объект инстанса пользваотеля хозяина армии
     *
     * @return DE_UserInstance
     */
    public function getUserInstance()
    {
        if(!($this->userInstance instanceof DE_UserInstance))
        {
            $this->userInstance = DE_UserInstance::CreateFromID($this->userInstanceID);
        }
         
        return $this->userInstance;
    }

    /**
     * Устанавливает объект инстанса пользваотеля хозяина армии
     *
     * @return DE_UserInstance
     */
    public function setUserInstance($userInstanceId)
    {
        //assert($userInstanceId > 0);
         
        $this->userInstanceID = $userInstanceId;
        $this->userInstance = DE_UserInstance::CreateFromID($userInstanceId);
        
        $this->isModifyed = true;
    }


    /**
     * Армия по умолчанию ил нет.
     *
     * @return unknown
     */
    public function getIsDefault()
    {
        if($this->isDefault == 'f')
        {
            return false;
        }else{
            return true;
        }
    }

    /**
     * Армия может двигаться или нет
     *
     * @return unknown
     */
    public function getIsMoveble()
    {
        if($this->moveable == 'f')
        {
            return false;
        }else{
            return true;
        }
    }

    /**
     * Говарит армия сейас в дейтсвии или нет.
     *
     * @param unknown_type $mode
     * @return unknown
     */
    public function getInAction($mode='PHP')
    {
         

        assert($mode=='PHP' || $mode=='PG');

        if($mode=='PHP')
        {
            if($this->inAction == 'f')
            {
                return false;
            }else{
                return true;
            }
        }else{
            return $this->inAction;
        }
    }

    /**
     * Вовзращает имя армии
     *
     * @return unknown
     */
    public function getName($withLocalization = true)
    {
         
        
        assert(is_bool($withLocalization));
        
        if (ereg('{|HOME_ARMY|}', $this->unitContainerName) && $withLocalization)
        {
            $localizedHomeArmyName = DE_Localization::CreateForCurentLang()->get((int)1085);
            return str_replace('{|HOME_ARMY|}', $localizedHomeArmyName, $this->unitContainerName);
        }
        return $this->unitContainerName;
    }

    /**
     * Возвращает объект базовой соты
     *
     * @return DE_Comb
     */
    public function getBaseComb()
    {
     
        return DE_Comb::CreateFromID($this->id_basecomb);

    }

    public function getBaseCombID()
    {
         
        return $this->id_basecomb;
    }
    
    public function setBaseComb(DE_Comb $comb)
    {
        $this->id_basecomb = $comb->getCombID();
        $this->isModifyed = true;
    }

    /**
     * Возвращает объект текущего комба на котором находиться армия
     *
     * @return DE_Comb
     */
    public function getCurentComb()
    {
         
        return DE_Comb::CreateFromID($this->currentCombID);
    }


    public function getCurentCombID()
    {
         
        return $this->currentCombID;
    }

    /**
     * Есть ли сейчас текущая сота у армии
     *
     * @return unknown
     */
    public function haveCurrentComb()
    {
         
        if ($this->currentCombID > 0 )
        {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * Возвращает ID текущего ивента армии
     *
     * @return unknown
     */
    public function getBindEventID()
    {
         
        
        if(is_null($this->bindEventID))
        {
            $this->bindEventID = DS_Army::GetBindEventQueueIDByArmyID($this->getID());
            assert($this->bindEventID >= 0);
        }
        
        return $this->bindEventID;
    }

    /**
     * Утсанавливаем у армии ивент
     *
     * @param unknown_type $eventID
     */
    public function setBindEventID($eventID)
    {
         
        assert($eventID>=0);

        $this->bindEventID = $eventID;
    }


    /**
     * Устанавливаем текущую соту
     *
     * @param DE_CombInterface $comb
     */
    public function setCurentComb(DE_CombInterface $comb)
    {
        $this->currentCombID = $comb->getID();
        $this->isModifyed = true;
    }


    /**
     * Определяем что армия в дейтсивей
     *
     * @param unknown_type $val
     */
    public function setInAction($val)
    {
         
        assert(is_bool($val));

        if($val == true)
        {
            $this->inAction = 't';
        }else{
            $this->inAction = 'f';
            $this->setBindEventID(0);
        }
        $this->isModifyed = true;
    }

    public function reciveHit(DE_UnitsGroupWarPower $warPower)
    {
         

        $this->hitedWarPowers[] = $warPower;
    }

    public function fetchHits()
    {
         

        $out = $this->hitedWarPowers;

        $this->hitedWarPowers = array();

        return $out;
    }

    public function haveHits()
    {
         

        if(count($this->hitedWarPowers)>0)
        {
            return true;
        }else{
            return false;
        }
    }

    public function getBonus()
    {
         

        if(is_null($this->armyBonus))
        {
            //Так береться бонус только для нападающего
            $this->armyBonus = GL_BattleBonuses::GetStaticBonus($this, null, $this->useArtefacts ? 2 : 0);
        }

        return $this->armyBonus;
    }
    
    public function setBonus(DE_BattleBonuses $bonus)
    {
        $this->armyBonus = $bonus;
    }

    protected function _invariant($mode = 'public')
    {
        if(BS_Base::_exitInvariant($mode)) return;

        parent::_invariant($mode);

        assert(is_int($this->id_basecomb) && $this->id_basecomb>0);
        assert(is_int($this->userInstanceID) && $this->userInstanceID>0);
        assert($this->moveable =='t' || $this->moveable == 'f');
        assert($this->inAction =='t' || $this->inAction == 'f');
        assert($this->isDefault =='t' || $this->isDefault == 'f');
        assert(!empty($this->unitContainerName));
        assert(is_int($this->currentCombID) && $this->currentCombID>=0);        
        assert(is_array($this->hitedWarPowers));
        assert(is_null($this->armyBonus) || $this->armyBonus instanceof DE_BattleBonusesStatic);
    }

    public function commit()
    {
        parent::commit();
                
        if($this->isModifyed == true)
        {   
            if(DS_Army::UpdateArmyData($this))
            {
                $this->isModifyed = false;
            }
        }                   
    }
    
    public function additionDelete()
    {   
        if($this->isDeleted)
        {
            DS_Army::DeleteArmyByID($this->getID());
        }
    }
    
    /**
     * Удаление объекта армии и самой армии из БД
     *
     */
    public function delete()
    {
        parent::delete();       
        
        if(!$this->getArmyAction()->isNull())
        {
            $this->getArmyAction()->delete();
        }       
    }
}


?>