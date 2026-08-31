<?php $CVS_Version = '$Revision$ ; $Date$';


// обработчик защиты другого игрока
class EH_Defend extends EH_Event 
{

	private $checktime;
		
	private $userInstanceTo;
	
	private $userInstanceFrom;
	
	private $combTo;
	
	private $combFrom;
	
	private $army;
	
	function EH_Defend () 
	{
		EH_Event::EH_Event();
	}

	function handleEvent($eqid) 
	{		

		$this->eqid = $eqid;

		// если внешний рефреш
		if(!$this->checktime) 
		{
			$this->checktime = 'now()';
		}

		// получаем параметры эвента
		if(!$this->getEventParams()) 
		{
			return false;
		}
		
		$this->userInstanceTo = DE_UserInstance::CreateFromID((int)$this->eventparams["ID_UserInstanceFrom"]);
		$this->userInstanceFrom = DE_UserInstance::CreateFromID((int)$this->eventparams["ID_UserInstanceTo"]);
		$this->combTo = DE_Comb::CreateFromID((int)$this->eventparams["ID_CombTo"]);
		$this->combFrom = DE_Comb::CreateFromID((int)$this->eventparams["ID_CombFrom"]);
		$this->army = DE_Army::CreateFromID((int)$this->eventparams["ID_Army"]);

		// имя того кто послал армию
		$attackername = $ehres[0]['username'];
		
		// имя того, кому послали армию
		$defendername = $ehres[0]['username'];
		
		// название соты, куда послали армию
		$defendercombname = $ehres[0]['combname'];

		// название армии
		$attackerarmyname = $ehres[0]['unitcontainername'];

		// название соты откуда армия
		$attackercombname = $ehres[0]['combname'];


		if($this->checktime == 'now()') 
		{

			$ehsql = sprintf ( "select (now() >= eventendtime) as finished
					from ds_eventqueue
					where id_eventqueue = %d", 
					$this->eqid );

		} else {
			
			$ehsql = sprintf ( "select ('%s'::timestamp >= eventendtime) as finished
					from ds_eventqueue
					where id_eventqueue = %d", 
					$this->checktime,
					$this->eqid );			

		}

		$ehres = $this->common->getResultArray ( "finished", $ehsql, "", "", "" );
		if(!$ehres) 
		{
			$this->errormsg = "SYSTEM ERROR - EH_Defend::handlEvent6";
			return false;
		}



		if($ehres[0]['finished'] == 't') 
		{ //finished

			switch($this->eventparams["Stage"]) 
			{

				case 1: // прибыла к месту назначения
					
					// записываем действие армии
					$sql = "
					UPDATE ds_unitcontaineraction
					SET id_unitcontaineractiontype = (SELECT id_unitcontaineractiontype 
									from ds_unitcontaineractiontype 
									where actiontypename = 'IN_DEFEND' )
					WHERE id_unitcontainer = ".$this->eventparams['ID_Army'];
										
					$res = $this->common->executeQuery ($sql);
					assert(empty($this->common->errormsg));
		
					// если еще это не сделано, ставим армии текущую локацию
					$ehsql = sprintf ( "select id_currentcomb

							from ds_unitcontainer

							where id_unitcontainer = %d

							and id_currentcomb is null",

							$this->eventparams['ID_Army'] );



					$ehres = $this->common->getResultArray ( "id_currentcomb", $ehsql, "", "", "" );

					if ( $ehres === false ) {

						$this->errormsg = "SYSTEM ERROR - EH_Defend::handlEvent7";

						

						return false;

					}



					if ( sizeof ( $ehres ) != 0 ) {



						$ehsql = sprintf ( "update ds_unitcontainer

								set id_currentcomb = %d

								where id_unitcontainer = %d",

								$this->eventparams["ID_CombTo"],						

								$this->eventparams["ID_Army"] );



						$ehres = $this->common->executeQuery ( $ehsql );

						if ( $ehres === false ) {

							$this->errormsg = "SYSTEM ERROR - EH_Defend::handlEvent8";

							

							return false;

						}

						

						// проверяем идет ли на соте бой



						$ehsql = sprintf ( "select b.id_battle,

								bp.id_battleparticipant

								from ds_battle b,

								ds_battleparticipant bp

								where b.id_battle = bp.id_battle

								and b.id_battlecomb = %d

								and b.battleenddate is null

								and bp.id_userinstance = %d",

								$this->eventparams["ID_CombTo"],

								$this->eventparams["ID_UserInstanceTo"] );

								

						$ehres = $this->common->getResultArray ( "battles_inporgress_id_battlecomb_".$this->eventparams["ID_CombTo"], $ehsql, "", "", "" );

						if ( $ehres === false ) {

							$this->errormsg = "SYSTEM ERROR - EH_Defend::handlEvent9";

							

							return false;

						}

						

						// записываем армию участником во все идущие бои

						for ( $i=0; $i<sizeof ( $ehres ); $i++ ) {



							$ehsql = sprintf ( "select *

									from ds_battleparticipanthasunitcontainer bphuc,

									ds_battleparticipant bp

									where bphuc.id_battleparticipant = bp.id_battleparticipant

									and bp.id_battle = %d

									and bphuc.id_unitcontainer = %d

									and bp.id_userinstance = %d",

									$ehres[$i]['id_battle'],

									$this->eventparams["ID_Army"],

									$this->eventparams["ID_UserInstanceTo"] );

								

							$ehres2 = $this->common->getResultArray ( "bp", $ehsql, "", "", "" );

							if ( $ehres2 === false ) {

								$this->errormsg = "SYSTEM ERROR - EH_Defend::handlEvent10";

								

								return false;

							}



							if ( sizeof ( $ehres2 ) == 0 ) {



								$ehsql = sprintf ( "insert into ds_battleparticipanthasunitcontainer

										( id_battleparticipant, id_unitcontainer )

										values ( %d, %d)",

										$ehres[$i]['id_battleparticipant'],

										$this->eventparams['ID_Army'] );



								$ehres3 = $this->common->executeQuery ( $ehsql );

								if ( $ehres3 === false ) {

									$this->errormsg = "SYSTEM ERROR - EH_Defend::handlEvent11";

									

									return false;

								}								

							}



							$ehres2 = "";

							$ehres3 = "";

						}						

					}



					$messparams[0] = "{b} ".$attackerarmyname."{/b}";

					$messparams[1] = "{b} ".$defendercombname."{/b}";



					// системное сообщение

					DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'DEFEND_MESS3', $messparams, 1 );



				break;



				case 2: // вернулась домой



					$ehsql = sprintf ( "delete from 

							ds_unitcontaineraction

							where id_unitcontainer = %d",

							$this->eventparams["ID_Army"] );



					$ehres = $this->common->executeQuery ( $ehsql );

					if ( $ehres === false ) {

						$this->errormsg = "SYSTEM ERROR - EH_Defend::handlEvent11";

						

						return false;

					}



					$ehsql = sprintf ( "update ds_unitcontainer

							set inaction = false,

							id_currentcomb = null

							where id_unitcontainer = %d",

							$this->eventparams["ID_Army"] );



					$ehres = $this->common->executeQuery ( $ehsql );

					if ( $ehres === false ) {

						$this->errormsg = "SYSTEM ERROR - EH_Defend::handlEvent12";

						

						return false;

					}



					$messparams[0] = $attackerarmyname;

					$messparams[1] = $attackercombname;



					DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'ATTACK_MESS3', $messparams, 1 );



					$this->setTrash ();



				break;

			}



		} else { // вроцессе



			$messparams[0] = $attackerarmyname;



			switch ( $this->eventparams["Stage"] ) {



				case 1: // идет к месту назначения

				

					$ehsql = sprintf ( "select date_part( 'day', ( eventendtime - now())) as d,

							date_part( 'hour', ( eventendtime - now())) as h,

							date_part( 'minute', ( eventendtime - now())) as m,

							round (date_part( 'second', ( eventendtime - now()))) as s

							from ds_eventqueue

							where id_eventqueue = %d",

							$this->eqid );



					$ehres = $this->common->getResultArray ( "end_date", $ehsql, "", "", "" );

					if ( !$ehres ) {

						$this->errormsg = "SYSTEM ERROR - EH_Defend::handlEvent13";

						

						return false;

					}



					$messparams[2] = $ehres[0]['d'];

					$messparams[3] = $ehres[0]['h'];

					$messparams[4] = $ehres[0]['m'];

					$messparams[5] = $ehres[0]['s'];

						

					$messparams[1] = $defendercombname;



					DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'DEFEND_MESS2', $messparams, 1 );



				break;



				case 2: // идет домой

				

					$ehsql = sprintf ( "select date_part( 'day', ( eventendtime - now())) as d,

							date_part( 'hour', ( eventendtime - now())) as h,

							date_part( 'minute', ( eventendtime - now())) as m,

							round (date_part( 'second', ( eventendtime - now()))) as s

							from ds_eventqueue

							where id_eventqueue = %d",

							$this->eqid );



					$ehres = $this->common->getResultArray ( "end_date", $ehsql, "", "", "" );

					if ( !$ehres ) {

						$this->errormsg = "SYSTEM ERROR - EH_Defend::handlEvent14";

						

						return false;

					}



					$messparams[2] = $ehres[0]['d'];

					$messparams[3] = $ehres[0]['h'];

					$messparams[4] = $ehres[0]['m'];

					$messparams[5] = $ehres[0]['s'];



					$messparams[1] = $attackercombname;



					DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'ATTACK_MESS2', $messparams, 1 );


				break;

			}

		}

		return true;
	}

}



?>