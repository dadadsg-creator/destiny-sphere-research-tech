<?php $CVS_Version = '$Revision$ ; $Date$';
// закладка информации о здании
class PN_BuildingInfo extends UA_GetBuildMenu
{
	private $combAttributes;

	function __construct()
	{
		UA_GetBuildMenu::UA_GetBuildMenu ();
		$this->combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);
	}

	function getPanel ()
	{
		global $params;
		global $common;

		// корректировка времени
		$delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();

		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><getbuildinfo />";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();
		//$root->set_attribute ( "id", $this->bid );
		//$root->set_attribute ( "c", $this->cellnum );

		$destr = 0;
		$deact = 0;
		$activate = 0;
		$upgrade = 0;
		$downgrade = 0;
		$inactivation = 0;
		$inupgrade = 0;
		$busy = 0;
		$underconstruction = 0;
		$active = 0;
		$onlyasupgrade = 0;
		$inupgrade = 0;
		$upgradable = 0;
		// Время завершения строительства или апгрейда (для платного сервиса)
		$leftTime = 0;

		// что сейчас происходить со зданием ( строится, активируется и т.д )
		$sql = sprintf ( "select
				ab.active,
				ab.inactivation,
				ab.busy,
				ab.inupgrade,
				ab.underconstruction,
				b.onlyasupgrade,
				b.buildingname
				from ds_attachedbuilding ab,
				ds_building b
				where ab.id_building = b.id_building
				and ab.id_attachedbuilding =%d",
		$this->attachedbid);
		$res = $common->getResultArray ( "", $sql, "", "", "");
		if ( !$res )
		{
			$this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::getBuildingInfo1";
			return false;
		}

		if( $res[0]['inupgrade'] == 't')
		{
			$inupgrade = 1;
		}

		if ( $res[0]['inactivation'] == 't' )
		{
			$inactivation = 1;
		}

		if ( $res[0]['busy'] == 't' )
		{
			$busy = 1;
		}

		if ( $res[0]['underconstruction'] == 't' )
		{
			$underconstruction = 1;
		}

		if ( $res[0]['active'] == 't' )
		{
			$active = 1;
		}

		if ( $res[0]['busy'] == 't' )
		{
			$busy = 1;
		}

		if ( $res[0]['onlyasupgrade'] == 't' )
		{
			$onlyasupgrade = 1;
		}

		// если идет процесс, определяем время окончания и процент завершенности
		if ( $underconstruction == 1 || $inactivation == 1 || $inupgrade == 1 )
		{
			$sql = sprintf ( "
					select 
						(eq.eventendtime <= now() ) as finished,
						eq.eventendtime as enddate,
						(extract(epoch from (now()-eq.eventstarttime))/extract(epoch from (eq.eventendtime-eq.eventstarttime)))*100 as prog,
						eq.eventstarttime as a,
						eq.eventendtime as b,
						extract(epoch from (eq.eventendtime-now())) as lefttime,
						now() as now
					from 
						ds_eventqueue eq,
						ds_attachedbuildingbindeventqueue abbeq
					where 
						eq.id_eventqueue = abbeq.id_eventqueue
						AND 
						abbeq.id_attachedbuilding = %d
						AND
						eq.istrash = false",					
			$this->attachedbid );				
			
			$res2 = $common->getResultArray ( "finished_enddate", $sql, "", "", "" );
			if ( $res2 === false )
			{
				$this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::getBuildingInfo2";
				return false;
			}
			
			if(count($res2) == 0)
			{
				$res2[0]['finished'] = 'f';
			}


			// если уже закончено, но еще не делался рефреш
			if ( $res2[0]['finished'] == 't' )
			{
				$sql = sprintf ( "select ltl.text
						from ds_localizedtextlanguage ltl,
						ds_systemmessage sm
						where ltl.id_localizedtext = sm.id_localizedtext
						and ltl.id_language = %d
						and sm.messagename = 'FINISHED'",
				$params['lang'] );

				$res3 = $common->getResultArray ( "system_message_finished_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
				if ( $res3 === false )
				{
					$this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::getBuildingInfo3";
					return false;
				}

				$root->set_attribute ( "enddate", $res3[0]['text'] );
			} else {
				if (key_exists('enddate', $res2[0]))
				{
					$date = GB_TypeFactory::CreateBase('GB_Date', $res2[0]['enddate']);
					$root->set_attribute ( "enddate", $date->getFormatedFull($delta));
				} else {
					$root->set_attribute ( "enddate", 0);
				}
				
				// Сколько турнов еще будет строиться(для платной услуги)
				$leftTime = ceil($res2[0]['lefttime']/60/DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME'));
			}

			if(key_exists(0, $res2))
			{
				if (key_exists('prog',$res2[0]))
				{
					$val = number_format($res2[0]['prog'], 0, "", "");
				} else {
					$val = 0;

				}
			}else{
				$val = 0;
			}

			if($val < 0)
			{
				$val = 0;
			}

			if ( $underconstruction == 1 )
			{
				$root->set_attribute ( "un", $val );
			}

			if ( $inactivation == 1 )
			{
				$root->set_attribute ( "ia", $val );
			}

			if ( $inupgrade == 1 )
			{
				$root->set_attribute ( "up", $val );
			}

			if( $active != 1 )
			{
				$root->set_attribute ( "ac", 0 );
			}
		}

		// если здание изначально можно деактивировать
		$sql = sprintf ( "
				select 1 as deact
				from ds_attachedbuilding ab,
				ds_attachedbuildinghasattribute atbha,
				ds_attribute a,
				ds_attributename atn,
				ds_attributetype att
				where ab.id_attachedbuilding = atbha.id_attachedbuilding
				and atbha.id_attribute = a.id_attribute
				and a.id_attributename = atn.id_attributename
				and a.id_attributetype = att.id_attributetype
				and att.typename = 'CURRENT_FLAG'
				and atn.attributename = 'DEACTIVEABLE'
				and ab.id_attachedbuilding = %d",
		$this->attachedbid );

		$res = $common->getResultArray ( "is_deactiveable_id_attachedbuilding_".$this->attachedbid, $sql, "", "", "GLOBAL_CACHE" );
		if ( $res === false )
		{
			$this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::getBuildingInfo2";
			return false;
		}

		// если да и оно активно - ставим отметку, что здание можно деактивировать
		if ( count($res) > 0 && $active == 1 )
		{
			$deact = 1;
		}

		// изначально можно снести
		$sql = sprintf ("select 1 as destr
				from ds_attachedbuilding ab,
				ds_attachedbuildinghasattribute atbha,
				ds_attribute a,
				ds_attributename atn,
				ds_attributetype att
				where ab.id_attachedbuilding = atbha.id_attachedbuilding
				and atbha.id_attribute = a.id_attribute
				and a.id_attributename = atn.id_attributename
				and a.id_attributetype = att.id_attributetype
				and att.typename = 'CURRENT_FLAG'
				and atn.attributename = 'DESTROYABLE'
				and ab.id_attachedbuilding = %d",
		$this->attachedbid );
		$res = $common->getResultArray ( "is_destroyable_id_attachedbuilding_".$this->attachedbid, $sql, "", "", "GLOBAL_CACHE" );
		if ( $res === false )
		{
			$this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::getBuildingInfo3";
			return false;
		}

		// если да, ставим отметку что можно снести
		if(count($res) > 0)
		{
			$destr = 1;
		}

		if($active != 1)
		{
			// если здание не активно
			if($onlyasupgrade != 1)
			{
				// если его можно строить
				// проверяем есть ли необходимые для него здания
				$sql = sprintf ( "select
						(select count(id_building_2)
						from ds_buildingneedsbuilding
						where id_building_1 = %d) =
						(select count(distinct ab.id_building)
						from ds_buildingneedsbuilding bnb,
						ds_attachedbuilding ab
						where bnb.id_building_2 = ab.id_building
						and bnb.id_building_1 = %d
						and ab.id_comb = %d
						and ab.active = true) as bnb",
				$this->bid,
				$this->bid,
				$this->comb );

				$res = $common->getResultArray ( "isneedsbuildingpresent_id_comb_id_building_".$this->comb."_".$this->bid, $sql, "", $params["uiid"], "LOCAL_CACHE" );
				if(!$res)
				{
					$this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::getBuildingInfo4";
					return false;
				}

				if($res[0]['bnb'] == 't')
				{
					$bnb = 1;
				}
			} else {
				$bnb = 1;
			}

			if(isset($bnb) && $inactivation != 1 && $underconstruction != 1 )
			{
				$activate = 1; // можно активировать
			}
		}

		if ( $active == 1 )
		{
			$combObj = DE_Comb::CreateFromID((int)$this->comb);

			$where = "";
			$cache = "";
			// Cмотрим, является ли сота захватываемой
			if ($combObj->getIsCapture()) {
				$where = "
				AND NOT EXISTS
						(
							SELECT *
							FROM ds_disabledbuilding_colony
							WHERE 
								id_world = ".$_SESSION['world']."
								AND
								id_building = bcbu.id_to_building							
						)
                 		";
				$cache = "colony";
			}
			
			// если здание активно
			// может ли быть апгрейжено
			$sql = sprintf ( "
					SELECT id_to_building						
					FROM 
						ds_buildingcanbeupgraded AS bcbu,
						ds_buildingcanbuildbyrace AS bcbr
					WHERE 
						bcbu.id_to_building = bcbr.id_building
						AND
						id_from_building = %d
						AND 
						bcbr.id_race = %d
						AND NOT EXISTS
						(
							SELECT *
							FROM ds_disabledbuilding
							WHERE 
								id_world = ".$_SESSION['world']."
								AND
								id_building = bcbu.id_to_building							
						)
						$where",
			$this->bid,
			$params["race"] );
			$res = $common->getResultArray( "id_to_building_id_building_id_race_".$cache.$this->bid."_".$params["race"]."_".$_SESSION['world'], $sql, "", "FULL_PREFIX", "GLOBAL_CACHE");
			if ( $res === false )
			{
				$this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::getBuildingInfo5";
				return false;
			}

			// Можно ли здание апгрейлить в принципе
			$upgradable = 0;
			// Хватает ли ресов и наук для апгрейда здания
			$upgrade = 0;

			if ( sizeof ( $res ) > 0 )
			{
				// если может
				$id_building_to = (int)$res[0]['id_to_building'];

				// Ставим пометку, что здание можно апгрейдить
				$upgradable = 1;

				// текущее кол-во строек и апгрейдов
				$current = $this->combAttributes->getAttribute('CURRENT_AMOUNT', 'SIMILTANEOUS_BUILDS');

				// максимальное кол-во строек и апгрейдов
				$max = $this->combAttributes->getAttribute('MAX_AMOUNT', 'SIMILTANEOUS_BUILDS');

				// строек и апгрейдов дополнительно
				$addon = $this->worldSettings->get('SIMILTANEOUS_BUILDS_ADDON');

				$max += $addon;

				if (  $current < $max )
				{
					// если еще можно строить или апгрейдить
					// хватает ли ресурсов, науки и зданий что-бы сделать апгрейд
					$sql = sprintf ( "select isNeedsBuildingPresent_2 ( %d, %d ) as allowbuild,
							isResourcesEnoughToUpgrade(%d, %d, %d) as allowres,
							isScienceEnough ( %d, %d ) as allowscience",
					$this->comb,
					$id_building_to,
					$this->world,
					$this->comb,
					$id_building_to,
					$params["uiid"],
					$id_building_to );

					$res = $common->getResultArray ( "is_enougn_id_userinstance_id_building_".$params["uiid"]."_".$id_building_to, $sql, "", $params["uiid"], "LOCAL_CACHE" );

					if ( $res === false )
					{
						$this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::getBuildingInfo9";
					}

					if ( sizeof ( $res ) > 0 )
					{
						// если хватает - ставим отметку, что можно апгрейдить
						if ( $res[0]['allowbuild'] == 1 && $res[0]['allowscience'] == 1 && $res[0]['allowres'] == 1)
						{
							$upgrade = 1;
						}
					}
				}
			}
		}

		if($upgradable == 1 && $inupgrade != 1)
		{
			$root->set_attribute("upgrade", "1");

			$ch = $root->new_child("upgradedata");

			$ch->set_attribute('allow', $upgrade);

			$buildingToAttributes = DE_BuildingAttributes::CreateFromBuildingID($id_building_to);

			$attributeNames = $buildingToAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');

			//<upgradedata kad="1" nano="2" stat="3" food="4" prod="5" time="12" work="45" id_next_build="32" />

			$attrToXml = array(
			'KADERIUM' => 'kad',
			'NANO_CRYSTAL' => 'nano',
			'STASIS_ENERGY' => 'stat',
			'FOOD' => 'food',
			'PRODIUM' => 'prod',
			'WORKER' => 'work',
			'CONSTRUCTION_TIME' => 'time',
			);

			foreach($attrToXml as $attributeName => $attributeNameXML)
			{
				if($buildingToAttributes->haveAttribute('PRODUCTION_COSTS', $attributeName))
				{
					$val = $buildingToAttributes->getAttribute('PRODUCTION_COSTS', $attributeName) / DE_WorldSettings::CreateForCurentWorld()->get('UPGRADE_COSTS_DIVISOR');
				} else {
					$val = 0;
				}

				if($attributeName == 'CONSTRUCTION_TIME')
				{
					$val = $val * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
					$period = GB_TypeFactory::CreateBase('GB_TimePeriod', $val);
					$val = $period->getDSFormat();
				}

				$ch->set_attribute($attributeNameXML, urlencode($val));
			}

			$ch->set_attribute('id_next_build', $id_building_to);

			// Выбираем ТРЕБУЮЩИЕСЯ уровни наук для апгрейда здания
			$scienceToXml = array(
			'KADERIUM' => 'kad',
			'NANO_CRYSTAL' => 'nano',
			'STASIS_ENERGY' => 'stat',
			'FOOD' => 'food',
			'PRODIUM' => 'prod',
			'TRADE' => 'trade',
			'SPYING' => 'spying',
			);


			$sctarg = "";
			$comma = "";
			foreach($scienceToXml as $scienceName => $scienceNameXML)
			{
				$sctarg .= $comma.DS_Building::GetScienceLevelForUpgradeBuilding($scienceName, $id_building_to);
				$comma = ',';
			}

			$ch->set_attribute('sctarg', $sctarg);

			// Выбираем текущие уровни наук
			$sccur = "";
			$comma = "";

			foreach($scienceToXml as $scienceName => $scienceNameXML)
			{
				$sccur .= $comma.DS_Science::GetScienceBranchLevel($scienceName, $params["uiid"]);
				$comma = ',';
			}

			$ch->set_attribute('sccur', $sccur);
		}

		if ( $inupgrade != 1 && $inactivation != 1 && $destr == 1 && $busy != 1 ) $root->set_attribute ( "destr", "1" );
		if ( $inupgrade != 1 && $inactivation != 1 && $deact == 1 && $busy != 1 ) $root->set_attribute ( "deact", "1" );
		if ( $deact != 1 && $inactivation != 1 && $activate == 1 ) $root->set_attribute ( "activate", "1" );
		if ($activate == 1) $root->set_attribute("ac", "0");

		$root->set_attribute ( "bid", $this->bid );

		//Тип грунта ячейки
		$sqlc = "SELECT
					gt.groundname
				 FROM
   					ds_cell as c
					INNER JOIN ds_groundtype as gt on (gt.id_groundtype = c.id_groundtype)
				 WHERE c.id_cell = ".$this->id_cell."";
		$res = $common->getResultArray ( "GET_CELL_GROUND_TYPE_FOR_ALLOW_BUILDINGS_".$this->id_cell, $sqlc, "", "", "GLOBAL_CACHE");
		assert(empty($common->errormsg));

		$root->set_attribute ( "gt", substr($res[0]['groundname'], 0, 1));
		// закладки

		$this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab );

		// потребление здания
		$sql = sprintf ( "select a.id_attribute,
				atn.attributename as attname,
				bha.value
				from ds_building b,
				ds_buildinghasattribute bha,
				ds_attribute a,
				ds_attributetype att,
				ds_attributename atn
				where b.id_building = bha.id_building
				and b.id_building = %d
				and bha.id_attribute = a.id_attribute
				and a.id_attributename = atn.id_attributename
				and a.id_attributetype = att.id_attributetype
				and att.typename = 'TURN_UPKEEP'",
		$this->bid );

		$res = $common->getResultArray ( "building_upkeep_id_building_".$this->bid, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( $res === false )
		{
			$this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::getBuildingInfo10";
		}

		$ch = $root->new_child ( "tu" );

		for  ( $i=0; $i<sizeof ( $res ); $i++ )
		{
			$ch->set_attribute ( substr ( $res[$i]['attname'], 0, 1), number_format ( $res[$i]['value'], 0, ".", " " ) );
		}

		// производство здания
		$sql = sprintf ( "select atn.id_attributename,
				atn.attributename as attname,
				bha.value
				from ds_building b,
				ds_buildinghasattribute bha,
				ds_attribute a,
				ds_attributetype att,
				ds_attributename atn
				where b.id_building = bha.id_building
				and b.id_building = %d
				and bha.id_attribute = a.id_attribute
				and a.id_attributename = atn.id_attributename
				and a.id_attributetype = att.id_attributetype
				and att.typename = 'TURN_PRODUCTION'",
		$this->bid );

		$res = $common->getResultArray ( "building_production_id_building_".$this->bid, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( $res === false )
		{
			$this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::getBuildingInfo11";
		}

		// бонусы от артефактов
		$sql = sprintf ( "select atn2.id_attributename,
				sum(ahb.value) as bonus
				from ds_artefactattachedbuilding aab,
				ds_artefact ar,
				ds_artefactinstance ai,
				ds_artefacthasbonus ahb,
				ds_attribute a2,
				ds_attributename atn2
				where aab.id_artefact = ai.id_artefactinstance
				and ai.id_artefact = ar.id_artefact
				and ahb.id_artefact = ar.id_artefact
				and aab.id_attachedbuilding = %d
				and ahb.id_attribute = a2.id_attribute
				and a2.id_attributename = atn2.id_attributename
				group by atn2.id_attributename",
		$this->attachedbid );

		$res2 = $common->getResultArray ( "attachedbuilding_bonus_id_attachedbuilding_".$this->attachedbid, $sql, "", $params["uiid"], "LOCAL_CACHE" );
		if ( $res2 === false )
		{
			$this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::getBuildingInfo12";
		}

		for ( $i=0; $i<sizeof ( $res2 ); $i++ )
		{
			$bonus[$res2[$i]['id_attributename']] = $res2[$i]['bonus'];
		}

		$ch = $root->new_child ( "tp" );

		if(!isset($bonus))
		{
			$bonus = array();
		}

		if(count($res)>0)
		{
			for  ( $i=0; $i<sizeof ( $res ); $i++ )
			{

				$v = $res[$i]['value'];
				if(key_exists($i, $res2))
				{
					if(key_exists($res2[$i]['id_attributename'], $bonus))
					{
						$v .= "%2B".$bonus[$res2[$i]['id_attributename']];
					}
				}


				$ch->set_attribute ( substr ( $res[$i]['attname'], 0, 1 ), urlencode ( $v ) );
			}
		}


		// где может быть построено
		$sql = sprintf ( "select c.CellTypeName,
				g.GroundName
				from DS_CellType c,
				DS_GroundType g,
				DS_Building b,
				DS_BuildingCanBeBuildOn bcbo
				where b.ID_Building = bcbo.ID_Building
				and bcbo.ID_CellType = c.ID_CellType
				and bcbo.ID_GroundType = g.ID_GroundType
				and b.ID_Building = %d
				order by g.id_groundtype",
		$this->bid );

		$res = $common->getResultArray ( "celltypename_groundname_id_building_".$this->bid, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		if ( $res === false )
		{
			$this->errormsg .= " SYSTEM ERROR UA_GetBuildMenu::getBuildingInfo13";
			return false;
		}

		for ( $i=0; $i<sizeof ( $res ); $i++ )
		{
			$bo = $root->new_child ( "bo" );
			$bo->set_attribute ( "ctype", $res[$i]['celltypename'] );
			$bo->set_attribute ( "gtype", $res[$i]['groundname'] );
		}

		//$panel = "xmldata=";
		//$panel .= $doc->dump_mem();
		//$this->logResponse ( $panel );
		// print $panel;
/*<MSG id="1" titl="ДАСТРОЙ СВОЯ ХАТА" head="ЗАВЕРШИТЬ СТРОИТЕЛЬСТВО ЗА 10000 серебра" 
txt="Нажав эту кнопку вы сможете un momento получить уже%0Aаццки готовое здание за свои кровыне грОши.%0AБез базара." 
avail="1" status="Дэних мало, да? Ходи Сера куплять магазын." desc="FAST_BUILD"/>*/

		// Если это здание в процессе строительства или апгрейда и этот процесс еще не завершен
		if (($inupgrade == 1 || $underconstruction == 1) && $leftTime > 0) {
			// Услуга завершения строительства и апгрейда
			
			// Получаем стоимомсть завершения турна
			$actionType = DE_MoneyActionType::CreateFromName('PS_BUY_TRAINING_COMPLETION');
			$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
			$price = $MoneyActionType->getAmount()*$leftTime;
			$credit = $MoneyActionType->getCreditName();
			
			assert($price > 0);
			
			// Получаем количество денег у игрока
			$world = DE_WorldFromCollection::CreateFromID(DE_World::CreateForCurrentWorld()->getShortName());
			$member = new MU_EntityOperationMember(DE_User::CreateFromID($_SESSION['uid']), $world);
			$account = MU_SearchOperations::GetAccountData($member);
			assert(!empty($account));
			
			$messageParams[0] = $price;
			// Услуга позволяет моментально завершать постройку зданий и изучение науки не выше двадцатого уровня на всех сотах игрока в данном игровом мире.
			// Исключением является здание Гондола-Транспортер, на которое данная услуга не действует. Кроме того, апгрейд зданий также не завершается.
			// Стоимость услуги % серебрянных кредитов.
			$txt = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_BY_TRAINING_COMPLETION_DESC', $messageParams, null, null, false);
			$messageParams = array();
			// Моментальное завершение строительства и апгрейда зданий
			$title = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_BY_TRAINING_COMPLETION_TITLE', $messageParams, null, null, false);
			// Окончание строительства и исследований
			$head= DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_BY_TRAINING_COMPLETION_HEAD', $messageParams, null, null, false);

			
			// Если это здание гондола-транспортер
			if ($this->bid == 18) {
				// Если у игрока недостаточно денег
				$avail = 0;
				// Нельзя завершать строительство здания Гондола-Транспортер
				$status = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_BY_TRAINING_COMPLETION_NOT_NUB', $messageParams, null, null, false);
			} elseif ($account[$credit] < $price) {
				$avail = 0;
				$status = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NOT_ENOUGH_CREDIT', $messageParams, null, null, false);
			} else {
				$avail = 1;
				$messageParams[0] = $price;
				// Вы действительно хотите завершить строительство  или апгрейд здания за % серебра?'
				$status = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_TRAINING_COMPLETION_ASK', $messageParams, null, null, false);
				$status = str_replace('0A', '%0A', $status);
			}
			
			$msg = $root->new_child ( "MSG" );
			$msg->set_attribute("id", 1);
			$msg->set_attribute("titl", $head);
			$msg->set_attribute("head", $title);
			$msg->set_attribute("txt", $txt);
			$msg->set_attribute("avail", $avail);
			$msg->set_attribute("status", $status);
			$msg->set_attribute("desc", "FAST_BUILD");
		}
		

		// Услуга моментального окончания строительства зданий и изучения науки ниже 20го уровня
		/*if (DS_PaymentService::IsServiceActive('PS_BUY_TRAINING_COMPLETION')) {
			
			// Получаем стоимомсть услуги (шага)
			$actionType = DE_MoneyActionType::CreateFromName('PS_BUY_TRAINING_COMPLETION');
			$MoneyActionType = MU_EntityMoneySumm::CreateFromMoneyActionType($actionType);
			$price = $MoneyActionType->getAmount();
			$credit = $MoneyActionType->getCreditName();
			
			// Смотрим, сколько осталось до окончания строительства

			// Получаем количество денег у игрока
			$world = DE_WorldFromCollection::CreateFromID(DE_World::CreateForCurrentWorld()->getShortName());
			$member = new MU_EntityOperationMember(DE_User::CreateFromID($_SESSION['uid']), $world);
			$account = MU_SearchOperations::GetAccountData($member);
			assert(!empty($account));

			$messageParams[0] = $price;
			// Услуга позволяет моментально завершать постройку зданий и изучение науки не выше двадцатого уровня на всех сотах игрока в данном игровом мире.
			// Исключением является здание Гондола-Транспортер, на которое данная услуга не действует. Кроме того, апгрейд зданий также не завершается.
			// Стоимость услуги % серебрянных кредитов.
			$txt = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_BY_TRAINING_COMPLETION_DESC', $messageParams, null, null, false);
			$messageParams = array();
			// Моментальное завершение строительства зданий и изучения науки
			$title = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_BY_TRAINING_COMPLETION_TITLE', $messageParams, null, null, false);
			// Окончание строительства и исследований
			$head= DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_BY_TRAINING_COMPLETION_HEAD', $messageParams, null, null, false);

			// Если у игрока есть изучающиеся науки выше 20го уровня
			if (!DS_PaymentService::CheckUserIsNub($_SESSION['uiid'])) {
				$avail = 0;
				$status = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_BY_TRAINING_COMPLETION_NOT_NUB', $messageParams, null, null, false);
			} elseif (DS_PaymentService::GetCountUserEvents($_SESSION['uiid']) == 0) {
				// Если у игрока нет ивентов науки или постройки зданий (за исключением здания Гондола-Транспортер)
				$avail = 0;
				$status = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_BY_TRAINING_COMPLETION_NO_EVENTS', $messageParams, null, null, false);
			} elseif ($account[$credit] < $price) {
				// Если у игрока недостаточно денег
				$avail = 0;
				$status = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NOT_ENOUGH_CREDIT', $messageParams, null, null, false);
			} else {
				$avail = 1;
				$messageParams[0] = $price;
				// Вы действительно хотите завершить строительство зданий и изучение науки за % серебра?
				$status = DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('PS_TRAINING_COMPLETION_ASK', $messageParams, null, null, false);
				$status = str_replace('0A', '%0A', $status);

			}

			$msg = $root->new_child ( "MSG" );
			$msg->set_attribute("id", 1);
			// Гена грамотей блин!! =(((((((((((
			$msg->set_attribute("titl", $head);
			$msg->set_attribute("head", $title);
			$msg->set_attribute("txt", $txt);
			$msg->set_attribute("avail", $avail);
			$msg->set_attribute("status", $status);
			$msg->set_attribute("desc", "FAST_BUILD");

		}*/

		SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );

		$res = "";
		$res2 = "";
		$doc = "";
		$root = "";
		return true;
	}
}
?>