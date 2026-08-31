<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Производство юнита числдеться в тобличке ds_unitproduxtion а самого производства нет
 */

class MP_DBUnitProductionWithoutEvent  extends MP_Patch 
{			
	public function patch()
	{
		$sql = "
			SELECT
    			uip.id_attachedbuilding
			FROM
    			ds_unitinproduction AS uip
    			INNER JOIN ds_attachedbuilding AS ab ON (ab.id_attachedbuilding = uip.id_attachedbuilding)
    			LEFT JOIN ds_attachedbuildingbindeventqueue AS abbeq ON (abbeq.id_attachedbuilding = ab.id_attachedbuilding)
    			LEFT JOIN ds_eventqueue AS eq ON (eq.id_eventqueue = abbeq.id_eventqueue)
			WHERE
    			abbeq.id_attachedbuilding IS NULL
    			OR
    			eq.id_eventqueue IS NULL
    		";
		$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
	

		if(count($res) > 0)
		{
			$str =  "		".__CLASS__.": ERROR! Find ".count($res)." Unit production without events.";
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
		
		
		$sql = "DELETE FROM ds_unitinproduction
				WHERE
					id_attachedbuilding IN 
					(	
						SELECT
    						uip.id_attachedbuilding
						FROM
    						ds_unitinproduction AS uip
    						INNER JOIN ds_attachedbuilding AS ab ON (ab.id_attachedbuilding = uip.id_attachedbuilding)
    						LEFT JOIN ds_attachedbuildingbindeventqueue AS abbeq ON (abbeq.id_attachedbuilding = ab.id_attachedbuilding)
    						LEFT JOIN ds_eventqueue AS eq ON (eq.id_eventqueue = abbeq.id_eventqueue)
						WHERE
    						abbeq.id_attachedbuilding IS NULL
    						OR
    						eq.id_eventqueue IS NULL
					);";
		$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));		
	}
}

?>