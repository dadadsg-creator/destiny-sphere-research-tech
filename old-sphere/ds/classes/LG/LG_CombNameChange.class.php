<?php $CVS_Version = '$Revision: 1.3 $ ; $Date: 2007/10/16 10:08:29 $';

/**
 * √ейт анализатора.
 * »стори€ изменений названий сот, за выбранный промежуток времени
 *
 */
class LG_CombNameChange extends LG_Base 
{
	static private $startDate;
	
	static private $endDate;
	
	static public function GetChangeLog($startDate, $endDate, $world)
	{
		assert(is_int($startDate) && $startDate > 0);
		assert(is_int($endDate) && $endDate > 0);
		assert(ereg("[A-Z]{1,3}", $world));
		
		self::$startDate = GB_TypeFactory::CreateConstant('GB_Date', $startDate);
		self::$endDate = GB_TypeFactory::CreateConstant('GB_Date', $endDate);
		self::$world = $world;
		
		self::_getCommon();
		
		$logs = self::_getLogs();
		
		foreach ($logs as $k => $rename)
		{
			echo $rename['old_combname'].';'.$rename['new_combname'].";\n";
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
				old_combname,
				new_combname,
				date
			FROM
				ds_combname_history
			WHERE
				date >= '".self::$startDate->getPGFormat()."'
				AND
				date <= '".self::$endDate->getPGFormat()."'
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
}

?>