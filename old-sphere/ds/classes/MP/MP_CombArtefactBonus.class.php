<?php $CVS_Version = '$Revision$ ; $Date$';

class MP_CombArtefactBonus extends MP_CombBase
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
			       sum(ahb.value) as colvo
			FROM
	    		ds_artefactattachedcomb aac
        		INNER JOIN ds_attachedbuilding AS ab ON (aac.id_attachedbuilding = ab.id_attachedbuilding)
        		INNER JOIN ds_building AS b ON (ab.id_building = b.id_building)
        		INNER JOIN ds_artefactinstance AS ai ON (aac.id_artefact = ai.id_artefactinstance)
        		INNER JOIN ds_artefact AS a ON (ai.id_artefact = a.id_artefact)
        		INNER JOIN ds_artefactscope AS ascop ON (a.id_artefactscope = ascop.id_artefactscope)
        		INNER JOIN ds_artefacthasbonus AS ahb ON (ahb.id_artefact = a.id_artefact)
        		INNER JOIN ds_attribute AS at ON (at.id_attribute = ahb.id_attribute)
        		INNER JOIN ds_attributename AS atn ON (atn.id_attributename = at.id_attributename)
        		INNER JOIN ds_attributetype AS att ON (att.id_attributetype = at.id_attributetype)
			WHERE
         			aac.id_comb = ".$this->combID."
               	AND ab.active = true
         		AND ab.underconstruction = false
         		AND ab.inactivation = false
                AND ab.inupgrade = false         		     			
                AND ascop.scopename = 'COMB'
           GROUP BY att.typename, atn.attributename";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		
		foreach($rs as $k=>$bonusValue)
		{
			$attributeTypeName = $bonusValue['typename'];
			$attributeName = $bonusValue['attributename'];
			$countedValue = $bonusValue['colvo'];
			
			$combValue = self::GetCombAttributeByTypeAndName($this->combID, $attributeTypeName, $attributeName);
			
			$productionValue = $combValue['value'];
			$productionID = $combValue['id'];
			
			if($productionValue != $countedValue)
			{
				$str =  "		".__CLASS__.": ERROR! Mismatch '$attributeTypeName'=> '$attributeName' COUNTED = $countedValue with COMB = $productionValue";
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