<?php $CVS_Version = '$Revision$ ; $Date$';

class UA_PowerShildStart extends UA_Action
{
    /**
     * Объект инстанса игрока
     *
     * @var DE_UserINstance
     */ 
    private $userInstance;
    
    /**
     * Минимальное время работы щита
     *
     * @var integer
     */
    private $minWorkTime;
    
    /**
     * Максимальное время работя щита
     *
     * @var integer
     */
    private $maxWorkTime;
    
    /**
     * Стоимость одного рутна заморозки
     */
    private $freezTurnCost;
    
    /**
     * Сумарная стоимость щита на указанный период
     *
     * @var integer
     */
    private $totalCost;
    
    /**
     * Время на сколько тунов хотя поставить щит
     *
     * @var integer
     */
    private $turns;
    
    /**
     * Атрибуты соты ткуда старутем поле
     *
     * @var DE_CombAttributes
     */
    private $combAttributes;
    
    public function __construct()
    {
        UA_Action::UA_Action ();
        
        $this->userInstance = DE_UserInstance::CreateFromID((int)$this->uiid);
        
        $this->minWorkTime = (int)DE_WorldSettings::CreateForCurentWorld()->get('PS_MIN_FREEZ_TIME');
        assert($this->minWorkTime > 0);
        
        $this->maxWorkTime = (int)DE_WorldSettings::CreateForCurentWorld()->get('PS_MAX_FREEZ_TIME');
        assert($this->maxWorkTime > 0);
        
        assert($this->minWorkTime < $this->maxWorkTime);
        
        $this->freezTurnCost = GL_PowerShildTurnPrice::Count($this->userInstance);
        assert($this->freezTurnCost > 0);
        
        $this->combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);
    }
    
    public function checkLocalParams() 
    {       
        $this->turns = (int)$this->root->get_attribute("time");     
        assert($this->turns >= $this->minWorkTime);
        assert($this->turns <= $this->maxWorkTime);
        
        return true;
    }
    
    public function checkAction ()
    {       
        //Проверяем есть ли вобще здание
        $buildingData = DS_Building::GetAttachedBuildingByCombIDAndBuildingID((int)$this->comb, 66);
        assert(is_array($buildingData) && count($buildingData) > 0);
        
        //Проверяем можно ли сейчас жать запуск
        $nextStatusData = DS_Freez::GetPowerShildNextStatus($this->userInstance->getID(), false);
        assert(is_array($nextStatusData) && count($nextStatusData) > 0);

        assert(key_exists('tostatusname', $nextStatusData[0]));
        $nextStatusName = $nextStatusData[0]['tostatusname'];
        
        assert($nextStatusName == 'WORKING');
        
        //Хватает ли энергии
        $this->totalCost = ceil($this->turns * $this->freezTurnCost);                       
        
        return true;
    }
    
    public function performAction()
    {   
        
        if(!GL_CanFreez::Test($this->userInstance))
        {
            /*
            switch (GL_CanFreez::FetchErrorCode())
            {
                case 'HAVE_LOTS':
                    $messparams = array();
                    DE_SystemMessages::AddMessageFromAction(__CLASS__, 'PS_ERROR_HAVE_LOTS', $messparams, 0);
                    return true;
                case 'HAVE_BIDS':
                    $messparams = array();
                    DE_SystemMessages::AddMessageFromAction(__CLASS__, 'PS_ERROR_HAVE_BIDS', $messparams, 0);
                    return true;
                case 'HAVE_BATTLES':
                    $messparams = array();
                    DE_SystemMessages::AddMessageFromAction(__CLASS__, 'PS_ERROR_BATTLE_ON_COMB', $messparams, 0);
                    return true;
                default:
                    $messparams = array();
                    DE_SystemMessages::AddMessageFromAction(__CLASS__, 'PS_ERROR_BATTLE_ON_COMB', $messparams, 0);
                    return true;
            }
            */
            $messparams = array();
            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'PS_ERROR_BATTLE_ON_COMB', $messparams, 0);
            return true;
        }
        
        //Снимаем энергию.
        if($this->combAttributes->getAttribute('CURRENT_AMOUNT', 'STASIS_ENERGY') < $this->totalCost)
        {
            $messparams = array();
            DE_SystemMessages::AddMessageFromAction(__CLASS__, 'PS_ERROR_NOT_ENOUGHT_ENERGY', $messparams, 0);          
            return true;
        }else{
            //Снимаем энергию
            $this->combAttributes->deltaAttribute('CURRENT_AMOUNT', 'STASIS_ENERGY', -$this->totalCost);    
        }
        
            
        //Меняем сосотояние щита
        $startDate = GB_TypeFactory::CreateBase('GB_Date', time());
        
        $freezTime = $this->turns * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;        
        $endDate = GB_TypeFactory::CreateBase('GB_Date', time() + $freezTime);
        
        DS_Freez::ChangePowerShildStatusToNext($this->userInstance->getID(), $startDate, $endDate, false);  
        
        //Отмечаем эту дату как послоднее время работы
        $length = (int)($endDate->value - $startDate->value);
        DS_Freez::MarkPowerShildLastWorkDate($this->userInstance->getID(), $length, $endDate);
        
        //Замораживаем соту
        ME_FreezUser::Exec($this->userInstance, true);
        
        //Сразу же значения значения иентов и рефреша на указаное время
        ME_UnFreezUser::Exec($this->userInstance, $startDate, $endDate);
        
        //Пишем мессагу о том что стартонули
        $messparams = array();
        DE_SystemMessages::AddMessageFromAction(__CLASS__, 'PS_START_WORK', $messparams, 0);    
        
        return true;    
    }
}

?>