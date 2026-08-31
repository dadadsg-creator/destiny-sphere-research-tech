<?php $CVS_Version = '$Revision$ ; $Date$';

class UA_Buy extends UA_Action 
{
	/**
	 * Enter description here...
	 *
	 * @var float
	 */	
	private $tradeLotID;
	
	/**
	 * Лот с которым работаем
	 *
	 * @var DE_TradeLot
	 */
	private $tradeLot;

	/**
	 * Новая цена. Актуальна только для аукциона с торгом
	 *
	 * @var float
	 */
	private $newPrice;
		
	/**
	 * Пароль для покупки персонального лота
	 *
	 * @var string
	 */
	private $personPassword;
	
	
	/**
	 * Enter description here...
	 *
	 * @var DE_Comb
	 */
	private $customerComb;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_UserInstance
	 */
	private $customer;
	
	private $checkErrorCode = '';
	
	/**
	 * Флаг того что было обнарушженно что лот уже купленн
	 *
	 * @var boolean
	 */
	private $emptyLotFlag = false; 
	
	/**
	 * Флаг анонимности покупателя
	 *
	 * @var int
	 */
	private $buyAnonymous;
	
	function __construct() 
	{
		parent::UA_Action();	
	}

	// проверяем пришедшие параметры
	function checkLocalParams () 
	{
		
		// id аукциона
		$this->tradeLotID = (int)$this->root->get_attribute("id_trade");
		assert($this->tradeLotID > 0);
		
		// ставка
		$this->newPrice = (float)ceil($this->root->get_attribute("newprice"));
		assert($this->newPrice >= 0);
		
		// анонимность покупателя
		$this->buyAnonymous = (int)$this->root->get_attribute("anonym");
		assert($this->buyAnonymous == 0 || $this->buyAnonymous == 1);
		
		//Пароль
		$this->personPassword = $this->root->get_attribute("password");				
		
		$this->customerComb = DE_Comb::CreateFromID((int)$this->comb);
		
		$this->customer = DE_UserInstance::CreateFromID((int)$this->uiid);
				
		return true;
	}

	// проверяем допустимость действия
	function checkAction () 
	{				
		$data = DS_Trade::GetTradeLotByID($this->tradeLotID);
		assert(is_array($data));
		
		if(count($data) == 0)
		{
			$this->emptyLotFlag = true; 	
			return true;
		}
		
		$this->tradeLot = DE_TradeLot::CreateFromID($this->tradeLotID, $data);
		
		// Эсли это альянсовый скоп, лот не может быть куплен анонимно
		if ($this->tradeLot->getTradeScopeID() == 4) {
			$this->buyAnonymous = 0;
		}
				
		if(!GL_TradeCanByThisLotByThisPrice::Test($this->tradeLot, $this->customerComb, $this->newPrice, $this->personPassword))
		{
			$this->checkErrorCode = GL_TradeCanByThisLotByThisPrice::GetErrorCode();
		}
		
		return true;
	}

	// выполняем команду
	function performAction()
	{	
		
		//Если пытаемся купить лот который уже куплен.
		if($this->emptyLotFlag)
		{
			$messparams = array();
		
			DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TRADE_LOT_NOT_EXISTS', $messparams, 0);	
			
			$helper = '<?xml version="1.0" encoding="utf-8"?>'."\n".'<buyok id="'.$this->tradeLotID.'" status="NOT"/>';			
			SU_OutputHandler::Singleton()->addString($helper, 'helper');
			
			return true;
		}
				
		if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
		{
			$this->_vocationModeAlert();			
			return true;
		}
		
		if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
		{
			$this->_shildModeAlert();			
			return true;
		}				
		
		//Лочим паралельную покупку лотов и проверяем залочиность ивента
		$lockActionID = __CLASS__."_".$this->tradeLotID;		
		if(!SU_Locker::CerateForCurrentDB('ACTION')->lockWithTest($lockActionID) || $this->tradeLot->getLotEvent()->isLockedForHandl())
		{
			DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $lockActionID, 'ACTION');
		}
		
		//Обрабатываем ошибки если есть
		if(!empty($this->checkErrorCode))
		{
			switch($this->checkErrorCode)
			{
				case 'TO_OLD_TRADE':
					$messparams = array();
		
					DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TRADE_TO_OLD', $messparams, 0);	
			
					$helper = '<?xml version="1.0" encoding="utf-8"?>'."\n".'<buyok id="'.$this->tradeLotID.'" status="NOT"/>';			
					SU_OutputHandler::Singleton()->addString($helper, 'helper');
			
					return true;
					
					break;
				case 'DONT_HAVE_NEED_AMOUNT':
					$messparams = array();
		
					DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TRADE_NOT_ENOUGHT_RESOURCE_TO_BAY', $messparams, 0);	
			
					$helper = '<?xml version="1.0" encoding="utf-8"?>'."\n".'<buyok id="'.$this->tradeLotID.'" status="NOT"/>';			
					SU_OutputHandler::Singleton()->addString($helper, 'helper');
			
					return true;
					
					break;
					
				case 'CANT_BUY_WITH_TRADE_BAN':
					$messparams = array();
					DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TRADE_BAN', $messparams);
					return true;
					
					break;
					
				case 'CANT_BY_WITH_RANDOM_DEFEND':
					$messparams = array();
					DE_SystemMessages::AddMessageFromAction(__CLASS__, 'CANT_BY_WITH_RANDOM_DEFEND', $messparams);
					return true;
					
					break;
					
				default:
					ds_error("Buy error: ".$this->checkErrorCode);
			}			
		}

		try 
		{
			if($this->tradeLot->getIsAuction())
			{
				$this->_doBid();
			}else{
				$this->_bayLot();
			}
		} catch (ME_TradeExecLotBuyException $e) {
			switch ($e->getMessage()) {
				case 'NOT_ENOUGHT_RESOURCES_FOR_BUY_THIS_UNITS':
					$messparams = array();
					DE_SystemMessages::AddMessageFromAction(__CLASS__, 'TRADE_NOT_ENOUGHT_RESOURCES_FOR_BUY_THIS_UNITS', $messparams);
					return true;
					break;
			
				default:
					ds_error("Buy error: ".$e->getMessage());
			}		
		}
		
		if(!$this->refresh(2, "", "", 1)) 
		{
			return false;
		}
	
		$this->_formHelper();
		
		return true;
	}	
	
	private function _doBid()
	{		
		ME_TradeDoBindOnLot::Exec($this->tradeLot, $this->customerComb, GB_TypeFactory::CreateBase('GB_Date', time()), $this->newPrice, $this->buyAnonymous);	
		return true;
	}
	
	private function _bayLot()
	{
		$this->tradeLot->setCurrentCustomer($this->customerComb);		
		$bayResult = ME_TradeExecLotBay::Exec($this->tradeLot, GB_TypeFactory::CreateBase('GB_Date', time()), $this->buyAnonymous);
		return true;
	}
	
	private function _formHelper()
	{
		if($this->tradeLot->getIsAuction())
		{
			$status = 'BID';
		}else{
			$status = 'BUY';
		}
				
		if($status == 'BID')
		{
			$prices = GL_TradeCountMinAndMaxPrices::Count($this->tradeLot->getTradeType(), $this->tradeLot->getAmount(), $this->newPrice);								
			$nextPrice = $prices['NEXT_PRICE'];
			$maxPrice = $prices['MAX_PRICE'];	
			
			if($nextPrice >= $maxPrice)
			{
				$status = 'NOT';
			}
		}else{
			$nextPrice = 0;
			$maxPrice = 0;	
		}
		
		
		$bayerName = $this->customer->getUser()->getName();
		$bestPrice = $this->tradeLot->getBestPrice();
		//$bayerName = ' ';
		$helper = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<buyok id=\"".$this->tradeLotID."\" status=\"".$status."\" newprice=\"".GB_Integer::Format($this->newPrice)."\"  nextprice=\"".GB_Integer::Format($nextPrice)."\" maxprice=\"".GB_Integer::Format($maxPrice)."\" enddate=\"".$this->tradeLot->getEndDate()->getPgFormat(DE_User::CreateForCurrentUser()->getTimeDelta())."\" newbuyer=\"".$bayerName."\" best_price=\"".$bestPrice."\"/>";
		
		SU_OutputHandler::Singleton()->addString($helper, 'helper');
	}
}

?>