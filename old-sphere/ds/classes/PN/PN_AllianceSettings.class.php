<?php $CVS_Version = '$Revision$ ; $Date$';
// закладка здания ALLIANCE_CONNECTOR - для лидера альянса - возможность
// сменить пароль для вступления и смена лидера альянса

class PN_AllianceSettings extends UA_GetBuildMenu 
{

	function __construct() {
		UA_GetBuildMenu::UA_GetBuildMenu ();
	}

	function getPanel () {

		global $params;
		global $common;

		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><alliancesettings />";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();

		// лидер альянса?

		$sql = sprintf ( "select id_alliance
				from ds_userhasalliancestatus
				where id_userinstance = %d",
				$params['uiid'] );

		$res = $common->getResultArray ( "id_alliance_id_userinstance_".$params["uiid"], $sql, "", $params["uiid"], "LOCAL_CACHE" );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR - AllianceSettings::getPanel1";
			
			return false;
		}

		if ( sizeof ( $res ) > 0 ) { // если лидер - доступно

			$root->set_attribute ( "leader", "1" );

			$id_alliance = $res[0]['id_alliance'];

			// все остальные члены альянса

			$sql = sprintf ( "select u.username,
					ui.id_userinstance 
					from ds_user u,
					ds_userinstance ui,
					ds_userhasalliance uha
					where uha.id_alliance = %d
					and uha.id_userinstance = ui.id_userinstance
					and ui.id_user = u.id_user
					and uha.id_userinstance != %d",
					$id_alliance,
					$params['uiid'] );
		
			$res = $common->getResultArray ( "alliance_members_id_alliance_".$id_alliance, $sql, "", $params["uiid"], "LOCAL_CACHE" );
			if ( $res === false ) {
				$this->errormsg .= " SYSTEM ERROR - AllianceSettings::getPanel2";
				
				return false;
			}
			
			for ( $i=0; $i<sizeof ( $res ); $i++ ) {
				
				$ch = $root->new_child ( "user" );
				
				$ch->set_attribute ( "name_", $res[$i]['username'] );
				$ch->set_attribute ( "id", $res[$i]['id_userinstance'] );
			}

		} else { // не лидер альянса - меню недоступно
			
			$root->set_attribute ( "leader", "0" );
		}

		// закладки здания

		$this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab );

		//$Panel = "xmldata=";
		//$panel .= $doc->dump_mem();

		//$this->logResponse ( $panel );

		// print $panel;

		SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata'  );

		$res = "";
		$doc = "";
		$root = "";

		return true;
	}
}

?>
