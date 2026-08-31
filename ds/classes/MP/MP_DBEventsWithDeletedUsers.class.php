<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * »щем ивенты спутыми арми€ми
 */

class MP_DBEventsWithDeletedUsers  extends MP_Patch 
{	
	public function __construct()
	{
		
	}	
	
	public function patch()
	{
		$sql = "
			SELECT 
       			eq.id_eventqueue AS id_eventqueue,
       			eho.value AS id_comb,
       			eho2.value AS id_unitcontainer
			FROM
    			ds_eventqueue AS eq
    			INNER JOIN ds_event AS e ON (e.id_event = eq.id_event)
		        			
    			INNER JOIN ds_eventhasoption AS eho ON (eho.id_eventqueue = eq.id_eventqueue)
    			INNER JOIN ds_eventoption AS eo ON (eo.id_eventoption = eho.id_eventoption)
		        		
    			INNER JOIN ds_eventhasoption AS eho2 ON (eho2.id_eventqueue = eq.id_eventqueue)
    			INNER JOIN ds_eventoption AS eo2 ON (eo2.id_eventoption = eho2.id_eventoption)
	    
			WHERE
     			eo.optionname IN ('ID_CombFrom', 'ID_CombTo')
     			AND
     			eo2.optionname = 'ID_Army'
     			AND
     			e.eventname IN ('BATTLE_ROUND', 'ARMY_MOVEMENT_FROM', 'ARMY_MOVEMENT_TO')
     			AND
     			eq.istrash = false
     			AND EXISTS
     			(
         			SELECT id_comb
         			FROM ds_comb AS c
         			WHERE
              		c.id_comb = eho.value
              		AND
              		c.id_userinstance IS NULL
     			)";
		$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));

		if(count($res) > 0)
		{
			$str =  "		".__CLASS__.": ERROR! Find ".count($rs)." battle evens with deleted users.";
			self::_out($str);	
			
			if(self::$onlyValidate)
			{	
				$str =  "		ONLY VALIDATE!";
				self::_out($str);
			} else {	
				$this->_deleteBadEvents($res);
			}
		}				
	}

	private function _deleteBadEvents($res)
	{
		assert(is_array($res) && count($res) > 0);
		
		foreach($res as $k=>$eventData)
		{
			$sql = "BEGIN";
			$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
			assert(empty(self::$common->errormsg));
			
			$sql = "UPDATE ds_unitcontainer SET inaction = false WHERE id_unitcontainer = ".$eventData['id_unitcontainer'];
			$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
			assert(empty(self::$common->errormsg));
			
			$sql = "DELETE FROM  ds_unitcontaineraction WHERE id_unitcontainer = ".$eventData['id_unitcontainer'];
			$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
			assert(empty(self::$common->errormsg));
			
			$sql = "DELETE FROM  ds_unitcontainerbindeventqueue WHERE id_unitcontainer = ".$eventData['id_unitcontainer'];
			$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
			assert(empty(self::$common->errormsg));
			
			$sql = "COMMIT";
			$res =  self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
			assert(empty(self::$common->errormsg));
		}
	}
}

?>