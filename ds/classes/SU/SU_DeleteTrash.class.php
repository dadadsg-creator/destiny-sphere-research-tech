<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Скрипт по вычищению мусора из базы скопившегося за день
 * 
 */
class SU_DeleteTrash extends SU_DeleteTrashBase
{
    /**
     * @var DS_Common
     */
    static private $common;
    
    static private $trashManagers = array
    (
        'SU_DeleteTrashEvents', // Удаление устаравших ивентов  
        'SU_DeleteTrashBattles', // удаление боев
        'SU_DeleteTrashRequestLog', // удаление старых логов запросов
        'SU_DeleteTrashUnitDeathLog', // удаление старых логов помирания юнитов
        'SU_DeleteTrashGameObjectsDeleteLog', // удаление старых логов удаления игровых объектов
        'SU_DeleteTrashUserSupportRequest', // удаление старых запросов в сапорт
        'SU_DeleteTrashGameMessages', // удаление старых игровых сообщений
        'SU_DeleteTrashTradeHistory', // удаление старых торговых логов
        'SU_DeleteTrashTradeHistoryArtefact', // удаление старых торговых логов артефактов (В отдельном обработчике, так как из за него может не удаляться вся торговля)
        'SU_DeleteTrashReservedCombs', // удаление резервирований сот
        'SU_DeleteTrashLoginHistory', // удаление логин хистори
        'SU_DeleteTrashTransferLog', // удаление старых логов трансфера
        'SU_DeleteTrashStealingLog', // удаление старых логов налога на грабеж
        'SU_DeleteTrashSpyLog', // удаление старых логов шпионажа
        'SU_DeleteTrashReinrnation', // удаление старых логов реинкарнации
    );
    
    static public function Exec($DBID)
    {
        assert(is_int($DBID) && $DBID >= 0);

        self::$DBID = $DBID;
        
        $_SESSION['dbId'] = self::$DBID;
        
        self::$serverName = $GLOBALS['dbWorldAccess'][self::$DBID]['server'];
        
        self::$startTime = time();
    
        self::_out('==========================================');
        self::_out('    START DELETE TRASH IN DB '.$GLOBALS['dbWorldAccess'][self::$DBID]['server']);
        self::_out('==========================================');

        $childs = array();
        if(function_exists('pcntl_signal'))
        {
            pcntl_signal(SIGCHLD, SIG_IGN);
        }
        
        foreach (self::$trashManagers as $k=>$managerName)
        {
            $fork = false;
            
            if(function_exists('pcntl_fork'))
            {   
                $pid = pcntl_fork();
                $childs[] = $pid;
            
                if($pid == -1)
                {
                    self::_out('CAN\'T FORK');
                } elseif($pid) {
                    //self::_out('FORK '.$managerName.' PID '.$pid);
                    continue;
                } elseif($pid == 0) {
                    $fork = true;
                }
            }
            
            self::$common = DS_Common::CreateForCurentDB(self::$DBID);
            
            $manager = new $managerName(self::$common);
            self::_checkTimeLimit();
            $manager->delete();
            
            if($fork)
            {
                return;
            }
        }       
        
        if(function_exists('pcntl_waitpid') && count($childs) > 0)
        {
            foreach ($childs AS $k => $pid)
            {
                pcntl_waitpid($pid, $status);
                //self::_out('PID '.$pid.' EXIT');
            }
        }
        
        self::_out('DELETE TRASH COMPLIT');
        self::_out('');
    }   
}

?>