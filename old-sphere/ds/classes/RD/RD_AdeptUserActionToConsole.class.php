<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2007/09/17 09:25:23 $';


class RD_AdeptUserActionToConsole
{       
    static private $UIID;
    
    static private $DBID;
    
    static public $xml = '';
    
    static private $currentCombID = 0;
    
    static public $commandLineFlag = false;
    
    static public $prepareTransfer = false;
    
    static private $common;
    
    static private $checkDate;
    
    static public function Exec()
    {
        $sapi = php_sapi_name();
        if(strstr($sapi, "apache") !== false)
        {
            return;         
        }
                
        // $command = constant('PHP_PATH')." -f useraction.php 0 1111 0 default 1";
        
        assert(key_exists('1', $_SERVER['argv']));
        self::$DBID = (int)$_SERVER['argv'][1];
        assert(self::$DBID >= 0);
        
        assert(key_exists(2, $_SERVER['argv']));
        self::$UIID = (int)$_SERVER['argv'][2];

        if(key_exists(3, $_SERVER['argv']))
        {
            self::$xml = $_SERVER['argv'][3];
            //assert(!empty(self::$xml)); 
            
            self::$xml = str_replace("#", '"', self::$xml);             
        }

        if(key_exists(4, $_SERVER['argv']))
        {
            self::$currentCombID = $_SERVER['argv'][4];
            
            if(self::$currentCombID == 'default')
            {
                self::$currentCombID = 0;
            }
            
            assert(self::$currentCombID >= 0);  
        }
        
        if(key_exists(5, $_SERVER['argv']))
        {
            if($_SERVER['argv'][5] == '1')
            {
                self::$prepareTransfer = true;
            }
        }
        
        if(key_exists(6, $_SERVER['argv']))
        {
            if(!empty($_SERVER['argv'][6]))
            {
                self::$checkDate = $_SERVER['argv'][6];
            }
        }
        
        self::$commandLineFlag = true;  

        self::_formSessionStart();  
        
        self::$common = DS_Common::CreateForCurentDB();
        
        self::_formSessionEnd();
        
        self::_formRequest();
    }
    
    static private function _formRequest()
    {
        
        if(empty(self::$xml))
        {
            self::$xml = "<empty/>";
        }

        $_POST['xmldata'] = self::$xml;
        $_REQUEST['xmldata'] = self::$xml;
        $_GET['xmldata'] = self::$xml;
    }
    
    static private function _formSessionStart()
    {
        @session_start();       
    
        $_SESSION['dbId'] = self::$DBID;
        $_SESSION['uiid'] = self::$UIID;
        $_SESSION['profi_game'] = 1;
        $_SESSION['windowKey'] = 666;
    }
    
    static private function _formSessionEnd()
    {
        $uiidData = DS_UserInstance::GetConstantPartByID(self::$UIID);
        $userData = DS_User::GetByID((int)$uiidData['id_user']);
        
        $_SESSION['world'] = (int)$uiidData['id_world'];
        $_SESSION['uid'] = (int)$uiidData['id_user'];
        $_SESSION['lang'] = $userData['id_language'];
        $_SESSION['race'] = $uiidData['id_race'];
        $additionalData = RD_RefreshDemonSearch::GetStartUserData(self::$common, $_SESSION['world'], $userData['username']);
        $_SESSION["ring"] = $additionalData['id_ring'];
        $_SESSION["combcomplex"] = $additionalData['id_combcomplex'];       
        $_SESSION["celltype"] = 1;
        $_SESSION["combnumber"] = $additionalData['combnumber'];
        $_SESSION["time_delta"] = $additionalData['time_delta'];    
        $_SESSION["LOGIN_ADM_ENTER"] = true;
        $_SESSION["USERNAME"] = $userData['username'];
        
        if(self::$currentCombID > 0)
        {
            $_SESSION["comb"] = self::$currentCombID;
        }else{
            $_SESSION["comb"] = $additionalData['id_comb'];
        }
    }
}

?>