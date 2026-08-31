<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Проверка потребления за турн на сот 
 */

class MP_CombUpkeep extends MP_CombBase
{	
	public function __construct($combData)
	{
		parent::__construct($combData);
	}	
	
	public function patch()
	{
		$sql = "	
			 SELECT
                atn.attributename,                        			
      			sum(ba.value) as  production	 	
			FROM
    			ds_attachedbuilding AS ab
    			INNER JOIN ds_building AS b ON (ab.id_building = b.id_building)
    			INNER JOIN ds_buildinghasattribute as ba ON (b.id_building = ba.id_building)
    			INNER JOIN ds_attribute AS atr ON (ba.id_attribute = atr.id_attribute)
    			INNER JOIN ds_attributename AS atn ON (atr.id_attributename = atn.id_attributename)
    			INNER JOIN ds_attributetype AS att ON (atr.id_attributetype = att.id_attributetype)
			WHERE
         			ab.id_comb = ".$this->combID."
         		AND ab.active = true
         		AND ab.underconstruction = false
         		AND ab.inactivation = false
                AND ab.inupgrade = false    		     			         		
     			AND att.typename = 'TURN_UPKEEP'
			GROUP BY atn.attributename";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		
		foreach($rs as $k=>$productionValue)
		{
			$attributeName = $productionValue['attributename'];
			$countedValue = $productionValue['production'];
			
			$combValue = self::GetCombAttributeByTypeAndName($this->combID, 'TURN_UPKEEP', $attributeName);
			
			$productionValue = $combValue['value'];
			$productionID = $combValue['id'];
			
			if($productionValue != $countedValue)
			{
				$str =  "		".__CLASS__.": ERROR! Mismatch 'TURN_UPKEEP'=> '$attributeName' COUNTED = $countedValue with COMB = $productionValue";
				self::_out($str);
				
				if(self::$onlyValidate)
				{	
					$str =  "		ONLY VALIDATE!";
					self::_out($str);
				}else{	
					self::SetCombAttributeByID((int)$productionID, $countedValue);
				}
			}			
		}					
	}		
}

?>