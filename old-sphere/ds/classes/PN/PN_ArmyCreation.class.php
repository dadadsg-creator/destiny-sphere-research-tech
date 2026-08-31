<?php $CVS_Version = '$Revision: 1.7 $ ; $Date: 2009/08/27 07:37:06 $';

// закладка военной базы - создание армии

class PN_ArmyCreation extends UA_GetBuildMenu {

	function __construct() {
		UA_GetBuildMenu::UA_GetBuildMenu ();
	}

	function getPanel () {

		global $params;
		global $common;

		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><armycreation />";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();	
		//$root->set_attribute ( "c", $this->cellnum );
		//$root->set_attribute ( "lang", $params["lang"] );

		// “екущее_количество_армий_игрока_на_соте:ћаксимальное_количество_армий_на_соте
		$root->set_attribute ( "cur", DS_Army::GetCountUserArmiesOnComb(DE_Comb::CreateFromID($this->comb)).":".$this->worldSettings->get('MAX_USER_ARMIES_PER_COMB'));
		
		// “екущее_количество_армий_на_всех_сотах_игрока:ћаксимальное_количество_армий_на_всех_сотах
		$root->set_attribute ( "max", DS_Army::GetTotalCountUserArmies($params["uiid"]).":".$this->worldSettings->get('MAX_USER_ARMIES_TOTAL'));

		// все юниты какие есть в наличии
		$sql = sprintf ( "select u.id_unit,
				u.unitname,
				uchu.amount,
				(select sum(uha.value*(select exchangerate 
				from ds_resource 
				where id_attributename = atn.id_attributename))
				from ds_unithasattribute uha,
				ds_attribute a,
				ds_attributename atn,
				ds_attributetype att
				where uha.id_attribute = a.id_attribute
				and a.id_attributename = atn.id_attributename
				and a.id_attributetype = att.id_attributetype
				and att.typename = 'PRODUCTION_COSTS'
				and atn.attributename != 'CONSTRUCTION_TIME'
				and uha.id_unit = u.id_unit) as ttt
				from ds_unitcontainerhasunit uchu,
				ds_unitcontainer uc,
				ds_unit u
				where uchu.id_unit = u.id_unit
				and uchu.id_army = uc.id_unitcontainer
				and uc.id_basecomb = %d
				and uc.isDefault = true
				order by ttt",
				$this->comb );

		$res = $common->getResultArray ( "units_has_id_userinstance_".$params["uiid"], $sql, "", $params["uiid"], "LOCAL_CACHE" );
		if ( $res === false ) {
			$this->errormsg .= " SYSTEM ERROR ArmyCreation::getPane1";
			
			return false;
		}			
		for ( $i=0; $i<sizeof ( $res ); $i++ ) {
			$ch = $root->new_child ( "unit" );	
			$ch->set_attribute ( "id", $res[$i]['id_unit'] );
			$ch->set_attribute ( "unitname", $res[$i]['unitname'] );
			$ch->set_attribute ( "amount", intval((real)$res[$i]['amount']) );
			$ch->set_attribute ( "PW", $res[$i]['ttt']/10 );
		}

		// закладки
		$this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab );

		//$panel = "xmldata=";
		//$panel .= $doc->dump_mem();

		//$this->logResponse ( $panel );

		// print $panel;
		
		SU_OutputHandler::Singleton()->addString( $doc->dump_mem() , 'xmldata' );
		

		$res = "";
		$res2 = "";
		$doc = "";
		$root = "";

		return true;
	}
}
?>