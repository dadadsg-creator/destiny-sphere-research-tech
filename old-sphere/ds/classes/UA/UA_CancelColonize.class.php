<?php $CVS_Version = '$Revision$ ; $Date$';

// команда отмена колонизации
class UA_CancelColonize extends UA_Action
{
	public $id_module;
	public $combnamefrom;
	public $coordsfrom;
	public $combnameto;
	public $coordsto;
	public $bindeqid;
	public $errorname;
	public $id_comb_to;
	public $traveltime;

	function UA_CancelColonize ()
	{
		UA_Action::UA_Action ();
	}

	// провер€ем параметры
	function checkLocalParams ()
	{

		// id колонизационного модул€
		$this->id_module = $this->root->get_attribute ( "id_module" );
		if ( empty ( $this->id_module ) )
		{
			$this->errormsg .= " SYSTEM ERROR UA_CancelColonize::checkLocalParams1";
			return false;
		}

		return true;
	}

	// провер€ем доступность действи€
	function checkAction ()
	{
		global $common;
		global $params;

		// получаем информацию об эвенте
		$sql = "
			SELECT
    			t.id_eventqueue,
    			t.istrash,
				(
       				SELECT
            			eho1.value
	   				FROM
            			ds_eventhasoption eho1,
						ds_eventoption eo1
	   				WHERE
            			eho1.id_eventoption = eo1.id_eventoption
	        			AND eo1.optionname = 'Stage'
	        			AND eho1.id_eventqueue = t.id_eventqueue
    			) AS stage,
				(
       				SELECT 
       					eho1.value
       				FROM
           				ds_eventhasoption eho1,
	       				ds_eventoption eo1
       				WHERE
           				eho1.id_eventoption = eo1.id_eventoption
           				AND eo1.optionname = 'TravelTime'
	       				AND eho1.id_eventqueue = t.id_eventqueue
    			) AS traveltime
				FROM
    			(
           			SELECT
               			eq.id_eventqueue,	
               			eq.istrash
	       			FROM
               			ds_eventhasoption eho,
			   			ds_eventqueue eq,
			   			ds_eventparticipant ep,
			   			ds_eventoption eo,
			   			ds_event e
           			WHERE
               			eho.id_eventqueue = eq.id_eventqueue
		       			AND eq.id_eventqueue = ep.id_eventqueue
		       			AND ep.id_userinstance = ".$params["uiid"]."
		       			AND eho.id_eventoption = eo.id_eventoption
		       			AND eq.id_event = e.id_event
		       			AND e.eventname = 'COLONIZE'
		       			AND eo.optionname = 'ID_Module'
		       			AND eho.value = ".$this->id_module."
		       			AND eq.istrash != TRUE
    			) t ";

		$res = $common->getResultArray ( "unitcontainer_has_bind_eventqueue_id_userinstance_id_army_".$params["uiid"]."_".$this->id_module, $sql, "", "", "" );
		if ( !$res )
		{
			$this->errormsg .= " SYSTEM ERROR UA_CancelColonize::checkAction1";
			return false;
		}

		// если фаза не путь туда
		if ( $res[0]['stage'] != 1 )
		{
			$this->errormsg .= " SYSTEM ERROR UA_CancelColonize::checkAction2";
			return false;
		}

		$this->bindeqid = (int)$res[0]['id_eventqueue'];
		$this->traveltime = (float)$res[0]['traveltime'];

		// название и координаты места назначени€
		$sql = "SELECT
					id_startcomb,
					id_targetcomb
				FROM ds_unitcontaineraction
				WHERE
					id_unitcontainer = ".$this->id_module;
		$res2 = $common->getResultArray ( "", $sql, "", "", "" );
		assert(empty($common->errormsg));

		$this->id_comb_to = (int)$res2[0]['id_targetcomb'];
		$this->id_comb_from = (int)$res2[0]['id_startcomb'];

		$combTo = DE_Comb::CreateFromID((int)$this->id_comb_to);
		$combFrom = DE_Comb::CreateFromID((int)$this->id_comb_from);

		$this->combnameto = $combTo->getName();
		$this->coordsto = $combTo->getFormatedAddress();


		// название и координаты места отправлени€
		$this->combnamefrom = $combFrom->getName();
		$this->coordsfrom = $combFrom->getFormatedAddress();

		return true;
	}

	// выполн€ем действие
	function performAction ()
	{
		global $params;
		global $common;

		if ( !$this->errorname )
		{
			if(GL_IsUserInVocationMode::Test(DE_UserInstance::CreateFromID((int)$this->uiid)))
		{
			$this->_vocationModeAlert();			
			return true;
		}

		if(DE_PowerShild::CreateFromUserInstance((int)$this->uiid)->isWorks())
		{
			$this->_shildModeAlert();			
			return true;
		}

			// смотрим сколько уже прошло времени
			$sql = sprintf ( "select *,now() > eventendtime as f,
					date_part('epoch', now() - eventendtime)/60/
					( ".$this->worldSettings->get('TURN_TIME')." ) as passed
					from ds_eventqueue
					where id_eventqueue = %d",
					$this->bindeqid );

			$res = $common->getResultArray ( "bind_eventqueue_is_finished_id_eventqueue".$this->bindeqid, $sql, "", "", "" );
//DebugTools::logAll($sql, '!zya_UA_CancelColonize.log');
//DebugTools::logAll($res, '!zya_UA_CancelColonize.log');

			if($res == false)
			{
				$this->errormsg .= " SYSTEM ERROR UA_CancelColonize::performAction1";
				return false;
			}

			$passed = (float)$res[0]['passed'];



			// апдейтим врем€ окончани€ эвента

			if ( $res[0]['f'] == 't' || ( $passed > $this->traveltime ) )
			{
				$sql = sprintf ( "update ds_eventqueue
						set eventendtime =
						(select now() + (eventendtime - eventstarttime)
						from ds_eventqueue
						where id_eventqueue = %d)
						where id_eventqueue = %d",
						$this->bindeqid,
						$this->bindeqid );
			} else {
				$sql = sprintf ( "update ds_eventqueue
						set eventendtime =
						(select now() + (now() - eventstarttime)
						from ds_eventqueue
						where id_eventqueue = %d)
						where id_eventqueue = %d",
						$this->bindeqid,
						$this->bindeqid );
			}

			$res = $common->executeQuery ( $sql );
			if ( $res === false )
			{
				$this->errormsg .= " SYSTEM ERROR UA_CancelColonize::performAction2";
				return false;
			}

			// мен€ем фазу эвента
			$eventOptions = DE_EventQueueAttributes::CreateFromEventID((int)$this->bindeqid);
			$eventOptions->setAttribute('Stage', 2);

			// стираем название колонии
			$sql = sprintf ( "delete from DS_CombName
					where id_comb = %d",
					$this->id_comb_to );

			$res = $common->executeQuery ( $sql );
			if ( $res === false )
			{
				$this->errormsg .= " SYSTEM ERROR UA_CancelColonize::performAction4";
				return false;
			}

			// стираем колонию
			$sql = sprintf ( "delete from ds_colony
					where id_comb_to = %d",
					$this->id_comb_to );

			$res = $common->executeQuery ( $sql );
			if ( $res === false )
			{
				$this->errormsg .= " SYSTEM ERROR UA_CancelColonize::performAction5";
				return false;
			}

			// убираем место назначени€ у модул€
			$sql = sprintf ( "update ds_unitcontaineraction
					set id_targetcomb = null
					where id_unitcontainer = %d",
					$this->id_module );

			$res = $common->executeQuery ( $sql );
			if ( $res === false )
			{
				$this->errormsg .= " SYSTEM ERROR UA_CancelColonize::performAction6";
				return false;
			}

			$messparams[0] = "{b} ".$this->combnameto."{/b}";
			$messparams[1] = "{b} ".$this->coordsto."{/b}";
			$messparams[2] = "{b} ".$this->combnamefrom."{/b}";
			$messparams[3] = "{b} ".$this->coordsfrom."{/b}";

			// системное сообщение
			DE_SystemMessages::AddMessageFromAction(__CLASS__, "CANCEL_COLONIZE_MESS", $messparams );
		}

		$res = "";
		$sql = "";

		// минимальный рефреш
		//if ( $_SESSION['profi_game'] )
		//{
			$noCellData = true;
		//} else {
		//	$noCellData = false;
		//}

		if ( !$this->refresh ( 2, "", "", "", $noCellData ) )
		{
			return false;
		}

		return true;
	}
}

?>