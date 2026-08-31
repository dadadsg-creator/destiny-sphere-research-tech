<?php $CVS_Version = '$Revision$ ; $Date$';
class ME_ReturnArmysFromCollection
{
	static public function Exec(DC_Base $combCollection, GB_Date $checkDate)
	{
		$combCollection->resetNext();
		while($army = $combCollection->next())
		{
			$armyAction = $army->getArmyAction();

			if($armyAction->isNull())
			{
				continue;
			}

			switch ($armyAction->getActionType())
			{
				case 'MOVE_TO_DEFEND':
					ME_CancelDefend::Cancel($army, $checkDate);

					//Отправляем системное сообщение
					$messparams[0] = "{b} ".ME_CancelDefend::$combTo->getName()."{/b}";
					$messparams[1] = "{b} ".ME_CancelDefend::$combTo->getUserInstance()->getUser()->getName()."{/b}";
					$messparams[2] = "{b} ".$army->getName()."{/b}";
					$messparams[3] = "{b} ".ME_CancelDefend::$combFrom->getFullName()."{/b}";
					//$messparams[4] = "{b} ".ME_CancelDefend::$passedTime->getDSFormat()."{/b}";
					if (!empty(ME_CancelDefend::$passedTime)) {
						$messparams[4] = "{b} ".ME_CancelDefend::$passedTime->getDSFormat()."{/b}";
						$messparams[5] = "{b} ".ME_CancelDefend::$returnResources->getShortReturnList()."{/b}";
					} else {
						// Если возвращали армию из защиты
						$messparams[4] = "{b} ".ME_CancelDefend::$travelTime->getDSFormat()."{/b}";
						$messparams[5] = "-";
					}
					DE_SystemMessages::AddMessageFromMessageCompiler($checkDate, $army->getUserInstance()->getID(), 'CANCEL_DEFEND_MESS1', $messparams, 1, "GREEN");
					break;
				case 'IN_DEFEND':
					// Смотрим, бьется ли сейчас армия
					if(count (DS_Battle::NotEndedBattlesOnComb($army->getCurentComb())) > 0) {
						// Помечаем армии пользователя, находящиеся в бою на соте соальянсовика,
						//чтобы они отозвались после окончания боя
						ME_ArmyOccurrence::CreateOccurrence($army, 'END_BATTLE', 'RETURN_ARMY_FROM_DEFEND');
					} else {
						// Отзаваем армию с защиты
						ME_CancelDefend::Cancel($army, $checkDate);

						//Отправляем системное сообщение
						$messparams[0] = "{b} ".ME_CancelDefend::$combTo->getFullName()."{/b}";
						$messparams[1] = "{b} ".ME_CancelDefend::$combTo->getUserInstance()->getUser()->getName()."{/b}";
						$messparams[2] = "{b} ".$army->getName()."{/b}";
						$messparams[3] = "{b} ".ME_CancelDefend::$combFrom->getName()."{/b}";
						$messparams[4] = "{b} ".ME_CancelDefend::$travelTime->getDSFormat()."{/b}";
						DE_SystemMessages::AddMessageFromMessageCompiler($checkDate, $army->getUserInstance()->getID(), 'CANCEL_DEFEND_MESS2', $messparams, 1, "GREEN");
					}
					break;
				case 'MOVE_TO_ATTACK':
					ME_CancelAttack::Cancel($army, $checkDate);					
					//Отправляем системное сообщение
					$messparams[0] = "{b} ".ME_CancelAttack::$armyAction->getCombFrom()->getFullName()."{/b}";
					$messparams[1] = "{b} ".$army->getName()."{/b}";
					$messparams[2] = "{b} ".ME_CancelAttack::$armyAction->getCombTo()->getFullName()."{/b}";
					$messparams[3] = "{b} ".ME_CancelAttack::$armyAction->getEndDate()->getSystemMessageFormat()."{/b}";
					$messparams[4] = "";
					if (ME_CancelAttack::$returnResources instanceof GL_FullUnitsMovementCost)
					{
						$messparams[5] = "{b} ".ME_CancelAttack::$returnResources->getShortReturnList()."{/b}";
					} else {
						$messparams[5] = "";
					}

					//системное сообщение
					DE_SystemMessages::AddMessageFromMessageCompiler($checkDate, $army->getUserInstance()->getID(), 'CANCEL_ATTACK_MESS', $messparams, 1, "GREEN");
					break;
				default:
					continue;
			}
		}
	}
}


?>