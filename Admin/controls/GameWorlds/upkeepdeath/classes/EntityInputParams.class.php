<?php 

class EntityInputParams 
{ 
        /** 
         * Enter description here... 
         * 
         * @var EntityInputParams 
         */ 
        static private $instance; 
         
        private $params = array(            
                'USER_NAME' => '', 
                'WORLD_NAME' => '', 
                'ACTION' => '',
                'YEAR' => '',
                'MOUTH' => '',
                'DAY' => '',
                'YEAR2' => '',
                'MOUTH2' => '',
                'DAY2' => '',
                
        ); 

        private function __construct() 
        { 
                $this->_getParams(); 
        } 
         
        static public function Singleton() 
        { 
                if(!(self::$instance instanceof EntityInputParams)) 
                { 
                        self::$instance = new EntityInputParams; 
                        
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
                 
                if(key_exists('WORLD_NAME', $_POST)) 
                { 
                        $this->params['WORLD_NAME'] = $_POST['WORLD_NAME']; 
                        assert(!empty($this->params['WORLD_NAME'])); 
                } 
                if(key_exists('ACTION', $_POST)) 
                { 
                        $this->params['ACTION'] = $_POST['ACTION']; 
                        assert(!empty($this->params['ACTION'])); 
                } 
               if(key_exists('YEAR', $_POST)) 
                { 
                        $this->params['YEAR'] = $_POST['YEAR']; 
                        assert(!empty($this->params['YEAR'])); 
                } 
                if(key_exists('MOUTH', $_POST)) 
                { 
                        $this->params['MOUTH'] = $_POST['MOUTH']; 
                        assert(!empty($this->params['MOUTH'])); 
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
                if(key_exists('MOUTH2', $_POST)) 
                { 
                        $this->params['MOUTH2'] = $_POST['MOUTH2']; 
                        assert(!empty($this->params['MOUTH2'])); 
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
