<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * ѕрроверка производства за турн на соте. —равниваем сколько оно должно
 * производитьс€ по рассчетам с тем скколько записанно в атрибутах соты.
 */

class MP_CombTurnProduction extends MP_CombBase
{	
	public function __construct($combData)
	{
		parent::__construct($combData);
	}	
	
	public function patch()
	{
		$sql = "
		 	SELECT
		 		att.typename,
                atn.attributename,                        			
      			sum(ba.value) as  production,
      			atr.id_attribute      			
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
     			AND att.typename IN ('TURN_PRODUCTION', 'STORAGE_FACTOR', 'STORAGE_FACTOR_ABSOLUT')
			GROUP BY att.typename, atn.attributename, atr.id_attribute
			ORDER BY att.typename, atn.attributename";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		
		foreach($rs as $k=>$productionValue)
		{
			$attributeName = $productionValue['attributename'];
			$typeName = $productionValue['typename'];
			$countedValue = $productionValue['production'];
			$attributeID = $productionValue['id_attribute'];
			
			$combValue = self::GetCombAttributeByTypeAndName($this->combID, $typeName, $attributeName);
			
			if(is_array($combValue))
			{
				$productionValue = $combValue['value'];
				$productionID = $combValue['id'];					
			}else{
				$productionValue = 'N/A';
				$productionID = 0;
			}
						
			if($productionValue != $countedValue)
			{
				$str =  "		".__CLASS__.": ERROR! Mismatch '$typeName'=> '$attributeName' COUNTED = $countedValue with COMB = $productionValue";
				self::_out($str);
				
				if(self::$onlyValidate)
				{	
					$str =  "		ONLY VALIDATE!";
					self::_out($str);
				}else{	
					if($productionID == 0)
					{
						self::InsertCombAttribute((int)$this->combID, $countedValue, (int)$attributeID);
					}else{
						self::SetCombAttributeByID((int)$productionID, $countedValue);	
					}
					
				}
			}			
		}					
	}		
}

?>