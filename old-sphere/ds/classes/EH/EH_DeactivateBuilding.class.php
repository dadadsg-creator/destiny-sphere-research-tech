<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2010/03/10 09:51:08 $';

// обработчик деактивации зданий
class EH_DeactivateBuilding extends EH_Event
{
	public $deact;
	
	public $checktime;

	function EH_DectivateBuilding ()
	{
		EH_Event::EH_Event ();
	}

	function handleEvent ( $eqid )
	{
		global $common;

		if ( !isset ( $_SESSION['time_delta'] ) )
		{
			$delta = 0;
		} else {
			$delta = $_SESSION['time_delta'];
		}

		$this->eqid = $eqid;
		if ( !$this->checktime )
		{
			$this->checktime = 'now()';
		}

		// параметры эвента
		if ( !$this->getEventParams () )
		{
			return false;
		}


			// если не внешний рефреш

			// название соты
			$comb = DE_Comb::CreateFromID((int)$this->eventparams["ID_Comb"]);
			$combname = $comb->getName();
			$messparams[0] = $comb->getName();

			// название здания
			$raceID = DE_UserInstance::CreateFromID((int)$this->eventparams["ID_UserInstance"])->getRaceID();
			$raceBuilding = DE_Building::CreateFromID((int)$this->eventparams["ID_Building"])->getRaceBuilding((int)$raceID);
			$messparams[1] = $raceBuilding->getLocalizedName();


		if ( $this->_isTimeToHandle())
		{
			// если закончено
			$this->deact = new UA_DeactivateBuilding;
			if ( !$this->deact )
			{
				$this->errormsg = "SYSTEM ERROR - EH_DectivateBuilding::handlEvent4";
				return false;
			}

			// список зданий для каскадной деактивации
			$this->deact->todeact[$this->eventparams["ID_AttachedBuilding"]] = $this->eventparams["ID_Building"];
			$this->deact->getBuildingToDeact ( $this->eventparams["ID_Building"], $this->eventparams["ID_Comb"] );

			// деактивируем их всех
			if(!$this->deact->deactivate($this->deact->todeact, $this->eventparams["ID_Comb"], $this->eventparams["ID_UserInstance"] , $this->eventparams["ID_Language"] ) )
			{
				$this->errormsg = $this->deact->errormsg;
				return false;
			}
			
			//Рестартим кэш вида соты
			$buidlingData = ME_CombViewGetAtachedBuildingData::Get((int)$this->eventparams["ID_AttachedBuilding"]);
			CR_CombView::CreateFromCombID($buidlingData['COMB_ID'])->resetBuildings($buidlingData['CELL_TYPE_ID']);
			
			$buildingName = DE_Building::CreateFromID((int)$this->eventparams["ID_Building"])->getName();

			// если это Центральные Ворота
			if ($buildingName  == 'CENTRAL_GATE' )
			{
				// деативируем все здания в центральной и нейтральной части соты
				$ehsql = sprintf ( "
						select 
							ab.id_attachedbuilding,
							abbeq.id_eventqueue,
							ab.inactivation,
							ab.inupgrade,
							ab.underconstruction
						from 
							ds_attachedbuilding AS ab
							INNER JOIN ds_cell AS ce USING (id_cell)
							INNER JOIN ds_celltype AS ct ON (ce.id_celltype = ct.id_celltype)
							INNER JOIN ds_attachedbuildingbindeventqueue AS abbeq USING (id_attachedbuilding)
							INNER JOIN ds_eventqueue AS eq ON (eq.id_eventqueue = abbeq.id_eventqueue)
						where 
							ce.id_comb = %d
							AND 
							ct.celltypename != 'HOME'
							AND 
							ct.celltypename != 'NEUTRAL'
							AND
							(
								ab.inactivation = true
								OR 
								ab.underconstruction = true
								OR 
								ab.inupgrade = true
							)
							AND NOT eq.istrash",
						$this->eventparams["ID_Comb"] );

				$ehres = $common->getResultArray ( "events_to_trash", $ehsql, "", "", "" );
				if ( $ehres === false )
				{
					$this->errormsg = "SYSTEM ERROR - EH_DectivateBuilding::handlEvent6";
					return false;
				}

				for ( $i=0; $i<sizeof ( $ehres ); $i++ )
				{
					if ( $ehres[$i]['underconstruction'] == 't' )
					{
						ME_CancelBuilding::Cancel($ehres[$i]['id_attachedbuilding']);

					} else 	if ( $ehres[$i]['inactivation'] == 't' )
					{
						$ehsql = sprintf ( "update ds_attachedbuilding
								set inactivation = false
								where id_attachedbuilding = %d",
								$ehres[$i]['id_attachedbuilding'] );
						$ehres2 = $common->executeQuery ( $ehsql );
						assert(empty($common->errormsg));

					}else if($ehres[$i]['inupgrade'] == 't' ){

						ME_CancelUpgradeBuilding::Cancel((int)$ehres[$i]['id_attachedbuilding']);

						$ehsql = sprintf ( "SELECT * FROM ds_attachedbuilding
								where id_attachedbuilding = %d",
								$ehres[$i]['id_attachedbuilding'] );
						$ehres2 = $common->getResultArray ( "buildings_to_deactivate", $ehsql, "", "", "" );
						assert(empty($common->errormsg));

					}


					$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->eventparams["ID_Comb"]);					
					if($ehres[$i]['underconstruction'] == 't' || $ehres[$i]['inupgrade'] == 't')
					{						
						$combAttributes->deltaAttribute('CURRENT_AMOUNT', 'SIMILTANEOUS_BUILDS', -(float)1);
					}					

					$event = DE_EventQueue::CreateFromID((int)$ehres[$i]['id_eventqueue']);
					$event->setIsTrash(true);
				}



				// список зданий которые можно деактивировать

				$ehsql = sprintf ( "select ab.id_attachedbuilding,
						ab.id_building
						from ds_attachedbuilding ab,
						ds_cell ce,
						ds_celltype ct,
						ds_attachedbuildinghasattribute abha,
						ds_attribute a,
						ds_attributename atn,
						ds_attributetype att
						where ab.id_cell = ce.id_cell
						and ab.active = true
						and ab.id_attachedbuilding = abha.id_attachedbuilding
	                                              	and abha.id_attribute = a.id_attribute
           		                                   	and a.id_attributename = atn.id_attributename
						and a.id_attributetype = att.id_attributetype
						and atn.attributename = 'DEACTIVEABLE'
						and att.typename = 'CURRENT_FLAG'
						and ce.id_celltype = ct.id_celltype
						and ce.id_comb = %d
						and ct.celltypename in ('CENTRAL'/*, 'NEUTRAL'*/)",
						$this->eventparams["ID_Comb"] );

				$ehres = $common->getResultArray ( "buildings_to_deactivate", $ehsql, "", "", "" );
				if ( $ehres === false )
				{
					$this->errormsg = "SYSTEM ERROR - EH_DectivateBuilding::handlEvent13";
					return false;
				}

				// деактивируем их всех
				for ( $i=0; $i<sizeof ( $ehres ); $i++ )
				{
					$this->deact->todeact[$ehres[$i]['id_attachedbuilding']] = $ehres[$i]['id_building'];
					//$this->deact->getBuildingToDeact ( $this->eventparams["ID_Building"], $this->eventparams["ID_Comb"] );

					if ( !$this->deact->deactivate ( $this->deact->todeact, $this->eventparams["ID_Comb"], $this->eventparams["ID_UserInstance"] , $this->eventparams["ID_Language"] ) )
					{
						$this->errormsg = $this->deact->errormsg;
						return false;
					}
				}

				$ehsql = sprintf ( "update ds_attachedbuilding
						set inactivation = false,
						active = false
						where id_attachedbuilding = %d",
						$this->eventparams["ID_AttachedBuilding"] );

				$ehres = $common->executeQuery ( $ehsql );
				if ( $ehres === false )
				{
					$this->errormsg = "SYSTEM ERROR - EH_DectivateBuilding::handlEvent14";
					return false;
				}

				// remove tradescopes
				$ehsql = sprintf ( "delete from ds_comballowedtradescope
						where id_comb = %d
						and id_tradescope in
						(select id_tradescope
						from ds_trade_scope
						where tradescopename in
						('RING_TRADING', 'INTERRING_TRADING', 'ALLIANCE_TRADING'))",
						$this->eventparams["ID_Comb"] );

				$ehres = $common->executeQuery ( $ehsql );
				if ( $ehres === false )
				{
					$this->errormsg = "SYSTEM ERROR - EH_DectivateBuilding::handlEvent15";
					return false;
				}

				// return armyes back
				$comb = DE_Comb::CreateFromID((int)$this->eventparams["ID_Comb"]);
				$checkDate = GB_TypeFActory::CreateBase('GB_Date', $this->eventendtime);

				ME_ReturnAllArmysToComb::ReturnArmys($comb, $checkDate);
				ME_KickAllArmysFromCombOnDefend::ReturnArmys($comb, $checkDate);

				//---------------- BEGIN cancel trades BEGIN ---------------------------------	
							
				$userInstance = DE_UserInstance::CreateFromID((int)$this->eventparams["ID_UserInstance"]);
				
				// не возвращаем лоты и ставки с локального торгователя
				//ME_CancelAllUserTrades::Cancel($userInstance, true, array(1));
								
				//---------------- END cancel trades END ---------------------------------
			}

			$this->setTrash ();

		} else {
			// not finished ( in progress )

				// if full refresh only
				$prog = $this->_getComplitPart() * 100;

				if ( $prog > 100 ) $prog = 100;
				if ( $prog < 0 ) $prog = 0;

				$messparams[2] = number_format ( $prog, 1, ".", "" );

				$event = DE_EventQueue::CreateFromID((int)$this->eqid);
				$messparams[3] = $event->getEndDate()->getSystemMessageFormat();

				DE_SystemMessages::AddMessageToEventParticipants($this->eqid, "PROGRESS_DEACTIVATE_BUILDING_MESS", $messparams, null, null, 'NOACCUMULATE' );

		}
		
		return true;
	}
}

?>