<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Гейт анализатора.
 * История изменений логинов пользователей
 *
 */
class LG_UserNameChange
{
	/**
	 * Начальная дата
	 *
	 * @var GB_Date
	 */
	static private $startDate;
	
	/**
	 * Конечная дата
	 *
	 * @var GB_Date
	 */
	static private $endDate;
	
	/**
	 * Получить список изменений
	 *
	 * @param int $startDate
	 * @param int $endDate
	 */
	static public function GetChangeLog($startDate, $endDate)
	{
		assert(is_int($startDate) && $startDate > 0);
		assert(is_int($endDate) && $endDate > 0);
		
		self::$startDate = GB_TypeFactory::CreateConstant('GB_Date', $startDate);
		self::$endDate = GB_TypeFactory::CreateConstant('GB_Date', $endDate);
		
		$logs = self::_getLogs();
		
		foreach ($logs as $k => $rename)
		{
			echo $rename['old_username'].';'.$rename['new_username'].";\n";
		}
	}
	
	/**
	 * Выборка логов
	 *
	 * @return array
	 */
	static private function _getLogs()
	{
		if(GameDown())
		{
			die('GameDown');
		}
		
		$common = DS_Common::CreateForCurentDB(0);
		
		$sql = "
			SELECT
				old_username,
				new_username,
				change_date
			FROM
				ds_userdata_change_log
			WHERE
				change_date >= '".self::$startDate->getPGFormat()."'
				AND
				change_date <= '".self::$endDate->getPGFormat()."'
				AND
				char_length(new_username) > 0
			";
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
}

?>