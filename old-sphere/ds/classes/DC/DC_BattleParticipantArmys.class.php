<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Коллекцию юнит контенеров которая участвует от данного учатника боя.
 */
class DC_BattleParticipantArmys extends DC_Base
{
		
	function __construct($participantID)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($participantID) && $participantID>0);
						
		parent::__construct($participantID, 'DE_BattleParticipantArmy');
		
		$data = DS_Battle::GetBattleParticipantArmys($participantID);
		
		if(count($data)<1 || $data == false)
		{
			return;
		}
		
		foreach($data as $k=>$v)
		{			
			$battleParticipantArmyID = (int)$v['id_battleparticipanthasunitcontainer'];
			assert($battleParticipantArmyID>0);			
							
			$this->_addNewElement(DE_BattleParticipantArmy::CreateFromID($battleParticipantArmyID));
		}	

		$this->_invariant('construct');	
	}
		
	static public function CreateFromParticipantID($participantID)
	{
		assert(is_int($participantID) && $participantID>0);				
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $participantID);				
	}	
	
	static public function CreateInDB($participantID, DE_Comb $comb, $attackerArmy = null)
	{
		assert(is_int($participantID) && $participantID > 0);		
		assert(is_null($attackerArmy) || $attackerArmy instanceof DE_Army);		
		
		$battleParticipant = DE_BattleParticipant::CreateFromID($participantID);				
		
		if(is_null($attackerArmy))
		{
			$armiesOfParticipant = DS_Battle::GetParticipantArmiesOnComb($battleParticipant, $comb);

			// Вылезала непонятная ошибка 
			// Assert failed in /home/www/htdocs/ds/classes/DC/DC_BattleParticipantArmys.class.php (54)
			//assert(is_array($armiesOfParticipant));		
		}else{
			$armiesOfParticipant = array(
				0 => array(
					'id_unitcontainer' => $attackerArmy->getID(),
					'unitcontainername' => $attackerArmy->getName(false)
				)
			);
		}
		

		foreach($armiesOfParticipant as $key=>$army)
		{
			assert(array_key_exists('id_unitcontainer', $army));
			assert(array_key_exists('unitcontainername', $army));
			
			$unitContainerID =(int)$army['id_unitcontainer'];
			assert($unitContainerID >0);		

			DE_BattleParticipantArmy::CreateInDB($participantID, $unitContainerID, $army['unitcontainername']);
		}
		
		return self::CreateFromParticipantID($participantID);	
	}
		
	public function getParticipantArmyByArmy(DE_Army $army)
	{
		$this->resetNext();
		while($participantArmy = $this->next())
		{
			if($participantArmy->getArmyID() == $army->getID())
			{
				return $participantArmy;
			}
		}
		
		return false;
	}
	
	protected function _collectionCondition($element)
	{
		if(!($element instanceof DE_BattleParticipantArmy ))
		{
			return false;
		}		

		if($element->getBattleParticipantID() == $this->getID())
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