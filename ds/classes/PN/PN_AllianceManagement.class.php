<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2010/05/11 11:04:15 $';

// закладка здания ALLIANCE_CONNECTOR

class PN_AllianceManagement extends UA_GetBuildMenu {

	function __construct() {
		UA_GetBuildMenu::UA_GetBuildMenu ();
	}

	function getPanel () {

		global $params;
		global $common;

		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><alliancemanagement />";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();
		//$root->set_attribute ( "c", $this->cellnum );

		// смотрим в каком альянсе состоит игрок и какой статус имеет

		$sql = sprintf ( "select al.id_alliance,
				al.alliancename,
				al.alliancetag,
				(select religionname
				from ds_religion
				where id_religion = al.id_religion) as religion,
				(select ltl.text
				from ds_useralliancestatus uas,
				ds_userhasalliancestatus uhals,
				ds_localizedtextlanguage ltl
				where uas.id_status = uhals.id_status
				and uhals.id_userinstance = uhal.id_userinstance
				and uas.id_localizedstatus = ltl.id_localizedtext
				and ltl.id_language = %d ) as localizedstatus,
				(select uas.statusname
				from ds_useralliancestatus uas,
				ds_userhasalliancestatus uhals
				where uas.id_status = uhals.id_status
				and uhals.id_userinstance = uhal.id_userinstance ) as status
				from ds_alliance al,
				ds_userhasalliance uhal
				where al.id_alliance = uhal.id_alliance
				and uhal.id_userinstance = %d",
				$params["lang"],
				$params["uiid"] );

		$res = $common->getResultArray ( "useralliance_id_language_id_userinstance_".$params["lang"]."_".$params["uiid"], $sql, "", $params["uiid"], "LOCAL_CACHE" );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR - AllianceManagement::getPanel1";
			
			return false;
		}
		
 		if ( sizeof ( $res ) > 0 ) { // состоит в альянсе - может его покинуть

			$ch = $root->new_child ( "alliancemember" );
			$ch->set_attribute ( "id_alliance", $res[0]['id_alliance'] );
			$ch->set_attribute ( "alliancename", trim ( urlencode ( $res[0]['alliancename'] ) ) );
			$ch->set_attribute ( "tag", trim ( urlencode ( $res[0]['alliancetag'] ) ) );
			if ( key_exists('religion', $res[0]) ) $ch->set_attribute ( "religion", $res[0]['religion'] );
			if ( key_exists('localizedstatus', $res[0]) ) $ch->set_attribute ( "status", urlencode ( $res[0]['localizedstatus'] ) );
			$ch->set_attribute ( "leave", "1" );

 		} else { // не состоит в альянсе - может вступить в альянс, либо создать альянс

 			// список доступных для вступления альянсов
			if($_SESSION["worldtype"] != 'RACE_VS_RACE'){
				$sql = sprintf ( "
					SELECT 
						al.id_alliance,
						al.alliancename,
						al.alliancetag,
						(
							SELECT religionname
							FROM ds_religion
							WHERE id_religion = al.id_religion
						) as religion,
						(
							SELECT u.username
							FROM 
								ds_user u,
								ds_userinstance ui,
								ds_userhasalliancestatus uhas,
								ds_useralliancestatus uas
							WHERE 
									u.id_user = ui.id_user
								and ui.id_userinstance = uhas.id_userinstance
								and uhas.id_status = uas.id_status
								and uas.statusname = 'LEADER'
								and uhas.id_alliance = al.id_alliance
						) as leader
					FROM ds_alliance al
					WHERE al.id_world = %d
					ORDER BY al.alliancetag",
					$this->world );
			}else{
				$sql = sprintf ( "
					SELECT
						al.id_alliance,
						al.alliancename,
						al.alliancetag,
						(SELECT religionname
						FROM ds_religion
						WHERE id_religion = al.id_religion) as religion,
                        u.username as leader
					FROM
                        ds_alliance al,
                        ds_user u,
						ds_userinstance ui,
						ds_userhasalliancestatus uhas,
						ds_useralliancestatus uas
					WHERE
                         al.id_world = %d AND
						 u.id_user = ui.id_user AND
						 ui.id_userinstance = uhas.id_userinstance AND
						 uhas.id_status = uas.id_status AND
						 uas.statusname = 'LEADER' AND
						 uhas.id_alliance = al.id_alliance AND
						 ui.id_race = %d
					ORDER BY al.alliancetag",
					$this->world,
					$_SESSION["race"] );
			}
			
			$res = $common->getResultArray ( "alliance_list", $sql, "", $params["uiid"], "LOCAL_CACHE" );
			if ( $res === false ) {
				$this->errormsg .= " SYSTEM ERROR - AllianceManagement::getPanel2";
				
				return false;
			}
			
			# Если альянсов не существует, то подсовывает псевдо альянс
			if(!sizeof ($res)) 
			{
				$ch = $root->new_child("alliancetojoin");
				$root->append_child($ch);
			} else {
 				for ( $i=0; $i<sizeof ( $res ); $i++ ) 
 			 	{
					$ch = $root->new_child ( "alliancetojoin" );
					$ch->set_attribute ( "id_alliance", $res[$i]['id_alliance'] );
					$ch->set_attribute ( "alliancename", trim ( urlencode ( $res[$i]['alliancename'] ) ) );
					$ch->set_attribute ( "tag", trim ( urlencode ( $res[$i]['alliancetag'] ) ) );
					$ch->set_attribute ( "leader", trim ( urlencode ( $res[$i]['leader'] ) ) );
					if ( $res[$i]['religion'] != "" ) $ch->set_attribute ( "religion", $res[$i]['religion'] );
			 	}
			}
		}

		// закладки здания
		$this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab );
				
		//$panel = "xmldata=";
		//$panel .= $doc->dump_mem();

		//$this->logResponse ( $panel );

		// print $panel;
		
		SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );


		$res = "";
		$doc = "";
		$root = "";

		return true;
	}
}

?>
