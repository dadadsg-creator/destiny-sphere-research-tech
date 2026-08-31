<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * »щем ивенты спутыми арми€ми
 */

class MP_DBEventsWithEmptyArmys  extends MP_Patch 
{	
	public function __construct()
	{
		
	}	
	
	public function patch()
	{
		$sql = "
					SELECT
      					eq.id_eventqueue
					FROM
    					ds_eventqueue AS eq
						INNER JOIN ds_eventhasoption AS eho ON (eq.id_eventqueue = eho.id_eventqueue)
						INNER JOIN ds_eventoption AS eo ON (eho.id_eventoption = eo.id_eventoption)
					WHERE
						eo.optionname = 'ID_Army'
    					AND NOT EXISTS 
    					(
    						SELECT uc.id_unitcontainer 
    						FROM ds_unitcontainer AS uc
    						WHERE uc.id_unitcontainer = eho.value
    					)    					    					
						AND eq.istrash = 'f'";
		$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));

		if(count($rs) > 0)
		{
			$str =  "		".__CLASS__.": ERROR! Find ".count($rs)." event with empty armys.";
			self::_out($str);	
			
			if(self::$onlyValidate)
			{	
				$str =  "		ONLY VALIDATE!";
				self::_out($str);
			}else{	
				$sql = "	
					update ds_eventqueue
					SET
	 					istrash = 't'
					WHERE
					id_eventqueue IN 
					(
						SELECT
      						eq.id_eventqueue
						FROM
    						ds_eventqueue AS eq
							INNER JOIN ds_eventhasoption AS eho ON (eq.id_eventqueue = eho.id_eventqueue)
							INNER JOIN ds_eventoption AS eo ON (eho.id_eventoption = eo.id_eventoption)
						WHERE
							eo.optionname = 'ID_Army'
    						AND NOT EXISTS 
    						(
	    						SELECT uc.id_unitcontainer 
    							FROM ds_unitcontainer AS uc
    							WHERE uc.id_unitcontainer = eho.value
    						)  
							AND 
							eq.istrash = 'f'
					)";
					$rs =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
					assert(empty(self::$common->errormsg));
			}	
		}				
	}		
}

?>