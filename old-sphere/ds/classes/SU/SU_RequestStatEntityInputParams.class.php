<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_RequestStatEntityInputParams 
{ 
        /** 
         * Enter description here... 
         * 
         * @var EntityInputParams 
         */ 
        static private $instance; 
         
        private $params = array(            
                'USER_NAME' => '', 
                'WORLD' => '', 
                'ACTION' => '',
                'DB_ID' => '',
                'YEAR' => '',
                'MONTH' => '',
                'DAY' => '',
                'YEAR2' => '',
                'MONTH2' => '',
                'DAY2' => '',
                
        ); 

        private function __construct() 
        { 
                $this->_getParams(); 
        } 
         
        static public function Singleton() 
        { 
                if(!(self::$instance instanceof SU_RequestStatEntityInputParams)) 
                { 
                        self::$instance = new SU_RequestStatEntityInputParams; 
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
                
                if(key_exists('USER_NAME', $_POST)) 
                { 
                        
                       $this->params['USER_NAME'] = $_POST['USER_NAME'];                     
                       assert(!empty($this->params['USER_NAME'])); 
                } 
                 
                if(key_exists('WORLD', $_POST)) 
                { 
                        $this->params['WORLD'] = $_POST['WORLD']; 
                        assert(!empty($this->params['WORLD'])); 
                } 
                if(key_exists('ACTION', $_POST)) 
                { 
                        $this->params['ACTION'] = $_POST['ACTION']; 
                        assert(!empty($this->params['ACTION'])); 
                } 
               if(key_exists('DB_ID', $_POST)) 
                { 
                        $this->params['DB_ID'] = $_POST['DB_ID']; 
                        assert(!empty($this->params['DB_ID'])); 
                } 
                 if(key_exists('YEAR', $_POST)) 
                { 
                        $this->params['YEAR'] = $_POST['YEAR']; 
                        assert(!empty($this->params['YEAR'])); 
                } 
                if(key_exists('MONTH', $_POST)) 
                { 
                        $this->params['MONTH'] = $_POST['MONTH']; 
                        assert(!empty($this->params['MONTH'])); 
                } 
                if(key_exists('DAY', $_POST)) 
                { 
                        $this->params['DAY'] = $_POST['DAY']; 
                        assert(!empty($this->params['DAY'])); 
                } 
                if(key_exists('YEAR2', $_POST)) 
                { 
                        $this->params['YEAR2'] = $_POST['YEAR2']; 
                        assert(!empty($this->params['YEAR2'])); 
                } 
                if(key_exists('MONTH2', $_POST)) 
                { 
                        $this->params['MONTH2'] = $_POST['MONTH2']; 
                        assert(!empty($this->params['MONTH2'])); 
                } 
                if(key_exists('DAY2', $_POST)) 
                { 
                        $this->params['DAY2'] = $_POST['DAY2']; 
                        assert(!empty($this->params['DAY2'])); 
                } 
                 
        }
        public function exportParams() 
        { 
                return $this->params; 
        } 
                               
} 


?> 
