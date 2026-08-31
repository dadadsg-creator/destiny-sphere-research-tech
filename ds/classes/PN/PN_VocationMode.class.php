<?php $CVS_Version = '$Revision$ ; $Date$';

class PN_VocationMode extends UA_GetBuildMenu 
{		
	/**
	 * Люъект инстанса игрока
	 *
	 * @var DE_UserInstance
	 */
	private $userInstance;
	
	/**
	 * Минимальнео время отпуска в секундах
	 *
	 * @var integer
	 */
	private $minVocationTime = 0;
	
	/**
	 * Текущее время
	 *
	 * @var GB_Date
	 */
	private $nowDate;
	
	public function __construct()
	{
		parent::UA_GetBuildMenu ();
		
		$this->userInstance = DE_UserInstance::CreateFromID((int)$this->uiid);
		
		$this->minVocationTime = DE_WorldSettings::CreateForCurentWorld()->get('MIN_VACATION_TIME') * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
		
		$this->nowDate = GB_TypeFactory::CreateBase('GB_Date', time());			
	}

	public function getPanel () 
	{		
		$vacationRes = GL_IsUserInVocationMode::Test($this->userInstance);

		if(count($vacationRes) > 0)
		{
			$vocationStartDate = GB_TypeFactory::CreateBase('GB_Date', $vacationRes['startdate']);
			
			if(($vocationStartDate->value + $this->minVocationTime) > $this->nowDate->value)
			{
				
				$delta = $vocationStartDate->value + $this->minVocationTime - time();
				
				$period = GB_TypeFactory::CreateBase('GB_TimePeriod', $delta);
				
				$params = array(
					'PERIOD' => $period->getDSFormat()
				);
				
				$dsc = DE_ServerSideInterfaceLocalization::CreateForCurentLang()->get('VACATION_DESC_TIME_TO_END', $params);			
				
				$msg = DE_ServerSideInterfaceLocalization::CreateForCurentLang()->get('VACATION_STOP_IS_BLOCKED');
				
				$this->_formOut(2, $msg, $dsc);					
			}else{
				$msg = DE_ServerSideInterfaceLocalization::CreateForCurentLang()->get('VACATION_READY_TO_STOP');
				$this->_formOut(3, $msg);	
			}						
		}else{		
			
			if(GL_CanFreez::Test($this->userInstance))
			{
				$msg = DE_ServerSideInterfaceLocalization::CreateForCurentLang()->get('VACATION_INVITE');
				
				$period = GB_TypeFactory::CreateBase('GB_TimePeriod', $this->minVocationTime);
				
				$params = array(
					'PERIOD' => $period->getDSFormat()
				);
				
				$dsc = DE_ServerSideInterfaceLocalization::CreateForCurentLang()->get('VACATION_DESC_MIN_TIME', $params);
				
				$this->_formOut(1, $msg, $dsc);	
			}else{
				$msg = DE_ServerSideInterfaceLocalization::CreateForCurentLang()->get('VACATION_CANT_START');
				
				$period = GB_TypeFactory::CreateBase('GB_TimePeriod', $this->minVocationTime);
				
				$params = array(
					'PERIOD' => $period->getDSFormat()
				);
				
				$dsc = DE_ServerSideInterfaceLocalization::CreateForCurentLang()->get('VACATION_DESC_MIN_TIME', $params);
				
				$this->_formOut(2, $msg, $dsc);
			}			
		}
				
		return true; 
	}		
	
	private function _formOut($status, $msg, $dsc = '')
	{
		assert($status == 1 || $status == 2 || $status == 3);
		assert(!empty($msg));
		
		
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><otpusk/>\n";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();	
				
		$this->getBuildingTabs($root, $this->bid, $this->params["lang"], $this->tab);
		
		$out = "<otpusk><data status=\"$status\">$msg</data><desc>$dsc</desc>";
		
		$forOut = str_replace('<otpusk>', $out, $doc->dump_mem());
		
		SU_OutputHandler::Singleton()->addString($forOut, 'xmldata');		
		
		return true; 
	}
}



?>