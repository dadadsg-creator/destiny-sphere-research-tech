<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Сущность ЮНИТ
 */

class DE_Unit extends DE_Base implements DE_TradeGoodInterface
{       
    
    private $localizednameID;
    
    private $localizeddescriptionID;
    
    private $unitname;
    
    private $extendeddescriptionID;
    
    private $unitClassNumber;
    
    /** Пересчет юнита в стазис */
    private $unitPower = null;
        
    
    function __construct($id)
    {
        assert(is_int($id) && $id>0);
        
        parent::__construct($id);       
        
        $data = DS_Unit::GetByID($id, false);
        
        assert(is_array($data));
        assert(count($data)>0);
        
        assert ( key_exists ( 'id_localizedname', $data ) );        
        $this->localizednameID = (int)$data['id_localizedname'];
        assert(is_int($this->localizednameID) && $this->localizednameID>0);
        
        assert ( key_exists ( 'id_localizeddescription', $data ) );     
        $this->localizeddescriptionID = (int)$data['id_localizeddescription'];
        assert(is_int($this->localizeddescriptionID) && $this->localizeddescriptionID>0);
        
        assert ( key_exists ( 'unitname', $data ) );        
        $this->unitname = $data['unitname'];
        assert(!empty($this->unitname));
        
        assert ( key_exists ( 'id_extendeddescription', $data ) );      
        $this->extendeddescriptionID = (int)$data['id_extendeddescription'];
        assert((is_int($this->extendeddescriptionID) && $this->extendeddescriptionID>0) || !is_null($this->extendeddescriptionID));
        
        assert ( key_exists ( 'classnumber', $data ) );     
        $this->unitClassNumber = (int)$data['classnumber'];
        assert($this->unitClassNumber >= 0);        
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $id
     * @return DE_Unit
     */
    static public function CreateFromID($id)
    {       
        assert(is_integer($id) && $id>0);
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, "", "GLOBAL_CACHE");
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $name
     * @return DE_Unit
     */
    static public function CreateFromName($name)
    {       
        assert(!empty($name));
        
        $id = DS_Unit::GetIDByUnitName($name);
        assert(is_int($id) && $id>0);
        
        return self::CreateFromID($id);
    }
    
    /**
     * Enter description here...
     *
     * @return DE_UnitAttributes
     */
    public function getAttributes()
    {
                
        return DE_UnitAttributes::CreateFromUnitID($this->getID());
    }
    
    public function getCapacity()
    {
                
        $attribute = $this->getAttributes();
        if($attribute->haveAttribute('MAX_AMOUNT', 'CAPACITY'))
        {
            $capacity = (float)$attribute->getAttribute('MAX_AMOUNT', 'CAPACITY');
        }else{
            $capacity = (float)0;           
        }
        
        return $capacity;
    }
    
    public function getClassNumber()
    {
        return $this->unitClassNumber;
    }
    
    
    public function getUnitName()
    {

        return $this->unitname;
    }
    
    public function getName()
    {
        return $this->getUnitName();
    }
    
    public function getUnitVsUnitBonus(DE_Unit $unitTo)
    {
        
        return DE_UnitVSUnitBonus::CreateFromUnitFromID($this->getID())->getBonusVsUnit($unitTo);
    }
    
    public function getUnitNameLocolized()
    {
            
        return DE_Localization::CreateForCurentLang()->get($this->localizednameID);
    }
    
    public function getUnitLocolizedId()
    {
        return $this->localizednameID;
    }
    
    public function getLocalizedName()
    {
        return $this->getUnitNameLocolized();
    }
    
    public function getUnitPower()
    {
        if(is_null($this->unitPower))
        {
            $this->unitPower = $this->getAttributes()->getStasis();
        }
        
        return $this->unitPower;
    }
    
    public function getStazis()
    {
        return $this->getUnitPower();   
    }
    
}//END OF CLASS

?>