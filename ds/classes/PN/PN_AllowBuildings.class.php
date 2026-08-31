<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2008/07/01 08:53:45 $';

// список доступных для строительства зданий

class PN_AllowBuildings extends UA_GetBuildMenu
{

	function __construct()
	{
		UA_GetBuildMenu::UA_GetBuildMenu ();
	}

	function getPanel ()
	{
		
//assert(false);
		global $params;
		global $common;

		if(!key_exists("allow_buildings", $_SESSION))
		{
			$_SESSION["allow_buildings"] = array();
		}

		// если список зданий для этой соты, типа ячейки и типа почвы
		// висит в сесии - просто вытаскиваем его оттуда
//unset($_SESSION["allow_buildings"][$this->comb][$this->celltype][$this->groundid]);
		if(isset($_SESSION["allow_buildings"][$this->comb][$this->celltype][$this->groundid]))
		{
			$xml = $this->_formXML($_SESSION["allow_buildings"][$this->comb][$this->celltype][$this->groundid]);
			SU_OutputHandler::Singleton()->addString($xml, 'xmldata');
			
		} else {

			$combObj = DE_Comb::CreateFromID((int)$this->comb);

			$where = "";
			// Cмотрим, является ли сота захватываемой
			if ($combObj->getIsCapture()) {
				$where = "
				AND b.id_building NOT IN
						(
                 			SELECT db.id_building
                 			FROM ds_disabledbuilding_colony db
                 			WHERE db.id_world = ".$_SESSION["world"]."
                 		)
                 		";
			}
				
				
			// если в сессии его нет
			// непосредственно сам список зданий			
			$sql = "
					SELECT
						b.isunique,
						b.id_building,

						/* resource value for sort */
						(
							SELECT bha.value
							FROM
								ds_buildinghasattribute bha,
								ds_attribute a,
								ds_attributename atn,
								ds_attributetype att
							WHERE
									bha.id_attribute = a.id_attribute
								AND a.id_attributename = atn.id_attributename
								AND a.id_attributetype = att.id_attributetype
								AND att.typename = 'PRODUCTION_COSTS'
								AND atn.attributename = 'CONSTRUCTION_TIME'
								AND bha.id_building = b.id_building
						) AS ttt
					FROM
							ds_building b,
							ds_buildingcanbuildbyrace bcbr,
							ds_buildingcanbebuildon bcbo
					WHERE
							b.id_building = bcbr.id_building
						AND b.id_building = bcbo.id_building
						AND bcbr.id_race = ".$_SESSION["race"]."
						AND bcbo.id_celltype = ".$this->celltype."
						AND bcbo.id_groundtype = ".$this->groundid."
						AND b.buildable = true

						/* buildings Вынесена в отдельный запрос так как возникаили просто невероятные глюки */
						/*AND
						(
							(
								SELECT COUNT(id_building_2)
								FROM
									ds_buildingneedsbuilding
								WHERE
									id_building_1 = b.id_building
							)
							=
							(
								SELECT COUNT(distinct id_building)
								FROM
									ds_buildingneedsbuilding bnb,
									ds_attachedbuilding ab
								WHERE
										bnb.id_building_2 = ab.id_building
									AND bnb.id_building_1 = b.id_building
									AND ab.id_comb = ".$this->comb."
									AND ab.active = true
							)
						) IS true */

						/* science */
						AND
						(
							(
								SELECT COUNT(sc.id_science)
								FROM
									ds_science sc,
									ds_buildingneedsscience bns
								WHERE
										sc.id_science = bns.id_science
									AND bns.id_building = b.id_building
							)
							=
							(
								SELECT COUNT(bns.id_science)
								FROM
									ds_buildingneedsscience bns,
									ds_userhasscience uhs,
									ds_userinstance ui
								WHERE
										bns.id_building = b.id_building
									AND bns.id_science = uhs.id_science
									AND uhs.id_userinstance = ui.id_userinstance
									AND ui.id_userinstance = ".$_SESSION["uiid"]."
									AND uhs.inresearch = false
							)
						) IS true


						AND b.id_building NOT IN
						(
                 			SELECT db.id_building
                 			FROM ds_disabledbuilding db
                 			WHERE db.id_world = ".$_SESSION["world"]."
                 		)
                 		$where
					ORDER BY ttt desc";					
			$resTmp = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));				

			$res = array();
			foreach ($resTmp as $k=>$row)
			{
				// Если здание Генератор защитного поля
				if ($row['id_building'] == 91) {
					// Cмотрим, является ли сота захватываемой
					if (!$combObj->getIsCapture()) {
						continue;
					}
				}
				
				//Запрос был вынесен в отдельный из-за глюков необъяснимых в осоатве оснвоного
				$sqlTmp = "
					SELECT ((
								SELECT COUNT(id_building_2)
								FROM
									ds_buildingneedsbuilding
								WHERE
									id_building_1 = ".$row['id_building']."
							)
							=
							(
								SELECT COUNT(distinct id_building)
								FROM
									ds_buildingneedsbuilding bnb,
									ds_attachedbuilding ab
								WHERE
										bnb.id_building_2 = ab.id_building
									AND bnb.id_building_1 = ".$row['id_building']."
									AND ab.id_comb = ".$this->comb."
									AND ab.active = true
							)) as b";
				$resTmp2 = $common->getResultArray("", $sqlTmp, "", "", "");
				assert(empty($common->errormsg));

				if($resTmp2[0]['b'] == 'f')
				{
					continue;
				}
				
				$res[] = $row;
			}
			
			//Делавем выборку лицензированных зданий для данного игрока
			$sql = "
				SELECT
      				b.id_building,
      				b.isunique,
      				(
						SELECT bha.value
						FROM
							ds_buildinghasattribute bha,
							ds_attribute a,
							ds_attributename atn,
							ds_attributetype att
						WHERE
							bha.id_attribute = a.id_attribute
							AND 
							a.id_attributename = atn.id_attributename
							AND 
							a.id_attributetype = att.id_attributetype
							AND 
							att.typename = 'PRODUCTION_COSTS'
							AND 
							atn.attributename = 'CONSTRUCTION_TIME'
							AND 
							bha.id_building = b.id_building
        			) AS ttt
				FROM
    				ds_buildingsalowedtouserinstance AS bau
    				INNER JOIN ds_building AS b ON (b.id_building = bau.id_building)
    				INNER JOIN ds_buildingcanbebuildon AS bcbo ON (b.id_building = bcbo.id_building)
				WHERE					
     				bau.id_userinstance = ".$this->uiid."
     				AND 
     				bcbo.id_celltype = ".$this->celltype."
					AND 
					bcbo.id_groundtype = ".$this->groundid."
				ORDER BY ttt desc";
			$resTmp = $common->getResultArray("", $sql, "", "", "");
			assert(empty($common->errormsg));
	
			foreach ($resTmp as $k=>$row)
			{
				// Если здание дом отдыха
				if ($row['id_building'] == 65) {
					// Cмотрим, не имеется ли оно  уже на одной из сот
						$sql =
						"SELECT
							b.id_building	
						FROM
							ds_comb c
							INNER JOIN ds_attachedbuilding attb ON (c.id_comb = attb.id_comb)
							INNER JOIN ds_building b ON (attb.id_building = b.id_building)
						WHERE
							c.id_userinstance = ".$this->uiid."
							AND
							b.id_building = 65";
					$vocBuilb = $common->getQueryArray($sql);
					assert(empty($common->errormsg));
					
					if (count($vocBuilb) > 0) {
						continue;
					}
				}
				
				if(!in_array($row, $res))
				{
					$res[] = $row;
				}
			}
			$_SESSION["allow_buildings"][$this->comb][$this->celltype][$this->groundid] = $res;

			SU_OutputHandler::Singleton()->addString($this->_formXML($res), 'xmldata');
		}

		return true;
	}

	private function _formXML($res)
	{
		global $params;
		
		$xml = '<?xml version="1.0" encoding="utf-8"'."?".">\n<getbuildmenu />";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();
		//$root->set_attribute ( "c", $this->cellnum );

		$root->set_attribute ( "turn", $this->worldSettings->get('TURN_TIME') * 60);
				
		$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);

		// сколько строек и апгдейдов в наличии
		$current = $combAttributes->getAttribute('CURRENT_AMOUNT', 'SIMILTANEOUS_BUILDS');

		// сколько строек и апгдейдов максимально
		$max = $combAttributes->getAttribute('MAX_AMOUNT', 'SIMILTANEOUS_BUILDS');

		// сколько строек и апгдейдов дополнительно ( для некоторых миров )
		$addon = $this->worldSettings->get('SIMILTANEOUS_BUILDS_ADDON');

		$max += $addon;

		for ( $i=0; $i<sizeof ( $res ); $i++ )
		{
			$disabledBuilding = false;

			$buildingID = (int)$res[$i]['id_building'];
			assert($buildingID > 0);

			// если здание уникальное, проверям его уникальность
			if ( $res[$i]['isunique'] == 't' )
			{
				if(!$this->checkUnique($buildingID, $this->comb))
				{
					continue;
				}
			}

			$ch = $root->new_child("b");
			$ch->set_attribute("id", $buildingID);

			$buildingAttributes = DE_BuildingAttributes::CreateFromBuildingID($buildingID);
			$attributeNames = $buildingAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');
//SU_DebugTestTools::LogAll('1' , 'SystemErrors.log');						

			foreach ($attributeNames as $k => $resourceName)
			{
				/*if ($resourceName == 'SILVER_CREDIT' OR $resourceName == 'GOLD_CREDIT')
				{
					//Смотрим модифаер
					$worldSettings = DE_WorldSettings::CreateForCurentWorld();
					$modifier = $worldSettings->get('BUILDING_CAN_BUIL_BY_MONEY');

					//Проверяем модифаер
					assert($modifier == 1 || $modifier == 0);

					//Если в настройках мира у нас стоит запрет на постройку зданий за деньги
					if ($modifier == 0)
					{
						continue;
					}
					
					if ($resourceName == 'SILVER_CREDIT')
					{
						$currentMoneyAccount = $money['sc_amount'];
					} elseif ($resourceName == 'GOLD_CREDIT') 
					{
						$currentMoneyAccount = $money['gc_amount'];
					}
					
					//Устанавливаем значения количества требуемых денег для постройки здания
					if ($resourceName == 'SILVER_CREDIT')
					{
						$ch->set_attribute("SC", $buildingAttributes->getAttribute('PRODUCTION_COSTS', $resourceName));
					} elseif ($resourceName == 'GOLD_CREDIT') 
					{
						$ch->set_attribute("GC", $buildingAttributes->getAttribute('PRODUCTION_COSTS', $resourceName));
					}
					
					//Получаем сведения об акаунте данного инстанса
					$userInstance = DE_UserInstance::CreateFromID($this->uiid);
					$member = MU_EntityOperationMember::CreateFromUserInstance($userInstance);
					$money = MU_SearchOperations::GetAccountData($member);
										
					//Проверяем, чтобы у юзверя был денежный аккаунт
					assert(!empty($money));
					
					//Если аккаунт неактивный, то здание строить незьзя
					if (!$money['IS_ACTIVE'])
					{
						$disabledBuilding = true;
						continue;
					}				
					
					if ($resourceName == 'SILVER_CREDIT')
					{
						$currentMoneyAccount = $money['sc_amount'];
					} elseif ($resourceName == 'GOLD_CREDIT') 
					{
						$currentMoneyAccount = $money['gc_amount'];
					}
//$currentMoneyAccount = 3;					
					//Если количество денет, тредбуемое для постройки здания больше текущего количества денег
					if($buildingAttributes->getAttribute('PRODUCTION_COSTS', $resourceName) > $currentMoneyAccount)
					{
						$disabledBuilding = true;
						continue;
					}
				}*/
				
				if (!$combAttributes->haveAttribute('CURRENT_AMOUNT', $resourceName))
				{
					continue;
				}		

				if($buildingAttributes->getAttribute('PRODUCTION_COSTS', $resourceName) > $combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName))
				{
					$disabledBuilding = true;
					continue;
				}
			}

			// если кол-во строек >= максимального кол-ва - ставим флаг что недоступно

			if($current >= $max)
			{
				$disabledBuilding = true;
			}

			if($disabledBuilding)
			{
				$ch->set_attribute ( "s", "0" );
			}
		}
		
		$this->getBuildingTabs($root, 0, $params["lang"], 'PN_AllowBuildings');
		
		$out = $doc->dump_mem();
		return $out;
	}
}
?>