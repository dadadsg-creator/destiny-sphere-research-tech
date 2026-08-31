<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_UnitAttributes extends DE_Base
{
    private $attributes = array();
    
    private $unitID;
    
    function __construct($unitID)
    {
        GB_SingletonObjectStorage::TestSingletonCreation();
        assert(is_int($unitID) && $unitID>0);
        parent::__construct($unitID);
        
        $this->unitID = (int)$unitID;
        
        $res = DS_Unit::GetUnitAtrributes($unitID, false);
        
        $this->_importAttributes($res);
        $this->_invariant('construct');
    }   
    
    /**
     * Enter description here...
     *
     * @param integer $unitID
     * @return DE_UnitAttributes
     */
    static public function CreateFromUnitID($unitID)
    {               
        assert(is_int($unitID) && $unitID>0);       
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $unitID, "", "GLOBAL_CACHE");
    }
    
    private function _importAttributes($res)
    {
        assert(is_array($res) && count($res)>0);

        foreach($res as $k => $v)           
        {
            $val = array(
                'VALUE' => (float)$v['value'],
                'STASIS' => $v['value'] * $v['exchangerate']
            ); 
            $this->attributes[$v['typename']][$v['attributename']] = $val;
        }
    }
    
    public function getUnit()
    {
        $this->_invariant();
        return DE_Unit::CreateFromID($this->unitID);
    }
    
    public function getAttributeNamesByTypeName($attributeTypeName)
    {
        $this->_invariant();
        
        if(key_exists($attributeTypeName, $this->attributes))
        {
            return array_keys($this->attributes[$attributeTypeName]);
        }else{
            return array();
        }
    }
    
    public function getAttribute($attributeTypeName, $attributeName)
    {
        $this->_invariant();
        assert(!empty($attributeName));
        assert(!empty($attributeTypeName));

        if (!(key_exists($attributeTypeName, $this->attributes))) return 0;
        if (!(key_exists($attributeName, $this->attributes[$attributeTypeName]))) return 0;
                
        return (float)$this->attributes[$attributeTypeName][$attributeName]['VALUE'];
    }
    
    public function haveAttribute($attributeTypeName, $attributeName)
    {
        $this->_invariant();
        assert(!empty($attributeName));
        assert(!empty($attributeTypeName)); 
        
        if(!key_exists($attributeTypeName, $this->attributes)) 
        {           
            return false;
        };
        if(!key_exists($attributeName, $this->attributes[$attributeTypeName]))
        {
            return false;
        }
        
        return true;        
    }

    public function getStasis()
    {
        $this->_invariant();
        
        $stasis = 0;

        foreach($this->attributes['PRODUCTION_COSTS'] as $k => $v)      
        {
            $stasis += $v['STASIS'];
        }
        
        return (float)$stasis;
    }
    
    public function getProductionCost()
    {
        $this->_invariant();        
        
        $out = array();
        

        foreach($this->attributes['PRODUCTION_COSTS'] as $attributeName => $value)
        {
            $out[$attributeName] = $value['VALUE'];
        }       

        return $out;
    }
    
    protected function _invariant($mode = 'public')
    {
        if(BS_Base::_exitInvariant($mode)) return;
        
        parent::_invariant($mode);
        
        assert(is_int($this->unitID) && $this->unitID > 0);
        assert(is_array($this->attributes) && count($this->attributes)>0);
    }
    
    public function commit()
    {
        $this->_invariant();        
        parent::commit();               
    }
    
    function __destruct()
    {
        $this->_invariant();
        parent::__destruct();       
    }

}//END OF CLASS

?>