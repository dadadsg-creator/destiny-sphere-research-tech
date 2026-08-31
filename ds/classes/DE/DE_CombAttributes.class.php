<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Атрибуты соты
 *
 */
class DE_CombAttributes extends DE_Base
{
    private $attributes = array();
    
    private $dataSecondary = array();
    
    private $startValues = array();
    
    private $maxAttributeSize = 999999999999;
    
    static private $controlErrorsMode = false;
    
    function __construct($combID)
    {
        GB_SingletonObjectStorage::TestSingletonCreation();
        assert(is_int($combID) && $combID>0);
        parent::__construct($combID);

        $this->_importAttributes();

        $this->_validateAttributes();

        $this->_invariant('construct');
    }

    static public function CreateFromCombID($combID)
    {
        assert(is_int($combID) && $combID>0);
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $combID);
    }

    private function _validateAttributes()
    {       
        $attributeNames = $this->getAttributeNamesByTypeName('MAX_AMOUNT');
        foreach($attributeNames as $key => $attributeName)
        {
            //Не должно быть отрецательным
            if($this->getAttribute('MAX_AMOUNT', $attributeName) < 0)
            {               
                    $this->setAttribute('MAX_AMOUNT', $attributeName, (float)0);            
            }
        }

        //Проверяем корректность текущих значений
        $attributeNames = $this->getAttributeNamesByTypeName('CURRENT_AMOUNT');
        foreach($attributeNames as $key => $attributeName)
        {
            $startValue = $this->getAttribute('CURRENT_AMOUNT', $attributeName);
            $tmpValue = $startValue;
                    
            //Недолжны быть дробными
            if($tmpValue != floor($tmpValue))
            {
                $tmpValue = floor($tmpValue);   
            }           
            
            if($tmpValue < 0)
            {
                $tmpValue = 0;
            }
            
            //Недолжны быть больше максимальнго значения            
            if (    
                    $attributeName != 'SIMILTANEOUS_BUILDS' && 
                    $this->haveAttribute('MAX_AMOUNT', $attributeName) && 
                    $tmpValue > $this->getAttribute('MAX_AMOUNT', $attributeName)
                )
            {
                $tmpValue = $this->getAttribute('MAX_AMOUNT', $attributeName);
            }           
                
            if($startValue != $tmpValue)        
            {
                $this->setAttribute('CURRENT_AMOUNT', $attributeName, (float)$tmpValue);    
            }
        }

        //Првоеряем продукцию за турн
        $attributeNames = $this->getAttributeNamesByTypeName('TURN_PRODUCTION');
        foreach($attributeNames as $key => $attributeName)
        {
            //Не должно быть отрецательным
            if($this->getAttribute('TURN_PRODUCTION', $attributeName) < 0)
            {               
                $this->setAttribute('TURN_PRODUCTION', $attributeName, (float)0);           
            }
        }       
    }

    private function _formValidateErrorText($attributeTypeName, $attributeName, $msg = '')
    {
        $comb = DE_Comb::CreateFromID($this->getID());
        $user = $comb->getUserInstance()->getUser();
        return $user->getName()." => ".$comb->getName()." => ".$attributeTypeName.' => '.$attributeName." = ".$this->getAttribute($attributeTypeName, $attributeName)." ".$msg;
    }

    public function haveAttribute($attributeTypeName, $attributeName)
    {
        $this->_invariant();

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

    public function getAttribute($attributeTypeName, $attributeName, $noObjectReturn = false)
    {
        $this->_invariant();
        #assert(key_exists($attributeTypeName, $this->attributes));
        #assert(key_exists($attributeName, $this->attributes[$attributeTypeName]));

        if (key_exists($attributeTypeName, $this->attributes) && key_exists($attributeName, $this->attributes[$attributeTypeName]))
            return $this->attributes[$attributeTypeName][$attributeName]['VALUE'];
        else
            return 0;
    }

    public function getAttributeNameByAttributeID($attributeID)
    {       
        assert(key_exists($attributeID, $this->dataSecondary));

        return $this->dataSecondary[$attributeID]['attributeName'];
    }

    public function getAttributeTypeNameByAttributeID($attributeID)
    {       
        assert(key_exists($attributeID, $this->dataSecondary));

        return $this->dataSecondary[$attributeID]['attributeTypeName'];
    }

    public function getAttributeID($attributeTypeName, $attributeName)
    {
        $this->_invariant();
        assert(key_exists($attributeTypeName, $this->attributes));
        assert(key_exists($attributeName, $this->attributes[$attributeTypeName]));

        return $this->attributes[$attributeTypeName][$attributeName]['ID'];
    }

    public function getStasisAttribute ($attributeName, $attributeTypeName)
    {
        $this->_invariant();

        assert(key_exists($attributeTypeName, $this->attributes));
        assert(key_exists($attributeName, $this->attributes[$attributeTypeName]));

        return  $this->attributes[$attributeTypeName][$attributeName]['STASIS'];
    }

    public function convertStasisToAttribute($attributeName, $attributeTypeName, $value)
    {
        $this->_invariant();

        assert(key_exists($attributeTypeName, $this->attributes));
        assert(key_exists($attributeName, $this->attributes[$attributeTypeName]));
        assert(is_float($value));

        $exchangerate = $this->attributes[$attributeTypeName][$attributeName]['EXCHANGERATE'];

        return (float)($value / $exchangerate);
    }
    
    public function exportFormatedAttributes($onlyModifaed = false)
    {
        $this->_invariant();
        
        $out = '';
        foreach($this->attributes as $attributeTypeName => $attributes)
        {
            foreach($attributes as $attributeName => $value)
            {
                if($onlyModifaed && !$value['IS_MODIFYED'])
                {
                    continue;
                }
                
                $out .= $attributeTypeName." => ".$attributeName." = ".$value['VALUE']." (".implode(", ", $this->startValues[$attributeTypeName][$attributeName]).")\n";
            }           
        }
        
        return $out;
    }
    
    public function setAttribute($attributeTypeName, $attributeName, $value)
    {
        $this->_invariant();

        assert(key_exists($attributeTypeName, $this->attributes));
        assert(key_exists($attributeName, $this->attributes[$attributeTypeName]));
        assert(is_float($value));
        
        $value = $this->_validateSetAttribute($attributeTypeName, $attributeName, $value);
        
        $exchangerate = $this->attributes[$attributeTypeName][$attributeName]['EXCHANGERATE'];
        $stasis = $value * $exchangerate;

        $this->attributes[$attributeTypeName][$attributeName]['VALUE'] = $value;
        $this->attributes[$attributeTypeName][$attributeName]['STASIS'] = $stasis;
        $this->attributes[$attributeTypeName][$attributeName]['IS_MODIFYED'] = true;

/*if($attributeTypeName == 'CURRENT_AMOUNT' && $attributeName == 'STASIS_ENERGY')
{
    $str = "SET ".DE_Comb::CreateFromID($this->getID())->getFullName()." => $attributeTypeName => $attributeName = $value";
    SU_DebugTools::LogUser('XxForrestxX', $str, __CLASS__); 
}*/

        if($this->attributes[$attributeTypeName][$attributeName]['VALUE'] > $this->maxAttributeSize)
        {
            $this->attributes[$attributeTypeName][$attributeName]['VALUE'] = $this->maxAttributeSize;
        }
        
        $this->startValues [$attributeTypeName][$attributeName][] = "S: ".$value;
        
        $this->isModifyed = true;
    }
    
    private function _validateSetAttribute($attributeTypeName, $attributeName, $value)
    {
        assert(is_string($attributeTypeName) && !empty($attributeTypeName));
        assert(is_string($attributeName) && !empty($attributeName));
        assert(is_float($value));
        
        if($attributeTypeName != 'CURRENT_AMOUNT')
        {
            return $value;
        }
        
        if($value < 0 && $value > -1)
        {
            $value = (float)0;
        }
        
        return $value;
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

    public function deltaAttribute($attributeTypeName, $attributeName, $delta = '')
    {
        $this->_invariant();
        
        //Это надо так как сболлшим колвом знаков после запятой пхп корячит
        $delta = round($delta, 5);

        assert(is_float($delta));
        assert(key_exists($attributeTypeName, $this->attributes));

        assert(key_exists($attributeName, $this->attributes[$attributeTypeName]));


        $exchangerate = $this->attributes[$attributeTypeName][$attributeName]['EXCHANGERATE'];
        $stasisDelta = $delta * $exchangerate;

        $this->attributes[$attributeTypeName][$attributeName]['VALUE'] += $delta;
        
        $this->attributes[$attributeTypeName][$attributeName]['VALUE'] = 
        $this->_validateSetAttribute($attributeTypeName, $attributeName, $this->attributes[$attributeTypeName][$attributeName]['VALUE']);
        
        $this->attributes[$attributeTypeName][$attributeName]['STASIS'] += $stasisDelta;
        $this->attributes[$attributeTypeName][$attributeName]['IS_MODIFYED'] = true;

        if($this->attributes[$attributeTypeName][$attributeName]['VALUE'] > $this->maxAttributeSize)
        {
            $this->attributes[$attributeTypeName][$attributeName]['VALUE'] = $this->maxAttributeSize;
        }

        $this->startValues [$attributeTypeName][$attributeName][] = "D: ".$delta;
        
        $this->isModifyed = true;
    }


    private function _importAttributes()
    {               
        $res = DS_Comb::GetCombAtrributes($this->getID());
        assert(is_array($res) && count($res)>0);
        
        foreach($res as $k=>$v)
        {
            $attributeID = (int)$v['id_attribute'];
            assert($attributeID > 0);
            
            $combHasResourceID = (int)$v['id_combhasresource'];
            assert($combHasResourceID > 0);
            
            $value = (float)$v['value'];
            if($value > $this->maxAttributeSize)
            {
                $value = $this->maxAttributeSize;
            }
            
            $attributeName = ST_Attributes::Create()->getAttributeNameByAttributeID($attributeID);                  
            
            $typeName = ST_Attributes::Create()->getAttributeTypeNameByAttributeID($attributeID);       

            if(in_array($attributeName, GB_ResourcesList::$allResources))
            {
                $exchangeRate = (float)DE_Resource::CreateFromName($attributeName)->getExchangeRate();
            }else{
                $exchangeRate = (float)0;
            }
            
            $array = array
            (
                'VALUE' => $value,
                'EXCHANGERATE' => $exchangeRate,
                'STASIS' => (float)($value * $exchangeRate),
                'ID' => $combHasResourceID,
                'IS_MODIFYED' => false
            );
            
            $this->attributes[$typeName][$attributeName] = $array;
            
            $this->startValues [$typeName][$attributeName] = array($value);
            
            $this->dataSecondary[$combHasResourceID] = array("attributeTypeName"=>$typeName, "attributeName"=>$attributeName);
        }   
    }

    protected function _invariant($mode = 'public')
    {
        if(BS_Base::_exitInvariant($mode)) return;

        parent::_invariant($mode);

        assert(is_array($this->attributes) && count($this->attributes)>0);
    }

    public function commit()
    {
        $this->_invariant();
        parent::commit();

        $this->_validateAttributes();

        if($this->isModifyed)
        {
            DS_Comb::UpdateCombAttribute($this->attributes);
            $this->isModifyed = false;
        }
    }

}//END OF CLASS

?>