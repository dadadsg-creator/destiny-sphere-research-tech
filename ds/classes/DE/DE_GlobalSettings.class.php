<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс для получения глобалтных настроек игры
 *
 */
class DE_GlobalSettings
{
	static private $settings = array();
	
	static private $init = false;
	
	/**
	 * Импорт настроек из базы
	 *
	 */
	static private function _import()
	{
		if(self::$init)
		{
			return;
		}
		
		$settings = DS_Misc::GetGlobalSettings();
		
		foreach ($settings as $k => $set)
		{
			self::$settings[$set['settings_name']] = $set['value'];
		}
		
		self::$init = true;
	}
	
	/**
	 * 
	 *
	 * @param string $name
	 * @param bool $supressError подавлять ли ошибку, если не найдена такая настройка
	 * @return mixed
	 */
	static public function Get($name, $supressError = false)
	{
		self::_import();

		if($supressError && !key_exists($name, self::$settings))
		{
			return false;
		}

		assert(key_exists($name, self::$settings));
		
		return self::$settings[$name];
	}
}

?>