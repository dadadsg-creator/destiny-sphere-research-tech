<?php $CVS_Version = '$Revision$ ; $Date$';

class GP_CombParalelBuilds extends GP_CombBase
{
	private $combData;
	private $combID;
	
	
	public function __construct($combData)
	{
		assert(is_array($combData) && count($combData) > 0);
		$this->combData = $combData;
		
		assert(key_exists('id_comb', $this->combData));
		$this->combID = (int)$this->combData['id_comb'];
		assert($this->combID > 0);
	}	
	
	public function patch()
	{
		$sql = "
		 	SELECT count(*) as colvo
 			FROM 
 				ds_attachedbuilding as ab
 			WHERE
      			ab.id_comb = ".$this->combID."
      			AND
      			(
      				ab.underconstruction = true 
      				-- OR ab.inactivation = true 
      				OR ab.inupgrade = true
      			)";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		$realBuilds = (int)$rs[0]['colvo'];
				
		//Берем занчение CURRENT_AMOUNT => SIMILTANEOUS_BUILDS
		$inCombAttributesBuilds = self::GetAttributeByID($this->combID, 28);
		
		//Берем занчение MAX_AMOUNT => SIMILTANEOUS_BUILDS
		$combMaxAttributesBuilds = self::GetAttributeByID($this->combID, 18);
		
		//Берем занчение настройки мира
		$worldID = self::GetWorldIDByCombID($this->combID);
		$modifaerValue = self::GetWorldSettingByWorldIDSettingName($worldID, 'SIMILTANEOUS_BUILDS_ADDON');
		
		$maxBuilds = $modifaerValue + $combMaxAttributesBuilds['value'];
		
		if($realBuilds != (int)$inCombAttributesBuilds['value'])
		{
			$tmp = $inCombAttributesBuilds['value'];
			$inCombAttributesBuilds['value'] = $realBuilds;			
			$str =  "		".__CLASS__.": ERROR! Mismatch REAL_BUILDS = $realBuilds and COMB_BUILDS = ".$tmp;
			self::_out($str);
			
			if(self::$onlyValidate)
			{	
				$str =  "		ONLY VALIDATE!";
				self::_out($str);
			}else{	
				self::SetCombAttributeByID((int)$inCombAttributesBuilds['id_combhasresource'], $inCombAttributesBuilds['value']);
			}
		}
		
		if($inCombAttributesBuilds['value'] > $maxBuilds)
		{
			$str = "		".__CLASS__.": WARNING! CURRENT_BUILDS = ".$inCombAttributesBuilds['value']." biger then MAX_BUILDS = $maxBuilds";
			self::_out($str);
		}
	}
		
}

?>