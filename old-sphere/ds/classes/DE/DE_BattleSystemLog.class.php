<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Системный лог боя, нужен для отладки/проверки боев. Смотреть можно из админки.
 */
class DE_BattleSystemLog
{
    static private $forLog = array();           
    
    static private $workMode = false;
    
    static private $battle;
    
    static public function StartWork(DE_Battle $battle)
    {
        self::$workMode = true;     
        self::$battle = $battle;
        
        if(!key_exists(self::$battle->getID(), self::$forLog))
        {
            self::$forLog[self::$battle->getID()] = '';
        }
    }   
    
    static public function EndWork()
    {
        self::$workMode = false;        
        self::$battle = null;
    }
    
    static public function Insert($str)
    {                       
        if(!self::$workMode)
        {
            return;
        }
        
        self::$forLog[self::$battle->getID()] .= $str;
    }       
    
    public static function FlashAll()
    {
        foreach(self::$forLog as $battleID => $content)
        {
            if(!empty($content))
            {               
                if(key_exists('uiid', $_SESSION))
                {
                    $UIID = (int)$_SESSION['uiid'];
                }else{
                    $UIID = 0;  
                }
                
                DS_Battle::InsertItoBattleSystemLog($battleID, $UIID, $content);
//SU_DebugTools::LogAll($content, "BattleSystemLog_".$battleID.".log");
//SU_DebugTools::LogUser('oleg123oleg', $content, "BattleSystemLog_".$battleID.".log");
            }       
        }   
    }
}
?>