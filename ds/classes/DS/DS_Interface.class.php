<?php $CVS_Version = '$Revision$ ; $Date$';

class DS_Interface extends DS_Base
{
	static function GetBuildingTabs($langID, $buildingID)
	{
		
		assert(is_int($langID) && $langID>0);
		assert(is_int($buildingID) && $buildingID>0);
		
		$common = self::getCommon();
		
		if(key_exists('profi_game', $_SESSION) && $_SESSION['profi_game'])
		{
			$profiInjection = "";
		}else{
			$profiInjection = "AND sf.onlyforpremium = 'f'";
		}
		
		$sql = "SELECT 
					sf.id_specialfunction,
					sf.specialfunctionname,
					sf.tab_image,
					sf.url,
					sf.affected_by_modifier,
					bhsf.isdefault,
					(
						SELECT text
						FROM 
							ds_localizedtext l,
							ds_localizedtextlanguage ltl
						WHERE 
								l.id_localizedtext = ltl.id_localizedtext
							AND l.id_localizedtext = sf.id_localizedname
							AND ltl.id_language = ".$langID."
					) AS localizedname
				FROM 
					ds_building b,
					ds_buildinghasspecialfunction bhsf,
					ds_specialfunction sf
				WHERE 
						b.id_building = bhsf.id_building
					AND bhsf.id_specialfunction = sf.id_specialfunction
					AND b.id_building = ".$buildingID."
					AND sf.level = 1
					".$profiInjection."
				ORDER BY sf.id_specialfunction";
				
		$res = $common->getResultArray (self::FormMark(__METHOD__, $langID."_".$buildingID."_".$_SESSION['profi_game']), $sql, "", "FULL_PREFIX", "GLOBAL_CACHE");		
		assert(empty($common->errormsg));				
		
		return $res;
	}//END OF FUNCTION 	
		
	
	static function GetChildTabs($langID, $tabID)
	{
		
		assert(is_int($langID) && $langID>0);
		assert(is_int($tabID) && $tabID>0);		
		
		$common = self::getCommon();
		
		if(key_exists('profi_game', $_SESSION) && $_SESSION['profi_game'])
		{
			$profiInjection = "";
		}else{
			$profiInjection = "AND sf.onlyforpremium = 'f'";
		}
		
		$sql = "SELECT 
					sf.id_specialfunction,
					sf.specialfunctionname,
					sf.tab_image,
					(
						SELECT text
						FROM 
							ds_localizedtext l,
							ds_localizedtextlanguage ltl
						WHERE 
								l.id_localizedtext = ltl.id_localizedtext
							AND l.id_localizedtext = sf.id_localizedname
							AND ltl.id_language = ".$langID."
					) AS localizedname
				FROM 
					ds_specialfunction sf,
					(
						SELECT sfr.id_child
						FROM
							ds_specialfunction sf,
							ds_specialfunctionrelation sfr
						WHERE
								sfr.id_parent = sf.id_specialfunction
							AND sf.id_specialfunction = ".$tabID."
					) AS t
				WHERE
					sf.id_specialfunction = t.id_child
					".$profiInjection."
				ORDER BY
					sf.id_specialfunction";
				
		$res = $common->getResultArray (self::FormMark(__METHOD__, $langID."_".$tabID."_".$_SESSION['profi_game']), $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE");		
		assert(empty($common->errormsg));				
		
		return $res;
	}//END OF FUNCTION 		
}

?>