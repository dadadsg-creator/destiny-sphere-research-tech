<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_RefreshBattle
{
		
	/**
	 * Здесь батл интеерсуеться не появилось чли что-то новенького 
	 * среди защитников, что могло бы подключиться к бою
	 *
	 * @param DE_Battle $battle
	 */
	static public function InternalRefresh(DE_Battle $battle)
	{	
		//Составляем список армий которые уже есть вэтом бою.
		$usedArmysID = self::_formListOfUsedArmys($battle);				

		$battleCombID = $battle->getCombID();
		
		//А теперь сомтрим какие етсь объекты армия,
		$armysList = GB_SingletonObjectStorage::GetObjectsOfClass('DE_Army');

		$armysList->resetPointer();
		while($army = $armysList->getNextElement())
		{
			$armyActionType = $army->getArmyAction()->getActionType();			
						
			$preCondition = ($army->getCurentCombID() == $battleCombID && $armyActionType == 'IN_DEFEND') || ($army->getBaseCombID() == $battleCombID && !$army->getInAction());
			
			if($preCondition && !in_array($army->getID(), $usedArmysID))
			{//Т.е. мы еще эту армию незнаем и она должна участовать в бое
				//Раз такое дело то надо ее к бою добавить.
				self::AddArmy($battle, $army);
			}
		}
				
	}	
	
	static private function _formListOfUsedArmys(DE_Battle $battle)
	{
		$usedArmysID = array();
		
		//ДОбавляем армию атакующего
		$attakerArmysCollectio = $battle->getAttacker()->getArmiesColletion();		
		$attakerArmysCollectio->resetNext();
		$usedArmysID[] = $attakerArmysCollectio->next()->getArmyID();
		
		//Перебираем обороняющихся
		$defendersCollection = $battle->getDefendersCollection();		
		$defendersCollection->resetNext();
		while($defender = $defendersCollection->next())
		{
			//Перебераем армии обороняющегося
			$partycipantArmysCollection = $defender->getArmiesColletion();			
			$partycipantArmysCollection->resetNext();
			while($participantArmy = $partycipantArmysCollection->next())
			{
				$usedArmysID[] = $participantArmy->getArmyID();
			}			
		}
		
		return $usedArmysID;
	}
	
	static public function AddArmy(DE_Battle $battle, DE_Army $army)
	{
		//Сперва надо узнать елси участник В этом бою для этой армии
		$battleParticipant = $battle->getBattleParticipantBy($army->getUserInstance());
		
		if(!($battleParticipant instanceof DE_BattleParticipant))
		{			
			$battleParticipant = self::AddDefenderBattleParticipant($battle, $army->getUserInstance());
		}
		
		self::AddDefenderArmy($battle, $battleParticipant, $army);
		
	}
	
	/**
	 * Добавление новго участника к бою.
	 *
	 * @param DE_Battle $battle
	 * @param DE_UserInstance $userInstance
	 * @return DE_BattleParticipant
	 */
	static public function AddDefenderBattleParticipant(DE_Battle $battle, DE_UserInstance $userInstance)
	{
		$battleParticipant = DE_BattleParticipant::CreateInDB($battle->getID(), $battle->getComb(), $userInstance, 'D', 'f');
		GB_SingletonObjectStorage::RefreshObjectInCollections($battleParticipant);	
			
		return 	$battleParticipant;
	}
	
	/**
	 * ДОбавления новой армии к существующеку уже участнику боя
	 *
	 * @param DE_Battle $battle
	 * @param DE_BattleParticipant $battleParticipant
	 * @param DE_Army $army
	 * @return DE_BattleParticipantArmy
	 */
	static public function AddDefenderArmy(DE_Battle $battle, DE_BattleParticipant $battleParticipant, DE_Army $army)
	{
		//Узнаем есть ли уже такая армия у партиципанта			
		$battleParticipantArmy = $battleParticipant->getArmiesColletion()->getParticipantArmyByArmy($army);			
						
		if($battleParticipantArmy instanceof DE_BattleParticipantArmy)
		{
			return 	$battleParticipantArmy;			
		}else{
			$battleParticipantArmy = DE_BattleParticipantArmy::CreateInDB($battleParticipant->getID(), $army->getID(), $army->getName(false));				
			GB_SingletonObjectStorage::RefreshObjectInCollections($battleParticipantArmy);		
			
			return $battleParticipantArmy;
		}		
	}
}
?>