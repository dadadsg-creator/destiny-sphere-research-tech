<?
<?php $CVS_Version = "$Revision: 1.3 $ ; $Date: 2007/09/11 07:58:56 $";

class EH_Event {

	var $eventparams;
	var $eqid;
	var $errormsg;
	var $sysmsg;
	var $eventstarttime;
	var $eventendtime;
	var $combresupdate;
	var $halfrefresh;

	function EH_Event() {
		$this->eventparams = array ();
		$this->combresupdate = array ();
		$this->sysmsg = & ClassFactory::getInstance ( "SystemMessages" );
	}

	function getEventParams () {

		global $common;

		$ehsql = sprintf ( "select eo.OptionName,  eho.value
				from DS_EventHasOption eho,
				DS_EventOption eo
				where eho.ID_EventOption = eo.ID_EventOption
				and eho.ID_EventQueue = %d",
				$this->eqid );

		$ehres = $common->getResultArray ( "event_has_option", $ehsql, "", "", "" );
		if ( $ehres === false ) {
			$this->errormsg = "SYSTEM ERROR - EH_Event::getEventParams1";
			
			return false;
		}

		if ( sizeof ( $ehres ) == 0 ) {
			return true;
		}

		for ( $i=0; $i<sizeof ( $ehres ); $i++ ) {
			$this->eventparams[$ehres[$i]['optionname']] = $ehres[$i]['value'];
		}

		$ehsql = sprintf ( "select eq.eventstarttime, eq.eventendtime
				from DS_EventQueue eq
				where ID_EventQueue = %d",
				$this->eqid );

		$ehres = $common->getResultArray ( "starttime_endtime", $ehsql, "", "", "" );
		if ( !$ehres ) {
			$this->errormsg = "SYSTEM ERROR - EH_Event::getEventParams2";
			
			return false;
		}

		$this->eventstarttime = $ehres[0]['eventstarttime'];
		$this->eventendtime = $ehres[0]['eventendtime'];

		return true;
	}

	function checkEventParams () {
		return true;
	}

	function handleEvent ( $eqid ) {
		return true;
	}

	function createChildEvent () {
		return true;
	}

	function setTrash ( $eqtotrash = "" ) {
		
		global $common;

		if ( $eqtotrash == "" ) {
			$eqtotrash = $this->eqid;
		}
		
		$ehsql = sprintf ( "update only DS_EventQueue 
				set istrash = true 
				where ID_EventQueue = %d", 
				$eqtotrash );

		$ehres = $common->executeQuery ( $ehsql );
		if ( $ehres === false ) {
			$this->errormsg = "SYSTEM ERROR - EH_Event::setTrash1";
			
			return false;
		}

		return true;
	}

	function updateScoreUp ( $combid, $uiid, $bid ) {

		global $common;

		$ehsql = sprintf ( "select COALESCE(sum(combhas),0) as brank
				from (select 
				bha.value*(select exchangerate
				from ds_resource
				where id_attributename = atn.id_attributename) as combhas
				from ds_buildinghasattribute bha,
				ds_attribute a,
				ds_attributename atn,
				ds_attributetype att
				where bha.id_building = %d
				and bha.id_attribute = a.id_attribute
				and a.id_attributename = atn.id_attributename
				and a.id_attributetype = att.id_attributetype
				and att.typename = 'PRODUCTION_COSTS'
				and atn.attributename != 'CONSTRUCTION_TIME') as t",
				$bid );

		$ehres = $common->getResultArray ( "building_cost_id_building_".$bid, $ehsql, "", "", "GLOBAL_CACHE" );
		if ( !$ehres ) {
			$this->errormsg = "SYSTEM ERROR - EH_Event::updateScoreUp1";
			
			return false;
		}


		$brank = $ehres[0]['brank'];

		$ehsql = sprintf ( "update only ds_comb
				set combscore = combscore + %d
				where id_comb = %d",
				$brank/10, $combid );

		$ehres = $common->executeQuery ( $ehsql );
		if ( $ehres === false ) {
			$this->errormsg = "SYSTEM ERROR - EH_Event::updateScoreUp2";
			
			return false;
		}

		$ehsql = sprintf ( "update only ds_userinstance
				set userscore = userscore + %d
				where id_userinstance = %d",
				$brank/10, $uiid );

		$ehres = $common->executeQuery ( $ehsql );
		if ( $ehres === false ) {
			$this->errormsg = "SYSTEM ERROR - EH_Event::updateScoreUp3";
			
			return false;
		}

		return true;
	}

	function updateScoreDown ( $combid, $uiid, $bid ) {
		
		global $common;

		$ehsql = sprintf ( "select COALESCE(sum(combhas),0) as brank
				from (select 
				bha.value*(select exchangerate
				from ds_resource
				where id_attributename = atn.id_attributename) as combhas
				from ds_buildinghasattribute bha,
				ds_attribute a,
				ds_attributename atn,
				ds_attributetype att
				where bha.id_building = %d
				and bha.id_attribute = a.id_attribute
				and a.id_attributename = atn.id_attributename
				and a.id_attributetype = att.id_attributetype
				and att.typename = 'PRODUCTION_COSTS'
				and atn.attributename != 'CONSTRUCTION_TIME') as t",
				$bid );

		$ehres = $common->getResultArray ( "building_cost_id_building_".$bid, $ehsql, "", "", "GLOBAL_CACHE" );
		if ( !$ehres ) {
			$this->errormsg = "SYSTEM ERROR - EH_Event::updateScoreDown1";
			
			return false;
		}

		$brank = $ehres[0]['brank'];

		$ehsql = sprintf ( "update only ds_comb
				set combscore = combscore - %d
				where id_comb = %d",
				$brank/10, $combid );

		$ehres = $common->executeQuery ( $ehsql );
		if ( $ehres === false ) {
			$this->errormsg = "SYSTEM ERROR - EH_Event::updateScoreDown2";
			
			return false;
		}

		$ehsql = sprintf ( "update only ds_userinstance
				set userscore = userscore - %d
				where id_userinstance = %d",
				$brank/10, $uiid );

		$ehres = $common->executeQuery ( $ehsql );
		if ( $ehres === false ) {
			$this->errormsg = "SYSTEM ERROR - EH_Event::updateScoreDown3";
			
			return false;
		}

		return true;
	}

	function refreshUserinstance ( $id_userinstance, $eh = 0 ) { // lev 1 - with event handling

		global $common;
		global $checktime;
		global $params;

		$common->clearLocalCache ( $id_userinstance );

		$checktime = 'now()';

		if ( $eh == 1 ) {

			$ehsql = sprintf ( "select eq.id_eventqueue
					from ds_eventqueue eq,
					ds_eventparticipant ep
					where  eq.id_eventqueue = ep.id_eventqueue
					and ep.id_userinstance = %d
					and eq.istrash = false
					for update",
					$id_userinstance );

			$ehres = $common->getResultArray ( "lock_events", $ehsql, "", "", "" );
			if ( $ehres === false ) {
				$this->errormsg = "SYSTEM ERROR - EH_Attack::refreshUserinstance1";
				
				return false;
			}

			$ehsql = sprintf ( "select eq.id_eventqueue,
					eh.eventhandlerclass,
					(select count(id_userinstance)
					from ds_eventparticipant
					where id_eventqueue = eq.id_eventqueue) as amount
					from ds_eventqueue eq,
					ds_eventparticipant ep,
					ds_event e,
					ds_eventhandler eh
					where  eq.id_eventqueue = ep.id_eventqueue
					and eq.id_event = e.id_event
					and e.id_eventhandler = eh.id_eventhandler
					and ep.id_userinstance = %d
					and eq.istrash = false
					order by eq.eventendtime",
					$id_userinstance );

			$ehres = $common->getResultArray ( "user_events", $ehsql, "", "", "" );
			if ( $ehres === false ) {
				$this->errormsg = "SYSTEM ERROR - EH_Attack::refreshUserinstance1";
				
				return false;
			}

			for ( $i=0; $i<sizeof ( $ehres ); $i++ ) {

				if ( $ehres[$i]['amount'] == 1 ) {

					$ehandler = & ClassFactory::getInstance( $ehres[$i]['eventhandlerclass'] );

					if ( !$ehandler ) {
						continue;
					}

					//$ehandler->sysmsg = &$this->sysmsg;
					$ehandler->sysmsg = & ClassFactory::getInstance ( "SystemMessages" );

					$ehandler->halfrefresh = 1;
					$ehandler->checktime = $this->eventendtime;

					if ( !$ehandler->handleEvent ( $ehres[$i]['id_eventqueue'] ) ) {

						$this->errormsg = $ehandler->errormsg;
						$common->errormsg = '';
				
						return false;
					}

					$ehandler = "";
				}
			}
		}

		$turncalc2 = & ClassFactory::getInstance ( "TurnCalc" );
		if ( !$turncalc2 ) {
			$this->errormsg .= " SYSTEM ERROR - EH_Attack::refreshUserinstance2";
			return false;
		}

		$turncalc2->halfrefresh = 1;
		$turncalc2->checktime = $this->eventendtime;

		$ehsql = sprintf ( "select id_comb
				from ds_comb
				where id_userinstance = %d
				for update",
				$id_userinstance );

		$ehres = $common->getResultArray ( "comb_id_userinstance", $ehsql, "", "", "" );
		if ( !$ehres ) {
			$this->errormsg = "SYSTEM ERROR - EH_Attack::refreshUserinstance3";
			
			return false;
		}

		for ( $i=0; $i<sizeof ( $ehres ); $i++ ) {

			if ( !$turncalc2->calculateComb ( $ehres[$i]['id_comb'], $id_userinstance ) ) {
				$this->errormsg = $turncalc2->errormsg;
				return false;
			}
		}

		$ehsql = sprintf ( "update ds_userinstance 
				set lastrefresh = '%s'::timestamp
				where id_userinstance = %d",
				$this->eventendtime, 
				$id_userinstance );

		$ehres = $common->executeQuery ( $ehsql );
		if ( $ehres === false ) {
			$this->errormsg = "SYSTEM ERROR - EH_Attack::refreshUserinstance4";
			
			return false;
		}

		$checktime = '';

		return true;
	}
	
	function isStillAlive ( $id_eventqueue, $id_userinstance ) {
		
		global $common;

		/*$ehsql = sprintf ( "select id_eventqueue
				from ds_eventqueue
				where id_eventqueue = %d
				for update",
				$id_eventqueue );
				
		$ehres = $common->executeQuery ( $ehsql );
		if ( $ehres === false ) {
			$this->errormsg = "SYSTEM ERROR - EH_Event::isStillAlive1";
			
			return false;
		}*/
		
		$ehsql = sprintf ( "select eq.id_eventqueue
				from ds_eventqueue eq,
				ds_eventparticipant ep
				where eq.id_eventqueue = ep.id_eventqueue
				and ep.id_userinstance = %d
				and eq.istrash = false
				and eq.id_eventqueue = %d",
				$id_userinstance,
				$id_eventqueue );

		$ehres = $common->getResultArray ( "is_event_alive_id_eventqueue_id_userinstance_", $ehsql, "", "", "" );
		if ( $ehres === false ) {
			$this->errormsg = "SYSTEM ERROR - EH_Event::isStillAlive2";
			
			return false;
		}
		
		if ( sizeof ( $ehres ) == 0 ) return false;
		
		return true;
	}
}

?>