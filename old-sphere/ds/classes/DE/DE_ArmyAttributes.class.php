<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Атрибуты армии
 */
class DE_ArmyAttributes extends DE_Base
{
    private $attributes = array();
    
    function __construct($armyID)
    {
        assert(is_int($armyID) && $armyID>0);
        parent::__construct($armyID);
        
        $res = DS_Army::GetArmyAtrributes($armyID);
        if (!is_array($res))
        {
            $res = DE_AttributesNull::Create();
        }
        else {
            $this->_importAttributes($res);
            $this->_invariant('construct');
        }
    }

    /**
     * Enter description here...
     *
     * @param int $armyID
     * @return DE_ArmyAttributes
     */
    static public function CreateFromArmyID($armyID)
    {               
        assert(is_int($armyID) && $armyID>0);       
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $armyID);                     
    }

    private function _importAttributes($res)
    {
        assert(is_array($res)); // && count($res)>0
        
        foreach($res as $k=>$v)
        {
            $val = array(
                'VALUE' => $v['value'],
                'ID' => $v['id_unitcontainerhasattribute'], 
                'IS_MODIFYED' => false,
                'NEED_INSERT' => false
            ); 
            $this->attributes[$v['typename']][$v['attributename']] = $val;
        }
    }

    public function getAttribute($attributeTypeName, $attributeName)
    {
 
        assert(!empty($attributeName));
        assert(!empty($attributeTypeName));     
        
        //assert(array_key_exists($attributeTypeName, $this->attributes));

        $exists = @array_key_exists($attributeName, $this->attributes[$attributeTypeName]);
        if (!$exists) {
            if ($attributeName=='MOVE_SPEED') return 100;
            else if ($attributeName=='RESOURCE_FOR_STEALING') return 'ALL_RES';
            else assert(false);
        }
        return $this->attributes[$attributeTypeName][$attributeName]['VALUE'];
    }
    
    public function setAttribute($attributeTypeName, $attributeName, $value)
    {

        $this->_invariant();
        assert(!empty($attributeName));
        assert(!empty($attributeTypeName));     
        
        if(!$this->haveAttribute($attributeTypeName, $attributeName))
        {
            $this->createAttribute($attributeTypeName, $attributeName, $value);
        }else{
            $this->attributes[$attributeTypeName][$attributeName]['VALUE'] = $value;
            $this->attributes[$attributeTypeName][$attributeName]['IS_MODIFYED'] = true;
        }
    }
    
    public function createAttribute($attributeTypeName, $attributeName, $value)
    {
 
        assert(!empty($attributeName));
        assert(!empty($attributeTypeName));             
                
        $val = array(
                'VALUE' => $value,
                'ID' => DS_Army::GetNewIDForArmyAttribute(), 
                'IS_MODIFYED' => false,
                'NEED_INSERT' => true
        ); 
        
        $this->attributes[$attributeTypeName][$attributeName] = $val;
    }
    
    public function haveAttribute($attributeTypeName, $attributeName)
    {
 
        assert(!empty($attributeName));
        assert(!empty($attributeTypeName)); 
            
        if (array_key_exists($attributeTypeName, $this->attributes) && array_key_exists($attributeName, $this->attributes[$attributeTypeName]))
        {
            return true;
        }else{
            return false;
        }
    }

    public function deltaAttribute($attributeTypeName, $attributeName, $value)
    {       
 
        
        assert(!empty($attributeName));
        assert(!empty($attributeTypeName));
        assert(is_float($value));

        if(!$this->haveAttribute($attributeTypeName, $attributeName))
        {
            $this->createAttribute($attributeTypeName, $attributeName, $value);
        }else{
            $this->attributes[$attributeTypeName][$attributeName]['VALUE'] += $value;
            $this->attributes[$attributeTypeName][$attributeName]['IS_MODIFYED'] = true;
        }                   
    }   

    public function deleteAttributes()
    {
        return DS_Army::DeleteArmyAttributes($this->getID());
    }
    
    protected function _invariant($mode = 'public')
    {
 
        
        parent::_invariant($mode);
        assert(is_array($this->attributes));
    }
    
    public function commit()
    {
        $this->_invariant();        
        parent::commit();
        
        if(!DS_Army::GetByID($this->getID()))
        {
            return;
        }


        foreach($this->attributes as $k=>$v)
        {
            foreach ($v as $key => $value)
            {
                
                if($value['NEED_INSERT'])
                {
                    DS_Army::CreateArmyAttributes($k, $key, $this->attributes[$k][$key]['VALUE'], $this->getID(), $this->attributes[$k][$key]['ID']);
                    $this->attributes[$k]['IS_MODIFYED'] = false;
                    $this->attributes[$k]['NEED_INSERT'] = false;
                }else{
                    if($value['IS_MODIFYED'])
                    {   
                        DS_Army::UpdateArmyAttribute($this->attributes[$k][$key]['ID'], $this->attributes[$k][$key]['VALUE']);                  
                        $this->attributes[$k]['IS_MODIFYED'] = false;                   
                    }                       
                }               
            }

        }//END FOREACH      
    }
    
    public function delete()
    {
        parent::delete();
        
        $this->deleteAttributes();              
    }
    
    function __destruct()
    {
        $this->_invariant();
        parent::__destruct();       
    } 
}//END OF CLASS



?>