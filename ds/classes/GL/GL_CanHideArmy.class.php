<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_CanHideArmy
{
	/**
	 * Армия которую проверяем 
	 *
	 * @var DE_Army
	 */
	static private $army;
	
	/**
	 * Код ошибки
	 *
	 * @var string
	 */
	static public $errorCode = '';
	
	static public function Test(DE_Army $army)
	{

		self::$army = $army;
		
		if(!self::_isAllowHideInWorld())
		{
			self::$errorCode = 'HIDE_NOT_ALOWED';
			return false;
		}
		
		if(self::_haveCurrentAction())
		{
			self::$errorCode = 'HAVE_OTHER_ACTION';
			return false;
		}

		if(self::_isDefaultArmy())
		{
			self::$errorCode = 'IS_DEFAULT_ARMY';
			return false;
		}
		
		if(self::_haveBattle())
		{
			self::$errorCode = 'ARMY_IN_BATTLE';
			return false;
		}
		
		self::$errorCode = '';

		return true;
	}
	
	static private function _isAllowHideInWorld()
	{
		$tmp = DE_WorldSettings::CreateForCurentWorld()->get('ARMYS_HIDDING_ALLOW');
		
		if($tmp == 1)
		{
			return true;
		}else{
			return false;
		}
	}
	
	static public function _haveCurrentAction()
	{
		if(self::$army->getArmyAction()->isNull())
		{
			return false;
		}else{
			return true;
		}
	}
		
	
	static public function _isDefaultArmy()
	{
		$tmp = self::$army->isDefault();
		if($tmp == 't')
		{
			return true;
		}else{
			return false;
		} 
	}
	
	static public function _haveBattle()
	{
		$rs = DS_Battle::NotEndedBattlesOnComb(self::$army->getBaseComb());
		
		if(count($rs) > 0)
		{
			return true;
		}else{
			return false;
		}
	}
}

?>