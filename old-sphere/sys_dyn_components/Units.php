<?
function print_rr ($array) {
	echo "<xmp>";
	print_r ( $array ) ;
	echo "</xmp>";
}

//if( !class_exists( 'DS_Common' ) ){
	require_once $_SERVER['DOCUMENT_ROOT']."/Admin/include/DS_Common.php";
//}

//if( !class_exists( 'Object' ) ){
	
	require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/classes/BaseClasses/Object.class.php";
//}
//if( !class_exists( 'FastTemplate' ) ){
	
	require_once $_SERVER['DOCUMENT_ROOT']."/sys/include/classes/BaseClasses/FastTemplate.class.php";
//}

class Units {
	
	var $tpl;
	var $common;
	
	var $lang;
	//  id_race
	var $race;
	//
	var $racename;
	
	var $pageId;
	
	/*
	Array Format:
		$Array
			[buildingname]
					[ParamName] = Value;
					[ParamName] = Value;
	*/
	var $resources = array( 'PRODUCTION_COSTS'	=> array ( 'KADERIUM' => array(), 'NANO_CRYSTAL' => array(), 'STASIS_ENERGY' => array(), 'FOOD' => array(), 'PRODIUM' => array(), 'WORKER' => array(), 'CONSTRUCTION_TIME' => array() ) );
	var $science = array( 'KADERIUM' => array(), 'NANO_CRYSTAL' => array(), 'STASIS_ENERGY' => array(), 'FOOD' => array(), 'PRODIUM' => array(), 'TRADE' => array(), 'SPYING' => array() );
	var $localize;
	
	var $raceLocalize = array(
			'TECH'	=> 'PORTAL_UNITS_TECH',
			'BIO'	=> 'PORTAL_UNITS_BIO',
			'PSI'	=> 'PORTAL_UNITS_PSI'
		);

	var $pageparent = array (
			'TECH'	=>	'warunits_tech',
			'BIO'	=>	'warunits_bio',
			'PSI'	=>	'warunits_psi'
		);
	var $parenttype;
	var $types;

	function Units( &$parent, $lang, $race, $p, $type ){
		
		$this->tpl =& new FastTemplate ($_SERVER['DOCUMENT_ROOT'].'/templates/');
		
		$this->common =& new DS_Common;
		$this->common->setDbId(0);

		$this->lang = $this->getLangIdByName ( $lang );		
		$this->race = ($race > 0) ? $race : 1;		
		$this->types = array_flip($this->pageparent);
		$this->type = ($type != '') ? $type : 'TECH';		

		assert( $this->getRaceName() );
		assert( $this->getResource() );
		assert( $this->getScience() );
		assert( $this->getLocalize() );
		assert( $this->showUnits() );
	}
	
	function showUnits( ){

		$this->tpl->define( array( 'units' => '/Units/Units.tpl.html' ) );
		$this->tpl->defineDynamic('unitItem', 'units');

		$assign['PAGE_TITLE'] = local( $this->raceLocalize[ $this->racename ] );
		$assign['PAGE_HEADER'] = $this->getHeader();
		
		$races = $this->getRacesList();
		$assign['RACE1'] = $races[1]; 
		$assign['RACE2'] = $races[2];
		$assign['RACE3'] = $races[3];
		
		foreach($this->localize as $unitName => $text ){

			$assign['UNIT_IMAGE'] = $unitName;
			$assign['LOCAL_UNIT_NAME'] = $text['name'];
			
			$assign['UNIT_ID'] = $text['id'];

			foreach ( $this->resources['FIGHT_ATTRIBUTE'] as $attributeName => $unitResouces ){
				$assign['ATTRIBUTES_'.$attributeName] = isset($unitResouces[$unitName]) ? $unitResouces[$unitName] : 0 ;
				$assign['ATTRIBUTES_'.$attributeName] = ( $assign['ATTRIBUTES_'.$attributeName] < 1 ) ? $assign['ATTRIBUTES_'.$attributeName]*100 : $assign['ATTRIBUTES_'.$attributeName] ;
				
			}

			foreach ( $this->resources['PRODUCTION_COSTS'] as $attributeName => $unitResouces ){
				$assign['RES_CONSTRUCT_'.$attributeName] = isset($unitResouces[$unitName]) ? $unitResouces[$unitName] : 0 ;
			}
			
			foreach ( $this->science as $branchName => $scienceLevel ){
				$assign['SCI_'.$branchName] = isset($scienceLevel[$unitName]) ? $scienceLevel[$unitName] : 1 ;
			}

			$assign['UNIT_DESCRIPTION'] = nl2br( $this->localize[$unitName]['desc'] );
		
			$this->tpl->assign( $assign );
			$this->tpl->parse('UNIT', '.unitItem');
		}
		
		$this->tpl->parse('MAIN', 'units');
				
		echo $this->tpl->fetch('MAIN');
		
		return true;
	}
	
	function getRacesList()
	{
		$sql = "
			SELECT
				l.text, r.id_race
			FROM
				ds_race AS r
				INNER JOIN ds_localizedtextlanguage AS l ON r.id_localizedname = l.id_localizedtext
			WHERE 
				l.id_language = {$this->lang} 
			";
		
		$k = $this->common->getResultArray("", $sql, "", "", "");
		
		if($k)
		{
			foreach($k as $key => $val)
			{
				$res[$val['id_race']] = $val['text'];
			}
			
			return($res);
		}
		
		return false;
	}
	
	function getLangIdByName( $langName ){
		
		$sql = sprintf(
		"
		SELECT
			l.id_language
		FROM
			ds_language l
		WHERE
			l.language = '%s'
			OR l.shortname = '%s'
		;",
		pg_escape_string( $langName ),
		pg_escape_string( $langName )
		);

		$res = $this->common->getQueryArray( $sql );
		assert( empty($this->common->errormsg) );
	
		return ( isset($res[0]['id_language']) ? $res[0]['id_language'] : 3);
	}
	
	function getHeader( ){

		$out = array();
		foreach ( $this->raceLocalize as $typeName => $shortLocalize ){
			
			if( $typeName == $this->type ){
				continue;
			}
			$out[] = '<b><a href="?p='.$this->pageparent[$typeName].'&type='.$typeName.'" class=wnd_header_txt>'.local( $shortLocalize ).'</a></b>';
		}
		
		$out = implode( "&nbsp;|\n", $out );
		
		return $out;
	}
	
	function getRaceName( ){
		
		$sql = sprintf ( 
		"
		SELECT
			racename
		FROM
			ds_race
		WHERE
			id_race= %d
		;",
		$this->race 
		);
		
		$res = $this->common->getResultArray ( "getRaceName", $sql,"", "", "" );
		
		assert( empty( $this->common->errormsg ) );
		
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR getRaceName::getRaceName";
			return false;
		}
		
		$this->racename = $res[0]['racename'];
		
		return true;
	}
	
	
	function getResource( ){
		
		$sql = sprintf ( 
		"
		SELECT
			u.unitname,
			uct.containertypename,
			uha.value,
			an.attributename,
			at.typename

		FROM
            ds_unit u
            INNER JOIN ds_unitorcontainertype uct ON ( u.id_unitcontainertype = uct.id_unitcontainertype )
		    INNER JOIN ds_unithasattribute uha ON ( u.id_unit = uha.id_unit )
		    INNER JOIN ds_attribute a ON ( uha.id_attribute = a.id_attribute )
		    INNER JOIN ds_attributename an ON ( a.id_attributename = an.id_attributename )
		    INNER JOIN ds_attributetype at ON ( a.id_attributetype = at.id_attributetype )
		    INNER JOIN ds_unitcanbebuildbyrace ucb ON u.id_unit = ucb.id_unit

		WHERE
             u.id_unit > 0
		     AND a.id_attributetype IN (
				SELECT
					id_attributetype
				FROM
					ds_attributetype
				WHERE
					typename IN ('PRODUCTION_COSTS', 'FIGHT_ATTRIBUTE')
             )
             AND uct.containertypename = 'ARMY'
	         AND ucb.id_race= %d
		
		ORDER BY
		      u.id_unit;
		",
		$this->race
		);
		$res = $this->common->getResultArray ( "getResource", $sql,"", "", "" );
//		die( $this->common->errormsg."\n<br>\n".$sql );
		assert( empty( $this->common->errormsg ) );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR getResource::getResource";
			return false;
		}
		
		foreach ( $res as $row ){
			
			$this->resources[ $row['typename'] ][ $row['attributename'] ][ $row['unitname'] ] = $row['value'];
		}
		
		return true;
	}
	

	function getScience( ){
		/// get needed Sciences
		$sql = sprintf ( 
		"
		SELECT
			u.unitname,
			sb.branchname,
			s.level,
			ltl.text
		FROM
		    ds_unit u
			INNER JOIN ds_unitorcontainertype uct ON ( u.id_unitcontainertype = uct.id_unitcontainertype )
		    INNER JOIN ds_unitneedsscience uns ON u.id_unit = uns.id_unit
		    INNER JOIN ds_science s ON uns.id_science = s.id_science
		    INNER JOIN ds_sciencebranch sb ON s.id_branch = sb.id_branch
		    INNER JOIN ds_localizedtext  lt ON sb.id_localizedname = lt.id_localizedtext
		    INNER JOIN ds_localizedtextlanguage  ltl ON lt.id_localizedtext = ltl.id_localizedtext
			INNER JOIN ds_unitcanbebuildbyrace ucb ON u.id_unit = ucb.id_unit
		WHERE
             ltl.id_language = %d
             AND ucb.id_race = %d
			 AND uct.containertypename = 'ARMY'
        ORDER BY
              u.id_unit;
		",
		$this->lang,
		$this->race
		);

		$res = $this->common->getResultArray ( "getScience", $sql,"", "", "" );
		assert( empty( $this->common->errormsg ) );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR getScience::getScience";
			return false;
		}
		
		foreach ( $res as $row ){
			
			$this->science[ $row['branchname'] ]['text'] = $row['text'];
			$this->science[ $row['branchname'] ][ $row['unitname'] ] = $row['level'];
		}
		
		return true;
	}
	
	

	function getLocalize( ){

		/// get localizedname & Description
		$sql = "
				SELECT 
					u.unitname, u.id_unit,
					ltl.text AS localunitname,
					ltl2.text AS localunitdescription,
					(SELECT ltl3.text from ds_localizedtext as lt3, ds_localizedtextlanguage as ltl3 WHERE lt3.id_localizedtext = ltl3.id_localizedtext AND ltl3.id_language = ltl.id_language AND lt3.id_localizedtext = u.id_extendeddescription) AS localunitextendeddescription
				FROM
					ds_unit u
					INNER JOIN ds_unitorcontainertype uct ON (u.id_unitcontainertype = uct.id_unitcontainertype)
					INNER JOIN ds_unitcanbebuildbyrace ucb ON (u.id_unit = ucb.id_unit)
					INNER JOIN ds_localizedtext lt ON (u.id_localizedname = lt.id_localizedtext)
					INNER JOIN ds_localizedtextlanguage ltl ON (lt.id_localizedtext = ltl.id_localizedtext)
					INNER JOIN ds_localizedtextlanguage ltl2 ON (ltl.id_language = ltl2.id_language)
					INNER JOIN ds_localizedtext lt2 ON (u.id_localizeddescription = lt2.id_localizedtext)
					INNER JOIN ds_unithasclass uhc ON (u.id_unit = uhc.id_unit)
					INNER JOIN ds_unitclass uc ON (uhc.id_unitclass = uc.id_unitclass) AND
					(lt2.id_localizedtext = ltl2.id_localizedtext)
				WHERE
					(ucb.id_race = '".$this->race."') AND 
					(ltl.id_language = '".$this->lang."') AND 
					(uct.containertypename = 'ARMY')
				ORDER BY
					uc.classnumber
			";
		
		$res = $this->common->getResultArray ( "getLocalize", $sql,"", "", "" );
		assert( empty( $this->common->errormsg ) );
		if ( $res === false ) {
			$this->errormsg = "SYSTEM ERROR getLocalize::getLocalize";
			return false;
		}
		
		foreach ( $res as $row ){
			
			$this->localize[ $row['unitname'] ][ 'name' ] = $row['localunitname'];
			$this->localize[ $row['unitname'] ][ 'id' ] = $row['id_unit'];
			if ( !empty($row['localunitextendeddescription']) )
			{
				$this->localize[ $row['unitname'] ][ 'desc' ] = stripslashes($row['localunitextendeddescription']);
			}
			else 
			{
				$this->localize[ $row['unitname'] ][ 'desc' ] = stripslashes($row['localunitdescription']);
			}
			
			
		}
		
		return true;
	}

}




//print_rr ( $GLOBALS ) ;
$test = new Units( &$this, constant('DEFAULT_LONG_LANG'), $unitRaceID, $_GET[p], $_GET['type'] );

/*		echo '<pre>';
		print_r( $test );
		echo '</pre>';
*/
?>