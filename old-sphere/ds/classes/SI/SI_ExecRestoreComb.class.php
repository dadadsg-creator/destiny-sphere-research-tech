<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_ExecRestoreComb extends SI_Base
{
	static private $newCombID;

	static private $worldID;

	static private $UIID;

	/**
	 * Enter description here...
	 *
	 * @var SI_ResultComb
	 */
	static private $combResult;

	static private $combCoords;

	static private $templateMode = false;

	static private $worldToData = array();

	static public function Exec($worldID, $UIID, $combCoords, SI_ResultComb $combResult)
	{
		assert((int)$worldID > 0);
		assert((int)$UIID >= 0);
		assert(is_array($combCoords));
		assert(key_exists('RING',$combCoords));
		assert(key_exists('COMPLEX',$combCoords));
		assert(key_exists('COMB',$combCoords));

		self::$worldID = (int)$worldID;
		self::$UIID = (int)$UIID;
		self::$combResult = $combResult;
		self::$combCoords = $combCoords;

		self::$worldToData = SI_SearchUserInstance::GetWorldData(self::$worldID);

		self::_out(' - START RESTORE COMB '.$combResult->getCombName());

		self::_assignComb();

		self::_restoreCellsAndBuildings();

		self::_restoreCombAttributes();

		self::_restoreArmys();

		self::_restoreArchStoreArts();

		self::_restoreTradeScopes();
		
		// Если сота захватываемая
		if ($combResult->getIsCapture() == 't') {
			SI_SearchComb::SetCombCapture(self::$newCombID);
			self::_restoreProtectComb();
		}
	}

	static public function SetTemplateMode($mode)
	{
		assert(is_bool($mode));

		self::$templateMode = $mode;
	}


	static private function _assignComb()
	{
		self::_out(' - Start comb placing');

		self::$newCombID = SI_SearchComb::GetCombIDByCoordinates(self::$worldID, (int)self::$combCoords['RING'], (int)self::$combCoords['COMPLEX'], (int)self::$combCoords['COMB']);

		//Занесли в словарик
		self::$idDictionary['ID_Comb'][self::$combResult->getCombID()] = self::$newCombID;

		//
		if(self::$templateMode)
		{
			return;
		}

		self::_testCombName();

		if(!SI_SearchComb::IsCombFree(self::$newCombID))
		{
			ds_error("Comb ".self::$combCoords['RING'].".".self::$combCoords['COMPLEX'].".".self::$combCoords['COMB']." not free.");
		}

		SI_SearchComb::AssignComb(self::$UIID, self::$combResult->getCombName(), self::$newCombID);
	}

	static private function _testCombName()
	{
		$combName = self::$combResult->getCombName();
		$newCombName = $combName;

		$counter = 0;

		while(!SI_SearchComb::IsCombNameUnic($newCombName))
		{
			$newCombName = $combName."_".GB_RandomPassword::GenerateOnlyNumbers(3);
			self::$combResult->setCombName($newCombName);
			self::_out(' - Comb new name = '.$newCombName);

			assert($counter < 10);
		}
	}

	static private function _restoreCellsAndBuildings()
	{
		self::_out(' - Start restore combs and buildings');
		$cellsData = self::$combResult->getCellData();
		foreach($cellsData as $k=>$cellData)
		{
			$cellAndBuildingID = SI_SearchComb::SetCellAndBuilding(self::$newCombID, $cellData);
		

			//Занесли в словарик
			self::$idDictionary['ID_Cell'][$cellData['id_cell']] = $cellAndBuildingID['CELL_ID'];
			self::$idDictionary['ID_AttachedBuilding'][$cellData['id_attachedbuilding']] = $cellAndBuildingID['ATTACHED_BUILDING_ID'];

			foreach($cellData['arts'] as $k=>$artefactData)
			{
				self::_attachArtefactsToBuilding($artefactData, $cellAndBuildingID);
			}

			foreach($cellData['attributes'] as $k=>$attributeID)
			{
				SI_SearchComb::InsertAttachedBuildingAttributes($cellAndBuildingID['ATTACHED_BUILDING_ID'], $attributeID);
			}

			//self::_out(" - - Cell: ".$cellData['celltypename'].":".$cellData['cellnumber']." Building: ".$cellData['buildingname']." Arts: ".count($cellData['arts']));
		}
	}

	static private function _attachArtefactsToBuilding($artefactData, $cellAndBuildingID)
	{
		//self::_out(' - Start attach artefacts to buildings');

		$artfeactInstanceID = SI_SearchComb::CreateArtInstance((int)$artefactData['id_artefact'], $artefactData['immortal'], $artefactData['birth_time'], $artefactData['death_time']);
		SI_SearchComb::AttachArtefactToBuilding($artfeactInstanceID, $cellAndBuildingID['ATTACHED_BUILDING_ID']);

		$artefactScopeName = SI_SearchComb::GetArtefactScope((int)$artefactData['id_artefact']);

		switch($artefactScopeName)
		{
			case 'COMB':
				SI_SearchComb::AttachArtefactToComb($artfeactInstanceID, $cellAndBuildingID['ATTACHED_BUILDING_ID'], self::$newCombID);
				break;
			case 'COMB_COMPLEX':
				$combData = SI_SearchComb::GetCombDataByID(self::$newCombID);
				SI_SearchComb::AttachArtefactToCombComplex($artfeactInstanceID, $cellAndBuildingID['ATTACHED_BUILDING_ID'], $combData['id_combcomplex']);
				break;
			case 'WAR':
				SI_SearchComb::AttachArtefactToComb($artfeactInstanceID, $cellAndBuildingID['ATTACHED_BUILDING_ID'], self::$newCombID);
				break;
		}

	}

	static private function _restoreCombAttributes()
	{
		self::_out(' - Start restore comb atrributes');

		$combAttributes = self::$combResult->getCombAttributes();

		foreach($combAttributes as $attributeTypeName => $attributes)
		{
			foreach($attributes as $attributeName => $value)
			{
				$attributeID = SI_SearchComb::GetCombAttributeIDBuTypeAndName(self::$newCombID, $attributeTypeName, $attributeName);

				if($attributeID != 0)
				{
					SI_SearchComb::UpdateCombAttribute($attributeID, $value);
				}
			}
		}
	}

	static private function _restoreArchStoreArts()
	{
		self::_out(' - Start restore arch arts');	
		
		$archStoreArtsData = self::$combResult->getArchArt();
		
		foreach($archStoreArtsData as $k=>$artefactData)
		{
			$artefactInstanceID = SI_SearchComb::CreateArtInstance((int)$artefactData['id_artefact'], $artefactData['immortal'], $artefactData['birth_time'], $artefactData['death_time']);
			SI_SearchComb::InsertArtInToArchStore(self::$newCombID, $artefactInstanceID);
		}
	}

	static private function _restoreArmys()
	{
		self::_out(' - Start restore armys');

		$armyData['id_unitcontainertype'] = 1;
		$armyData['moveable'] = 'f';
		$armyData['inaction'] = 'f';
		$armyData['unitcontainername'] = self::$combResult->getCombName()." {|HOME_ARMY|}";
		$armyData['isdefault'] = 't';

		$armyID = SI_SearchComb::InsertNewArmy(self::$newCombID, self::$UIID, $armyData);

		$unitsData = self::$combResult->getUnits();
		foreach($unitsData as $k=>$unitsGroupData)
		{
			//Проверяем если мир расса против рассы то фильруем юниты другой рассы
			if(self::$UIID != 0 && (self::$worldToData['worldtype'] == 'RACE_VS_RACE') && !SI_SearchComb::TestSameRaceOfUserAndUnit(self::$UIID, $unitsGroupData['id_unit']))
			{
				$comb = DE_Comb::CreateFromID(self::$newCombID);
				DS_LogUnitsDeath::InsertRecord(10, $comb, $comb->getUserInstance(), DE_Unit::CreateFromID((int)$unitsGroupData['id_unit']),
				GB_TypeFactory::CreateBase('GB_Date', time()), $unitsGroupData['amount'], $unitsGroupData['amount']);
				continue;
			}

			SI_SearchComb::InsertNewUnitsGroup($armyID, $unitsGroupData);
		}
	}

	static private function _restoreTradeScopes()
	{
		self::_out(' - Start restore tradescopes');

		$tradeScopes = 	self::$combResult->getTradeScopes();
		foreach($tradeScopes as $k=>$scopeID)
		{
			SI_SearchComb::InsertTradeScope(self::$newCombID, $scopeID);
		}
	}

	static private function _restoreProtectComb()
	{
		self::_out(' - Start restore protect capture colony');

		$protectedFieldArr = self::$combResult->getProtectField();
		
		$end_temporary_defend = $protectedFieldArr[0]['end_temporary_defend'];
		$protected_field = $protectedFieldArr[0]['protected_field'];
		
		SI_SearchComb::InsertProtectField(self::$newCombID, $protected_field, $end_temporary_defend);
	}
}
?>