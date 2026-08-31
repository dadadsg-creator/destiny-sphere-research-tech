<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_Money extends DE_Base
{
	private $name;	
	private $shortName;
	private $localizedNameID;
	
	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_integer($id) && $id>0);
		
		parent::__construct($id);
		
		$data = DS_Misc::GetMoneyByID($id);		
		assert(is_array($data) && count($data)>0);								
					
		assert(key_exists('money_name', $data));		
		$this->name = $data['money_name'];
		assert(!empty($this->name));
		
		assert(key_exists('short_name', $data));		
		$this->shortName = $data['short_name'];
		assert(!empty($this->shortName));
		
		assert(key_exists('id_locolizedname', $data));		
		$this->localizedNameID = (int)$data['id_locolizedname'];
		assert($this->localizedNameID > 0);		
	}//END OF CONTRUCTOR
	
	/**
	 * Enter description here...
	 *
	 * @param integer $id
	 * @return DE_Money
	 */
	static public function CreateFromID($id)
	{
		assert(is_integer($id) && $id>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);		
	}//END OF METHOD
	
	/**
	 * Enter description here...
	 *
	 * @param string $shortName
	 * @return DE_Money
	 */	
	static public function CreateFromShortName($shortName)
	{
		assert(!empty($shortName));
		$id = DS_Misc::GetMoneyIDByShortName($shortName);	
		
		assert(is_integer($id) && $id>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);		
	}//END OF METHOD
	
	/**
	 * Enter description here...
	 *
	 * @param string $name
	 * @return DE_Money
	 */
	static public function CreateFromName($name)
	{
		assert(!empty($name));
		$id = DS_Misc::GetMoneyIDByName($name);	
		
		assert(is_integer($id) && $id>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);		
	}//END OF METHOD
	
	public function getShortName()
	{
		return $this->shortName;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getLocolizedName($lang = DEFAULT_LANGUAGE)
	{
		$lang = (int)$lang;
		assert(is_int($lang) && $lang > 0);
		
		$name = DS_Misc::GetLocolizedText($this->localizedNameID, $lang);
		
		if(count($name) == 0)
		{
			return $this->name;
		}
		
		return $name['text'];
	}
	
	
}

?>