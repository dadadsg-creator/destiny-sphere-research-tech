<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Предок всех простейших типов данных в сиситеме 
 */

class GB_DataType extends BS_Base
{   
    static protected $className;
    
    protected $valueStored;
    
    protected $mode = 'BASE'; 
    
    protected static $allowedModes = array('BASE', 'MASTER', 'CONSTANT');
    
    protected $master;
    
    const externalValueName = 'value';
    
    public function __construct($value, $mode = 'UNDEFINED')
    {   
        GB_TypeFactory::TestFabricCreation();
        
        parent::__construct();              
                            
        $this->__set('value', $value);      
        
        assert(!empty($mode) && in_array($mode, self::$allowedModes));              
        $this->mode = $mode;
        
        $this->_setMaster();        
    }       
    
    function __get($name)
    {
        assert($name === GB_DataType::externalValueName);
        $this->_validateValue($this->valueStored);
        return $this->valueStored;
    }
    
    function __set($name, $value)
    {
        assert($this->_checkUpdatePermission());
        assert($name === GB_DataType::externalValueName);
        
        $this->_validateValue($value);
        $this->valueStored = $value;
    }
    
    protected function _validateValue(&$value)
    {
        assert(!is_null($value));
    }       
    
    protected function _checkUpdatePermission()
    {
        switch($this->mode)
        {
            case 'CONSTANT':
                return false;
                break;
            case 'MASTER':          
                if($this->_testMaster())
                {
                    return true;
                }else{
                    return false;
                }
                break;
            case 'BASE':
                return true;
                break;
        };
                
    }
    
    protected function _setMaster()
    {
        //Времено закрыта работа метода так как есть подозрения что
        //использование функции debug_backtrace() сильно перегружает сервер
        //и вызывает ошики вызванные обрщением к несуществующей памяте.
        
        /*$debug = debug_backtrace();       
        
        if(key_exists(3, $debug))
        {
            $this->master = $debug[3]['class'];
        }else{
            $this->master = '';
        }*/     
        
        $this->master = '';
    }
    
    protected function _testMaster()
    {
        //Времено закрыта работа метода так как есть подозрения что
        //использование функции debug_backtrace() сильно перегружает сервер
        //и вызывает ошики вызванные обрщением к несуществующей памяте.
        
        /*$debug = debug_backtrace();       
        return  $this->master === @$debug[4]['class'];*/
        
        return true;
    }
    
}

?>