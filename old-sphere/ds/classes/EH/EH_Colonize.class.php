<?php $CVS_Version = '$Revision: 1.8 $ ; $Date: 2009/08/06 09:22:11 $';

// обработчик колонизации
class EH_Colonize extends EH_Event
{
	public $checktime;

	function EH_Colonize()
	{
		EH_Event::EH_Event();
	}

	function handleEvent( $eqid )
	{
		global $common;
		global $params;

		$this->eqid = $eqid;

		// внешний рефреш
		if ( !$this->checktime ) $this->checktime = 'now()';

		// снимаем параметры эвента
		if ( !$this->getEventParams () )
		{
			return false;
		}

		// название соты откуда
		$combnamefrom = DE_Comb::CreateFromID((int)$this->eventparams["ID_CombFrom"])->getName();

		// фаза эвента
		$stage = $this->eventparams["Stage"];

		// если закончен
		if ($this->_isTimeToHandle())
		{
			//finished
			switch ( $stage )
			{
				case 1: // прибыл на кольцо колоний

					// название и координаты колонии
					$comb = DE_Comb::CreateFromID((int)$this->eventparams["ID_CombTo"]);

					$colonycoords = $comb->getFormatedAddress();
					$colonyname = $comb->getName();

					// прибиваем действие
					$ehsql = sprintf ( "delete from
							ds_unitcontaineraction
							where id_unitcontainer = %d",
							$this->eventparams["ID_Module"] );

					$ehres = $common->executeQuery ( $ehsql );
					if ( $ehres === false )
					{
						$this->errormsg = "SYSTEM ERROR - EH_Colonize::handlEvent4";
						return false;
					}

					// снимаем эвент с модуля
					$ehsql = sprintf ( "delete from
							ds_unitcontainerbindeventqueue
							where id_unitcontainer = %d",
							$this->eventparams["ID_Module"] );

					$ehres = $common->executeQuery ( $ehsql );
					if ( $ehres === false )
					{
						$this->errormsg = "SYSTEM ERROR - EH_Colonize::handlEvent5";
						return false;
					}

					// снимаем действие с модуля
					$ehsql = sprintf ( "update ds_unitcontainer
							set inaction = false,
							id_currentcomb = %d
							where id_unitcontainer = %d",
							$this->eventparams["ID_CombTo"],
							$this->eventparams["ID_Module"] );

					$ehres = $common->executeQuery ( $ehsql );
					if ( $ehres === false )
					{
						$this->errormsg = "SYSTEM ERROR - EH_Colonize::handlEvent6";
						return false;
					}

					/*
					 	ПРОШУ ОТМЕТИТЬ ЧТО ЭТО ЕДИСНТВЕНО МЕСТО ГДЕ МЕНЯЕТЬСЯ ПРИНАДЛЕЖНОСТЬ СОТЫ ИГРОКУ!!!!!! В ПРОЦЕССЕ ИГРЫ!!!!
					*/
					
					// записываем юзера хозяином соты
					$ehsql = sprintf ( "update ds_comb
							set id_userinstance = %d,
							combscore = 0
							where id_comb = %d",
							$this->eventparams["ID_UserInstance"],
							$this->eventparams["ID_CombTo"] );
					$ehres = $common->executeQuery ( $ehsql );
					if ( $ehres === false )
					{
						$this->errormsg = "SYSTEM ERROR - EH_Colonize::handlEvent7";
						return false;
					}
					
					//И не забыли в объекте который уже создан пропистаь инстанс игорка как хозяина
					$comb->setUserInstanceID($this->eventparams["ID_UserInstance"]);
					
					//SU_DebugTools::LogAll('eventparams='.var_export($this->eventparams, true),'refs.log');
					// подготавливаем соту
					$userInstance = DE_UserInstance::CreateFromID((int)$this->eventparams["ID_UserInstance"]);
					$id_race = $userInstance->getRaceID();
					
			
					$wu = new WorldUtils;
					if ( !$wu )
					{
						$this->errormsg = "SYSTEM ERROR - EH_Colonize::handlEvent9";
						return false;
					}

					// добавляем ресурсы
					if ( !$wu->setCombResource ( $this->eventparams["ID_CombTo"], $id_race ) )
					{
						$this->errormsg = $wu->errormsg;
						return false;
					}

					//учитываем бонусы религии
					if(!$wu->setReligionBonuses ($this->eventparams["ID_UserInstance"], $this->eventparams["ID_CombTo"]))
					{
						$this->errormsg = $wu->errormsg;
						return false;
					}

					// создаем дефолтовую армию
					$armyname = $colonyname." ".'{|HOME_ARMY|}';

					if ( strlen( $armyname ) > 30 ) $armyname = substr ( $armyname, 0, 29 );

					$ehsql = sprintf ( "insert into ds_unitcontainer ( id_basecomb, id_unitcontainertype, id_userinstance, moveable,
							inaction, unitcontainername, isDefault ) values ( %d, 1, %d, false, false, '%s', true )",
							$this->eventparams["ID_CombTo"], $this->eventparams["ID_UserInstance"], $armyname );

					$ehres = $common->executeQuery ( $ehsql );
					if ( $ehres === false )
					{
						$this->errormsg = "SYSTEM ERROR - EH_Colonize: Cannot create home army $armyname for comb ".$this->eventparams["ID_CombTo"]." (ui=".$this->eventparams["ID_UserInstance"].")"; // EH_Colonize::handlEvent11
						return false;
					}
					
					//Ресетим грунт на соте что бы убрть последствия тероформации
					for($celltype=1; $celltype<4; $celltype++) 
					{

						$sql = "SELECT 
									id_celltemplate
								FROM ds_celltemplate
								WHERE id_celltype = ".$celltype;
						$res = $common->getResultArray("", $sql, "", "", "");
						assert(empty($common->errormsg));			

						for( $i=0; $i<sizeof($res); $i++)
						{
							$templates[$i] = $res[$i]['id_celltemplate'];
						}

						$templrandid = $templates[rand(0, sizeof($templates)-1)];

						$sql = 	"
							UPDATE 
								ds_cell
							SET id_groundtype = t.id_groundt
							FROM
								(
									SELECT
										id_groundtype AS id_groundt,
										cellnumber AS cellnum
									FROM ds_celltemplateelement
									WHERE id_celltemplate = ".$templrandid."
								) AS t
							WHERE 
								id_comb = ".$this->eventparams["ID_CombTo"]."
								AND
								id_celltype = ".$celltype."
								AND
								cellnumber = t.cellnum";
						$res = $common->executeQuery($sql);
						assert(empty($common->errormsg));			
					}
					
					// удаляем инфу о резервировании соты
					$sql = "DELETE FROM ds_reservedcomb WHERE id_comb = ".$this->eventparams["ID_CombTo"];
					$res = $common->executeQuery($sql);
					assert(empty($common->errormsg));
					
					$messparams[0] = "{b} ".$colonyname."{/b}";
					$messparams[1] = "{b} ".$colonycoords."{/b}";

					// системное сообщение
					DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'COLONIZE_MESS1', $messparams );

				  $sql = "SELECT 
									count(id_comb) as cnt
								FROM ds_comb
								WHERE id_userinstance = ".$this->eventparams["ID_UserInstance"];
			  	$res = $common->getResultArray("", $sql, "", "", "");
				  assert(empty($common->errormsg));			
		      $cntColonies = $res[0]['cnt'];
		      if ($cntColonies==2) //Т.к. домашняя сота тоже учитывается, то если $cnt==2, то
				                //колоний всего одна, с учётом только что созданной), и 
				                //поэтому можно начислить реферальное бабло, если есть кому
          {
					  $recuser = DS_User::GetRecommendedUser($userInstance->getUserID());
					  
				    if ($recuser) //Юзер создал колонию и чей-то реферал
				    {
				      //теперь проверим общее число колоний. Если только одна
 				      $recommendid = $recuser['id_user_recomend'];
   			      $sum = intval(UC_PaymentGC::GetParam('COLONY_OPEN_SUM'));

   			      if ($sum > 0)
	  		         UC_PaymentServiceMoneyOperation::EnterMoney($recommendid, $sum, 'SC', 'REFERRAL_PAYMENT');
				    }
          }	
          
  			// прибиваем эвент
				$this->setTrash ();

				break;
				case 2: // вернулся домой (колонизация была отменена)
					$ehsql = sprintf ( "delete from
							ds_unitcontaineraction
							where id_unitcontainer = %d",
							$this->eventparams["ID_Module"] );

					$ehres = $common->executeQuery ( $ehsql );
					if ( $ehres === false )
					{
						$this->errormsg = "SYSTEM ERROR - EH_Colonize::handlEvent12";
						return false;
					}

					$ehsql = sprintf ( "delete from
							ds_unitcontainerbindeventqueue
							where id_unitcontainer = %d",
							$this->eventparams["ID_Module"] );

					$ehres = $common->executeQuery ( $ehsql );
					if ( $ehres === false )
					{
						$this->errormsg = "SYSTEM ERROR - EH_Colonize::handlEvent13";
						return false;
					}

					$ehsql = sprintf ( "update ds_unitcontainer
							set inaction = false
							where id_unitcontainer = %d",
							$this->eventparams["ID_Module"] );

					$ehres = $common->executeQuery ( $ehsql );
					if ( $ehres === false )
					{
						$this->errormsg = "SYSTEM ERROR - EH_Colonize::handlEvent14";
						return false;
					}

					$messparams[0] = "{b} ".$combnamefrom."{/b}";

					DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'COLONIZE_MESS2', $messparams );

					// прибиваем эвент
					$this->setTrash ();
				break;
			}
		} else {
			// в процессе
			switch ( $stage )
			{
				case 1: // едет на мелсион
					$comb = DE_Comb::CreateFromID((int)$this->eventparams["ID_CombTo"]);
					$colonycoords = $comb->getFormatedAddress();
					$colonyname = $comb->getName();

					$timeParts = $this->_getTimePeriodToEnd()->getWithParts();
					$messparams[0] = "{b} ".$colonycoords."{/b}";
					$messparams[1] = "{b} ".$colonyname."{/b}";
					$messparams[2] = $timeParts['DAYS'];
					$messparams[3] = $timeParts['HOURS'];
					$messparams[4] = $timeParts['MINUTES'];
					$messparams[5] = $timeParts['SECONDS'];

					// системное сообщение
					DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'COLONIZE_MESS3', $messparams, null, null, 'NOACCUMULATE');

				break;
				case 2: // едет домой
					$timeParts = $this->_getTimePeriodToEnd()->getWithParts();
					$messparams[0] = "{b} ".$combnamefrom."{/b}";
					$messparams[1] = $timeParts['DAYS'];
					$messparams[2] = $timeParts['HOURS'];
					$messparams[3] = $timeParts['MINUTES'];
					$messparams[4] = $timeParts['SECONDS'];

					// системное сообщение
					DE_SystemMessages::AddMessageToEventParticipants($this->eqid, 'COLONIZE_MESS4', $messparams, null, null, 'NOACCUMULATE');
				break;
			}
		}

		$ehres = "";
		$ehres2 = "";
		$ehres3 = "";
		$ehsql = "";

		return true;
	}

	public function handleInExternalRefresh(DE_EventQueue $event, DE_UserInstance $refreshedInstance, GB_Date $chekDate)
	{
		return false;
	}

}

?>