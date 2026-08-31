<?php $CVS_Version = '$Revision$ ; $Date$';

// закладка тренировки археологов и отправки экспедиций

class PN_ArcheologyTeam extends UA_GetBuildMenu
{

	function __construct()
	{
		UA_GetBuildMenu::UA_GetBuildMenu();
	}

	function getPanel()
	{
		global $common;
		global $params;

		// корректировка отображаемого времени
		
		$delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();



		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><archeology />";

		$doc = domxml_open_mem ( $xml );

		$root = $doc->document_element ();

		//$root->set_attribute ( "c", $this->cellnum );

		$root->set_attribute ( "turn", $this->worldSettings->get('TURN_TIME')*60 );



		// открыты ли Центральные Ворота
		if(DS_Comb::HaveOpenCentralGate((int)$this->comb))
		{
			$root->set_attribute ( "gate", "1" );
		}

		$localization = DE_Localization::CreateForCurentLang();

		$ch = $root->new_child ( "artsize" );



		/******************************************************************************/
		// Лпределяем размер артефакта (probability) для указания минимально-приносимого размера группой археологов
		$sql = "
					SELECT
						aa.id_artefactprobability,
						aa.id_localizedprefix
					FROM
						ds_artefactprobability aa
					WHERE
						aa.id_artefactprobability IN (1,2,3,4,5,6)
					 ORDER BY aa.id_artefactprobability
		";

		$res = $common->getResultArray ( "", $sql, "", $params["uiid"], "LOCAL_CACHE" );
		if ($res === false)
		{
			$this->errormsg .= " SYSTEM ERROR ArcheologyTeam::getPanel11";
			return false;
		}

		//$localization->get()

		for ( $i=0; $i<sizeof ( $res ); $i++ )
		{
			$ch2 = $ch->new_child ( "size" );
			$ch2->set_attribute('name',urlencode($localization->get((int)$res[$i]['id_localizedprefix'])) );
			$ch2->set_attribute('ID',$res[$i]['id_artefactprobability'] );
		}






		/***********************************************************************************************/






		$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);

		// текущее кол-во рабочих
		$work = $combAttributes->getAttribute('CURRENT_AMOUNT', 'WORKER', true);

		// максимальное кол-во археологов
		$archeologistmax = $combAttributes->getAttribute('MAX_AMOUNT', 'ARCHEOLOGIST', true);

		// текущее кол-во археологов
		$archeologistcurrent = $combAttributes->getAttribute('CURRENT_AMOUNT', 'ARCHEOLOGIST', true);

		// кол-во археологов в процессе тренировки
		$sql = sprintf ( "select value as archeologistcurrent
				from ds_eventhasoption eho1,
				ds_eventoption eo1
				where eho1.id_eventoption = eo1.id_eventoption
				and eo1.optionname = 'CountToTrain'
				and eho1.id_eventqueue =
				(select eq.id_eventqueue
				from ds_combbindeventqueue cbeq,
				ds_eventqueue eq,
				ds_event e,
				ds_eventhasoption eho,
				ds_eventoption eo
				where cbeq.id_eventqueue = eq.id_eventqueue
				and eq.id_event = e.id_event
				and eq.istrash = false
				and cbeq.id_comb = %d
				and e.eventname = 'TRAIN_UNIT'
				and cbeq.id_eventqueue = eho.id_eventqueue
				and eho.id_eventoption = eo.id_eventoption
				and eo.optionname = 'ID_Unit'
				and value =
				(select id_unit
				from ds_unit
				where unitname = 'ARCHEOLOGIST'))",
				$this->comb );
		$res = $common->getResultArray ( "", $sql, "", "", "" );
		assert(empty($common->errormsg));
		if ( sizeof ( $res ) > 0 )
		{
			$archeologistcurrent += $res[0]['archeologistcurrent'];
			$inprogress = $res[0]['archeologistcurrent'];
		}


		// кол-во археологов в экспедициях
		$archAwayAmount = $combAttributes->getAttribute('AWAY_AMOUNT', 'ARCHEOLOGIST', true);

//		if(sizeof($res)>0)
//		{
			// общее суммарное значение археологов в наличии
			$archeologistcurrent += $archAwayAmount;

//		}

		$allowbyroom = $archeologistmax - $archeologistcurrent;

		if ( $allowbyroom < 0 ) $allowbyroom = 0;



		$ch = $root->new_child ( "train" );

		// на сколько археологов хватает ресурсов
		$unitAttributes = DE_Unit::CreateFromName('ARCHEOLOGIST')->getAttributes();

		$showResorces = array('CONSTRUCTION_TIME', 'WORKER', 'FOOD', 'KADERIUM');
		foreach($showResorces as $k=>$resourceName)
		{
			if(!$unitAttributes->haveAttribute('PRODUCTION_COSTS', $resourceName))
			{
				continue;
			}

			$prodValue = $unitAttributes->getAttribute('PRODUCTION_COSTS', $resourceName);

			// стоимость одного археолога
			$ch->set_attribute ( substr ( $resourceName, 0, 1 ), $prodValue );

			if($resourceName !=  'CONSTRUCTION_TIME')
			{
				$maxCountTemp[] = floor($combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName, true) / $prodValue);
			}
		}

		$archeologistallow = min($maxCountTemp);

		// допустимое для тренировки колличество
		if ( $archeologistallow > $archeologistmax ) $archeologistallow = $archeologistmax;
		if ( $archeologistallow > $allowbyroom ) $archeologistallow = $allowbyroom;
		if ( $archeologistallow > $work ) $archeologistallow = $work;

		$ch->set_attribute ( "work", $work );
		$ch->set_attribute ( "archeologistmax", $archeologistmax );
		$ch->set_attribute ( "archeologistallow", $archeologistallow );

		if(isset($inprogress) && !empty($inprogress))
		{
			$ch->set_attribute ( "inprogress", $inprogress );
		}


		if ($_SESSION['profi_game'])
		{
			$orderby = "order by at.endtime ASC";
		} else {
			$orderby = "order by teamnumber";
		}
		
		// показать все текущие экспедиции
		$sql = sprintf ( "
				select 
					at.amount,
					ct.celltypename,
					at.size,
					at.endtime
				from 
					ds_archeologicalteam at,
					ds_celltype ct
				where 
					at.id_celltype = ct.id_celltype
					and 
					at.id_comb = %d
				%s",				
				$this->comb,
				$orderby );

		$res = $common->getResultArray ( "archeology_team_id_comb_".$this->comb, $sql, "", $params["uiid"], "LOCAL_CACHE" );
		if ($res === false)
		{
			$this->errormsg .= " SYSTEM ERROR ArcheologyTeam::getPanel10";
			return false;
		}

		for ( $i=0; $i<sizeof ( $res ); $i++ )
		{
			
			$endDate = GB_TypeFactory::CreateBase('GB_Date', $res[$i]['endtime']);
			
			$ch = $root->new_child ( "team" );
			$ch->set_attribute( "count", $res[$i]['amount'] );
			$ch->set_attribute( "dest", $res[$i]['celltypename'] );
			$ch->set_attribute( "enddate", $endDate->getFormatedFull(DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta()));
			$ch->set_attribute( "size", $res[$i]['size'] );
		}
		
		// Кнопка "Купить Археолгов"
		if (DS_PaymentService::IsServiceActive('PS_BY_UNIT_ARCHEOLOG')) {
			
			// Получаем количество денег у игрока
			$world = DE_WorldFromCollection::CreateFromID(DE_World::CreateForCurrentWorld()->getShortName());
			$member = new MU_EntityOperationMember(DE_User::CreateFromID($_SESSION['uid']), $world);
			$account = MU_SearchOperations::GetAccountData($member);
			assert(!empty($account));
		
			// Получаем цену
			$price = UC_PaymentServiceMoneyOperation::GetServicePrice('PS_BY_UNIT_ARCHEOLOG');

			// Получаем максимальное количество ученых, которое мы можем купить
			$maxAmount = DS_PaymentService::GetMaxUnitAmount($this->comb, (int)$_SESSION['uiid'], 'ARCHEOLOGIST');

			// Максимальное количество пакетов, на которое у игрока хватает денег
			$avail = min($maxAmount, (int)floor($account['SC']/$price));

			// Высчитываем количество юнитов, на которое нам хватает места
			$ch = $root->new_child("MSG");
			$ch->set_attribute("id", 32);
			$ch->set_attribute("titl", DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('BY_UNIT_ARCHEOLOGIST', array(), null, null, false));
			$ch->set_attribute("head", DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('BY_UNIT_ARCHEOLOGIST', array(), null, null, false));
			$ch->set_attribute("txt", DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('BY_UNIT_ARCHEOLOGIST_DESC', array($price), null, null, false));
			$ch->set_attribute("rem", str_replace('0A', '%0A', DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('BY_UNIT_ARCHEOLOGIST_MAX', array(DE_WorldSettings::CreateForCurentWorld()->get('ARCHEOLOGIST_BUY_LIMIT'), $avail), null, null, false)));
			$ch->set_attribute("avail", $avail);
			$ch->set_attribute("price", $price);
			$ch->set_attribute("res", 0);

			// Если мы не можем купить ни одного археолога, потому что нет денег
			if ($avail == 0  && $maxAmount != 0) {
				// Кончились деньги
				$ch->set_attribute("status", DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NOT_ENOUGH_CREDIT', array(), null, null, false));
			} else {
				$ch->set_attribute("status", "");
			}

			$ch->set_attribute("desc", "BUY_ARCHEOLOGIST");
		}


		// все закладки здания
		$this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab );

		SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );

		return true;

	}

}



?>