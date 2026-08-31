<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_TradeCheckNeedAmountOfGood
{
	static public function Exec(DE_Comb $comb, $tradeCategoryID, $articleID, $needAmount)
	{
		assert(is_int($tradeCategoryID) && $tradeCategoryID > 0);
		assert(is_int($articleID) && $articleID > 0);
		assert(is_float($needAmount) && $needAmount > 0);
		
		$tradeCategoryName = DS_Trade::GetTradeCategoryNameByID($tradeCategoryID);
		
		$combHasResource = 0;
		
		switch($tradeCategoryName)
		{
			case 'RESOURCE':
				$attributeName =  ST_Attributes::Create()->getAttributeNameByAttributeNameID($articleID);
				$combHasResource = $comb->getAttributes()->getAttribute('CURRENT_AMOUNT', $attributeName);
				break;
			case 'UNIT':				
				$unitGroupData = DS_Army::GetUnitsAmountInHomeArmyByCombAndUnit($comb, $articleID);

				if(count($unitGroupData) == 0)
				{
					$combHasResource = 0;
				}else{
					assert(key_exists('amount', $unitGroupData));
					$combHasResource = (float)$unitGroupData['amount'];
				}							
				break;
			case 'ARTEFACT':							
				$combHasResource = DS_Artefact::GetArtefactsAmountOnCombStore($comb, $articleID);							
				break;				
			case 'MONEY':			
				$member = new MU_EntityOperationMember(
					$comb->getUserInstance()->getUser(), 
					DE_WorldFromCollection::CreateFromID(DE_World::CreateForCurrentWorld()->getShortName())
				);
				$moneyData = MU_SearchOperations::GetAccountData($member);								
				
				$money = DE_Money::CreateFromID($articleID);				
				$combHasResource = $moneyData[$money->getShortName()];				
								
				break;
			default:
				ds_error('Unknown TradeCategoryName = '.$tradeCategoryName);
		}
		
		if($combHasResource < $needAmount)
		{
			return false;
		}else{
			return true;
		}	
	}
}


?>