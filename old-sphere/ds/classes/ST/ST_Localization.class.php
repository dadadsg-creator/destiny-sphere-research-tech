<?php $CVS_Version = '$Revision$ ; $Date$';

class ST_Localization extends ST_Base
{	
		
	private $langID;
	
	function __construct($langID)
	{					
		settype($langID, 'integer');
		assert($langID>0);

		$this->langID = $langID;
		
		parent::__construct(parent::_formMark(__CLASS__, $langID));
						
	}
	
	protected function _getDataFromDB()
	{
		return DS_Misc::GetAllLocolizedText($this->langID, false);
	}
	
	protected function _parse()
	{
		foreach($this->preData as $k=>$v)
		{			
			$this->data[(int)$v['id']]= $v['text'];
		}
	}
	
	public function getLocolizedTextByID($id)
	{	
		assert($id>0);
		
		assert(key_exists($id, $this->data));
				
		return $this->data[$id];
	}	
		
	public static function Create($langID)
	{		
		settype($langID, 'integer');
		assert($langID>0);
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $langID, '', 'GLOBAL_CACHE');
	}	
	
	public function __destruct()
	{
		parent::__destruct();
	}
}

?>