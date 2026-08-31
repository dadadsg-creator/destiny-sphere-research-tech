<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Бои незавершенные у которыхз нет ивентов
 */

class MP_DBNotEndedBattlesWithoutEvent  extends MP_Patch 
{			
	public function patch()
	{
		$sql = "
			SELECT b.id_battle
			FROM ds_battle AS b
			WHERE
     			b.battleenddate IS NULL
     			AND NOT EXISTS
     			(
         			SELECT
      					eq.id_eventqueue
         			FROM
    					ds_eventqueue AS eq
						INNER JOIN ds_eventhasoption AS eho ON (eq.id_eventqueue = eho.id_eventqueue)
						INNER JOIN ds_eventoption AS eo ON (eho.id_eventoption = eo.id_eventoption)
		 			WHERE
						eo.optionname = 'ID_Battle'
						AND
						eho.value = b.id_battle
						AND
                        eq.istrash = 'f'
     			)";
		$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
	

		if(count($res) > 0)
		{
			$str =  "		".__CLASS__.": ERROR! Find ".count($res)." Not ended battles without events.";
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
		
		
		$sql = "DELETE FROM ds_battle
				WHERE
					id_battle IN 
					(	
						SELECT b.id_battle
						FROM ds_battle AS b
						WHERE
     						b.battleenddate IS NULL
     						AND NOT EXISTS
     						(
         						SELECT
      								eq.id_eventqueue
         						FROM
    								ds_eventqueue AS eq
									INNER JOIN ds_eventhasoption AS eho ON (eq.id_eventqueue = eho.id_eventqueue)
									INNER JOIN ds_eventoption AS eo ON (eho.id_eventoption = eo.id_eventoption)
		 						WHERE
									eo.optionname = 'ID_Battle'
									AND
									eho.value = b.id_battle
									AND
                        			eq.istrash = 'f'
     						)
					)";
		$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));		
	}
}

?>