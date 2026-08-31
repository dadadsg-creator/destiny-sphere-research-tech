<?php $CVS_Version = '$Revision$ ; $Date$';

// обработка эвента активация здания
class EH_ActivateBuilding extends EH_Event
{

	public $checktime;

	function EH_ActivateBuilding()
	{
		EH_Event::EH_Event ();
	}

	function handleEvent($eqid)
	{

		global $common;

		// корректировка времени для системных сообщений
		if(!isset ($_SESSION['time_delta']))
		{
			$delta = 0;
		}else{
			$delta = $_SESSION['time_delta'];
		}

		$this->eqid = $eqid;

		// если эвент обрабатывается другим игроком (при нападении)
		if ( !$this->checktime ) $this->checktime = 'now()';

		// забираем параметры эвента
		if ( !$this->getEventParams () ) {
			return false;
		}

		// если не обрабатывается другим игроком
		
			// название соты для системного сообщения
			$messparams[0] = "{b} ".DE_Comb::CreateFromID((int)$this->eventparams["ID_Comb"])->getName()."{/b}";

			$raceID = DE_UserInstance::CreateFromID((int)$this->eventparams["ID_UserInstance"])->getRaceID();

			// название здания для системного сообщения
			$raceBuilding = DE_Building::CreateFromID((int)$this->eventparams["ID_Building"])->getRaceBuilding((int)$raceID);


			$messparams[1] = $raceBuilding->getLocalizedName();
		


		if ( $this->_isTimeToHandle() )
		{
			// если закончилось

			// активируем здание
			$ehsql = sprintf ( "update only DS_AttachedBuilding
					set active = true,
					inactivation = false
					where ID_AttachedBuilding = %d",
					$this->eventparams["ID_AttachedBuilding"] );

			$ehres = $common->executeQuery ( $ehsql );
			if ( $ehres === false ) {
				$this->errormsg = "SYSTEM ERROR - EH_ActivateBuilding::handlEvent4";

				return false;
			}


			$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->eventparams["ID_Comb"]);
			$buildingAttributes = DE_BuildingAttributes::CreateFromBuildingID((int)$this->eventparams["ID_Building"]);

			// добавляем в атрибуты соты значения upkeep/production для этого здания
			ME_AddBuildingAttributesToComb::AddAttributes($buildingAttributes, $combAttributes);

			//Начисляем бонусы артефактов который в задние
			ME_AddBuildingAttributesToComb::AddArtefactAttributes($this->eventparams["ID_AttachedBuilding"], $combAttributes);
			
			//Рестартим кэш вида соты
			$buidlingData = ME_CombViewGetAtachedBuildingData::Get((int)$this->eventparams["ID_AttachedBuilding"]);
			CR_CombView::CreateFromCombID($buidlingData['COMB_ID'])->resetBuildings($buidlingData['CELL_TYPE_ID']);
			
			// внутреннее имя здания
			$buildingName = DE_Building::CreateFromID((int)$this->eventparams["ID_Building"])->getName();

			// если это Центральные Ворота
			if ($buildingName == 'CENTRAL_GATE') 
			{
				// добавляем торговые скопы, если надо
				$ehsql = sprintf("
						insert into ds_comballowedtradescope 
							( id_comb, id_tradescope)
						select 
							%d, 
							bhts.id_tradescope
						from 
							ds_buildinghastradescope bhts,
							ds_attachedbuilding ab
						where 
							bhts.id_building = ab.id_building
							and 
							ab.id_comb = %d
							and 
							ab.active = true
							and
							ab.underconstruction = false
							and 
							not exists
							(
								select 
									*
								from 
									ds_comballowedtradescope
								where 
									id_comb = %d
									and 
									id_tradescope = bhts.id_tradescope
							)",
						$this->eventparams["ID_Comb"],
						$this->eventparams["ID_Comb"],
						$this->eventparams["ID_Comb"] );

				$ehres = $common->executeQuery ( $ehsql );
				if($ehres === false) 
				{
					$this->errormsg = "SYSTEM ERROR - EH_ActivateBuilding::handlEvent8";
					return false;
				}
				
				// Проверяем альянсовый скоп
				if(!DE_Alliance::CreateFromUserInstanceID($this->uiid) instanceof DE_AllianceEmpty)
				{
					$tradeScopesData = DS_TradeInterface::GetCombAllowedTradeScopes(DE_Comb::CreateFromID((int)$this->eventparams["ID_Comb"]));
					assert(is_array($tradeScopesData));	
					
					$hasAllianceTradeScope = false;
					
					foreach($tradeScopesData as $k => $scope)
					{
						if($scope['tradescopename'] == 'ALLIANCE_TRADING')
						{
							$hasAllianceTradeScope = true;
							break;
						}
					}
					
					if(!$hasAllianceTradeScope)
					{
						$sql = "
						INSERT INTO ds_comballowedtradescope
							(id_comb, id_tradescope)
						SELECT
							".$this->eventparams["ID_Comb"].",
							id_tradescope
						FROM
							ds_trade_scope
						WHERE
							tradescopename = 'ALLIANCE_TRADING'
						";
						$common->executeQuery($sql);
						assert(empty($common->errormsg));
					}
				}
				
				// Рандомная защита
				$randomDefendInTurns = DE_WorldSettings::CreateForCurentWorld()->get('MAX_TIME_RANDOM_DEFEND_TIME');
				assert($randomDefendInTurns >= 0);
				
				// Если в мире включена рандомная защита
				if ($randomDefendInTurns != 0) {
					// Берем случайное значение
					$defendEndDateInTurns = rand(0, $randomDefendInTurns);
					// Если у нас не получился 0
					if ($defendEndDateInTurns != 0) {
						// Ставим время окончания рандомной защиты
						$defendEndDate = GB_TypeFactory::CreateConstant('GB_Date', time() + $defendEndDateInTurns*60*DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME'));
						$defendEndDateMax = GB_TypeFactory::CreateConstant('GB_Date', time() + $randomDefendInTurns*60*DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME'));
						$userInstance = DE_UserInstance::CreateFromID((int)$this->eventparams["ID_UserInstance"]);
						$userInstance->setRandomDefendEndDate($defendEndDate);
						$userInstance->setRandomDefendEndDateMax($defendEndDateMax);
						$userInstance->commit();
						
						/*// Отправляем сообщение
						// Вы находитесь под защитой, которая продлится максимум до %, но может быть снята раньше. Вы не можете торговать на кольце и межкольце, Вас не могут ограбить, однако напасть на Вас могут.
						$messparamsDef[0]	= GB_TypeFactory::CreateConstant('GB_Date', time() + $randomDefendInTurns*60*DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME'))->getFormatedFull();
						DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "RANDOM_DEFEND_MESS", $messparamsDef, null, null, 'NOACCUMULATE');*/
					}
				}
			}

			// если рефреш нормальный, добавляем юзеру системное сообщение
			DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "FINISH_ACTIVATE_BUILDING_MESS", $messparams);

			// прибиваем эвент
			$this->setTrash ();

		} else { // если не закончено
				// дата окончания, процент завершенности
				$prog = $this->_getComplitPart() * 100;

				if ( $prog > 100 ) $prog = 100;
				if ( $prog < 0 ) $prog = 0;

				$messparams[2] = number_format ( $prog, 1, ".", "" );
				
				$event = DE_EventQueue::CreateFromID((int)$this->eqid);				
				$messparams[3] = $event->getEndDate()->getSystemMessageFormat();

				// добавляем сообщение
				DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "PROGRESS_ACTIVATE_BUILDING_MESS", $messparams, null, null, 'NOACCUMULATE');
		}

		return true;
	}
}

?>