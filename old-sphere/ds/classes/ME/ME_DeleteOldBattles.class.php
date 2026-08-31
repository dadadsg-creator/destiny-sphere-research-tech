<?php $CVS_Version = '$Revision$ ; $Date$';
class ME_DeleteOldBattles
{   
    /**
     * ÄÎñòóï ê ÁÄ
     *
     * @var DS_Common
     */
    private $common;        
    
    private $outPutMode = 'screen';     
    
    private $partDeleteTimeOutDays = 5;
    
    private $fullDeleteTimeOutDays = 7;
    
    private $logFileName = '';
    
    private $currentDBID = 0;
    
    function __construct()
    {
        $this->common = DS_Common::CreateForCurentDB(0);

        $sapi = php_sapi_name();
        if($sapi == "apache")
        {
            $this->_outPutMode = 'sreen';
        }else{
            $this->_outPutMode = 'log';
        }
        
    }//END OF METHOD

    private function _out($str, $onlyForHTTP = false)
    {   
        $str = SU_DebugTools::GetInternalDate()." ".$str."\n";              
        
        echo $str;
        flush();
        
        if(!$onlyForHTTP)
        {
            SU_DebugTools::WriteToLog($str, 'DeleteOldBattles_'.$this->currentDBID.'.log'); 
        }                           
    }
    
    function ScanOneBase($DBID)
    {               
        settype($DBID, 'integer');
        assert($DBID >= 0);
        
        $this->logFileName = 'DeleteOldBattles_'.$DBID.'.log';
        $serverData = $GLOBALS['dbWorldAccess'][$DBID];
                
        $this->currentDBID = $DBID; 
        
        $this->common->setDbId($DBID);                      
        $this->_out(" ----------- START WORK WITH DB_SERVER [".$serverData['server']."] ON HTTP_SERVER [".SU_DebugTools::GetServerName()."] ---------------- ");                        
        $this->process();               
        $this->_out(" ----------- DB_SERVER [".$serverData['server']."] COMPLIT -------------- ");      
    }//END OF METHOD

    function process()
    {   
        $this->execFullDelete();                                    
        $this->execPartDelete();        
    }

    function execFullDelete()
    {
        $this->_out('START FULL DELETE of battles older then '.$this->fullDeleteTimeOutDays.' days.');
        
        $battles = $this->getBattlesList($this->fullDeleteTimeOutDays);
        
        $this->_out('Selected '.count($battles).' for full delete');
        
        $counter = count($battles);     
        foreach($battles as $k=>$battleInfo)
        {
            $this->common->executeQuery('BEGIN');
            
            $this->deleteBattle($battleInfo['id_battle']);
            
            $this->common->executeQuery ('COMMIT');
            
            $this->_out(' - '.$counter.' - Battle ID = '.$battleInfo['id_battle'].' END_DATE = '.$battleInfo['battleenddate'].' is DELETED FULL', true);
            $counter--;
        }
        $this->_out('END FULL DELETE');
    }
    
    function execPartDelete()
    {
        $this->_out('START PART DELETE of battles older then '.$this->partDeleteTimeOutDays.' days.');
        
        $battles = $this->getBattlesList($this->partDeleteTimeOutDays);
        
        $this->_out('Selected '.count($battles).' for part delete');
        
        $counter = count($battles); 
        foreach($battles as $k=>$battleInfo)
        {
            $this->common->executeQuery('BEGIN');
            
            $this->deleteBattleResults($battleInfo['id_battle']);
            
            $this->common->executeQuery ('COMMIT');
            
            $this->_out($counter.' - Battle ID = '.$battleInfo['id_battle'].' END_DATE = '.$battleInfo['battleenddate'].' is PART DELETED', true);
            $counter--;
        }
        
        $this->_out('END PART DELETE');
    }

    function deleteBattleResults($battleID)
    {       
        $sql = "DELETE FROM ds_battleresult 
                WHERE id_battleround in (
                    SELECT id_battleround
                    FROM ds_battleround
                    WHERE id_battle = $battleID
                )";
        $rs = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg)); 
    }
    
    function deleteBattle($battleID)
    {       
        $sql = "DELETE FROM ds_battle WHERE id_battle = ".$battleID;
        $rs = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg)); 
    }

    function getBattlesList($days)
    {       
        settype($days, 'integer');
        assert($days > 0);
        
        $sql = "
                SELECT 
                    b.id_battle
                FROM                    
                    ds_battle AS b
                WHERE
                    b.battleenddate IS NOT NULL                                     
                    AND 
                    b.battleenddate < (now() - '".$days." DAY' ::interval)
                ORDER BY b.id_battle";
        $rs = $this->common->getResultArray("", $sql, "", "", "");
        assert(empty($this->common->errormsg));                     

        return $rs;
    }
    
}//END OF CLASS
?>
