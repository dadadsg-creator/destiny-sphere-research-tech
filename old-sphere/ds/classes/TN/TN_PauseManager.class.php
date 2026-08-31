<?php

class TN_PauseManager
{
	/**
	 * Enter description here...
	 *
	 * @var GB_Date
	 */
	static private $resumeDate;
	
	static public function Pause(GB_Date $resumeDate)
	{
		self::$resumeDate = $resumeDate;
		
		self::_checkActiveTurnir();
		
		TN_TurnirController::GetCommon()->startTrans();
		
		TN_Search::SetVocationEndTime(self::$resumeDate);
		
		TN_Search::SetWorldStatus('REGISTRATION_CLOSE');
		
		self::_freezAllUsers();
		
		GB_SingletonObjectStorage::CommitAllCommitable();
		
		TN_TurnirController::GetCommon()->closeTrans('COMMIT');		
	}
	
	static private function _checkActiveTurnir()
	{
		$notStartedTurnirs = TN_Search::GetNotStartedTurnirs();
		
		if(count($notStartedTurnirs) == 0)
		{
			echo 'NO ACTIVE TURNIRS FOUND';
			exit;
		}
		
		if(count($notStartedTurnirs) > 1)
		{
			echo 'MORE THEN 1 ACTIVE TURNIR FOUND';
		}
	}
	
	static private function _freezAllUsers()
	{
		$instances = TN_Search::GetAllInstances();
		
		$startDate = GB_TypeFactory::CreateBase('GB_Date', time());
		
		foreach($instances as $k => $i)
		{
			$userInstance = DE_UserInstance::CreateFromID((int)$i['id_userinstance']);

			//Замораживаем соту
			ME_FreezUser::Exec($userInstance);

			//Сразу же значения значения иентов и рефреша на указаное время
			ME_UnFreezUser::Exec($userInstance, $startDate, self::$resumeDate);
		}
	}
}

?>