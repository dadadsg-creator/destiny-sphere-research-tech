<?php

class DE_UserBoughtResources extends DE_Base 
{
    private $userID;
    
    private $worldID;
    
    private $attributes;
    
    private $isModified = false;
    
    private $needCreation = false;
    
    public function __construct($ID)
    {
        $arr = explode('_', $ID);
        $this->userID = (int)$arr[0];
        assert($this->userID >= 0);
        $this->worldID = (int)$arr[1];
        assert($this->worldID > 0);
        
        $this->_getAttributes();
    }
    
    private function _getAttributes()
    {
        $data = DS_BillingBuyResource::getUserBuyResourceCount($this->userID, $this->worldID);
        assert(is_array($data));
            
        if(count($data) == 0)
        {       
            $this->needCreation = true;
            
            $attr = ST_Attributes::Create();
            
            foreach(GB_ResourcesList::$allResources as $k => $resource)
            {
                $this->attributes[$resource]['count'] = 0;
                $this->attributes[$resource]['id'] = $attr->getAttributeIDByTypeAndName('CURRENT_AMOUNT', $resource);
            }
            
            return;
        }
                
        foreach($data as $k => $res) 
        {
            $this->attributes[$res['attributename']]['count'] = (int)$res['count'];
            $this->attributes[$res['attributename']]['id'] = (int)$res['id_attribute'];
        }       
    }
    
    static public function CreateFromID($ID)
    {
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $ID);
    }
    
    public function get($attribute)
    {
        assert(key_exists($attribute, $this->attributes));
        return $this->attributes[$attribute]['count'];
    }
    
    public function getAttrID($attribute)
    {
        assert(key_exists($attribute, $this->attributes));
        return $this->attributes[$attribute]['id'];
    }
    
    public function delta($attribute, $delta)
    {
        assert(is_int($delta));
        assert(key_exists($attribute, $this->attributes));
        
        $this->isModified = true;
        
        if($delta < 0)
        {
            if($this->attributes[$attribute]['count'] + $delta < 0)
            {
                $this->attributes[$attribute]['count'] - 0;
                return;
            }
        }
        
        $this->attributes[$attribute]['count'] += $delta;
    }
    
    public function isModified()
    {
        return $this->isModified;
    }
    
    public function needCreation()
    {
        return $this->needCreation;
    }
    
    public function getUserId()
    {
        return $this->userID;
    }
    
    public function getWorldId()
    {
        return $this->worldID;
    }
    
    public function commit()
    {
        if($this->isModified())
        {
            DS_BillingBuyResource::commitUserBuyResource($this);
        }
    }
}

?>