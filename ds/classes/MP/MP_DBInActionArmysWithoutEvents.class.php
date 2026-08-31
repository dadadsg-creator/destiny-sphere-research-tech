<?php

/**
 * При удалении мертвяков армии других игроков продолжают числится в 
 * полете на их соты но уже без евента
 */
class MP_DBInActionArmysWithoutEvents extends MP_Patch 
{
	public function patch()
	{
		// id_unitcontaineractiontype = 11 - армия находится на защите, евента у нее нет
		$sql = "
			SELECT
				uca.id_unitcontainer
			FROM
				ds_unitcontaineraction AS uca
				LEFT JOIN ds_unitcontainerbindeventqueue AS ucbeq USING (id_unitcontainer)
			WHERE
				ucbeq.id_eventqueue IS NULL
				AND
				uca.id_unitcontaineractiontype != 11
			";
		
		$res = self::$common->getResultArray ( "", $sql, "", "", "NO_CACHE" );
		assert(empty(self::$common->errormsg));
		
		if(count($res) > 0)
		{
			$str =  "		".__CLASS__.": ERROR! Find ".count($rs)." armys actions without events.";
			self::_out($str);	
			
			if(self::$onlyValidate)
			{	
				$str =  "		ONLY VALIDATE!";
				self::_out($str);
			} else {	
				$this->_returnArmys($res);
			}
		}	
	}
	
	private function _returnArmys(array $armyList)
	{
		foreach ($armyList as $k => $army)
		{
			$sql = "SELECT return_army(".$army['id_unitcontainer'].")";
			self::$common->executeQuery($sql);
			assert(empty(self::$common->errormsg));
		}
	}
}

?>