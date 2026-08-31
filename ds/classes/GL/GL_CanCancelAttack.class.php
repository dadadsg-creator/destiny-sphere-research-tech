<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_CanCancelAttack
{
	static public $errorCode;
	
	static public function Count(DE_UserInstance $canceledUserUnstance, DE_Army $army)
	{
		//Принадлежит ли армия игроку
		if($canceledUserUnstance->getID() != $army->getUserInstance()->getID())
		{
			self::$errorCode = 'NOT_USER_ARMY';
			return false;
		}
		
		$armyAction = $army->getArmyAction();
		
		if($armyAction->isNull())
		{
			self::$errorCode = 'ARMY_NOT_IN_ACTION';
			return false;
		}
		
		if($armyAction->getActionType() != 'MOVE_TO_ATTACK')
		{
			self::$errorCode = 'CANT_CANCEL_ATTACK';
			return false;
		}else{
			$now = GB_TypeFactory::CreateBase('GB_Date', time());
			
			if($armyAction->getEndDate()->value < $now->value)
			{
				self::$errorCode = 'CANT_CANCEL_ATTACK';
				return false;
			}
		}
		
		
		return true;
	}
}

?>
