<?php $CVS_Version = '$Revision$ ; $Date$';
class ME_GetUpkeepResourcesFromComb
{
	static private $enoughResource = false;
	
	/**
	 * Сколько ресурсов не хватает
	 *
	 * @var DE_UnitsMoveCostFullValue
	 */
	static private $neededResource;
	
	static public function GetMoovUpkeep (DE_Comb $comb, DE_UnitsMoveCostFullValue $resources)
	{
		if(!self::_getResources($comb, $resources)) 
		{						
			self::_sendNotEnoughResourceMessage();
			
			return false;
		}
		
		return true;
	}
	
	static private function _sendNotEnoughResourceMessage()
	{
		$messparams = array();
		$messparams[0] = self::$neededResource->getShortPayList();
		DE_SystemMessages::AddMessageFromAction(__CLASS__, "MOOVE_UPKEEP_NOT_ENOUGH_RESOURCES", $messparams, 0, 'RED' );		
	}
			
	static private function _getResources(DE_Comb $comb, DE_UnitsMoveCostFullValue $resources) 
	{
		$combAttributes = $comb->getAttributes();
		
		self::$enoughResource = true;
		self::$neededResource = new DE_UnitsMoveCostFullValue();
		
		foreach(GB_ResourcesList::$unitsLiveUpkeepResources as $k=>$resourceName)
		{
			$needValue = (float)$resources->getPay($resourceName);
			
			$currentAmount = $combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName);
			
			if($currentAmount < $needValue)
			{
				self::$enoughResource = false;
				self::$neededResource->setPay($resourceName, $needValue - $currentAmount);
			}
		}
		
		if(!self::$enoughResource)
		{
			return false;
		}
		
		foreach(GB_ResourcesList::$unitsLiveUpkeepResources as $k=>$resourceName)
		{
			$needValue = (float)$resources->getPay($resourceName);			
			$combAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, -$needValue);		
		}
		
		return true;		
	}
}

?>