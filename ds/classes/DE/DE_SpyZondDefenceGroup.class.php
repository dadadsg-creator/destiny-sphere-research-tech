<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Коллекция групп зондов на защите соты
 *
 */
class DE_SpyZondDefenceGroup extends DE_Base implements DE_SpyZondGroupInterface 
{
    /**
     * Enter description here...
     *
     * @var DE_Comb
     */
    private $comb;
    
    /**
     * Enter description here...
     *
     * @var DE_Unit
     */
    private $unit;
    
    /**
     * DC_SpyZondDefenceGroupCollection
     *
     * @var DC_SpyZondDefenceGroupCollection
     */
    private $groupCollection;
    
    public function __construct($id)
    {
        assert(is_int($id) && $id > 0);
        
        parent::__construct($id);
        
        $this->comb = DE_Comb::CreateFromID($id);
        
        $this->unit = DE_Unit::CreateFromName('SPY_ZOND_SCOUT');
        
        $this->groupCollection = DC_SpyZondDefenceGroupCollection::CreateFromCombID($id);
    }
    
    /**
     * Enter description here...
     *
     * @param integer $id
     * @return DE_SpyZondDefenceGroup
     */
    static public function CreateFromCombID($id)
    {
        assert(is_int($id) && $id > 0);
        
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);
    }
    
    /**
     * Enter description here...
     *
     * @return DE_Comb
     */
    public function getCombFrom()
    {
        return $this->comb;
    }
    
    /**
     * Enter description here...
     *
     * @return DE_Unit
     */
    public function getUnit()
    {
        return $this->unit;
    }
    
    /**
     * Общее кол-во зондов на защите
     *
     * @return int
     */
    public function getAmount()
    {
        $amount = 0;
        
        $this->groupCollection->resetNext();
        while ($group = $this->groupCollection->next())
        {
            $amount += $group->getAmount();
        }
        
        return $amount;
    }
    
    /**
     * Реальное кол-во (кол-во минус жертвы)
     *
     * @param bool $loses округлять или нет значение
     * @return float
     */
    public function getRealAmount($round = false)
    {
        assert(is_bool($round));
        
        $amount = 0;
        
        $this->groupCollection->resetNext();
        while ($group = $this->groupCollection->next())
        {
            $amount += $group->getRealAmount($round);
        }
        
        return $amount;
    }
    
    /**
     * Начальное кол-во зондов
     *
     * @return int
     */
    public function getStartAmount()
    {
        $amount = 0;
        
        $this->groupCollection->resetNext();
        while ($group = $this->groupCollection->next())
        {
            $amount += $group->getStartAmount();
        }
        
        return $amount;
    }
    
    public function setLoses($loses)
    {
        assert(is_float($loses));
        assert($loses > 0);
                
        $this->groupCollection->resetNext();
        while ($group = $this->groupCollection->next())
        {
            $key = $this->groupCollection->getCurrentPosition();
            $amount = (float)$group->getRealAmount();
            if($amount == 0)
            {
                $this->groupCollection->setPointerToPosition($key);
                continue;
            }
            // если повреждений больше, чем влезет в группу, то удаляем эту группу
                // а остаток удара передаем на следующую
            if($loses > $amount)
            {
                $loses -= $amount;
                $group->setLoses($amount);
                //$this->groupCollection->deleteElement($group);
                //$group->delete();
            } else {
                $group->setLoses($loses);
                break;
            }
            $this->groupCollection->setPointerToPosition($key);
        }
    }
    
    public function getLoses()
    {
        return $this->getStartAmount() - $this->getAmount();
    }
    
    public function getAttack()
    {
        return $this->unit->getAttributes()->getAttribute('FIGHT_ATTRIBUTE', 'ATTACK');
    }
    
    public function getDemage()
    {
        $demageMin = $this->unit->getAttributes()->getAttribute('FIGHT_ATTRIBUTE', 'DAMAGE_MIN');
        $demageMax = $this->unit->getAttributes()->getAttribute('FIGHT_ATTRIBUTE', 'DAMAGE_MAX');
        
        return GL_CountUnitAverageDemage::Count($demageMin, $demageMax);
    }
    
    public function getDefence()
    {
        return $this->unit->getAttributes()->getAttribute('FIGHT_ATTRIBUTE', 'DEFENCE');
    }
    
    public function getWarPower()
    {       
        return $this->getRealAmount() * $this->getAttack() * $this->getDemage();
    }
    
    public function getDefenceField()
    {       
        return 1/(1 - $this->getDefence()) - 1;
    }
    
    public function getOneHitPoints()
    {       
        return $this->unit->getAttributes()->getAttribute('FIGHT_ATTRIBUTE', 'HITPOINTS');
    }
    
    /**
     * Enter description here...
     *
     * @return DC_SpyZondDefenceGroupCollection
     */
    public function getSpyGroupsCollection()
    {
        return $this->groupCollection;
    }
    
    public function preCommit()
    {
        $this->groupCollection->resetNext();
        
        while ($group = $this->groupCollection->next())
        {
            $key = $this->groupCollection->getCurrentPosition();
            $group->preCommit();
            $this->groupCollection->setPointerToPosition($key);
        }
    }
    
    public function commit()
    {
        parent::commit();
        
        $this->groupCollection->resetNext();
        
        while ($group = $this->groupCollection->next())
        {
            $group->commit();
        }
    }
}

?>