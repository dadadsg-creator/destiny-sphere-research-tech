<?php $CVS_Version = '$Revision$ ; $Date$';

class GP_CombBonus extends GP_CombBase
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
		$countedValue = array(
			'BONUS_ABSOLUTE' => array
			(
				'KADERIUM' => 0, 
				'NANO_CRYSTAL' => 0, 
				'STASIS_ENERGY' => 0, 
				'FOOD' => 0, 
				'PRODIUM' => 0
			),
			
			'BONUS_RELATIVE' => array(
				'KADERIUM' => 0, 
				'NANO_CRYSTAL' => 0, 
				'STASIS_ENERGY' => 0, 
				'FOOD' => 0, 
				'PRODIUM' => 0
			),
		);
		
		$sql = "
			SELECT
                atta.typename,
                atna.attributename,      			
      			sum(artb.value) as colvo
			FROM
    			ds_attachedbuilding AS ab
    			INNER JOIN ds_building AS b ON (ab.id_building = b.id_building)    			
    			INNER JOIN ds_buildinghasattribute as ba ON (b.id_building = ba.id_building)
    			INNER JOIN ds_attribute AS atr ON (ba.id_attribute = atr.id_attribute)
    			INNER JOIN ds_attributename AS atn ON (atr.id_attributename = atn.id_attributename)
    			INNER JOIN ds_attributetype AS att ON (atr.id_attributetype = att.id_attributetype)
    			INNER JOIN ds_artefactattachedbuilding AS aab ON (ab.id_attachedbuilding = aab.id_attachedbuilding)
    			INNER JOIN ds_artefactinstance AS arti ON (aab.id_artefact = arti.id_artefactinstance)
    			INNER JOIN ds_artefact AS art ON (arti.id_artefact = art.id_artefact)
    			INNER JOIN ds_artefacthasbonus AS artb ON (art.id_artefact = artb.id_artefact)
    			INNER JOIN ds_attribute AS atra ON (artb.id_attribute = atra.id_attribute)
    			INNER JOIN ds_attributename AS atna ON (atra.id_attributename = atna.id_attributename)
    			INNER JOIN ds_attributetype AS atta ON (atra.id_attributetype = atta.id_attributetype)
			WHERE				
         			ab.id_comb = ".$this->combID."    			
				AND ab.active = true
         		AND ab.underconstruction = false
         		AND ab.inactivation = false
                AND ab.inupgrade = false         			
     			AND att.typename = 'TURN_PRODUCTION'
     		GROUP BY atta.typename, atna.attributename";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		
		foreach($rs as $k=>$bonusValue)
		{
			$attributeTypeName = $bonusValue['typename'];
			$attributeName = $bonusValue['attributename'];
			$countedValue[$attributeTypeName][$attributeName] = $bonusValue['colvo'];						
		}	
		
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
			$countedValue[$bonusValue['typename']][$bonusValue['attributename']] += $bonusValue['colvo'];						
		}		
		
		$sql = "
		 	SELECT
      			att.typename,
      			atn.attributename,
      			rha.value as colvo
			FROM
  				ds_comb AS c
  				INNER JOIN ds_userinstance AS ui ON (ui.id_userinstance = c.id_userinstance)
				INNER JOIN ds_race AS r ON (r.id_race = ui.id_race)
  				INNER JOIN ds_racehasattribute AS rha ON (rha.id_race = r.id_race)
  				INNER JOIN ds_attribute AS a ON (a.id_attribute = rha.id_attribute)
  				INNER JOIN ds_attributetype AS att ON (att.id_attributetype = a.id_attributetype)
  				INNER JOIN ds_attributename AS atn ON (atn.id_attributename = a.id_attributename)
			WHERE 
  				c.id_comb = $this->combID";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));

  		foreach($rs as $k=>$bonusValue)
		{
			$attributeTypeName = $bonusValue['typename'];
			$attributeName = $bonusValue['attributename'];
			
			if(!key_exists($bonusValue['typename'], $countedValue))
			{
				$countedValue[$bonusValue['typename']] = array();
			}
			
			if(!key_exists($bonusValue['attributename'], $countedValue[$bonusValue['typename']]))
			{
				$countedValue[$bonusValue['typename']][$bonusValue['attributename']] = 0;
			}
			
			$countedValue[$bonusValue['typename']][$bonusValue['attributename']] += $bonusValue['colvo'];						
		}	
  		
		$sql = "
			SELECT   				
  				uhr.id_status,
  				at.typename,
      			an.attributename,
  				rha.value as colvo
			FROM
 				public.ds_userinstance ui
 				INNER JOIN public.ds_comb c ON (ui.id_userinstance=c.id_userinstance)
 				INNER JOIN public.ds_userhasreligion uhr ON (ui.id_userinstance=uhr.id_userinstance)
 				INNER JOIN public.ds_religion r ON (uhr.id_religion=r.id_religion)
 				INNER JOIN public.ds_religionhasattribute rha ON (r.id_religion=rha.id_religion)
 				INNER JOIN public.ds_attribute a ON (rha.id_attribute=a.id_attribute)
 				INNER JOIN public.ds_attributetype at ON (a.id_attributetype=at.id_attributetype)
 				INNER JOIN public.ds_attributename an ON (a.id_attributename=an.id_attributename)
			WHERE
  				c.id_comb = $this->combID";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		
		foreach($rs as $k=>$bonusValue)
		{
			$attributeTypeName = $bonusValue['typename'];
			$attributeName = $bonusValue['attributename'];
			
			if(($bonusValue['id_status'] == 1 || $bonusValue['id_status'] == 3) && $bonusValue['colvo'] > 0)
			{
				continue;
			}

			if(!key_exists($bonusValue['typename'], $countedValue))
			{
				$countedValue[$bonusValue['typename']] = array();
			}
					
			if(!key_exists($bonusValue['attributename'], $countedValue[$bonusValue['typename']]))
			{
				$countedValue[$bonusValue['typename']][$bonusValue['attributename']] = 0;
			}
			
			$countedValue[$bonusValue['typename']][$bonusValue['attributename']] += $bonusValue['colvo'];						
		}	
  
		foreach($countedValue as $attributeTypeName=>$array)
		{
			foreach($array as $attributeName=>$value)
			{				
				$combValue = self::GetCombAttributeByTypeAndName($this->combID, $attributeTypeName, $attributeName);		
				
				if(!$combValue)
				{
					continue;
				}
					
				$production = round($combValue['value'], 5);
				$productionID = $combValue['id'];
				
				$counted = round($countedValue[$attributeTypeName][$attributeName], 5);
				
				if($counted != $production)
				{						
					$str =  "		".__CLASS__.": ERROR! Mismatch '$attributeTypeName'=> '$attributeName' COUNTED = ".$counted." with COMB = ".$production." ";
					self::_out($str);
					
					if(self::$onlyValidate)
					{	
						$str =  "		ONLY VALIDATE!";
						self::_out($str);
					}else{	
						self::SetCombAttributeByID((int)$productionID, $counted);
					}
				}			
			}
		}		
	}		
}

?>