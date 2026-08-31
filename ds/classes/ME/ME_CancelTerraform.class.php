<?php $CVS_Version = '$Revision$ ; $Date$';
class ME_CancelTerraform
{
	static private $common;

	static public function Cancel($cellID)
	{
		assert(is_int($cellID) && $cellID > 0);
		
		// Получаем атрибуты здания по типу ячейки
		$res = DS_Building::GetAttachedBuildingByCellID($cellID);

		$combID = (int)$res[0]['id_comb'];
		$attachedBuildingID = (int)$res[0]['id_attachedbuilding'];

		// Ивент терраформинга
		$bindeqid = DS_Terraforming::HaveTerraformingOnCell($cellID);

		$eventOptions = DE_EventQueueAttributes::CreateFromEventID($bindeqid);
		$terraforminglD = (int)$eventOptions->getAttribute('ID_Terraforming');

		// Возвращаем селу предидущий тип грунта
		DS_Terraforming::SetOldGroundTypeOnCellByTerraformingID($terraforminglD, $cellID);
		
		// процент завершенности
		self::$common = DS_Common::CreateForCurentDB();
		$sql = sprintf ( "select getProgress(%d) as prog", $bindeqid );
		$res = self::$common->getResultArray ( "progress_id_eventqueue_".$bindeqid, $sql, "", "", "LOCAL_CACHE" );
		assert(empty(self::$common->errormsg));
		$prog = (int)$res[0]['prog'];
		
		$worldSettings = DE_WorldSettings::CreateForCurentWorld();
		$minlost = $worldSettings->get('MIN_RES_LOST_ON_CANCEL_BUILDING') * 100;

		if ( (int)$prog < (int)$minlost )
		{
			$prog = $minlost;
		}

		$devisor = $worldSettings->get('UPGRADE_COSTS_DIVISOR');

		// возвращаем часть ресурсов
		//Вычетаем ресусры за терраформинг
		$combAttributes = DE_CombAttributes::CreateFromCombID($combID);
		$terraformingAttributes = DE_TerraformingAttributes::CreateFromTerraformingID($terraforminglD);
		
		$attributeNames = $terraformingAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');						
		foreach ($attributeNames as $k => $resourceName)
		{
			if (!$combAttributes->haveAttribute('CURRENT_AMOUNT', $resourceName))
			{
				continue;
			}
			
			$value = $terraformingAttributes->getAttribute('PRODUCTION_COSTS', $resourceName);

			if($resourceName != 'WORKER')
			{
				$delta = round(($value-($value*$prog)/100)/$devisor);
			}else{
				$delta = round($value / $devisor);
			}
			$combAttributes->deltaAttribute('CURRENT_AMOUNT', $resourceName, $delta);		
		}	
		
		// прибиваем эвент				
		$event = DE_EventQueue::CreateFromID((int)$bindeqid);
		$event->setIsTrash(true);
		
		//андиндем ивент
		DS_Terraforming::UnBindEventQueue($cellID);
		
		//Разрешаем удаление здания
		DS_Building::InsertAttachedBuildingAttribute($attachedBuildingID, 'CURRENT_FLAG', 'DESTROYABLE');
		
		return true;

	}

}

?>