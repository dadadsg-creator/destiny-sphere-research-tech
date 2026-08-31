<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_PowerShild extends DE_Base
{   
    /**
     * Время последней работы
     *
     * @var GB_Date
     */
    private $lastWorkDate;
    
    /**
     * Продолжительность послденего времени работы
     *
     * @var integer
     */
    private $lastWorkLength = 1;
    
    /**
     * Имя текущего статуса щита
     *
     * @var string
     */
    private $currentStatusName = 'NO_STATUS';
    
    /**
     * Вемя начала
     *
     * @var GB_Date
     */
    private $startDate;
    
    /**
     * Время окончания
     *
     * @var 
     */
    private $endDate;
    
    public function __construct($id)
    {
        assert(is_int($id));
        parent::__construct($id);       
        
        $powerShildData = DS_Freez::GetPowerShildRecord($id);
        
        if($powerShildData)
        {
            assert(key_exists('last_work_end_date', $powerShildData));
            $this->lastWorkDate = GB_TypeFactory::CreateBase('GB_Date', $powerShildData['last_work_end_date']);
            
            assert(key_exists('start_date', $powerShildData));
            $this->startDate = GB_TypeFactory::CreateBase('GB_Date', $powerShildData['start_date']);
            
            assert(key_exists('end_date', $powerShildData));
            $this->endDate = GB_TypeFactory::CreateBase('GB_Date', $powerShildData['end_date']);
            
            assert(key_exists('last_work_length', $powerShildData));
            $this->lastWorkLength = (int)$powerShildData['last_work_length'];
            assert($this->lastWorkLength > 0);
            
            assert(key_exists('statusname', $powerShildData));
            $this->currentStatusName = $powerShildData['statusname'];
            assert(!empty($this->currentStatusName));
        }else{          
            $date = GB_TypeFactory::CreateBase('GB_Date', 1);
            
            $this->lastWorkDate = $date;
            $this->startDate = $date;
            $this->endDate = $date;
        }           
    }       
    
    static public function CreateFromUserInstance($UIID)
    {
        assert(is_int($UIID));
        return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $UIID);
    }

    
    public function setNewStatus($statusName, GB_Date $startDate, GB_Date $endDate)
    {
        assert(!empty($statusName));
        
        $this->currentStatusName = $statusName;
        
        $this->startDate = $startDate;
        
        $this->endDate = $endDate;
    }
    
    public function getLastWorkDate()
    {
        return $this->lastWorkDate;
    }
    
    public function getEndDate()
    {
        return $this->endDate;
    }
    
    public function getLastWorkLength()
    {
        return $this->lastWorkLength;
    }
    
    public function isWorks($checkDate = null)
    {
        assert(is_null($checkDate) || ($checkDate instanceof GB_Date));
        
        if(RD_AdeptUserActionToConsole::$prepareTransfer)
        {
            return false;
        }
        
        if(is_null($checkDate))
        {
            $checkTime = time();
        }else{
            $checkTime = $checkDate->value;
        }
        
        if($this->currentStatusName != 'WORKING')
        {
            return false;
        }
        
        if($checkTime > $this->endDate->value)
        {
            return false;
        }
        
        return true;        
    }   
    
    public function commit()
    {
        $this->_invariant();
        parent::commit();           
    }
    
    public function delete()
    {
        parent::delete();               
    }

    function __destruct()
    {
        parent::__destruct();       
    }

}   

?>