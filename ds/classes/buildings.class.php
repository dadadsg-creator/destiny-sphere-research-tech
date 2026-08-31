<?php $CVS_Version = '$Revision$ ; $Date$';
// 18.03.2004 version 1.0
// refractive
		
import ('building_class');
require_once constant('CONFIG_PATH').'/settings.php';

if ( !isset ( $GLOBALS["common"] ) ) {
	$common = & ClassFactory::getInstance ( "DS_Common" );
	if ( !$common->setDbId(0) ) {
		echo "DB-connection failed";
		exit;
	}
}

class neededAttributes {

	public $errormsg;
	public $db;
	public $limit;
	public $trans;


	function neededAttributes () {	

		if ( !$this->generateneededAttributes ( $_GET ) ) {
			die ( $this->errormsg);
		}
	}

	function generateneededAttributes ( $data ) {

		global $common;

		if ( empty ( $_GET["lang"] ) ) {
			$lang = DEFAULT_LANGUAGE;
		} else if ( !empty ( $_GET["lang"] ) ) {
			$lang = $_GET["lang"];
		} 
		if ( empty ( $_GET["race"] ) ) {
			$race = 1;
		} else if ( !empty ( $_GET["race"] ) ) {
			$race = $_GET["race"];
		} 
		if ( empty ( $_GET["order"] ) ) {
			$order = "cmp_names";
		} else if ( !empty ( $_GET["order"] ) ) {
			$order = $_GET["order"];
		} 
		
		$sql = sprintf ("SELECT ds_race.racename FROM ds_race WHERE id_race = %d;", $race);
		$res = $common->getResultArray ("theRacename", $sql,"","","" );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR Racename::Racename1";
			return false;
		}
		$racename = $res[0]['racename'];

		// --------------------------------------------------
		$sql = sprintf ( "SELECT ds_building.buildingname, ds_buildinghasattribute.value, ds_attributename.attributename, ds_attributetype.typename, ds_buildinghasattribute.id_attribute, ds_attributetype.id_attributetype, ds_attributename.id_attributename, ds_building.id_building, ds_attribute.id_attributetype
FROM ((((ds_building INNER JOIN ds_buildinghasattribute ON ds_building.id_building = ds_buildinghasattribute.id_building) INNER JOIN ds_attribute ON ds_buildinghasattribute.id_attribute = ds_attribute.id_attribute) INNER JOIN ds_attributename ON ds_attribute.id_attributename = ds_attributename.id_attributename) INNER JOIN ds_attributetype ON ds_attribute.id_attributetype = ds_attributetype.id_attributetype) INNER JOIN ds_buildingcanbuildbyrace ON ds_building.id_building = ds_buildingcanbuildbyrace.id_building
WHERE (((ds_building.id_building)>0) AND ((ds_attribute.id_attributetype)=1 Or (ds_attribute.id_attributetype)=2 Or (ds_attribute.id_attributetype)=5) AND ((ds_buildingcanbuildbyrace.id_race)= %d))
ORDER BY ds_building.buildingname, ds_building.id_building;",$race );
		$res = $common->getResultArray ( "neededRes", $sql,"", "", "" );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR NeededRes::neededRes1";
			return false;
		}
		
		//iterate through recordset 
		$buildingname ='';
		
		for ( $i=0; $i<sizeof ( $res ); $i++ ) {
			
			if ($buildingname != $res[$i]['buildingname']){
				if($buildingname != '') $theBuildings[$aBuilding->get_b_id_building()] = $aBuilding; //attach instance to array
				$aBuilding = new building();  //init new building

				$aBuilding->set_b_name($res[$i]['buildingname']); //set bname
				$aBuilding->set_b_id_building($res[$i]['id_building']); //setID_B
				
				$this->fillBuilding($res[$i]['id_attribute'],$res[$i]['value'], &$aBuilding);
			
				$buildingname = $res[$i]['buildingname']; //set Controlvar
			}
			else{
				$this->fillBuilding($res[$i]['id_attribute'],$res[$i]['value'], &$aBuilding);
			}
	
		}
		$theBuildings[$aBuilding->get_b_id_building()] = $aBuilding; //attach instance to array
		
		/// get needed Sciences
		$sql = sprintf ( "SELECT ds_building.id_building, ds_science.level, ds_sciencebranch.branchname, ds_localizedtextlanguage.text, ds_localizedtextlanguage.id_language, ds_building.buildingname, ds_buildingcanbuildbyrace.id_race
FROM ((((ds_building INNER JOIN ds_buildingneedsscience ON ds_building.id_building = ds_buildingneedsscience.id_building) INNER JOIN ds_science ON ds_buildingneedsscience.id_science = ds_science.id_science) INNER JOIN ds_sciencebranch ON ds_science.id_branch = ds_sciencebranch.id_branch) INNER JOIN (ds_localizedtext INNER JOIN ds_localizedtextlanguage ON ds_localizedtext.id_localizedtext = ds_localizedtextlanguage.id_localizedtext) ON ds_sciencebranch.id_localizedname = ds_localizedtext.id_localizedtext) INNER JOIN ds_buildingcanbuildbyrace ON ds_building.id_building = ds_buildingcanbuildbyrace.id_building
WHERE (((ds_localizedtextlanguage.id_language)=%d) AND ((ds_buildingcanbuildbyrace.id_race)=%d))
ORDER BY ds_building.id_building;",$lang,$race );
		
		$res = $common->getResultArray ( "neededSciences", $sql,"", "", "" );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR NeededSciences::neededSciences1";
			return false;
		}
		
		//iterate through recordset 
		for ( $i=0; $i<sizeof ($res); $i++) {

			$theBuildings[$res[$i]['id_building']]->set_b_neededSciences($res[$i]['text'],$res[$i]['level'],$res[$i]['branchname']);
		}

		/// get needed Buildings
		$sql = sprintf ( "SELECT ds_building.id_building as idb, ds_building.buildingname, ds_building_1.id_building, ds_building_1.buildingname, ds_localizedtextlanguage.text, ds_localizedtextlanguage.id_language, ds_buildingcanbuildbyrace.id_race
FROM ((ds_buildingcanbuildbyrace INNER JOIN ((ds_building INNER JOIN ds_buildingneedsbuilding ON ds_building.id_building = ds_buildingneedsbuilding.id_building_1) INNER JOIN ds_building AS ds_building_1 ON ds_buildingneedsbuilding.id_building_2 = ds_building_1.id_building) ON ds_buildingcanbuildbyrace.id_building = ds_building.id_building) INNER JOIN ds_buildingcanbuildbyrace AS ds_buildingcanbuildbyrace_1 ON (ds_buildingcanbuildbyrace.id_race = ds_buildingcanbuildbyrace_1.id_race) AND (ds_building_1.id_building = ds_buildingcanbuildbyrace_1.id_building)) INNER JOIN (ds_localizedtext INNER JOIN ds_localizedtextlanguage ON ds_localizedtext.id_localizedtext = ds_localizedtextlanguage.id_localizedtext) ON ds_buildingcanbuildbyrace_1.id_localizedname = ds_localizedtext.id_localizedtext
WHERE (((ds_localizedtextlanguage.id_language)=%d) AND ((ds_buildingcanbuildbyrace.id_race)=%d))
ORDER BY ds_building.id_building;",$lang, $race );
		
		$res = $common->getResultArray ( "neededBuildings", $sql,"", "", "" );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR NeededSciences::neededSciences1";
			return false;
		}
		
		//iterate through recordset 
		for ( $i=0; $i<sizeof ($res); $i++) {
			$theBuildings[$res[$i]['idb']]->set_b_neededBuildings($res[$i]['text']);
		}

		/// get needed Groundtypes
		$sql = sprintf ( "SELECT ds_building.id_building, ds_celltype.celltypename, ds_groundtype.groundname, ds_buildingcanbuildbyrace.id_race
FROM (((ds_building INNER JOIN ds_buildingcanbebuildon ON ds_building.id_building = ds_buildingcanbebuildon.id_building) INNER JOIN ds_celltype ON ds_buildingcanbebuildon.id_celltype = ds_celltype.id_celltype) INNER JOIN ds_groundtype ON ds_buildingcanbebuildon.id_groundtype = ds_groundtype.id_groundtype) INNER JOIN ds_buildingcanbuildbyrace ON ds_building.id_building = ds_buildingcanbuildbyrace.id_building
WHERE (((ds_buildingcanbuildbyrace.id_race)=%d))
ORDER BY ds_building.id_building;",$race);
		
		$res = $common->getResultArray ( "neededgrundtypes", $sql,"", "", "" );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR Neededgriundtypes::neededgroundtypes1";
			return false;
		}

		//iterate through recordset 
		$i=0;

		while ($i<sizeof ($res)) {
			if($res[$i]['id_building'] !=5) {  // kein Centralwreck

				$theBuildings[$res[$i]['id_building']]->set_b_neededGroundtypes($res[$i]['celltypename'] , $res[$i]['groundname']);
			}
			$i++;
		}

		/// get localizedname & Description
		$sql = sprintf ( "SELECT ds_building.id_building, ds_buildingcanbuildbyrace.id_race, ds_localizedtextlanguage.text AS buildname, ds_localizedtextlanguage_1.text AS builddescription, ds_localizedtextlanguage.id_language
FROM ((ds_building INNER JOIN ds_buildingcanbuildbyrace ON ds_building.id_building = ds_buildingcanbuildbyrace.id_building) INNER JOIN (ds_localizedtext INNER JOIN ds_localizedtextlanguage ON ds_localizedtext.id_localizedtext = ds_localizedtextlanguage.id_localizedtext) ON ds_buildingcanbuildbyrace.id_localizedname = ds_localizedtext.id_localizedtext) INNER JOIN (ds_localizedtext AS ds_localizedtext_1 INNER JOIN ds_localizedtextlanguage AS ds_localizedtextlanguage_1 ON ds_localizedtext_1.id_localizedtext = ds_localizedtextlanguage_1.id_localizedtext) ON (ds_localizedtextlanguage.id_language = ds_localizedtextlanguage_1.id_language) AND (ds_buildingcanbuildbyrace.id_localizeddescription = ds_localizedtext_1.id_localizedtext)
WHERE (((ds_buildingcanbuildbyrace.id_race)=%d) AND ((ds_localizedtextlanguage.id_language)=%d));",$race,$lang);
		
		$res = $common->getResultArray ( "neededgrundtypes", $sql,"", "", "" );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR Neededgriundtypes::neededgroundtypes1";
			return false;
		}		
		//iterate through recordset 
		for ( $i=0; $i<sizeof ($res); $i++) {
			if($res[$i]['id_building'] !=5) {  // kein Centralwreck
				$theBuildings[$res[$i]['id_building']]->set_b_description($res[$i]['builddescription']);
				$theBuildings[$res[$i]['id_building']]->set_b_localizedname($res[$i]['buildname']);
			}
		}		
// ----------------------------------------------------------------------------------------		

//Printout all buildings
		$this->layoutHeader($lang,$race);

		usort($theBuildings, array ("building", $order));
		

		foreach ($theBuildings as $tb) {
			$tb->layoutBuilding($racename);
//			$tb->printBuilding();
		}
		$this->layoutFooter();
		$res = "";
		return true;
  	}

  	function fillBuilding($id_attribute,$thevalue, $theBuilding){

		switch($id_attribute){
			case 1: $theBuilding->set_b_kadcosts($thevalue);break;
			case 2: $theBuilding->set_b_nanocosts($thevalue);break;
			case 3: $theBuilding->set_b_stasiscosts($thevalue);break;
			case 4: $theBuilding->set_b_foodcosts($thevalue);break;
			case 5: $theBuilding->set_b_prodcosts($thevalue);break;
			case 6: $theBuilding->set_b_workercosts($thevalue);break;
			case 7: $theBuilding->set_b_upkeepRes('STASIS_ENERGY');$theBuilding->set_b_upkeep($thevalue);break;
			case 8: $theBuilding->set_b_buildtime($thevalue);break;
			case 9: $theBuilding->set_b_productionRes('KADERIUM');$theBuilding->set_b_production($thevalue);break;
			case 10: $theBuilding->set_b_productionRes('NANO_CRYSTAL');$theBuilding->set_b_production($thevalue);break;
			case 11: $theBuilding->set_b_productionRes('FOOD');$theBuilding->set_b_production($thevalue);break;
			case 12: $theBuilding->set_b_productionRes('PRODIUM');$theBuilding->set_b_production($thevalue);break;
			case 13: $theBuilding->set_b_productionRes('STASIS_ENERGY');$theBuilding->set_b_production($thevalue);break;
			case 49: $theBuilding->set_b_upkeepRes('FOOD');$theBuilding->set_b_upkeep($thevalue);break;
			default: Echo "id_attribute nicht gefunden";

		}

	}
	function layoutHeader($lang,$race) {

		if ($lang==3) {
		 include "req_header_ru.php";
		}
		else {
		 include "req_header_de.php";
		}

	}
	function layoutFooter() {
		include "req_footer.htm";
	}
}

$r = new neededAttributes;

?>
