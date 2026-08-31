<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Базовый класс для удаления мусора из базы.
 *
 */
class SU_DeleteTrashBase
{
    static protected $serverName;
    
    /**
     * Когда стартовал скрипт
     *
     * @var timestamp
     */
    static protected $startTime;
    
    /**
     * 03:30
     *
     * @var int
     */
    static protected $endTimeLimit = 330; 
    
    /**
     * 07:00
     *
     * @var int
     */
    static protected $startTimeLimit = 700;
    
    /**
     * Макс. время исполнения запросов
     *
     * @var unknown_type
     */
    static protected $sqlTimeOut = 1200000; // 20 минут
    
    /**
     * Макс. время работы скрипта
     *
     * @var unknown_type
     */
    static protected $maxExecutionTime = 18000; // 6 часов
    
    static protected $DBID;
    
    static protected function _out($msg)
    {               
        $out = SU_DebugTools::GetInternalDate()." - ".$msg."\n";
        echo $out;
                        
        $fileName = "DeleteTrash_".self::$serverName.".log";        
        SU_DebugTools::WriteToLog($out, $fileName);
                
        flush();
    }
    
    static protected function _checkTimeLimit()
    {       
        $checkTime = (int)date('Hi');
        $workTime = time() - self::$startTime;
        
        if(($checkTime > self::$endTimeLimit && $checkTime < self::$startTimeLimit) || $workTime > self::$maxExecutionTime || GameDown(self::$DBID))
        {
            
            self::_out("===== WORK TIME LIMIT. STOP. =====");
            self::_out("checkTime: ".$checkTime);
            self::_out("workTime: ".$workTime);
            exit();
        }
    }
}
?>