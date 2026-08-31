<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_SearchReturnResources extends SI_Base
{
	static public function GetCategoryNameByID($categoryID)
	{
		assert((int)$categoryID > 0);
		
		$sql = "SELECT categoryname
				FROM ds_tradecategory
				WHERE id_tradecategory = ".$categoryID;
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res[0]['categoryname'];
	}
	
	
	
	static public function DeltaCombCurrentAmount($combID, $attributeID, $delta)
	{
		assert($combID > 0);
		assert($attributeID > 0);		
		settype($delta, 'float');		
		
		$sql = "
			UPDATE ds_combhasresource
			SET value = value::REAL + $delta
			WHERE id_combhasresource =
      			(
      				SELECT chres.id_combhasresource
              		FROM
    					ds_combhasresource AS chres
    					INNER JOIN ds_attribute AS at ON (at.id_attribute = chres.id_attribute)    					
					WHERE
     					chres.id_comb = $combID
     					AND
     					at.id_attributetype = 4 -- CURRENT_AMOUNT
     					AND
     					at.id_attributename = $attributeID
     			)";
		
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
	}
	
	static public function HaveUnitsInHomeArmy($combID, $unitID)
	{
		assert($combID > 0);
		assert($unitID > 0);				
		
		$sql = "			
      			SELECT
      				uc.id_unitcontainer,
      				uchu.id_unitcontainerhasunit
				FROM
    				ds_unitcontainer AS uc 
    				LEFT JOIN ds_unitcontainerhasunit AS uchu ON (uchu.id_army = uc.id_unitcontainer  AND uchu.id_unit = $unitID)
				WHERE     				
     				uc.id_basecomb = $combID
     				AND
     				uc.isdefault = true";		
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res[0];		
	}
	
	static public function DeltaUnitsInUnitGroup($unitGroupID, $delta)
	{
		assert($unitGroupID > 0);
		
		$sql = "UPDATE ds_unitcontainerhasunit
				SET  amount = amount + $delta
				WHERE id_unitcontainerhasunit = $unitGroupID";				      		
		$res = self::$common->getResultArray("", $sql, "", "", "");

		assert(empty(self::$common->errormsg));
	}
	
}

?>