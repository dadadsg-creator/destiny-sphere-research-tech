<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Локинг аккаунтов инстансов для выполнения операциии
 *
 */
class MU_ExecLockUserInstanceAccount
{
	
	static private $lockAccounts = array();
	
	static public function Lock(GB_MoneyUserInstanceOperation $operation)
	{
		self::_lockUserInstanceAccount($operation->getFromUserInstance());
		
		if($operation->havePartner())
		{
			self::_lockUserInstanceAccount($operation->getToUserInstance());	
		}		
	}
	
	static private function _lockUserInstanceAccount(DE_UserInstance $userInstance)
	{
		if(in_array($userInstance->getID(), self::$lockAccounts))
		{
			return;
		}
		
		if(SU_Locker::CerateForCurrentDB('USER_INSTANCE_ACCOUNT')->isLocked($userInstance->getID()))
		{
			DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $userInstance->getID(), 'USER_INSTANCE_ACCOUNT');
		}else{
			SU_Locker::CerateForCurrentDB('USER_INSTANCE_ACCOUNT')->lock($userInstance->getID());
		};
		
		self::$lockAccounts[] = $userInstance->getID();
	}
}

?>