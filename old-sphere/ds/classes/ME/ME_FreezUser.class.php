<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2008/02/14 07:54:50 $';

/**
 * Менеджер заморозки пользователя.
 *
 */

class ME_FreezUser
{
	
	static private $userInstance;
	
	static public function Exec(DE_UserInstance $userInstance, $withCapture = false)
	{
		self::$userInstance = $userInstance;
		
		self::_freezWarEvents($withCapture);
		
		self::_freezTradeEvents();
		
		self::_freezSpyEvents();
	}
	
	/**
	 * Обрабатываем ивенты шпионажа. 
	 * Возвращаем летящие зонды
	 *
	 */
	static private function _freezSpyEvents()
	{		
		//Списко всех зандов, которые летят от меня или на меня
		$spyZondGroup = DS_Freez::AllSpyGroupsInActionsWithMy(self::$userInstance);
				
		foreach($spyZondGroup as $k=> $spyData)
		{
			assert(key_exists('id_eventqueue', $spyData));
			$idEventQueue = (int)$spyData['id_eventqueue'];
			assert($idEventQueue > 0);
			
			assert(key_exists('u_from', $spyData));
			$idUserInstanceFrom = (int)$spyData['u_from'];
			assert($idUserInstanceFrom > 0);
			
			$spyZondGroup = DE_SpyZondGroup::CreateFromEventQueue(DE_EventQueue::CreateFromId($idEventQueue));
			
			//  Возвращаем зонды
			ME_CancelSpy::Cancel($spyZondGroup);

			// Если нападают на игрока, который включил щит
			if (self::$userInstance->getUserInstanceID() != $idUserInstanceFrom) {
				// Отправляем мессагу
				$checkDate = GB_TypeFactory::CreateBase('GB_Date', time());

				$messparams = array();
				$messparams[0] = "{b}".$spyData['zonds_amount']."{/b}";
				$messparams[1] = "&#34;".DE_Unit::CreateFromID((int)$spyData['id_unit'])->getUnitNameLocolized()."&#34;";
				$messparams[2] = DE_Comb::CreateFromID((int)$spyData['id_comb_from'])->getFullName();
				$messparams[3] = self::$userInstance->getUser()->getName();

				// Группа из % зондов % возвращается на соту % так как игрок % включил щит или дом отдыха
				DE_SystemMessages::AddMessageFromMessageCompiler($checkDate, $idUserInstanceFrom, 'SPY_ZOND_MOVE_BACK_CHILD_OR_VOCATION', $messparams, 2, "GREEN");
			}
			
		}	
	}
	
	/**
	 * Обрабатываем ивенты военные. Та как нельзя уходит ьв заморозку когда
	 * идет бой с твоим участиеам то мы тольк овозвращаем армии свои домой.
	 *
	 */
	static private function _freezWarEvents($withCapture)
	{		
		$checkDate = GB_TypeFactory::CreateBase('GB_Date', time());

		//Списко всех армий которые связаны со мной действиями кроме тех кто возвращаеться
		$armys = DS_Freez::AllArmysInActionsWithMy(self::$userInstance, $withCapture);
				
		foreach($armys as $k => $armyData)
		{
			assert(key_exists('id_unitcontainer', $armyData));
			$armyID = (int)$armyData['id_unitcontainer'];
			assert($armyID > 0);
			
			assert(key_exists('actiontypename', $armyData));
			$actionName = $armyData['actiontypename'];
			assert(!empty($actionName));
			
			$army = DE_Army::CreateFromID($armyID);
			
			switch($actionName)
			{
				case 'MOVE_TO_DEFEND':
					ME_CancelDefend::Cancel($army, $checkDate);
					break;
				case 'IN_DEFEND':
					ME_CancelDefend::Cancel($army, $checkDate);
					break;
				case 'MOVE_TO_ATTACK':
					ME_CancelAttack::Cancel($army, $checkDate);
					break;
				default:
					ds_error('Cant freez army('.$armyID.') with action = '.$actionName);
			}			
		}		
	}
	
	static private function _freezTradeEvents()
	{
		// отменяем всю торговлю, но не снимаем налог
		ME_CancelAllUserTrades::Cancel(self::$userInstance, false, array(), true);	
	}
}



?>