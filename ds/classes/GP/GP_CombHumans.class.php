<?php $CVS_Version = '$Revision$ ; $Date$';

class GP_CombHumans extends GP_CombBase
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
		//Сперва просчитываем максимальное значение текущее реальное
		$sql = "
		 	SELECT
                atn.attributename,
      			sum(ba.value) as  max_count
                       			
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
      			--AND ab.inupgrade = 'f'        			
      			AND att.typename = 'MAX_AMOUNT'
      			AND atn.attributename in ('WORKER', 'SCIENTIST', 'ARCHEOLOGIST')
			GROUP BY atn.attributename";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		
		
		foreach($rs as $k=>$v)
		{			
			$attributeName = $v['attributename'];
			$realMaxValue = (int)$v['max_count'];
			
			$combMaxValueData = self::GetCombAttributeByTypeAndName($this->combID, 'MAX_AMOUNT', $attributeName);
			$combMaxValue = (float)$combMaxValueData['value'];
			$combMaxID = (int)$combMaxValueData['id'];
			
			$combCurrentValueData = self::GetCombAttributeByTypeAndName($this->combID, 'CURRENT_AMOUNT', $attributeName);			
			$combCurrentValue = (float)$combCurrentValueData['value'];
			$combCurrentID = (int)$combCurrentValueData['id'];
			
			if($combMaxValue != $realMaxValue)
			{
				$str = "		".__CLASS__." ERROR! $attributeName mismatch REAL_MAX_VALUE = $realMaxValue and COMB_MAX_VALUE = ".$combMaxValue." (".$this->combID.")";
				self::_out($str);	
				
				if(self::$onlyValidate)
				{	
					$str = "		".__CLASS__." ONLY VALIDATE!";
					self::_out($str);	
				}else{				
					self::SetCombAttributeByID($combMaxID, $realMaxValue);	
				}
			}
		
			if($combCurrentValue > $realMaxValue)
			{
				$str = "		".__CLASS__." ERROR! $attributeName CURRENT_VALUE = ".$combCurrentValue." > MAX_VALUE = ".$realMaxValue;
				self::_out($str);	
				
				if(self::$onlyValidate)
				{	
					$str = "		".__CLASS__." ONLY VALIDATE!";
					self::_out($str);
				}else{
					self::SetCombAttributeByID($combCurrentID, $realMaxValue);
				}
			}			
		}		
	}
		
}

?>