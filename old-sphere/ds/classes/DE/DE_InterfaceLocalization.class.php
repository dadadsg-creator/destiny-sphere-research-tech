<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс при создание извлекает из БД все GameModifaers для указанного мира.
 */

class DE_InterfaceLocalization extends BS_Base
{	
	/** ID языка */
	private $langID;

	private $localization = array();
	
	function __construct($langID)
	{				
		
		parent::__construct();
		
		assert(is_int($langID) && $langID > 0);		
		$this->langID = $langID;	

		$res = DS_Misc::GetInterfaceLocolization($this->langID);
		
		if(is_array($res))
		{
			foreach($res as $k=>$v)
			{
				$this->localization[$v['interfacename']] = $v['text'];
			}
		}		
							
	}//END OF METHOD
	
	
	public function get ($name)
	{
		
		$this->_invariant();
		
		assert(!empty($name));
		
		if(key_exists($name, $this->localization))
		{
			return $this->localization[$name];
		}else{
			return $name;
		}
					
	}//END OF METHOD	
	
	/**
	 * Проврека целостности класса
	 */
	private function _invariant ($mode = 'public')
	{				
		if(BS_Base::_exitInvariant($mode)) return;
		
		assert(is_int($this->langID) && $this->langID>0);	
		assert(is_array($this->localization));	
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
		return self::CreateFromLangID($langID);		
	}
	
	public function __destruct()
	{
		parent::__destruct();
	}
		
}//END OF CLASS
 
?>