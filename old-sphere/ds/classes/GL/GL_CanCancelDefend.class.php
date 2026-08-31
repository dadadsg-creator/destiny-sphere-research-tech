<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_CanCancelDefend
{
	static public $errorCode;

	static public function Count (DE_Army $army, $canceledUserUnstance = null)
	{
		//Принадлежит ли армия игроку
		if(!is_null($canceledUserUnstance))
		{
			if($canceledUserUnstance->getID() != $army->getUserInstance()->getID())
			{
				self::$errorCode = 'NOT_USER_ARMY';
				return false;
			}
		}

		$armyAction = $army->getArmyAction();

		if($armyAction->isNull())
		{
			self::$errorCode = 'ARMY_NOT_IN_ACTION';
			return false;
		}

		if($armyAction->getActionType() == 'IN_DEFEND')
		{
			if(!GL_CanMoveUnitsFromComb::IsCan($army->getCurentComb()))
			{
				/*$rs = DS_Battle::NotEndedBattlesOnComb($army->getCurentComb());

				if(count($rs) > 0)
				{
					//$battle = DE_Battle::CreateFromID()->getEndDate();
					
					// Рефрешим соту, которую защищаем
					$userInstanceObject = DE_UserInstance::CreateFromID($army->getCurentComb()->getUserInstance()->getID());
				
					$refreshCommand = new CR_RefreshUserInstanceCommand($userInstanceObject);
					$refreshCommand->setRefreshType(false, false);
					$refreshCommand->setExternalRefreshMode(GB_TypeFactory::CreateBase('GB_Date', time()), "");					
					
					$refreshController = new CR_RefreshUserInstance($refreshCommand);
					$refreshController->exec();			
					
		

					if(!GL_CanMoveUnitsFromComb::IsCan($army->getCurentComb()))
					{*/
						self::$errorCode = 'IN_BATTLE';
						return false;
					/*}
				}*/
			}
		}elseif($armyAction->getActionType() == 'MOVE_TO_DEFEND'){
			$now = GB_TypeFactory::CreateBase('GB_Date', time());

			if($armyAction->getEndDate()->value < $now->value)
			{
				self::$errorCode = 'NEED_REFRESH';
				return false;
			}else{
				return true;
			}
		}else{
			self::$errorCode = 'NOT_IN_DEFEND_ACTION';
			return false;
		}

		return true;
	}
}

?>
