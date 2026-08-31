<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Локинг аккаунтов инстансов для выполнения операциии
 *
 */
class MU_ExecLockOperation
{
	static private $lockAccounts = array();

	static public function Lock(MU_EntityOperation $operation)
	{
		$accountsToLock = $operation->getAcountsLIstToLock();
		foreach($accountsToLock as $k=>$userToLock)
		{
      self::_lockAccount($userToLock->getLockID());
		}
	}

	static private function _lockAccount($ID)
	{
		assert(!empty($ID));

		if(in_array($ID, self::$lockAccounts))
		{
			return;
		}

		if(!SU_Locker::Create('MONEY_ACCOUNT', 0)->lockWithTest($ID))
		{
			DS_ClientLockHandler('USER_LOCK_DELAY', constant('USER_LOCK_DELAY'), $ID, 'MONEY_ACCOUNT');
		}

		self::$lockAccounts[] = $ID;
	}
}
?>