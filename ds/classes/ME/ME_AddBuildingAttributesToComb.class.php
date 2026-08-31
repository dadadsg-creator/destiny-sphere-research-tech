<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_AddBuildingAttributesToComb
{
	static public function AddAttributes(DE_BuildingAttributes $buildingAttributes, DE_CombAttributes $combAttributes, $addType = 'plus')
	{
		assert($addType == 'minus' || $addType == 'plus');

		$syncAttributeTypes = array( 'TURN_PRODUCTION', 'MAX_AMOUNT', 'TURN_UPKEEP', 'STORAGE_FACTOR', 'STORAGE_FACTOR_ABSOLUT');
		foreach($syncAttributeTypes as $k1=>$attributeTypeName)
		{
			$atttributeNames = $buildingAttributes->getAttributeNamesByTypeName($attributeTypeName);

			foreach($atttributeNames as $k2=>$attributeName)
			{
				if(!$combAttributes->haveAttribute($attributeTypeName, $attributeName))
				{
					continue;
				}

				$delta = $buildingAttributes->getAttribute($attributeTypeName, $attributeName);

				switch ($addType)
				{
					case 'minus':
						$delta = -$delta;
						break;
					case 'plus':
						$delta = $delta;
						break;
					default:
						ds_error('Unknown addType = '.$addType);
				}

				switch($attributeTypeName)
				{
				/*	case 'STORAGE_FACTOR':
						if($addType == 'minus')
						{
							$shelterStorageFactor = DE_Building::CreateFromName('SHELTER')->getAttributes()->getAttribute('STORAGE_FACTOR', $attributeName);
							$combAttributes->setAttribute('STORAGE_FACTOR', $attributeName, $shelterStorageFactor);
						}else{
							$combAttributes->setAttribute('STORAGE_FACTOR', $attributeName, $delta);
						}
						break;
					
					case 'STORAGE_FACTOR_ABSOLUT':
						if($addType == 'minus')
						{
							$shelterStorageFactor = DE_Building::CreateFromName('SHELTER')->getAttributes()->getAttribute('STORAGE_FACTOR_ABSOLUT', $attributeName);
							$combAttributes->setAttribute('STORAGE_FACTOR_ABSOLUT', $attributeName, $shelterStorageFactor);
						}else{
							$combAttributes->setAttribute('STORAGE_FACTOR_ABSOLUT', $attributeName, $delta);
						}
						break;*/
						
					case 'MAX_AMOUNT':
					
						$combAttributes->deltaAttribute('MAX_AMOUNT', $attributeName, $delta);
						
						//Это для случая что бы обрезать текущее значение которая стсла больше максимального
						if($combAttributes->haveAttribute('CURRENT_AMOUNT', $attributeName) && $addType == 'minus')
						{
							if($combAttributes->getAttribute('CURRENT_AMOUNT', $attributeName) > $combAttributes->getAttribute('MAX_AMOUNT', $attributeName))
							{
								$combAttributes->setAttribute('CURRENT_AMOUNT', $attributeName, $combAttributes->getAttribute('MAX_AMOUNT', $attributeName));	
							}														
						}
						
						break;
					default:
						$combAttributes->deltaAttribute($attributeTypeName, $attributeName, $delta);
				}//END OF SWITCH
			}//END OF FOREACH
		}//END OF FOREACH
	}

	static public function AddArtefactAttributes($attachedBuildingID, DE_CombAttributes $combAttributes, $addType = 'plus')
	{
		assert($attachedBuildingID>0);
		assert($addType == 'minus' || $addType == 'plus');

		$bonuses = DS_Artefact::GetSummaryBonnusByAttachedBuildingID($attachedBuildingID);

		//Начисляем бонусы артефактов который в задние
		foreach($bonuses as $k=>$v)
		{
			if($combAttributes->haveAttribute($v['typename'], $v['attributename']))
			{
				switch ($addType)
				{
					case 'minus':
					$delta = -(float)$v['val'];
					break;
					case 'plus':
					$delta = (float)$v['val'];
					break;
					default:
					ds_error('Unknown addType = '.$addType);
				}
				$combAttributes->deltaAttribute($v['typename'], $v['attributename'], $delta);
			}
		}
	}
}


?>