<?php $CVS_Version = '$Revision$ ; $Date$';


/**
 * Класс при создание извлекает из БД все GameModifaers для указанного мира.
 */

class DE_WorldSettings  extends BS_Base {
    
    /** ID мира */
    private $worldId = NULL;
    
    /** Массив с параметрами мира */
    private $settings = array ();   
    
    /** ОБъект для работ с БД  */
    private $common;
    
    static private $instances = array();
    
    /**
    * @return WorldSettings
    * @param $common - Объект для раоты с БД.
    * @param $worldId - ID мира для которого делаем
    * @desc Конструтор класса
    */
    function __construct($worldId, $data = null)
    {
        parent::__construct();
                
        $this->common = DS_Common::CreateForCurentDB();
        
        settype ( $worldId, 'integer' );
        assert ( $worldId > 0 );
        
        $this->worldId = $worldId;
        
        $this->_getSettings($data);
        
    }//END OF METHOD
    
    static public function CreateForCurentWorld($data = null)
    {
        assert(is_array($data) || is_null($data));
        $worldID = (int)$_SESSION['world'];
        assert($worldID>0);
        $dbID = (int)$_SESSION['dbId'];
        assert($dbID>=0);
        
        if(!key_exists($dbID.'_'.$worldID, self::$instances))
        {
            return self::$instances[$dbID.'_'.$worldID] = new DE_WorldSettings($worldID);
        }
        
        return self::$instances[$dbID.'_'.$worldID];
    }
    
    function get($name)
    {           
        if (key_exists($name, $this->settings))
            return $this->settings[$name];
        else
            return 0;
    }
    
    /********************* PRIVATE *********************/
    
    function _getSettings($data)
    {       
        if(is_array($data))
        {
            $this->settings = $data;
        } else {
            $sql = "SELECT *
                    FROM ds_gamemodifier";
                    // WHERE id_world = ".$this->worldId."";
            $res = $this->common->getResultArray ( "GAME_MODIFAERS_".$this->worldId, $sql, "", "", "GLOBAL_CACHE");     

            assert(empty($this->common->errormsg));
            
            foreach($res as $k=>$v) 
            {           
                $this->settings[$v['modifiername']] = $v['value'];
            }
        
        }
    }//END OF METHOD
}//END OF CLASS
 
?>