<?php
/** Описание этого класса внесено в этот файл для уменьшения колва обращенией к сетевлй файловой сиситеме*/
//require_once $_SERVER['DOCUMENT_ROOT']."/sys/lib/Cache.class.php";

class PageCache 
{   
    public $pageID = 0;
    public $pageName = '';
    public $cacheLifeTime = 0; 
    public $cache = '';    
    public $mark = '';
    public $needCache = false; 
    
    function PageCache($defaultPageID)
    {       
        if ( empty($defaultPageID)) {
            die("Error Set default page!");
        }
        
        (int) $p;
        if($_REQUEST['p']){
            $p = $_REQUEST['p'];
            if(is_integer($p)){
                $this->pageID = $p;
            }else{
                $this->pageName = $p;
            }
        }else{
            $this->pageID = $defaultPageID;
        }
        
        $this->_getPageInfo();
        
        $this->cache = new Cache ('PAGE', constant('CACHE_STORE_PATH'));        
        
        $this->_formMark();
        
        $this->Collector();     
    }
    
    function get()
    {
        if($this->needCache){
            return $this->cache->get($this->mark);
        }else{
            return '';
        }
        
    }
    
    function set($data)
    {
        if($this->needCache){
            $this->cache->set($this->mark, $this->cacheLifeTime, $data);
        }
    }
    
    function _getPageInfo()
    {
        $file = file(constant('CACHE_PAGES_INFO_FILE'));

        if(is_array($file))
        {
            foreach($file as $k=>$v)
            {
                $page = explode(';', $v);
                if(($this->pageID == $page[0]) || ($this->pageName == $page[1])){                   
                    $this->pageID = $page[0];
                    $this->pageName = $page[1];
                    $this->cacheLifeTime = $page[2];
                    $this->needCache = true;                    
                    break;
                }               
            }
        }
    }
    
    function _formMark()
    {
        $forbidParams[] = 'p';
        $forbidParams[] = 'nfs';
        $forbidParams[] = get_cfg_var ('session.name');
        
        
        $mark = '';
        
        $postMark = '';
        if(count($_POST) > 0){
            foreach($_POST as $k=>$v)
            {
                if(in_array($k,$forbidParams)){
                    continue;
                }
                $post[] = $k."=".$v;
            }           
            if(is_array($post)){
                $postMark = implode('&', $post);
            }else{
                $postMark = '';
            }       
        }
        
        $getMark = '';

        if(count($_GET) > 0){
            foreach($_GET as $k=>$v)
            {
                if(in_array($k,$forbidParams)){
                    continue;
                }
                $get[] = $k."=".$v;
            }   
            if(is_array($get)){
                $getMark = implode('&', $get);
            }else{
                $getMark = '';
            }       
            
        }

        $mark = $postMark."&".$getMark;
        
        if($mark == '&'){
            $this->mark = $this->pageName;
        }else{
            $this->mark = $this->pageName.'_'.md5($mark);
        }
        $this->mark .= constant('DBName');
    }
    
    function Collector (){  

        $file = file(constant('CACHE_PAGES_INFO_FILE'));

        if(is_array($file))
        {
            foreach($file as $k=>$v)
            {
                $page = explode(';', $v);
                $arr[$this->cache->objectType."_".$page[0]] = intval($page[2]);
                $arr[$this->cache->objectType."_".$page[1]] = intval($page[2]);
                if (intval($page[2]) > $this->maxlifetime ) {
                    $this->maxlifetime = intval($page[2]);
                }
            }
        }

        list($usec, $sec) = explode(' ', microtime());
        $gen = (float) $sec + ((float) $usec * 100000);
        srand($gen);
        $randval = rand ( 0, 20 );
        
        if( $randval != 5 ){
            return true;
        }

        if ($handle = opendir(constant('CACHE_STORE_PATH'))) {
            while (false !== ($file = readdir($handle))) { 
                if ( $file == '.'  || $file == '..'){
                    continue;
                }
                
                if ( !file_exists ( constant('CACHE_STORE_PATH').'/'.$file ) ) {
                    continue;
                }
                
                if (
                 ( ( @filemtime ( constant('CACHE_STORE_PATH').'/'.$file ) + $this->cache->timeDecriment + $arr[$file] ) <  time () ) || 
                 ( ( @filemtime ( constant('CACHE_STORE_PATH').'/'.$file ) + $this->cache->timeDecriment + $this->maxlifetime ) < time () )
                ) {
                    @unlink ( constant('CACHE_STORE_PATH').'/'.$file );
                }
                
                
            }
    
            closedir($handle); 
        }           
                
    }//END OF METHOD Collector ()



}

class Cache 
{
    var $cacheStorePath = '';
    
    var $objectType = '';
    
    var $timeDecriment = 1000000;
    
    function Cache ($objectType, $cacheStorePath)
    {
        assert(!empty($objectType));
        $this->objectType = $objectType;
        
        assert(!empty($cacheStorePath));
        $this->cacheStorePath = $cacheStorePath;
    }
    
    function set($mark, $lifeTime, $data)
    {
        assert(!empty($lifeTime));
        assert(!empty($mark));
        
        if(file_exists($this->_formFileName($mark)))
        {
            unlink($this->_formFileName($mark));
        }
        
        if (!$handle = fopen($this->_formFileName($mark), 'a')) 
        {
             trigger_error("Cannot create file for cache store", E_USER_ERROR);
        }
        flock($handle,2);
        if (fwrite($handle, $data) === FALSE) 
        {
            trigger_error("Cannot store data in file for cache store", E_USER_ERROR);           
        }
        flock($handle,3);
        fclose($handle);
            
        $setTime = time() + $lifeTime - $this->timeDecriment;
        touch($this->_formFileName($mark), $setTime);       
    }
    
    function get($mark){
        
        if(!file_exists($this->_formFileName($mark)))
        {
            return '';
        }
        
        if(filemtime($this->_formFileName($mark)) < (time() - $this->timeDecriment))
        {
            unlink($this->_formFileName($mark));
            return '';
        }else{
            //return implode("", file($this->_formFileName($mark)));
            /*По сравнению с предыдущей строкой, скорость работы с фалами возрасла в 2 раза 0.028sec*/
            return file_get_contents($this->_formFileName($mark));
        }
    }
    
    function reset()
    {
    }
    
    function _formFileName($mark)
    {
        return  $this->cacheStorePath.'/'.$this->objectType.'_'.$mark;  
    }
}

?>