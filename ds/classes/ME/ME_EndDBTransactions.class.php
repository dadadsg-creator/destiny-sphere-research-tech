<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Менеджер завершения транзакции 
 *
 */
class ME_EndDBTransactions
{
	/**	 
	 *
	 * @var DS_Common
	 */
	static $common;
	
	/**
	 * Использовать PREPARE TRANSACTION
	 * см. доку по постгресу
	 *
	 * @var bool
	 */
	static public $usePreparedTransaction = false;
	
	static private $preparationPassed = true;
	
	static private $preparedTransactionRandomSeed = 0;
	
	static public function Exec($withoutDisconnect = false)
	{		
		//Проверяем целостность лок файлов
		if(!SU_Locker::TestLockFilesInAllObjects())
		{
			throw new Exception('USER_LOCK_DELAY');			
		}
		
		//Закрываем транзакции во всех соединениях
		self::$common = DS_Common::CreateForCurentDB();
		
		if(self::$usePreparedTransaction)
		{
			self::$preparedTransactionRandomSeed = rand(0, 10000);
			self::$preparationPassed = false;
			self::_prepareTransactions();
		}
		
		foreach(self::$common->dbWorldAccess as $DBID => $serverData)
		{
			if(!$serverData['conn'])
			{
				continue;
			}
			
			self::$common->setDbId($DBID);
						
			self::_commit();
		}
		
		//Рестартим все виды соты которые нкопились.
		CR_CombView::GlobalCacheReset();
		
		//Удаляем все метки кеша которые надо удалить
		DS_Cache::Singleton()->deleteAllMarksForDelete();
		
		if(!$withoutDisconnect)
		{
			self::Disconnect();
		}
		
		//Разлочиваем все что лочили
		SU_Locker::UnlockAllInAllObjects();		
	}
	
	static public function SetPreparedTransaction($val)
	{
		assert(is_bool($val));
		
		self::$usePreparedTransaction = $val;
	}
	
	static public function Disconnect()
	{
		self::$common->disconnectFromAll();
	}
	
	static private function _prepareTransactions()
	{
		foreach(self::$common->dbWorldAccess as $DBID => $serverData)
		{
			if(!$serverData['conn'])
			{
				continue;
			}
			
			self::$common->setDbId($DBID);
						
			if(!self::_prepare())
			{
				return false;
			}
		}
		
		self::$preparationPassed = true;
	}
	
	static private function _prepare()
	{
		//Завершаем траназкцию
		$connectionError = self::$common->haveErrorsInCurrentConnection();
		if(empty($connectionError))
		{
			$sql = "PREPARE TRANSACTION 'TRANS_DBID_".self::$common->dbId.'_'.self::$preparedTransactionRandomSeed."'";
			self::$common->executeQuery($sql);
			if(empty(self::$common->errormsg))
			{
				return true;
			}
		}
		
		return false;
	}
	
	static private function _commit()
	{					
		//Можно ставить с помомощью параметра $GLOBALS['USERS_FOR_ROLLBACK'] пользователей в вечны откат транзакции испоьзуеться
		//при отладке. Параметр обычно описан в setings_secondary.php	
		if(key_exists('USERNAME', $_SESSION) && key_exists('USERS_FOR_ROLLBACK', $GLOBALS) && is_array($GLOBALS['USERS_FOR_ROLLBACK']))
		{
			$userName = $_SESSION['USERNAME'];
			
			if(in_array($userName, $GLOBALS['USERS_FOR_ROLLBACK']))
			{
				$str = "User ".$_SESSION['USERNAME']." is in UNCOMMITABLE mode!";
				DS_Error($str, false, false, true, false);
		
				self::$common->closeTrans("rollback");
				self::$common->block();
				
				return;
			}
		}		
		
		//Завершаем траназкцию
		$connectionError = self::$common->haveErrorsInCurrentConnection();
		if(empty($connectionError) && self::$preparationPassed)
		{
			if(self::$usePreparedTransaction)
			{
				$sql = "COMMIT PREPARED 'TRANS_DBID_".self::$common->dbId.'_'.self::$preparedTransactionRandomSeed."'";
				//$sql = "COMMIT";
			} else {
				$sql = "COMMIT";
			}
			self::$common->closeTrans($sql);
		}else{
			if(self::$usePreparedTransaction)
			{
				$sql = "ROLLBACK PREPARED 'TRANS_DBID_".self::$common->dbId.'_'.self::$preparedTransactionRandomSeed."'";
			} else {
				$sql = "ROLLBACK";
			}
			self::$common->closeTrans($sql);
			self::$common->block();
		}	
	}
}
?>