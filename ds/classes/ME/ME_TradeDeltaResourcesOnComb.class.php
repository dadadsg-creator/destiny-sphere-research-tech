<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Менеджер добавляет или снимает ресурсы с соты
 *
 */
class ME_TradeDeltaResourcesOnComb
{
	/**
	 * ОСновнйо метод собственно иреализующий весь функционал
	 *
	 * @param string $categoryName
	 * @param DE_Comb $combTo
	 * @param integer $articleID
	 * @param float $amount
	 * @param GB_Date $addDate
	 */
	
	static public function Exec($categoryName, DE_Comb $combTo, $articleID, $amount, GB_Date $addDate)
	{		
		assert(!empty($categoryName));
		assert(is_int($articleID) && $articleID > 0);
		assert(is_float($amount));
		
		switch ($categoryName)
		{
			case 'RESOURCE':
				self::_deltaResourse($combTo, $articleID, $amount);			
				break;
			case 'ARTEFACT':
				self::_deltaArtefact($combTo, $articleID, $amount);
				break;
			case 'UNIT':
				self::_deltaUnits($combTo, $articleID, $amount, $addDate);
				break;
			default:
				ds_error("Unknown category name = ".$categoryName);
		}
	}
	
	static private function _deltaResourse(DE_Comb $combTo, $articleID, $amount)
	{
		assert(is_int($articleID) && $articleID > 0);
		assert(is_float($amount));
		
		$attributeName = ST_Attributes::Create()->getAttributeNameByAttributeNameID($articleID);
		$combTo->getAttributes()->deltaAttribute('CURRENT_AMOUNT', $attributeName, $amount);
		
	}
	
	static private function _deltaArtefact(DE_Comb $combTo, $articleID, $amount)
	{
		assert(is_int($articleID) && $articleID > 0);
		assert($amount == 1 || $amount == -1);
		
		if($amount > 0)
		{
			DS_Artefact::AddArtefactToCombStore($combTo, $articleID);
		}else{
			DS_Artefact::DeleteArtefactFromCombStore($combTo, $articleID);
		}		
	}
	
	static private function _deltaUnits(DE_Comb $combTo, $articleID, $amount, GB_Date $addDate)
	{
		assert(is_int($articleID) && $articleID > 0);
		assert(is_float($amount) && $amount != 0);
				
		$unit = DE_Unit::CreateFromID($articleID);
		
		if($amount > 0)
		{
			ME_AddUnitsToHomeArmy::AddUnit($combTo, $unit, $amount, $addDate);
		}else{
			ME_AddUnitsToHomeArmy::GetUnit($combTo, $unit, -$amount);
		}
	}
}

?>