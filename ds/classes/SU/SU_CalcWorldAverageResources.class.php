<?php

/**
 * Расчет средних по миру показателей производства и хранения ресурсов
 * 	нужно для шпионажа, для приблизительных данных о ресурсах на соте игрока
 *
 */
class SU_CalcWorldAverageResources
{
	static private $DBID;
	
	static private $worldID;
	
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static private $common;
	
	static public function Calc()
	{
		self::_out("============== START CALC AVERAGE ==============");
		
		foreach ($GLOBALS['dbWorldAccess'] as $id => $db)
		{
			self::_out("-------------------------------------------------");
			self::_out("Calc base ".$db['server']);

			self::$DBID = (int)$id;
			self::$common = DS_Common::CreateForCurentDB(self::$DBID);
			$worlds = self::_getWorlds();
			foreach ($worlds as $k => $w)
			{
				self::$worldID = (int)$w['id_world'];
				self::_updateAverage();
			}
		}	
				
		self::_out("================== COMPLIT ======================");
	
	}
	
	static private function _updateAverage()
	{
		self::_out("WorldId ".self::$worldID);

		// Смотрим, включен ли шпионаж
		$spyEnabled = self::_getSpyEnabled();
		if ($spyEnabled != 1) {
			self::_out("Spy not enabled!");
			return;
		}
		
		self::$common->startTrans();
		
		$sql = "SET STATEMENT_TIMEOUT TO 120000";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
		
		$sql = "UPDATE ds_average_resources SET is_current = false WHERE id_world = ".self::$worldID;
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
		
		$sql = "
			insert into ds_average_resources
				(id_world, id_attribute, value)
			select
				".self::$worldID.",
    			at.id_attribute,
				sum(value::DOUBLE PRECISION) / count(DISTINCT ui.id_userinstance)
			from
    			ds_combhasresource as chr
    			INNER JOIN ds_attribute AS at USING (id_attribute)
    			INNER JOIN ds_attributename AS an USING (id_attributename)
    			INNER JOIN ds_attributetype AS att USING (id_attributetype)
    			INNER JOIN ds_comb AS c USING (id_comb)
    			INNER JOIN ds_userinstance AS ui USING (id_userinstance)
    			INNER JOIN ds_user AS u USING (id_user)
			where
    			att.typename IN ('TURN_PRODUCTION', 'CURRENT_AMOUNT')
    			AND
    			an.attributename IN ('FOOD', 'STASIS_ENERGY', 'KADERIUM', 'NANO_CRYSTAL', 'PRODIUM')
    			AND
    			NOT u.admin
    			AND
    			ui.id_world = ".self::$worldID."
			group by at.id_attribute
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
		
		self::$common->closeTrans('COMMIT');
		
		self::_out("Complit");
	}
	
	static private function _getWorlds()
	{
		$sql = "SELECT id_world FROM ds_world";
		$res = self::$common->getResultArray("AVG_RES_ALL_WORLDS", $sql, '', '', 'GLOBAL_CACHE');
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static private function _getSpyEnabled()
	{
		$sql = "SELECT 
					value 
				FROM 
					ds_gamemodifier
				WHERE 
					modifiername = 'SPY_ENABLED'
					AND id_world = ".self::$worldID;
		$res = self::$common->getResultArray("", $sql, '', '', '');
		assert(empty(self::$common->errormsg));
		
		return $res[0]['value'];
	}
	
	static private function _out($msg)
	{				
		$out = SU_DebugTools::GetInternalDate()." - ".$msg."\n";
						
		$fileName = "CalcAverage.log";		
		SU_DebugTools::WriteToLog($out, $fileName);
				
		flush();
	}
}

?>