<?php
    
require_once $_SERVER['DOCUMENT_ROOT'].'/sys/lib/AdminMenuDB.php';

class AdminMenu
{
    var $db = '';
    
    var $menu = array();
    
    var $menu1Level = array();
    
    var $menu2Level = array();
    
    var $menu3Level = array();
    
    var $menuFlat = array();
    
    var $userManager = '';
    
    var $basePath = '/Admin/controls/';
    var $baseUrl = '';
    
    function AdminMenu($userManager)
    {           
        assert (is_a($userManager, 'UserManager'));
        
        $this->db = new AdminMenuDB($userManager);
                            
        //$this->baseUrl = 'http://'.$_SERVER['SERVER_NAME'].$this->basePath;
        
        $this->baseUrl = $this->basePath;
        
        $this->userManager = $userManager;
        
        $this->initMenu();
    }
    
    
    function initMenu()
    {       
        $rs = $this->db->get1LavelMenu();
        
        foreach($rs as $k=>$v)
        {           
            $this->menu1Level[$v['ID']]['Title'] = $v['Title'];
            $this->menu1Level[$v['ID']]['Url'] = $this->baseUrl.$this->url($v['Url']);
            $this->menu1Level[$v['ID']]['ID'] = $v['ID'];
            $this->menu1Level[$v['ID']]['Parent_ID'] = $v['Parent_ID'];
            
            $this->menuFlat[$this->baseUrl.$this->url($v['Url'])]['Parent_ID'] = $v['Parent_ID'];
            $this->menuFlat[$this->baseUrl.$this->url($v['Url'])]['ID'] = $v['ID'];
            $this->menuFlat[$this->baseUrl.$this->url($v['Url'])]['Lavel'] = 1;
            $this->menuFlat[$this->baseUrl.$this->url($v['Url'])]['Title'] = $v['Title'];
            
            $this->menu[$v['ID']]['Title'] = $v['Title'];
            $this->menu[$v['ID']]['Url'] = $this->basePath.$this->url($v['Url']);
            $this->menu[$v['ID']]['ID'] = $v['ID'];
            $this->menu[$v['ID']]['Parent_ID'] = $v['Parent_ID'];
            $this->menu[$v['ID']]['Childe'] = $this->get2Level($v['ID'], $this->baseUrl.$this->url($v['Url']));                     
        }
    }
    
    function get2Level($id, $url)
    {           
        $rs = $this->db->get2LavelMenu($id);
        if(!$rs) return array();
        
        foreach($rs as $k=>$v)
        {       
            $this->menu2Level[$v['ID']]['Title'] = $v['Title'];
            $this->menu2Level[$v['ID']]['Url'] = $url.$this->url($v['Url']);
            $this->menu2Level[$v['ID']]['ID'] = $v['ID'];
            $this->menu2Level[$v['ID']]['Parent_ID'] = $v['Parent_ID'];
            
            $this->menuFlat[$url.$this->url($v['Url'])]['Parent_ID'] = $v['Parent_ID'];
            $this->menuFlat[$url.$this->url($v['Url'])]['ID'] = $v['ID'];
            $this->menuFlat[$url.$this->url($v['Url'])]['Lavel'] = 2;
            $this->menuFlat[$url.$this->url($v['Url'])]['Title'] = $v['Title'];
                
            $out[$v['ID']]['Title'] = $v['Title'];
            $out[$v['ID']]['Url'] = $url.$this->url($v['Url']);
            $out[$v['ID']]['ID'] = $v['ID'];
            $out[$v['ID']]['Parent_ID'] = $v['Parent_ID'];
            $out[$v['ID']]['Childe'] = $this->get3Level($v['ID'],$url.$v['Url']);                       
        }
        return $out;
    }   

    function get3Level($id, $url)
    {
        $url = str_replace('//', '/', $url);
        $rs = $this->db->get3LavelMenu($id);
        if(!$rs) return array();
        
        foreach($rs as $k=>$v)
        {           
            $this->menu3Level[$v['ID']]['Title'] = $v['Title'];
            $this->menu3Level[$v['ID']]['Url'] = $url.$this->url($v['Url']);
            $this->menu3Level[$v['ID']]['ID'] = $v['ID'];
            $this->menu3Level[$v['ID']]['Parent_ID'] = $v['Parent_ID'];
            
            $this->menuFlat[$url.$this->url($v['Url'])]['Parent_ID'] = $v['Parent_ID'];
            $this->menuFlat[$url.$this->url($v['Url'])]['ID'] = $v['ID'];
            $this->menuFlat[$url.$this->url($v['Url'])]['Lavel'] = 3;   
            $this->menuFlat[$url.$this->url($v['Url'])]['Title'] = $v['Title'];
            
            $out[$v['ID']]['Title'] = $v['Title'];
            $out[$v['ID']]['Url'] = $url.$this->url($v['Url']);
            $out[$v['ID']]['ID'] = $v['ID'];
            $out[$v['ID']]['Parent_ID'] = $v['Parent_ID'];      
                        
        }
        return $out;
    }   
    
    function url($url)
    {
        if($url[0] == '/')
        {
            $out = substr($url, 1);
        }else{
            $out = $url;
        }
        
        if($url[strlen($url)-1] != '/')
        {
            $out = $out.'/';
        }
        
        return $out;
    }
    
    function curentLevel1Active()
    {
        $path = $this->getCurentPath();
        $punkt = $this->menuFlat[$path];                
        if($punkt['Lavel'] == 2)
        {
            return $punkt['Parent_ID'];
        }elseif($punkt['Lavel'] == 3){
            return $this->menu2Level[$punkt['Parent_ID']]['Parent_ID'];
        }
    }
    
    function curentLevel2Active()
    {
        $path = $this->getCurentPath();
        $punkt = $this->menuFlat[$path];                
        if($punkt['Lavel'] == 2)
        {
            return $punkt['ID'];
        }elseif($punkt['Lavel'] == 3){
            return $punkt['Parent_ID'];
        }
    }
    
    function curentLevel3Active()
    {
        $path = $this->getCurentPath();
        $punkt = $this->menuFlat[$path];                        
        return $punkt['ID'];        
    }
    
    function curentActive()
    {
        $path = $this->getCurentPath();
        $path = str_replace("\/\/","\/",$path);
        $punkt = $this->menuFlat[$path];
        return $punkt['ID'];
    }
    
    function chekMenu()
    {
        $path = $this->getCurentPath();
        return array_key_exists($path, $this->menuFlat);
    }
    
    function have3Lavel ($level1ID, $level2ID)
    {
        if(is_array($this->menu[$level1ID]['Childe'][$level2ID]['Childe']))
        {
            return true;
        }else{
            return false;
        }
    }
    
    function getCurentMenuFullName()
    {
        $id = $this->curentActive();
        $name = $this->db->getFullNameByID($id);

        if($name[0]['l1']){
            $out[] = $name[0]['l1'];
        }
        if($name[0]['l2']){
            $out[] = $name[0]['l2'];        
        }
        if($name[0]['l3']){
            $out[] = $name[0]['l3'];        
        }
        return implode (' / ', $out);       
    }
    
    function getFirstChilde()
    {
        $punktID = $this->curentActive(); 

        if(array_key_exists($punktID, $this->menu1Level))
        {
            $lavel = 1;
        }elseif(array_key_exists($punktID, $this->menu2Level)){
            $lavel = 2;
        }else{
            $lavel = 0;
        }
        
        if($lavel == 1)
        {
            $childe = $this->menu[$punktID]['Childe'];  
        }elseif($lavel == 2){
            $childe = $this->menu[$this->curentLevel1Active()]['Childe'][$punktID]['Childe'];
        }elseif($lavel == 0){
            $childe = $this->menu;
        }else{
            trigger_error('Uncorrect $lavel value', E_USER_ERROR);
        }
        foreach($childe as $k=>$v)
        {
            $a = $v['Url'];
            return $a;
        }
    }
    
    function getCurentPath()
    {
        //return 'http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF']).'/';
        
        return dirname($_SERVER['PHP_SELF']).'/';
    }
}




?>
