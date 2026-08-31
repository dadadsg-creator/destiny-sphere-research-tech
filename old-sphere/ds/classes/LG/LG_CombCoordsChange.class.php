<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * √ейт анализатора.
 * »стори€ изменений координат сот, за выбранный промежуток времени
 *
 */
class LG_CombCoordsChange extends LG_Base 
{
	static private $startDate;
	
	static private $endDate;
	
	static public function GetChangeLog($startDate, $endDate, $world)
	{
		assert(is_int($startDate) && $startDate > 0);
		assert(is_int($endDate) && $endDate > 0);
		assert(ereg("[A-Z]{1,3}", $world));
		
		self::$world = $world;
		self::$startDate = GB_TypeFactory::CreateConstant('GB_Date', $startDate);
		self::$endDate = GB_TypeFactory::CreateConstant('GB_Date', $endDate);
		
		self::_getCommon();
		
		$logs = self::_getLogs();
		
		foreach ($logs as $k => $rename)
		{
			echo $rename['old_combcoords'].';'.$rename['new_combcoords'].";\n";
		}
	}
	
	/**
	 * ¬ыборка истории изменений
	 *
	 * @return array
	 */
	static private function _getLogs()
	{
		if(GameDown())
		{
			die('GameDown');
		}
		
		$sql = "
			SELECT
				r.ringnumber||'_'||cc1.complexnumber||'_'||cch.old_combnumber as old_combcoords,
				r.ringnumber||'_'||cc2.complexnumber||'_'||cch.new_combnumber as new_combcoords,
				cch.date
			FROM
				ds_comb_coords_history AS cch
				INNER JOIN ds_combcomplex AS cc1 ON (cc1.id_combcomplex = cch.old_id_complex)
				INNER JOIN ds_combcomplex AS cc2 ON (cc2.id_combcomplex = cch.new_id_complex)
				INNER JOIN ds_ring AS r ON (r.id_ring = cc1.id_ring)
			WHERE
				cch.date >= '".self::$startDate->getPGFormat()."'
				AND
				cch.date <= '".self::$endDate->getPGFormat()."'
				AND
				r.id_world = ".self::$worldID."
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
}

?>