<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекция защитников соты
 */

class DC_CombDefendBattleParticipants extends DC_Base
{
		
	function __construct($battleID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($battleID) && $battleID>0);
		
		parent::__construct($battleID, 'DE_BattleParticipant');
		
		$data = DS_Battle::GetBattleDefendParticipants($battleID);
		
		if(count($data)<1)
		{
			return;
		}
		
		foreach($data as $k=>$v)
		{
			assert(array_key_exists('id_battleparticipant', $v));
			$battleParticipantID = (int)$v['id_battleparticipant'];
			assert($battleParticipantID >0);			
			
			$this->_addNewElement(DE_BattleParticipant::CreateFRomID($battleParticipantID));
		}	

		$this->_invariant('construct');	
	}
		
	static public function CreateFromBattleID($battleID)
	{
		assert(is_int($battleID) && $battleID>0);				
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $battleID);				
	}	
	
	static public function CreateInDB($battleID, DE_Comb $comb)
	{
		assert(!is_int($battleID) && $battleID > 0);
		assert(!is_null($comb));
		
		$combDefendUserInstances = array();
		$combDefendUserInstances = DS_Battle::GetCombDefendUserInstancesID($comb);
		foreach($combDefendUserInstances as $key=>$userInstanceID)
		{
			DE_BattleParticipant::CreateInDB($battleID, (int)$userInstanceID, 'DEFENDER');
		}
		 
//		return self::CreateFromBattleID($battle->getID());		
	}
	
	protected function _collectionCondition($element)
	{
		if(!($element instanceof DE_BattleParticipant))
		{
			return false;
		}
		
		if($element->getBattleID() == $this->getID())
		{
			return true;
		}else{
			return false;
		}
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