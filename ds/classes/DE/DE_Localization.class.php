<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс при создание извлекает из БД все 
 */

class DE_Localization extends BS_Base
{
	
	/** ID мира */
	private $langID;
		
	private $data;
	
	function __construct($langID)
	{				
		parent::__construct();
		
		assert(is_int($langID) && $langID > 0);		
		$this->langID = $langID;	
						
		//$this->data = ST_Localization::Create($this->langID);
				
		$this->_invariant('construct');
	}//END OF METHOD
	
	
	public function get ($id, $params = '')
	{
		
		$this->_invariant();
						
		assert(is_int($id) && $id >= 0);					
		
		//$this->data->getLocolizedTextByID($id);
		
		$str = DS_Misc::GetLocolizedText($id, $this->langID);
		$str = $str['text'];
		
		if(is_array($params))
		{		
			if(is_array($params))
			{	
				for($i = 0; $i < count($params); $i++) 
				{					
					$pos = strpos( $str, "%");				
					$str = substr_replace($str, $params[$i], $pos, 1);
				}				
			}
			else
			{				
				$str = str_replace("%", $params, $str);
			}
			
			return $str;
		}else{
			return $str;
		}				
		
		
	}//END OF METHOD	
	
	/**
	 * Проврека целостности класса
	 */
	private function _invariant ($mode = 'public')
	{				
		if(BS_Base::_exitInvariant($mode)) return;
		
		assert(is_int($this->langID) && $this->langID>0);
		assert(count($this->data)>0);		
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
		return DE_Localization::CreateFromLangID($langID);		
	}
	
	public function __destruct()
	{
		parent::__destruct();
	}
	
}//END OF CLASS
 
?>