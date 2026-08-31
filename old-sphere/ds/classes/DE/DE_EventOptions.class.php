<?php $CVS_Version = '$Revision$ ; $Date$';
/** 
 * Класс описания типа ивента
 * 
 */
class DE_EventOptions extends DE_Base
{
	/**
	 * Enter description here...
	 *
	 * @var array
	 */
	private $optionsByName = array();		
	
	function __construct($id, $data = '')
	{		
		assert(is_int($id) && $id>0);		
		
		parent::__construct($id);
		
		if($data == '')
		{
			$data = DS_Event::GetEventOptionsByEventID($id);			
		}		
		assert(is_array($data) && count($data)>0);
		
		foreach($data as $k=>$v)
		{
			$id = (int)$v['id_eventoption'];
			assert($id > 0);
			
			$name = (string)$v['optionname'];
			assert(!empty($name));
			
			$this->optionsByName[$name] = $id;
		}
	}
		
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return DE_EventOptions
	 */
	static public function CreateFromID($id)
	{
		assert(is_int($id) && $id>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, '', 'GLOBAL_CACHE');									
	}
		
	
	public function getIDByName($optionName)
	{
		assert(is_string($optionName) && !empty($optionName));
		assert(key_exists($optionName, $this->optionsByName));
		
		return $this->optionsByName[$optionName];
	}
	
}//END OF CLASS


?>