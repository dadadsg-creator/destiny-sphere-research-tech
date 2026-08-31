<?php $CVS_Version = '$Revision$ ; $Date$';


class ST_ServerSideInterfaceLocalization extends ST_Base
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
		return DS_Misc::GetServerSideInterfaceLocalization($this->langID, false);
	}
	
	protected function _parse()
	{
		foreach($this->preData as $k=>$v)
		{			
			$this->data[$v['name']]= $v['text'];
		}
	}
	
	public function getLocolizedTextByName($name)
	{	
		assert(!empty($name));
		
		assert(key_exists($name, $this->data));
				
		return $this->data[$name];
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