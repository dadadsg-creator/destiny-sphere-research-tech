<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_EntityOperationLogRecord
{
    /**
     * Enter description here...
     *
     * @var DE_MoneyActionType
     */
    private $actionType;
    
    /**
     * Имя таблицы куда логируем базовая
     *
     * @var string
     */
    private $tableNameFirst = 'ds_money_list';
    
    /**
     * Имя таблицы куда логируем вторричная
     *
     * @var string
     */
    private $tableNameSecond;
    
    private $fieldsFirst = array(
        'id_list' => null,
        'id_user' => null,
        'world_identifier' => null,
        'gc_amount' => null,
        'sc_amount' => null,
        'id_action' => null,
        'id_account' => null
    );
    
    private $fieldsSecond = array(
        'id_list' => null,      
    );
    
    
    /**
     * ID базы куда логируем
     *
     * @var integer
     */
    private $DBID;
    
    public function __construct(DE_MoneyActionType $actionType, $DBID)
    {
        // assert(is_integer($DBID) && $DBID >= 0);
        
        $this->actionType = $actionType;
        
        $this->tableNameSecond = $actionType->getLogActionTable();
        
        $this->DBID = $DBID;
    }
    
    public function setListID($id)
    {
        assert(is_int($id) && $id>0);
        
        $this->fieldsFirst['id_list'] = $id;
        $this->fieldsSecond['id_list'] = $id;
    }
    
    public function setField($name, $value)
    {
        assert(!empty($name) && is_string($name));
        assert(!is_null($value));
        assert($name != 'id_list');
        
        if(key_exists($name, $this->fieldsFirst))
        {
            $this->fieldsFirst[$name] = $value; 
        }else{
            $this->fieldsSecond[$name] = $value;    
        }
        
    }
    
    public function getSQLFirst()
    {                       
        return $this->_formSQL($this->fieldsFirst, $this->tableNameFirst);
    }
    
    public function getSQLSecond()
    {                       
        return $this->_formSQL($this->fieldsSecond, $this->tableNameSecond);
    }
    
    
    private function _formSQL($fieldsData, $tableName)
    {
        assert(is_array($fieldsData) && count($fieldsData) > 0);
        assert(is_string($tableName) && !empty($tableName));
        
        foreach($fieldsData as $fieldName =>$value)
        {
            assert(!is_null($value));
        }   
        
        $values = "'".implode("', '", $fieldsData)."'";
        $fields = implode(", ", array_keys($fieldsData));
        
        $sql = "INSERT INTO ".$tableName."( $fields ) VALUES ( $values )";
                
        return $sql;        
    }
    
    public function getDBID()
    {
        return $this->DBID;
    }
}

?>