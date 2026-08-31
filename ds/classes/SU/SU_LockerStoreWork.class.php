<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_LockerStoreWork extends BS_Base
{
    
    /**
     * Фактически тип объекта для локинга
     *
     * @var string
     */
    protected $lockObjectName;
    
    /**
     * ID базы с которой работаем
     *
     * @var integer
     */
    protected $DBID;
    
    /**
     * Время таймаута лок файла
     *
     * @var integer
     */
    protected $lockTimeOut;
    
    public function __construct($lockObjectName, $DBID, $lockTimeOut)
    {
        assert(!empty($lockObjectName));
        assert(is_int($DBID) && $DBID >= 0);    
        assert(is_int($lockTimeOut) && $lockTimeOut > 0);
        
        parent::__construct();
        
        $this->lockObjectName = $lockObjectName;
        $this->DBID = $DBID;
        $this->lockTimeOut = $lockTimeOut;      
    }
    
    public function lock($id) 
    {
        ds_error('This method must be implemented later');
    }
    
    public function unlock($id) 
    {
        ds_error('This method must be implemented later');
    }
    
    public function isLocked($id)
    {
        ds_error('This method must be implemented later');
    }
    
    public function getLockInfo($id)
    {
        ds_error('This method must be implemented later');
    }           
    
    public function lockCollector()
    {
        ds_error('This method must be implemented later');
    }
}


?>