<?php $CVS_Version = '$Revision$ ; $Date$';

class ST_ArmyActionTypes  extends ST_Base
{
	
	function __construct($id)
	{				
		parent::__construct(parent::_formMark(__CLASS__));
		
	}
	
	protected function _getDataFromDB() 
	{
		return DS_Army::GetAllArmyActionTypes(false);
	}
	
	protected function _parse()
	{
		foreach($this->preData as $k=>$v)
		{
			$this->data[$v['actiontypename']] = (int)$v['id_unitcontaineractiontype'];
		}
	}
	
	public function getActionTypeIDByName($typeName)
	{
		
		assert(key_exists($typeName, $this->data));		
		
		return $this->data[$typeName];
	}
	
	public static function Create()
	{		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, 111, '', 'GLOBAL_CACHE');
	}	
	
	public function __destruct()
	{
		parent::__destruct();
	}
}

?>