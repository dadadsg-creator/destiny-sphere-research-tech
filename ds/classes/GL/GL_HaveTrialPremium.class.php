<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_HaveTrialPremium
{
	static public function GetMode(DE_User $user, $DBID = null)
	{		
		assert(is_null($DBID) || (int)$DBID >= 0);
		
		//Сперва проверяем просто триал
		$regDate = $user->getRegDate();
						
		if(time() < ($regDate->value + constant('TRIAL_AFTER_REGISTRATION') * 86400)) 
		{		
			$mode = 1;
		}else{		
			$mode = 0;
		}

		if(DS_User::IsTrialPremium($user->getID()))
		{
			$mode = 1;
		}
		
		//Если просто входная проверка или понятно и так что уже премиум	
		if(is_null($DBID) || $mode == 1)
		{
			return $mode;
		}
		
		settype($DBID, 'integer');
		
		//Теперь проверяем триал по миру
		if(!key_exists($DBID, $GLOBALS['WORLDS_WITH_PREMIUM']))
		{
			return $mode;
		}
		
		$nowDate = GB_TypeFactory::CreateBase('GB_Date', time());		
		$startDate = GB_TypeFactory::CreateBase('GB_Date', $GLOBALS['WORLDS_WITH_PREMIUM'][$DBID]['FROM']);
		$endDate = GB_TypeFactory::CreateBase('GB_Date', $GLOBALS['WORLDS_WITH_PREMIUM'][$DBID]['TO']);
		
		if($nowDate->value > $startDate->value && $nowDate->value < $endDate->value)
		{
			return 1;
		}else{
			return 0;
		}
	}
}

?>