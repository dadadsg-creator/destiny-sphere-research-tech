<?php $CVS_Version = "$Revision: 1.3 $ ; $Date: 2007/04/23 14:13:56 $";
class SU_DataBaseStatInputParams
{ 
        /** 
         * Enter description here... 
         * 
         * @var EntityInputParams 
         */ 
        static private $instance; 
         
        private $params = array(            
                //'dbID' => '',
                'action' => '',
                'tables' => 'all tables',  
                'dataBase' => '',  
                'ShowCountOfRows' => 'no',              
        ); 

        private function __construct() 
        { 
                $this->_getParams(); 
        } 
         
        static public function Singleton() 
        { 
                if(!(self::$instance instanceof SU_DataBaseStatInputParams)) 
                { 
                        self::$instance = new SU_DataBaseStatInputParams; 
                } 
                 
                return self::$instance; 
        } 
         
        public function get($paramName) 
        { 
                assert(key_exists($paramName, $this->params));                 
                 
                return $this->params[$paramName];                
        } 
         
        public function set($paramName, $value) 
        { 
                assert(key_exists($paramName, $this->params)); 
                 
                $this->params[$paramName] = $value; 
        } 
         
        private function _getParams() 
        { 
                
                if(key_exists('tables', $_POST)) 
                { 
                        $this->params['tables'] = $_POST['tables'];                     
                        assert(!empty($this->params['tables'])); 
                } 
                 
                if(key_exists('dataBase', $_POST))
                {
 					$this->params['dataBase'] = $_POST['dataBase'];                     
                    assert(!empty($this->params['dataBase']));                 	
                	
                }
                if(key_exists('ShowCountOfRows', $_POST)) 
                { 
                        $this->params['ShowCountOfRows'] = $_POST['ShowCountOfRows']; 
                        assert(!empty($this->params['ShowCountOfRows'])); 
                }   
                
                if(key_exists('action', $_POST)) 
                { 
                        $this->params['action'] = $_POST['action']; 
                        assert(!empty($this->params['action'])); 
                } 
        }
        public function exportParams() 
        { 
                return $this->params; 
        } 
                               
} 


?> 
