<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Колекция раундов боя
 */
class DC_BattleRounds extends DC_Base 
{
	/**
	 * ID райнлда боя который находиться в процессе завершения. Т.е. сейчас идет.
	 *
	 * @var unknown_type
	 */
	private $currentRoundID = 0;
	
	function __construct($battleID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($battleID) && $battleID>0);
		
		parent::__construct($battleID, 'DE_BattleRound');
		
		$data = DS_Battle::GetBattleRoundsByBattleID($battleID);

		$battle = DE_Battle::CreateFromID($battleID);	
			
		if(count($data) < 1)
		{
			return;
		}
		
		foreach($data as $k=>$v)
		{
			assert(array_key_exists('id_battleround', $v));
			$roundID = (int)$v['id_battleround'];
			assert($roundID>0);		
			
			//Определяем текущий раунд.
			if(!$battle->isBattleEnded())
			{
				$this->currentRoundID = $roundID;
			}
			
			$this->_addNewElement((DE_BattleRound::CreateFromID($roundID)));
		}		

		$this->_invariant('construct');	
	}
		
	static public function CreateFromBattleID($battleID)
	{
		assert(is_int($battleID) && $battleID>0);				
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $battleID);				
	}	
	
	
	public function haveCurrentRound()
	{
		return (boolean)$this->currentRoundID;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_BattleRound
	 */
	public function getCurrentRound()
	{
		assert(!DE_Battle::CreateFromID($this->getID())->isBattleEnded());
		
		assert($this->currentRoundID > 0);
		return DE_BattleRound::CreateFromID($this->currentRoundID);
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		parent::_invariant($mode);
	}	
	
	public function __destruct()
	{
		parent::__destruct();
	}
}

?>