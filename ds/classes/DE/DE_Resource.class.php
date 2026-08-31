<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Игровой ресурс
 */
class DE_Resource extends DE_Base implements DE_TradeGoodInterface
{
	private $name;
	private $exchangerate;
	private $startAmount;
	private $locolizedNameID;
	private $locolizedShortNameID;
	
	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_integer($id) && $id>0);		
		parent::__construct($id);
		
		$data = DS_Misc::GetResourceByID($id, false);		
		assert(is_array($data) && count($data)>0);

		assert(key_exists('attributename', $data));		
		$this->name = $data['attributename'];				
		
		assert(key_exists('exchangerate', $data));		
		$this->exchangerate = (float)$data['exchangerate'];	
		
		assert(key_exists('startamount', $data));		
		$this->startAmount = (int)$data['startamount'];	
			
		assert(key_exists('id_localizedname', $data));		
		$this->locolizedNameID = (int)$data['id_localizedname'];
		
		assert(key_exists('id_locolizedshortname', $data));		
		$this->locolizedShortNameID = (int)$data['id_locolizedshortname'];
		
		$this->_invariant('construct');
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $name
	 * @return DE_Resource
	 */
	static public function CreateFromName($name)
	{
		assert(!empty($name));		
		$id = DS_Misc::GetResourceIDByName($name);		
		return self::CreateFromID($id);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $id
	 * @return DE_Resource
	 */
	static public function CreateFromID($id)
	{
		assert(is_int($id) && $id>0);			
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, '', "GLOBAL_CACHE");
	}
	
	public function getExchangeRate()
	{
		$this->_invariant();
		return $this->exchangerate;
	}
	
	public function getName()
	{
		$this->_invariant();
		
		return $this->name;
	}
	
	public function getShortName()
	{
		$this->_invariant();
		
		return substr($this->name, 0, 1);
	}
	
	public function getLocolizedName()					
	{
		$this->_invariant();
		
		return DE_Localization::CreateForCurentLang()->get($this->locolizedNameID);
	}
	
	public function getLocolizedId()					
	{
		return $this->locolizedNameID;
	}
	
	public function getLocalizedName()					
	{				
		return $this->getLocolizedShortName();
	}
	
	public function getLocolizedShortName()
	{
		$this->_invariant();
		
		return DE_Localization::CreateForCurentLang()->get($this->locolizedShortNameID);
	}
		
	public function convertToStazis($amount)
	{
		$this->_invariant();
		assert($amount >= 0);
		
		return $amount * $this->exchangerate;
	}
	
	public function getStazis()
	{
		return $this->convertToStazis(1);
	}
	
	public function convertFromStazis($amount)
	{
		$this->_invariant();
		assert($amount >= 0);
		
		return $amount / $this->exchangerate;
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		
		parent::_invariant($mode);
		
		assert(is_int($this->exchangerate) && $this->exchangerate>=0);
		assert(!empty($this->name));
		assert(is_int($this->startAmount) && $this->startAmount>=0);	
		assert(is_int($this->locolizedNameID) && $this->locolizedNameID>0);	
	}
	
	public function commit()
	{
		$this->_invariant();
		parent::commit();
		
		if($this->isModifyed == true)
		{
			if(DS_Army::UpdateArmyData($this))
			{
				$this->isModifyed = false;
			}			
		}
	}
	
	public function delete()
	{
		parent::delete();
		
		if(!$this->getArmyAction()->isNull())
		{
			$this->getArmyAction()->delete();
		}
		
		DE_ArmyAttributes::CreateFromArmyID($this->getID())->delete();
		
		DS_Army::DeleteArmyByID($this->getID());
	}
	
	function __destruct()
	{
		parent::__destruct();
		$this->_invariant();
		//$this->commit();
	}
}

?>