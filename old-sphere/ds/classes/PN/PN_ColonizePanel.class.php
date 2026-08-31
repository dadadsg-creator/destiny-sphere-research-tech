<?php $CVS_Version = '$Revision$ ; $Date$';

// закладка колонизационного центра, отправка модуля

class PN_ColonizePanel extends UA_GetBuildMenu {

	function __construct() {
		UA_GetBuildMenu::UA_GetBuildMenu ();
	}

	function getPanel () {

		global $params;
		global $common;
		
		$currentaction = '';
		// action
		// 3 - go to melsin
		// 2 - go home
		// 1 - stay home
		// 4 - stay at melsin
		
		$delta = DE_User::CreateFromID((int)$_SESSION['uid'])->getTimeDelta();


		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><colonize />";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();
		//$root->set_attribute ( "c", $this->cellnum );
		//$ch = $root->new_child ( "action" );
		
		// информация о колонизационном модуле
		$sql = sprintf ( "select *
				from ds_unitcontainer uc,
				ds_unitorcontainertype uct
				where uc.id_unitcontainertype = uct.id_unitcontainertype
				and uct.containertypename = 'COLONIZATION'
				and uc.id_basecomb = %d",
				$this->comb );

		
		$res = $common->getResultArray ( "colonize_modules_id_comb_".$this->comb, $sql, "", $params["uiid"], "LOCAL_CACHE" );

		if ( !$res ) {
			$this->errormsg .= " SYSTEM ERROR ColonyPanel::getPanel1";
			
			return false;
		}

		$root->set_attribute ( "id_module", $res[0]['id_unitcontainer'] );

		// если модуль в движении
		if ( $res[0]['inaction'] == 't' ) { // едет на мелсион или возвращается домой
			
			// куда едет и когда приедет
			$sql = sprintf ( "select id_targetcomb,
					(select combname
					from ds_combname
					where id_comb = id_targetcomb) as colonyname,
					(select ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber
					from ds_ring ri,
					ds_combcomplex cc,
					ds_comb co
					where ri.id_ring = cc.id_ring
					and cc.id_combcomplex = co.id_combcomplex
					and co.id_comb = id_targetcomb) as colonycoords,
					to_char ( eq.eventendtime + '%f hour'::interval, 'DD.MM') as enddate,
					to_char ( eq.eventendtime + '%f hour'::interval, 'HH24:MI') as endtime
					from ds_unitcontaineraction uca,
					ds_unitcontainerbindeventqueue ucbeq,
					ds_eventqueue eq
					where uca.id_unitcontainer = %d
					and uca.id_unitcontainer = ucbeq.id_unitcontainer
					and ucbeq.id_eventqueue = eq.id_eventqueue",
					$delta,
					$delta,
					$res[0]['id_unitcontainer'] );
					
			$res2 = $common->getResultArray ( "colonize_module_action_id_unitcontainer_".$res[0]['id_unitcontainer'], $sql, "", $params["uiid"], "LOCAL_CACHE" );
			if ( !$res2 ) {
				$this->errormsg .= " SYSTEM ERROR ColonyPanel::getPanel2";
				
				return false;
			}

			$colonyname = $res2[0]['colonyname'];
			$colonycoords = $res2[0]['colonycoords'];
			$enddate = $res2[0]['enddate'];
			$endtime = $res2[0]['endtime'];
			
			if ( $res2[0]['id_targetcomb'] ) { // едет на мелсион
			
				$root->set_attribute ( "action", "2" );

				$sql = sprintf ( "select ltl.text as message,
						sm.numberofparams
						from ds_localizedtextlanguage ltl,
						ds_systemmessage sm
						where ltl.id_localizedtext = sm.id_localizedtext
						and ltl.id_language = %d
						and sm.messagename = 'MODULE_GOES_TO_COLONY'",
						$params['lang'] );
	
				$res2 = $common->getResultArray ( "system_message_module_goes_to_colony_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
				if ( !$res2 ) {
					$this->errormsg .= " SYSTEM ERROR ColonyPanel::getPanel3";
					
					return false;
				}

				$currentaction = $res2[0]['message'];
	
				$messageparams[0] = $colonyname;
				$messageparams[1] = $colonycoords;
				$messageparams[2] = $enddate;
				$messageparams[3] = $endtime;

				for ($e=0; $e<$res2[0]['numberofparams']; $e++) {
					$pos = strpos($currentaction, "%");
					if ($pos === false) {
						$this->errormsg .= " SYSTEM ERROR ColonyPanel::getPanel4";
						
						return false;
					}
					$currentaction = substr_replace($currentaction, $messageparams[$e], $pos, 1);
				}

			} else { // едет домой

				$root->set_attribute ( "action", "3" );

				$sql = sprintf ( "select id_startcomb,
						(select combname
						from ds_combname
						where id_comb = id_startcomb) as homename,
						(select ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber
						from ds_ring ri,
						ds_combcomplex cc,
						ds_comb co
						where ri.id_ring = cc.id_ring
						and cc.id_combcomplex = co.id_combcomplex
						and co.id_comb = id_startcomb) as homecoords
						from ds_unitcontaineraction
						where id_unitcontainer = %d",
						$res[0]['id_unitcontainer'] );
					
				$res2 = $common->getResultArray ( "colonize_module_action_go_home_id_unitcontainer_".$res[0]['id_unitcontainer'], $sql, "", $params["uiid"], "LOCAL_CACHE" );
				if ( !$res2 ) {
					$this->errormsg .= " SYSTEM ERROR ColonyPanel::getPanel2";
					
					return false;
				}

				$messageparams[0] = $res2[0]['homename'];
				$messageparams[1] = $res2[0]['homecoords'];

				$sql = sprintf ( "select ltl.text as message,
						sm.numberofparams
						from ds_localizedtextlanguage ltl,
						ds_systemmessage sm
						where ltl.id_localizedtext = sm.id_localizedtext
						and ltl.id_language = %d
						and sm.messagename = 'MODULE_GOES_HOME'",
						$params['lang'] );
	
				$res2 = $common->getResultArray ( "system_message_module_goes_home_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
				if ( !$res2 ) {
					$this->errormsg .= " SYSTEM ERROR ColonyPanel::getPanel5";
					
					return false;
				}

				$currentaction = $res2[0]['message'];
	
				$messageparams[2] = $enddate;
				$messageparams[3] = $endtime;

				for ($e=0; $e<$res2[0]['numberofparams']; $e++) {
					$pos = strpos($currentaction, "%");
					if ($pos === false) {
						$this->errormsg .= " SYSTEM ERROR ColonyPanel::getPanel6";
						
						return false;
					}

					$currentaction = substr_replace($currentaction, $messageparams[$e], $pos, 1);
				}
			}

		} else { // находится дома или уже на мелсионе

			if ( $res[0]['id_currentcomb'] ) { // на мелсионе ( колонизирована сота )

				$root->set_attribute ( "action", "4" );
				
				// название колонии
				$sql = sprintf ( "select combname as colonyname
						from ds_combname
						where id_comb = %d",
						$res[0]['id_currentcomb'] );

						
				$res2 = $common->getResultArray ( "colonyname_id_comb_".$res[0]['id_currentcomb'], $sql, "", "", "GLOBAL_CACHE" );

				if ( !$res2 ) {
					$this->errormsg .= " SYSTEM ERROR ColonyPanel::getPanel7";
					
					return false;
				}
				
				$colonyname = $res2[0]['colonyname'];

				// координаты колонии
				$sql = sprintf ( "select ri.ringnumber||'.'||cc.complexnumber||'.'||co.combnumber as colonycoords
						from ds_ring ri,
						ds_combcomplex cc,
						ds_comb co
						where ri.id_ring = cc.id_ring
						and cc.id_combcomplex = co.id_combcomplex
						and co.id_comb = %d",
						$res[0]['id_currentcomb'] );

				$res2 = $common->getResultArray ( "combcoords_id_comb_".$res[0]['id_currentcomb'], $sql, "", "", "GLOBAL_CACHE" );
				if ( !$res2 ) {
					$this->errormsg .= " SYSTEM ERROR ColonyPanel::getPanel8";
					
					return false;
				}

				$colonycoords = $res2[0]['colonycoords'];

				// текст в меню что модуль на колонии
				$sql = sprintf ( "select ltl.text as message,
						sm.numberofparams
						from ds_localizedtextlanguage ltl,
						ds_systemmessage sm
						where ltl.id_localizedtext = sm.id_localizedtext
						and ltl.id_language = %d
						and sm.messagename = 'MODULE_COLONIZE_COMB'",
						$params['lang'] );
	
				$res2 = $common->getResultArray ( "system_message_module_colonyze_comb_id_language_".$params["lang"], $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
				if ( !$res2 ) {
					$this->errormsg .= " SYSTEM ERROR ColonyPanel::getPanel9";
					
					return false;
				}

				$currentaction = $res2[0]['message'];
	
				$messageparams[0] = $colonyname;
				$messageparams[1] = $colonycoords;

				for ($e=0; $e<$res2[0]['numberofparams']; $e++) {
					$pos = strpos($currentaction, "%");
					if ($pos === false) {
						$this->errormsg .= " SYSTEM ERROR ColonyPanel::getPanel10";
						
						return false;
					}
					$currentaction = substr_replace($currentaction, $messageparams[$e], $pos, 1);
				}

			} else { // находится дома

				$root->set_attribute ( "action", "1" );
				
				// кольца, куда можно послать модуль
				$sql = sprintf ( "select ri.ringnumber,
						ltl.text as ringname
						from ds_ring ri,
						(select distinct cc1.id_ring
						from ds_combcomplex cc1,
						ds_combcomplextype cct
						where cc1.id_complextype = cct.id_complextype
						and cct.complextype = 'COLONIZEABLE') t,
						ds_localizedtextlanguage ltl
						where ri.id_ring = t.id_ring
						and ri.id_world = %d
						and ri.id_localizedname = ltl.id_localizedtext
						and ltl.id_language = %d",
						$this->world,
						$params['lang'] );
						
				$res2 = $common->getResultArray ( "rings_to_colonize_id_world_id_language_".$this->world."_".$params["lang"], $sql, "", "", "GLOBAL_CACHE" );
		   
   				
				if ( $res2 === false ) {
					$this->errormsg .= " SYSTEM ERROR ColonyPanel::getPanel10";
					
					return false;
				}

				for ( $i=0; $i<sizeof ( $res2 ); $i++ ) {
				
					$ch = $root->new_child ( "ring" );
					$ch->set_attribute ( "ringnumber", $res2[$i]['ringnumber'] );
					$ch->set_attribute ( "ringname", urlencode ( $res2[$i]['ringname'] ) );					
				}			
			}
		}
		
		if($currentaction) 
		{
			$elem = $doc->create_text_node ( urlencode ( $currentaction ) );
			$root->append_child ( $elem );
		}

		// закладки
		$this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab );		

		SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );		

		return true;
	}
}

?>