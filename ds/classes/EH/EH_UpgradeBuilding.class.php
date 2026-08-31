<?php $CVS_Version = '$Revision: 1.10 $ ; $Date: 2010/07/07 09:08:17 $';

// обработчик апгрейда здания
class EH_UpgradeBuilding extends EH_Event
{

	public $checktime;

	function EH_UpgradeBuilding ()
	{
		EH_Event::EH_Event ();
	}

	function handleEvent($eqid)
	{
		settype($eqid, 'integer');
		assert($eqid > 0);

		global $common;

		// корректировка времени
		if(!isset($_SESSION['time_delta']))
		{
			$delta = 0;
		} else {
			$delta = $_SESSION['time_delta'];
		}

		// если внешний рефреш
		if(!$this->checktime)
		{
			$this->checktime = 'now()';
		}

		$this->eqid = $eqid;

		// получаем параметры эвента
		if(!$this->getEventParams())
		{
			return false;
		}

		$userInstance = DE_UserInstance::CreateFromID((int)$this->eventparams["ID_UserInstance"]);

		$raceID = $userInstance->getRaceID();
		$buildingFrom = DE_Building::CreateFromID((int)$this->eventparams["ID_BuildingFrom"]);
		$buildingTo = DE_Building::CreateFromID((int)$this->eventparams["ID_BuildingTo"]);

		$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->eventparams["ID_Comb"])->getFullName()."{/b}";
		$messparams[1] = "{b} ".$buildingFrom->getRaceBuilding($raceID)->getLocalizedName()."{/b}";
		$messparams[2] = "{b} ".$buildingTo->getRaceBuilding($raceID)->getLocalizedName()."{/b}";

		if ($this->_isTimeToHandle())
		{// пора обработать ивент

			$ehsql = "
						UPDATE only DS_AttachedBuilding 
					  	SET ID_Building = ".$this->eventparams["ID_BuildingTo"]."
					  	WHERE ID_AttachedBuilding = ".$this->eventparams["ID_AttachedBuilding"];
			$ehres = $common->executeQuery($ehsql);
			assert(empty($comon->errormsg));

			$ehsql = "
						UPDATE only DS_AttachedBuilding 
						SET inupgrade = false
						WHERE ID_AttachedBuilding = ".$this->eventparams["ID_AttachedBuilding"];
			$ehres = $common->executeQuery ( $ehsql );
			assert(empty($comon->errormsg));

			// Добавляем атрибуто нового здания
			$buildingAttributes = DE_BuildingAttributes::CreateFromBuildingID((int)$this->eventparams["ID_BuildingTo"]);
			$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->eventparams["ID_Comb"]);

			//Если у старого здания были атрибуты по рабочим то их вычетам так как их при поставновки на апгред не учитывали
			$buildingFromAttributes = DE_BuildingAttributes::CreateFromBuildingID((int)$this->eventparams["ID_BuildingFrom"]);
			if($buildingFromAttributes->haveAttribute('MAX_AMOUNT', 'WORKER'))
			{
				$combAttributes->deltaAttribute('MAX_AMOUNT', 'WORKER', -$buildingFromAttributes->getAttribute('MAX_AMOUNT', 'WORKER'));
			}

			ME_AddBuildingAttributesToComb::AddAttributes($buildingAttributes, $combAttributes, 'plus');

			//Добавляем арты здания
			ME_AddBuildingAttributesToComb::AddArtefactAttributes((int)$this->eventparams["ID_AttachedBuilding"], $combAttributes, 'plus');

			//Рестартим кэш вида соты
			$buidlingData = ME_CombViewGetAtachedBuildingData::Get((int)$this->eventparams["ID_AttachedBuilding"]);
			CR_CombView::CreateFromCombID($buidlingData['COMB_ID'])->resetBuildings($buidlingData['CELL_TYPE_ID']);

			// уменьшает текущие стройки
			$combAttributes->deltaAttribute('CURRENT_AMOUNT', 'SIMILTANEOUS_BUILDS', -(float)1);

			// аттрибуты нового здания - destroyable
			$ehsql = sprintf ( "select 1 as destr
					from ds_buildinghasattribute bha,
					ds_attribute a,
					ds_attributename atn,
					ds_attributetype att
					where bha.id_attribute = a.id_attribute
					and a.id_attributename = atn.id_attributename
					and a.id_attributetype = att.id_attributetype
					and att.typename = 'PERMANENT_FLAG'
					and atn.attributename = 'DESTROYABLE'
					and bha.id_building = %d
					and bha.value = 0",
			$this->eventparams["ID_BuildingTo"] );

			$ehres = $common->getResultArray ( "destroyable_permanent_flag_id_building_".$this->eventparams["ID_BuildingTo"], $ehsql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if ( $ehres === false ) {
				$this->errormsg = "SYSTEM ERROR - EH_UpgradeBuilding::handlEvent9";

				return false;
			}

			if ( count($ehres) == 0) $ehres[0]['destr'] = 0;

			if ( $ehres[0]['destr'] != 1 ) {

				$ehsql = sprintf ( "select ID_Attribute
						from DS_Attribute a,
						DS_AttributeName atn,
						DS_AttributeType att
						where a.ID_AttributeName = atn.ID_AttributeName
						and a.ID_AttributeType = att.ID_AttributeType
						and att.TypeName = 'CURRENT_FLAG'
						and atn.AttributeName = 'DESTROYABLE'" );

				$ehres = $common->getResultArray ( "id_attribute_current_flag_destroyable", $ehsql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
				if ( !$ehres ) {
					$this->errormsg = "SYSTEM ERROR - EH_UpgradeBuilding::handlEvent10";

					return false;
				}

				$id_attribute = $ehres[0]['id_attribute'];

				$ehsql = sprintf ( "select id_attachedbuilding
						from ds_attachedbuildinghasattribute
						where id_attachedbuilding = %d
						and id_attribute = %d",
				$this->eventparams["ID_AttachedBuilding"],
				$id_attribute );

				$ehres = $common->getResultArray ( "attachedbuildinghasattribute", $ehsql, "", "", "" );
				if ( $ehres === false ) {
					$this->errormsg = "SYSTEM ERROR - EH_UpgradeBuilding::handlEvent11";

					return false;
				}

				if ( sizeof ( $ehres ) == 0 ) {

					$ehsql = sprintf ( "insert into DS_AttachedBuildingHasAttribute (ID_AttachedBuilding, ID_Attribute)
							values (%d, %d)",
					$this->eventparams["ID_AttachedBuilding"],
					$id_attribute );

					$ehres = $common->executeQuery ( $ehsql );
					if ( $ehres === false ) {
						$this->errormsg = "SYSTEM ERROR - EH_UpgradeBuilding::handlEvent12";

						return false;
					}
				}
			}

			// аттрибуты нового здания - deactiveable
			$ehsql = sprintf ( "select 1 as deact
					from ds_buildinghasattribute bha,
					ds_attribute a,
					ds_attributename atn,
					ds_attributetype att
					where bha.id_attribute = a.id_attribute
					and a.id_attributename = atn.id_attributename
					and a.id_attributetype = att.id_attributetype
					and att.typename = 'PERMANENT_FLAG'
					and atn.attributename = 'DEACTIVEABLE'
					and bha.id_building = %d
					and bha.value = 0",
			$this->eventparams["ID_BuildingTo"] );

			$ehres = $common->getResultArray ( "deactiveable_permanent_flag_id_building_".$this->eventparams["ID_BuildingTo"], $ehsql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
			if ( $ehres === false ) {
				$this->errormsg = "SYSTEM ERROR - EH_UpgradeBuilding::handlEvent13";

				return false;
			}

			if ( count($ehres) == 0) $ehres[0]['deact'] = 0;

			if ( $ehres[0]['deact'] != 1 ) {

				$ehsql = sprintf ( "select ID_Attribute
						from DS_Attribute a,
						DS_AttributeName atn,
						DS_AttributeType att
						where a.ID_AttributeName = atn.ID_AttributeName
						and a.ID_AttributeType = att.ID_AttributeType
						and att.TypeName = 'CURRENT_FLAG'
						and atn.AttributeName = 'DEACTIVEABLE'" );

				$ehres = $common->getResultArray ( "id_attribute_current_flag_deactiveable", $ehsql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
				if ( !$ehres ) {
					$this->errormsg = "SYSTEM ERROR - EH_UpgradeBuilding::handlEvent14";

					return false;
				}

				$id_attribute = $ehres[0]['id_attribute'];

				$ehsql = sprintf ( "select id_attachedbuilding
						from ds_attachedbuildinghasattribute
						where id_attachedbuilding = %d
						and id_attribute = %d",
				$this->eventparams["ID_AttachedBuilding"],
				$id_attribute );

				$ehres = $common->getResultArray ( "attachedbuildinghasattribute", $ehsql, "", "", "" );
				if ( $ehres === false ) {
					$this->errormsg = "SYSTEM ERROR - EH_UpgradeBuilding::handlEvent15";

					return false;
				}

				if ( sizeof ( $ehres ) == 0 ) {

					$ehsql = sprintf ( "insert into DS_AttachedBuildingHasAttribute
							(ID_AttachedBuilding, ID_Attribute)
							values (%d, %d)",
					$this->eventparams["ID_AttachedBuilding"],
					$id_attribute );

					$ehres = $common->executeQuery ( $ehsql );
					if ( $ehres === false ) {
						$this->errormsg = "SYSTEM ERROR - EH_UpgradeBuilding::handlEvent16";

						return false;
					}
				}
			}

			// Выбираем очки за здание
			$buildinScore = DS_Building::GetBuildingScore((int)$this->eventparams["ID_BuildingTo"]);
			assert($buildinScore > 0);

			// увеличиваем рейтинг соты
			DE_Comb::CreateFromID((int)$this->eventparams["ID_Comb"])->deltaCombScore($buildinScore);

			// Увеличиваем строительный рейтинг игрока
			$buildinScoreFrom = DS_Building::GetBuildingScoreNew((int)$this->eventparams["ID_BuildingFrom"]);
			$buildinScoreTo = DS_Building::GetBuildingScoreNew((int)$this->eventparams["ID_BuildingTo"]);
			$buildinScore2 = $buildinScoreTo - $buildinScoreFrom;
			assert($buildinScore2 >= 0);
			DE_Ranking::CreateFromID((int)$this->eventparams["ID_UserInstance"])->deltaBuildingRanking($buildinScore2);

			// системное сообщение
			DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "FINISH_UPGRADE_BUILDING_MESS", $messparams );

			// прибиваем эвент
			$this->setTrash ();

			// защита ньюбов
			if($buildingTo->getName() == 'CENTRAL_GATE' && $userInstance->haveNewbDefend())
			{
				$defendEndDateInTurns = DE_WorldSettings::CreateForCurentWorld()->get('NEWB_DEFEND_AFTER_GATE_OPEN')*24;
				$defendEndDate = GB_TypeFactory::CreateConstant('GB_Date', time() + $defendEndDateInTurns*60*DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME')); //  <-> 21
				$userInstance->setNewbDefendEndDate($defendEndDate);
				$userInstance->commit();
				$messparams[0]	= DE_WorldSettings::CreateForCurentWorld()->get('NEWB_DEFEND_AFTER_GATE_OPEN');
				DE_SystemMessages::AddMessageFromAction(__CLASS__, "NEWB_OPEN_GATE", $messparams, 0, 'YELLOW');

				$recuser = DS_User::GetRecommendedUser($userInstance->getUserID());

				if ($recuser) //Юзер открыл ворота и чей-то реферал. Начислим ему бабла за это
				{
					$recommendid = $recuser['id_user_recomend'];
					$sum = intval(UC_PaymentGC::GetParam('GATE_OPEN_SUM'));

					if ($sum > 0)
					UC_PaymentServiceMoneyOperation::EnterMoney($recommendid, $sum, 'SC', 'REFERRAL_PAYMENT');
				}
			}
		} else { // не закончено

			$prog = $this->_getComplitPart() * 100;

			if ( $prog > 100 ) $prog = 100;
			if ( $prog < 0 ) $prog = 0;

			$messparams[3] = number_format ( $prog, 1, ".", "" );

			$event = DE_EventQueue::CreateFromID((int)$this->eqid);
			$messparams[4] = $event->getEndDate()->getSystemMessageFormat();

			// системное сообщение
			DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "PROGRESS_UPGRADE_BUILDING_MESS", $messparams, null, null, 'NOACCUMULATE' );
		}

		return true;
	}
}

?>