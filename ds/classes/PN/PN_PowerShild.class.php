<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Интерфейс заморозки
 *
 */

class PN_PowerShild extends UA_GetBuildMenu 
{		
	/**
	 * Люъект инстанса игрока
	 *
	 * @var DE_UserInstance
	 */
	private $userInstance;
		
	/**
	 * минимальне время заморозки
	 *
	 * @var integer
	 */
	private $minFreezTime = 1;
	
	/**
	 * Минимальне время заморозки
	 *
	 * @var integer
	 */
	private $maxFreezTime = 100; 
	
	/**
	 * Стоимость в стазисе за турн заморозки
	 *
	 * @var integer
	 */
	private $turnPrice;
	
	/**
	 * Время ьурна в секундах
	 *
	 * @var integer
	 */
	private $turnTime;
	
	public function __construct()
	{
		parent::UA_GetBuildMenu ();
		
		$this->userInstance = DE_UserInstance::CreateFromID((int)$this->uiid);
		
		$this->minFreezTime = (int)DE_WorldSettings::CreateForCurentWorld()->get('PS_MIN_FREEZ_TIME');
		assert($this->minFreezTime > 0);
		
		$this->maxFreezTime = (int)DE_WorldSettings::CreateForCurentWorld()->get('PS_MAX_FREEZ_TIME');
		assert($this->maxFreezTime > 0);
		
		$this->turnPrice = GL_PowerShildTurnPrice::Count($this->userInstance);
					
		$this->turnTime = DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
	}

	public function getPanel () 
	{		
		ME_PowerShildStatusActions::ControlShildRecordExists($this->userInstance);
		
		//Такой изврат нужен так как у нас есть ситуация когда пожно проскачит ьдва статису автоматически
		for($i=1; $i<=2; $i++)
		{
			ME_PowerShildStatusActions::ControlStatusAutoChange($this->userInstance);	
		}
		
		
		$powerShildData = DS_Freez::GetPowerShildRecord($this->userInstance->getID());
		assert(is_array($powerShildData));
		
		key_exists('statusname', $powerShildData);
		$currentStatusName = $powerShildData['statusname'];
				
		key_exists('end_date', $powerShildData);
		$endDateTMP = $powerShildData['end_date'];
		assert(!empty($endDateTMP));
		$endDate = GB_TypeFactory::CreateBase('GB_Date',$endDateTMP);
		
		key_exists('start_date', $powerShildData);
		$startDateTMP = $powerShildData['start_date'];
		assert(!empty($startDateTMP));
		$startDate = GB_TypeFactory::CreateBase('GB_Date',$startDateTMP);
				
		$msgParams = array();
		
		switch ($currentStatusName)
		{
			case 'CHARGING':														
					$msgParams['END_DATE']= $endDate->getFormatedFull($this->userInstance->getUser()->getTimeDelta());
					
					$msg = DE_ServerSideInterfaceLocalization::CreateForCurentLang()->get('PS_CHARGING', $msgParams);					
					$this->_formOut(0, $msg);
				break;
			case 'NO_ACTIVATED':									
					$msg = DE_ServerSideInterfaceLocalization::CreateForCurentLang()->get('PS_NO_ACTIVATED', $msgParams);					
					$this->_formOut(1, $msg);
				break;
			case 'ACTIVATING':									
					$msgParams['END_DATE']= $endDate->getFormatedFull($this->userInstance->getUser()->getTimeDelta());

					$msg = DE_ServerSideInterfaceLocalization::CreateForCurentLang()->get('PS_ACTIVATING', $msgParams);				
					$this->_formOut(2, $msg);
				break;
			case 'READY':				
					$delta = $endDate->value - $startDate->value;
					$timePeriod = GB_TypeFactory::CreateBase('GB_TimePeriod', $delta);					
					$msgParams['PERIOD']= $timePeriod->getDSFormat();	    				
					
					$msg = DE_ServerSideInterfaceLocalization::CreateForCurentLang()->get('PS_READY', $msgParams);	
					
					$this->_formOut(3, $msg);
				break;
			case 'WORKING':									
					$msgParams['END_DATE']= $endDate->getFormatedFull($this->userInstance->getUser()->getTimeDelta());
					
					$msg = DE_ServerSideInterfaceLocalization::CreateForCurentLang()->get('PS_WORKING', $msgParams);	
					$this->_formOut(4, $msg);
				break;
		}
				
		return true; 
	}		
	
	private function _formOut($status, $msg)
	{
		assert($status == 0 || $status == 1 || $status == 2 || $status == 3 || $status == 4);
		assert(!empty($msg));
		
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><powershild/>\n";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();	
				
		$this->getBuildingTabs($root, $this->bid, $this->params["lang"], $this->tab);
		
		$out = "<powershild turn=\"".$this->turnTime."\" mint=\"".$this->minFreezTime."\" maxt=\"".$this->maxFreezTime."\" status=\"$status\" price=\"".$this->turnPrice."\"><desc>$msg</desc>";
		
		$forOut = str_replace('<powershild>', $out, $doc->dump_mem());
		
		SU_OutputHandler::Singleton()->addString($forOut, 'xmldata');		
		
		return true; 
	}
}



?>