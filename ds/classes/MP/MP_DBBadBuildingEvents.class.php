<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Здания у котрохы стоит флаг какого-то дейтсвия но действия самого нет
 */

class MP_DBBadBuildingEvents  extends MP_Patch 
{	
	public function __construct()
	{
		
	}	
	
	public function patch()
	{
		$sql = "
			SELECT ab.id_attachedbuilding
			FROM ds_attachedbuilding AS ab
			WHERE
     		(
      			ab.inactivation = true
      			OR
      			ab.inupgrade = true
      			OR
      			ab.underconstruction = true
     		)
     		AND NOT EXISTS
     		(
      			SELECT *
      			FROM DS_AttachedBuildingBindEventQueue
      			WHERE id_attachedbuilding = ab.id_attachedbuilding
     		)";
		$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
	

		if(count($res) > 0)
		{
			$str =  "		".__CLASS__.": ERROR! Find ".count($res)." Buildings in process without events.";
			self::_out($str);	
			
			if(self::$onlyValidate)
			{	
				$str =  "		ONLY VALIDATE!";
				self::_out($str);
			}else{	
				$this->_repair($res);
			}	
		}				
	}

	private function _repair($res)
	{
		assert(is_array($res) && count($res) > 0);
		
		foreach($res as $k=>$buildingData)
		{			
			$sql = "UPDATE ds_attachedbuilding 
					SET 
						inactivation = false,
						inupgrade = false,
						underconstruction = false
					WHERE id_attachedbuilding = ".$buildingData['id_attachedbuilding'];
			$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
			assert(empty(self::$common->errormsg));
		}
	}
}

?>