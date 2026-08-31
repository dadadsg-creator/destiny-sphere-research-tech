<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция резултатов раунда (в какой армии сколько померло)
 */
class DC_BattleRoundResultsCollection extends DC_Base
{
	function __construct($roundID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($roundID) && $roundID>0);
		
		$this->mode = 'LINK';
		
		parent::__construct($roundID, 'DE_BattleRoundResult');
		
		$data = DS_Battle::GetBattleRoundResults($roundID);		
		assert(is_array($data) /*&& count($data)>0*/);
		
		if(is_array($data))
		{
			foreach($data as $k=>$v)
			{
				assert(key_exists('id_battleresult', $v));
				$this->_addNewElement(DE_BattleRoundResult::CreateFromID((int)$v['id_battleresult'], $v));
			}
		}	
				
		$this->_invariant('construct');
	}	

	public function addResult(DE_BattleRoundResult $roundResult)
	{
		$this->_addNewElement($roundResult->getID());
	}
	
	static public function CreateFromRoundID($roundID)
	{
		assert(is_int($roundID) && $roundID>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $roundID);				
	}
			
	protected function _invariant($mode='public')
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