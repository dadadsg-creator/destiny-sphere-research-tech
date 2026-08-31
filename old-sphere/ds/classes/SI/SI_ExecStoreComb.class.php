<?php $CVS_Version = '$Revision$ ; $Date$';
class SI_ExecStoreComb extends SI_Base
{		
	static public function Exec($combID, $tempColony = false)
	{			
		//Создаем объект для сохранени якомба
		$combResult = new SI_ResultComb($combID);
		
		self::_out(' - START STORE COMB'.$combResult->getCombName());	
			
		//ВСтавляем сведения по целам и зданиям
		self::_storeCellsAndBuildings($combResult);				
		
		//Вставляем арты в хранилиже архиологов
		self::_storeArhiologistArtefacts($combResult);
		
		//Сохраняем армии
		self::_storeArmys($combResult);
		
		//Сохранить атрибьуиты соты
		self::_storeCombAttributes($combResult);
		
		self::_storeTradeScopes($combResult);	
		
		// Если сота колония должна стать временной
		if ($tempColony) {
			$combResult->setIsCapture();
			$combResult->setProtectField();
		}
// Логируем на всякий случай
SU_DebugTools::LogAll($combResult, 'MassMoveCombs.log');		
		return 	$combResult;
	}	
	
	static private function _storeCellsAndBuildings(SI_ResultComb $combResult)
	{		
		self::_out(' - Start store cells and buildings');
		$combCells = SI_SearchComb::GetCombCellsAndBuildings($combResult->getCombID());	
			
		foreach($combCells as $k=>$cellData)
		{			
			$buildingArtefacts = SI_SearchComb::GetBuildingArtefacts($cellData['id_attachedbuilding']);	
			$buildingAttributes = SI_SearchComb::GetAttachedBuildingAttributes($cellData['id_attachedbuilding']);
			$combResult->setCell($cellData, $buildingArtefacts, $buildingAttributes);
			
			// логируем артефакты на соте
			SI_SearchComb::LogArtefactData($cellData, $buildingArtefacts);
			//self::_out(" - - Cell: ".$cellData['celltypename'].":".$cellData['cellnumber']." Building: ".$cellData['buildingname']." Arts: ".count($buildingArtefacts));
		}								
	}
	
	static private function _storeCombAttributes(SI_ResultComb $combResult)
	{
		self::_out(' - Start store comb attributes');
		$combAttributesData = SI_SearchComb::GetCombAttributes($combResult->getCombID());
		
		foreach($combAttributesData as $k=>$attributeData)
		{
			$combResult->setCombAttribute($attributeData['typename'], $attributeData['attributename'], $attributeData['value']);			
		}
	}	
	
	static private function _storeArhiologistArtefacts(SI_ResultComb $combResult)
	{
		self::_out(' - Start store arhiologists');
		
		$arts = SI_SearchComb::GetArchiologyArtefacts($combResult->getCombID());
		
		foreach($arts as $k=>$artData)
		{
			$combResult->setArchArt($artData);
		}
	}
	
	static private function _storeArmys(SI_ResultComb $combResult)
	{
		self::_out(' - Start store armys');
		
		$unitsData = SI_SearchComb::GetCombUnits($combResult->getCombID());
		
		$combResult->setUnits($unitsData);				
	}
	
	static private function _storeTradeScopes(SI_ResultComb $combResult)
	{
		self::_out(' - Start store TradeScopes');
		
		$tradeScopes = SI_SearchComb::GetCombTradeScopes($combResult->getCombID());
		
		foreach($tradeScopes as $k=>$tradeScope)
		{
			// Если это альянсовый скоуп, не сохраняем его
			if ($tradeScope['id_tradescope'] == 4) {
				continue;
			}
			$combResult->setTradeScope($tradeScope['id_tradescope']);
		}
		
	}
}


?>