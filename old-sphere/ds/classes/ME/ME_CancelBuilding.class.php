<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_CancelBuilding
{
	static private $common;


	static public function Cancel($attachedBuildingID)
	{
		self::$common = DS_Common::CreateForCurentDB();

		$worldSettings = DE_WorldSettings::CreateForCurentWorld();

		$sql ="SELECT * FROM ds_attachedbuilding WHERE id_attachedbuilding = ".$attachedBuildingID;
		$res = self::$common->getResultArray ( "", $sql, "", "", "" );
		assert(empty(self::$common->errormsg));

		assert(sizeof($res) > 0);

		$combID = $res[0]['id_comb'];
		$buildID = $res[0]['id_building'];

		$combAttributes = DE_CombAttributes::CreateFromCombID((int)$combID);

		// эвент строительства
		$sql = sprintf ( "select ab.ID_AttachedBuilding,
				abbeq.ID_EventQueue
				from DS_AttachedBuilding ab,
				DS_AttachedBuildingBindEventQueue abbeq,
				DS_EventQueue eq,
				DS_Event e
				where ab.ID_AttachedBuilding = abbeq.ID_AttachedBuilding
				and abbeq.ID_EventQueue = eq.ID_EventQueue
				and eq.ID_Event = e.ID_Event
				and eq.isTrash = false
				and e.EventName = 'CONSTRUCT_BUILDING'
				and ab.ID_AttachedBuilding = %d",
				$attachedBuildingID);

		$res = self::$common->getResultArray ( "", $sql, "", "", "" );
		assert(empty(self::$common->errormsg));

		assert(sizeof($res) > 0);

		$bindeqid = $res[0]['id_eventqueue'];		

		// сносим здание
		$sql = sprintf ( "delete from DS_AttachedBuilding
				where ID_AttachedBuilding = %d",
				$attachedBuildingID );
		$res = self::$common->executeQuery ( $sql );
		assert(empty(self::$common->errormsg));

		// узнаем процент завершенности
		$sql = sprintf ( "select getProgress(%d) as prog", $bindeqid );

		$res = self::$common->getResultArray ( "", $sql, "", "", "" );
		assert(empty(self::$common->errormsg));

		$prog = (int)$res[0]['prog'];

		$minlost = $worldSettings->get('MIN_RES_LOST_ON_CANCEL_BUILDING')*100;

		if ( (int)$prog < (int)$minlost )
		{
			$prog = $minlost;
		}


		//Возвращяем потраченные ресусрсы
		$buildingAttributes = DE_BuildingAttributes::CreateFromBuildingID((int)$buildID);


		$attributeNames = $buildingAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');

		foreach($attributeNames as $k=>$attributeName)
		{
			if($attributeName == 'CONSTRUCTION_TIME')
			{
				continue;
			}
			
			if ($attributeName == 'SILVER_CREDIT' OR $attributeName == 'GOLD_CREDIT')
			{
				//Здесь должен быть возврат денег
				continue;
			}

			if($attributeName == 'WORKER')
			{
				// возвращаем всех потраченных рабочих
				$max = $combAttributes->getAttribute('MAX_AMOUNT', $attributeName);
				$cur = $combAttributes->getAttribute('CURRENT_AMOUNT', $attributeName);
				$returnValue = $buildingAttributes->getAttribute('PRODUCTION_COSTS', $attributeName);

				if (($cur + $returnValue) > $max)
				{
					$returnValue = $max - $cur;
				}

			}else{
				//часть потраченных ресурсов
				$cost = $buildingAttributes->getAttribute('PRODUCTION_COSTS', $attributeName);
				$returnValue = (float)ceil($cost - ($cost * $prog)/100);
			}


			$combAttributes->deltaAttribute('CURRENT_AMOUNT', $attributeName, $returnValue);
		}

		// прибиваем эвент				
		$event = DE_EventQueue::CreateFromID((int)$bindeqid);
		$event->setIsTrash(true);
		
		return true;
	}


}

?>