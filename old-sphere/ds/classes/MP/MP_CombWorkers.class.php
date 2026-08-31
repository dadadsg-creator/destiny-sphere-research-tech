<?php $CVS_Version = '$Revision$ ; $Date$';

/** Уже не используеться .. вместо него используеться  MP_CombHumans*/

class MP_CombWorkers extends MP_CombBase
{	
	public function __construct($combData)
	{
		parent::__construct($combData);
	}	
	
	public function patch()
	{
		//Сперва просчитываем максимальное значение текущее реальное
		$sql = "
		 	SELECT
      			sum(ba.value) as  max_workers       			
			FROM
      			ds_attachedbuilding AS ab
      			INNER JOIN ds_building AS b ON (ab.id_building = b.id_building)
      			INNER JOIN ds_buildinghasattribute as ba ON (b.id_building = ba.id_building)
      			INNER JOIN ds_attribute AS atr ON (ba.id_attribute = atr.id_attribute)
      			INNER JOIN ds_attributename AS atn ON (atr.id_attributename = atn.id_attributename)
      			INNER JOIN ds_attributetype AS att ON (atr.id_attributetype = att.id_attributetype)
			WHERE
      				ab.id_comb = ".$this->combID."
      			AND ab.active = 't'
      			AND ab.underconstruction = 'f'
      			AND ab.inactivation = 'f'  
      			-- AND ab.inupgrade = 'f'        			
      			AND att.typename = 'MAX_AMOUNT'
      			AND atn.attributename = 'WORKER'";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		$realMaxWorkers = (int)$rs[0]['max_workers'];
				
		$combMaxWorkersAttribute = self::GetAttributeByID($this->combID, 14);
		$combCurrentWorkersAttribute = self::GetAttributeByID($this->combID, 25);
		
		if($combMaxWorkersAttribute['value'] != $realMaxWorkers)
		{
			$str = "		".__CLASS__." ERROR! Mismatch REAL_MAX_WORKERS = $realMaxWorkers and COMB_MAX_WORKERS = ".$combMaxWorkersAttribute['value'];
			self::_out($str);	
			
			if(self::$onlyValidate)
			{	
				$str =  "		ONLY VALIDATE!";
				self::_out($str);
			}else{		
				self::SetCombAttributeByID((int)$combMaxWorkersAttribute['id_combhasresource'], $realMaxWorkers);
			}
		}
		
		if($combCurrentWorkersAttribute['value'] > $realMaxWorkers)
		{
			$str = "		".__CLASS__." ERROR! Mismatch CURRENT_WORKERS = ".$combCurrentWorkersAttribute['value']." > MAX_WORKERS = ".$realMaxWorkers;
			self::_out($str);		
			
			if(self::$onlyValidate)
			{	
				$str =  "		ONLY VALIDATE!";
				self::_out($str);
			}else{	
				self::SetCombAttributeByID((int)$combCurrentWorkersAttribute['id_combhasresource'], $realMaxWorkers);
			}
		}		
	}
		
}

?>