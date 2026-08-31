<?php $CVS_Version = '$Revision$ ; $Date$';


/**
 * Ѕазовый классс статтический
 */
class DS_Base {
        
    protected static function FormMark($methodName, $mark='')
    {       
        //¬ремено закрыта работа метода так как есть подозрени€ что
        //использование функции debug_backtrace() сильно перегружает сервер
        //и вызывает ошики вызванные обрщением к несуществующей пам€ти.
        
        /*$trace = debug_backtrace();
        return $trace[1]['class']."::".$trace[1]['function']."(".$mark.")";*/
                
        assert(strpos($methodName, '::'));
            
        return $methodName."(".$mark.")";
    }   
    
    /**
     * ¬озвращ€ет из экземпл€р DS_Common 
     *
     * @return DS_Common
     */
    protected static function getCommon ( )
    {               
        return DS_Common::CreateForCurentDB();
    }//END OF METHOD
    
    public static function getNextSeqVal(DS_Common $common, $seqName)
    {
        assert(!empty($seqName));
        
        $sql = "select nextval('$seqName') as id";
        $res = $common->getResultArray ( "", $sql, "", "", "" );    
        assert(empty($common->errormsg) && count($res) == 1);       
        
        $id = (int)$res[0]['id'];
        assert($id > 0);    
        
        return $id;
    }
    
}//END OF CLASS


?>