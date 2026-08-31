<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * ЛОкализация интерфейса реализованная на стороне сервера.
 * ТОлько не срашивайте почему именно так ... попрасил разработчик клиента 
 * говарит типа так лучше часть разместить тут ....
 */
class DE_ServerSideInterfaceLocalization extends BS_Base
{
	/**
	 * ID языка
	 *
	 * @var integer
	 */
	private $langID;	
	
	/**
	 * Хранилище с данными по локализации нтерфейса
	 *
	 * @var ST_ServerSideInterfaceLocalization
	 */
	private $localization;
	
	function __construct($langID)
	{				
		parent::__construct();
		
		assert(is_int($langID) && $langID > 0);		
		$this->langID = $langID;	
						
		$this->localization = ST_ServerSideInterfaceLocalization::Create($this->langID);						
	}//END OF METHOD
	
	
	public function get($name, $params = '')
	{
		assert(!empty($name));
				
		$str = $this->localization->getLocolizedTextByName($name);

		if(is_array($params))
		{		
			foreach($params as $key=>$value)
			{
				$str = str_replace("{".$key."}", $value, $str);
			}						
		}		
		
		return $str;
	}//END OF METHOD	
		
	
	public static function CreateFromLangID($langID)
	{
		$langID = (int)$langID;
		assert(is_int((int)$langID) && (int)$langID>0);
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $langID);
	}
	
	public static function CreateForCurentLang()
	{
		$langID = (int)$_SESSION['lang'];
		assert(is_int($langID) && $langID>0);
		return DE_ServerSideInterfaceLocalization::CreateFromLangID($langID);		
	}
	
	public function __destruct()
	{
		parent::__destruct();
	}
	
}//END OF CLASS
 
?>