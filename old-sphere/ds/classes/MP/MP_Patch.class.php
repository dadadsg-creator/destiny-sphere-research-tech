<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Базовый класс для патчей всех
 *
 */

class MP_Patch
{
    /**
     * Объект для работы с БД
     *
     * @var DS_Common
     */
    static public $common;
    
    /**
     * Имя лог файл акуд алогируем
     * генериться автоматом изходя из даты 
     *
     * @var string
     */
    static protected $logFileName = null;
    
    /**
     * Режим патчинга. Только ищем ошибки или же их еще и правим
     *
     * @var unknown_type
     */
    static public $onlyValidate = false;
    
    
    static public $disableLog = true;
    
    /**
     * Метод вывода сообщений
     *
     * @param string $msg
     */
    static protected function _out($msg)
    {
        echo $msg."\n";
        flush();

        if(self::$disableLog == false)
        {
            SU_DebugTools::WriteToLog($msg."\n", self::GetLogFileName());
        }
    }

    /**
     * Метод генерации логфайла
     *
     * @return string
     */
    static public function GetLogFileName()
    {
        if (is_null(self::$logFileName))
        {
            self::$logFileName = 'MegaPatch_'.date("Y-m-d H_i_s").'_'.self::$common->getCurrentDBName().'.log';
            return self::$logFileName;
        } else {
            return self::$logFileName;
        }
    }
    
    /**
     * Метод формирует список патчей который сейчас зарегитрирован в сиситеме
     *
     */
    public function getPatchesList()
    {
        self::_out("USED PATCHES:");
        
        self::_out("For DB:");      
        foreach(MP_PatchDB::$patchesDB as $k=>$v)
        {
            self::_out("    $v");
        }
        
        self::_out("For UserInstance:");        
        foreach(MP_PatchUserInstance::$userInstancePatches as $k=>$v)
        {
            self::_out("    $v");
        }
        
        self::_out("For Comb:");
        foreach(MP_PatchComb::$combPatches as $k=>$v)
        {
            self::_out("    $v");
        }       
    }
    
    public function setDisableLog($flag)
    {
        assert(is_bool($flag));
        
        self::$disableLog = $flag;
    }

}

?>