<?php $CVS_Version = '$Revision$ ; $Date$';

class GP_CombArtefactSlots extends GP_CombBase
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
      			sum(ba.value) as  max_slots_count       			
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
      			AND ab.inupgrade = 'f'         		     			
      			AND att.typename = 'MAX_AMOUNT'
      			AND atn.attributename = 'ARTEFACT_SLOTS'";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		$realArtefactSlots = (int)$rs[0]['max_slots_count'];
				
		$combMaxArtefactSlotsAttribute = self::GetAttributeByID($this->combID, 17);		
		
		if($combMaxArtefactSlotsAttribute['value'] != $realArtefactSlots)
		{
			$str = "		".__CLASS__." ERROR! Mismatch REAL_MAX_ARTEFACT_SLOTS = $realArtefactSlots and COMB_MAX_ARTEFACT_SLOTS = ".$combMaxArtefactSlotsAttribute['value'];
			self::_out($str);	
			if(self::$onlyValidate)
			{	
				$str =  "		ONLY VALIDATE!";
				self::_out($str);
			}else{	
				self::SetCombAttributeByID((int)$combMaxArtefactSlotsAttribute['id_combhasresource'], $realArtefactSlots);
			}
		}				
	}
		
}

?>