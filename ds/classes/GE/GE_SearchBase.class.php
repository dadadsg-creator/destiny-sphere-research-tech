<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Базовый класс для методов полиска классов занимающихся поиском
 *
 */

class GE_SearchBase
{
	/**
	 * Класс для работы с БД
	 *
	 * @var DS_Common
	 */
	static protected $common;
	
	/**
	 * Заносим объект доля работы с БД
	 *
	 * @param DS_Common $common
	 */
	static public function SetCommon(DS_Common $common)
	{
		self::$common = $common;
	}
	
	/**
	 * Устанавливаем другую базу если надо
	 *
	 * @param integer $DBID
	 */
	static public function SetDBID($DBID)
	{
		assert(is_int($DBID) && $DBID >= 0);
		
		self::$common->setDbId($DBID);
	}
	
	/**
	 * С помощью этого метода потмоки будут получать доступ до объекта работы с БД
	 *
	 * @return DS_Common
	 */
	static protected function _getCommon()
	{
		assert(self::$common instanceof DS_Common);
		
		return self::$common;
	}
}


?>