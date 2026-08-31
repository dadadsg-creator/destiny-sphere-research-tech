<?php $CVS_Version = '$Revision$ ; $Date$';
class SI_ExecMassMove 
{   
    private $fileContent;
    private $userName;
    private $uid;
    private $fromDBID;
    private $toDBID;
    private $fromWorldName;
    private $toWorldName;
    private $coordinateMode;
    private $fromUIID;
    private $toWorldID;
    private $fromWorldID;
    private $callBackScript = '';
    private $combNewCoordinates = array();
    
    private $moveCounter = 0;
    
    private $downTimeIterval = array('start' => '330', 'end' => '700');
    
    private $tempColony = 0;
    
    public function __construct($fileName)          
    {       
        assert(file_exists($fileName));
        
        $this->fileContent = file($fileName);               
    }
    
    private function _out($msg, $log = 1, $error = 0)
    {
        echo $msg."\n";
        flush();
        
        if($log)
        {
            SU_DebugTools::WriteToLog($msg."\n", 'MassMove.log');
        }
        
        if($error)
        {
            SU_DebugTools::WriteToLog($msg."\n", 'MassMoveErrors.log');
        }
    }
    
    public function move()
    {   
        SI_Base::SetCommon(DS_Common::CreateForCurentDB());     
        
        self::_out("START MASS MOVE OF INSTANCES");         

        self::_out("Select ".count($this->fileContent)." rows for work.\n");    
                    
        foreach($this->fileContent as $k=>$v)
        {           
            if(date('Hi') > $this->downTimeIterval['start'] && date('Hi') < $this->downTimeIterval['end'])
            {
                self::_out("It's time to sleep..\n\n");
                break;
            }
            
            try {       
                $this->_moveInMass(str_replace("\"", '', $v));
            }catch (Exception $e) {
                self::_out('ERROR: '.$e->getMessage()."!", 1, 1);
            }
        }
        
        self::_out("\nMASS MOVE COMPLIT!\n\n");     
    }
    
    private function _moveInMass($string)
    {
        assert(!empty($string));
        
        $this->_getParams($string);             
        
        self::_out("-------------------- (".$this->moveCounter.") ".SU_DebugTools::GetInternalDate()." --------------------");      
        self::_out("USER: ".$this->userName." DIRECTION: ".$this->fromWorldName." => ".$this->toWorldName." MODE: ".$this->coordinateMode." TEMPCOLONY: ".$this->tempColony);       

        $this->_formMoveParams();   

        $res = $this->_callMoveScript();
                
        $this->_callBackScript($res);
        
        $this->moveCounter ++;
    }   
    
    private function _callMoveScript()  
    {
        $params = $this->fromDBID." ".$this->fromUIID." ".$this->toDBID." ".$this->toWorldID." ".$this->tempColony." ";
        
        foreach($this->combNewCoordinates as $combName => $combData)
        {
            $params.= '"'.$combName."|".$combData['RING']."|".$combData['COMPLEX']."|".$combData['COMB'].'" ';
        }
            
        assert(defined('PHP_PATH'));    
        $out = array(); 
        
        chdir('/home/www/ds/movecomb');
        $command = constant('PHP_PATH')." -f index_old.php ".$params;
        // self::_out("COMMAND: ".$command);
        
        SU_DebugTools::TimerStart('YOOOOOOOOOO!');
        $str = exec($command, $out);       
        $execTime = SU_DebugTools::TimerStop('YOOOOOOOOOO!');
        
        $strOut = implode("\n", $out);
        
        $str = 'COMPLIT RESTORE OF UserInstance; Log ID:';
        $pos = strpos($strOut, $str);
        
        if($pos)
        {
            $strOut = substr($strOut, $pos);
            
            $logID = array();
            if(preg_match('/COMPLIT RESTORE OF UserInstance; Log ID: ([0-9]+) -------/', $strOut, $logID))
            {
                return $logID[1];
            }
        
            return 1;
        }

        self::_out($params." : HAVE ERRORS! TIME: [".$execTime."]\n".$strOut, 1, 1);
        return 0;
    }
    
    private function _callBackScript($res)
    {
        if(empty($this->callBackScript))
        {
            return true;
        }
        
        if(!file_exists($this->callBackScript))
        {
            return false;
        }
        
        $params = $this->uid." ".$this->fromDBID." ".$this->fromWorldID." ".$this->toDBID." ".$this->toWorldID." ".$this->fromUIID." ".$res;
        
        $out = array();
        
        $command = constant('PHP_PATH')." -f ".$this->callBackScript." ".$params;
        
        $str = exec($command, $out);
        
        if($str == 'OK')
        {
            self::_out("CALL BACK SCRIPT: OK!");
            return true;
        }
        
        self::_out($str." [-".$params."] CALL BACK SCRIPT: <<<<<<<<< ERROR!!!!", 1, 1);
        return false;
    }
    
    private function _formMoveParams()
    {       
        // ----------- fromDBID work ------------------------
        SI_Base::GetCommon()->setDBID($this->fromDBID);     
        $_SESSION["dbId"] = $this->fromDBID;
        
        $userData = SI_SearchInterface::GetCombCoordsAndUIIDByUserNameAndWorldName($this->userName, $this->fromWorldName);
        
        if(count($userData) == 0)
        {
            throw new Exception('Cant find user in sorce world');           
        }
        
        $this->fromUIID = (int)$userData[0]['id_userinstance'];
        assert($this->fromUIID > 0);
        
        $this->uid = (int)$userData[0]['id_user'];
        assert($this->uid > 0);
        
        $this->fromWorldID = (int)$userData[0]['id_world'];
        assert($this->fromWorldID > 0);
        
        // ----------- toDBID work ------------------------
        SI_Base::GetCommon()->setDBID($this->toDBID);       
        $_SESSION["dbId"] = $this->toDBID;
        $toWorldData = SI_SearchInterface::GetWorldByName($this->toWorldName);
        
        if(count($toWorldData) == 0)
        {
            throw new Exception('Cant find target world');                      
        }
        
        $this->toWorldID = (int)$toWorldData['id_world'];
        assert($this->toWorldID > 0);
        
        $this->combNewCoordinates = array();
        if($this->coordinateMode == 'SAME')
        {
            $this->_formSameCoordinates($userData);
        } elseif($this->coordinateMode == 'FREE') {
            $this->_formFreeCoordinates($userData);
        } else {
            $this->_formCoordinatesFromReserve($userData);
        }
        
        return true;    
    }
    
    private function _formSameCoordinates($userData)
    {
        assert(is_array($userData));        
        
        foreach($userData as $k=>$combData)
        {
            assert(key_exists('combname', $combData));
            assert(key_exists('ringnumber', $combData));
            assert(key_exists('complexnumber', $combData));
            assert(key_exists('combnumber', $combData));
            
            $this->combNewCoordinates[$combData['combname']] = array
            (
                'RING' => $combData['ringnumber'],
                'COMPLEX' => $combData['complexnumber'],
                'COMB' => $combData['combnumber'] 
            );
        }       
    }
    
    
    private function _formFreeCoordinates($userData)
    {                           
        $selectedNewCombID = array();
        
        foreach($userData as $k=>$combData)
        {           
            $newCombID = (int)SI_SearchInterface::GetMinCombIDForPlacementOnRing($combData['ringnumber'], $this->toWorldID, $selectedNewCombID);

            $selectedNewCombID[] = $newCombID;
            
            if($newCombID == 0)
            {
                throw new Exception("No free combs on ring ".$combData['ringnumber']);              
            }

            $combData2 = SI_SearchComb::GetCombDataByID($newCombID);
            
            assert(key_exists('combname', $combData));
            assert(key_exists('ringnumber', $combData2));
            assert(key_exists('complexnumber', $combData2));
            assert(key_exists('combnumber', $combData2));
            $this->combNewCoordinates[$combData['combname']] = array(
                'RING' => $combData2['ringnumber'],
                'COMPLEX' => $combData2['complexnumber'],
                'COMB' => $combData2['combnumber'] 
            );
        }
    }
    
    private function _formCoordinatesFromReserve($userData)
    {
        SI_Base::GetCommon()->setDbId($this->toDBID);
        $_SESSION["dbId"] = $this->toDBID;

        GL_CombCoordsInNewWorld::SetCommon(SI_Base::GetCommon());
        $this->combNewCoordinates = array();
        $res = GL_CombCoordsInNewWorld::Get($userData, $this->userName, $this->toDBID, $this->toWorldID);
        $this->combNewCoordinates = $res;
//self::_out("NEW COORDS: ".print_r($this->combNewCoordinates, true));
    }   
    
    /**
     * Anna;0;EDR;0;HAM;SAME;CALLBACK_SCRIPT
     *
     * @param unknown_type $string
     */
    private function _getParams($string)
    {
        //Anna;0;EDR;0;HAM;SAME;CALLBACK_SCRIPT
        assert(!empty($string));
        
        $arrayTmp = explode(";", $string);
        $this->userName = trim($arrayTmp[0]);           
        if(empty($this->userName))
        {
            throw new Exception('Empty userName');          
        }           
            
        $this->fromDBID = (int)trim($arrayTmp[1]);
        if(!key_exists($this->fromDBID, $GLOBALS['dbWorldAccess']))
        {
            throw new Exception('Uncorrect fromDBID');          
        }       
        
        $this->fromWorldName = trim($arrayTmp[2]);
        if(empty($this->fromWorldName))
        {
            throw new Exception('Empty fromWorldName');                     
        }
        
        $this->toDBID = (int)trim($arrayTmp[3]);
        if(!key_exists($this->toDBID, $GLOBALS['dbWorldAccess']))
        {
            throw new Exception('Uncorrect toDBID');                
        }
        
        $this->toWorldName = trim($arrayTmp[4]);
        if($this->toWorldName < 0)
        {
            throw new Exception('Uncorrect fromWorldName');                         
        }
        
        $this->coordinateMode = trim($arrayTmp[5]);
        if($this->coordinateMode != 'SAME' && $this->coordinateMode != 'FREE' && $this->coordinateMode != 'RESERVE')
        {
            throw new Exception('Uncorrect coordinateMode');                                        
        }
        
        $this->tempColony = trim($arrayTmp[6]);
        if($this->tempColony < 0)
        {
            throw new Exception('Uncorrect Temp Colony');                           
        }
            
        if(key_exists(7, $arrayTmp))
        {
            $this->callBackScript = trim($arrayTmp[7]);
        }
    }
}
?>