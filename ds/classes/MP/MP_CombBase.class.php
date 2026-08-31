<?php $CVS_Version = '$Revision$ ; $Date$';

class MP_CombBase extends MP_Patch
{
	
	public $combData;
	
	public $combID;
	
	
	public function __construct($combData)
	{
		assert(is_array($combData) && count($combData) > 0);
		$this->combData = $combData;
		
		assert(key_exists('id_comb', $this->combData));
		$this->combID = (int)$this->combData['id_comb'];
		assert($this->combID > 0);
	}		
	
	static public function GetAttributeByID($combID, $attributeID)
	{
		assert(is_int($combID) && $combID > 0);
		assert(is_int($attributeID) && $attributeID > 0);
		
		$sql = "
		 	SELECT *
			FROM ds_combhasresource as ch
			WHERE
     			ch.id_comb = $combID
     			AND
     			ch.id_attribute = $attributeID";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		
		return $rs[0];
	}
	
	static public function GetCombAttributeByTypeAndName($combID, $attributeType, $attributeName)
	{
		assert(is_int($combID) && $combID > 0);
		assert(!empty($attributeType));		
		assert(!empty($attributeName));		
		
		$sql = "
		 	SELECT 
       			chs.id_combhasresource as id,
       			chs.value
			FROM ds_combhasresource as chs
				INNER JOIN ds_attribute as dat ON (dat.id_attribute = chs.id_attribute)
				INNER JOIN ds_attributename as atn ON (atn.id_attributename = dat.id_attributename)
				INNER JOIN ds_attributetype as att ON (att.id_attributetype = dat.id_attributetype)
			WHERE
				chs.id_comb = $combID
				AND
				atn.attributename = '$attributeName'
				AND
				att.typename = '$attributeType'";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		
		if(count($rs) > 0)
		{
			return $rs[0];
		}else{
			return false;
		}
		
		
	}
	
	
	static public function SetCombAttributeByID($id, $value)
	{
		assert(is_int($id) && $id > 0);
		
		$sql = "UPDATE ds_combhasresource
				SET value = '".$value."'
				WHERE id_combhasresource = $id";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
	}
	
	static public function InsertCombAttribute($combID, $value, $attributeID)
	{
		assert(is_int($combID) && $combID > 0);
		assert(is_int($attributeID) && $attributeID > 0);
		
		$sql = "INSERT INTO ds_combhasresource
				(id_comb, id_attribute, value)
				VALUES
				($combID, $attributeID, $value)";				 
		$rs =  self::$common->getResultArray("", $sql, "", "", "NO_CACHE");
		assert(empty(self::$common->errormsg));
	}
	
	static public function GetWorldIDByCombID($combID)
	{
		assert(is_int($combID) && $combID > 0);
		
		$sql = "
		 	SELECT
 				r.id_world as  id
			FROM
				ds_comb as c
				INNER JOIN ds_combcomplex as cc On (cc.id_combcomplex = c.id_combcomplex)
				INNER JOIN ds_ring as r on (r.id_ring = cc.id_ring)
			WHERE
  				c.id_comb = $combID";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		
		return (int)$rs[0]['id'];
	}
	
	static public function GetWorldSettingByWorldIDSettingName($worldID, $name)
	{
		assert(is_int($worldID) && $worldID>0);
		assert(!empty($name));
		
		$sql = "
		 	SELECT *
			FROM ds_gamemodifier as g
			WHERE 
				g.id_world = $worldID
				AND
				g.modifiername = '$name'";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		
		return (int)$rs[0]['value'];
	}
}


?>