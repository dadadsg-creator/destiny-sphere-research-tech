<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция со всеми лотами высталенными пользователем
 *
 */
class DC_TradeUserLots extends DC_Base 
{	
	function __construct($ID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($ID) && $ID>0);
		
		parent::__construct($ID, 'DE_TradeLot');
				
		
		$res = DS_Trade::GetAllUserLots($ID);		
		assert(is_array($res) && count($res)>=0);
		
		foreach($res as $k=>$data)
		{
			assert(key_exists('id', $data));
			$ID = (int)$data['id'];
			assert($ID > 0);
			
			$this->_addNewElement(DE_TradeLot::CreateFromID($ID, $data));
		}
		
		$this->_invariant('construct');
	}	
			
	static public function CreateFromUIID($UIID)
	{
		assert(is_int($UIID) && $UIID>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $UIID);						
	}	
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		parent::_invariant($mode);				
	}
	
	public function commit()
	{
		$this->_invariant();
		parent::commit();			
	}
	
	function __destruct()
	{
		$this->_invariant();
		parent::__destruct();
		//$this->commit();
	}

}

?>