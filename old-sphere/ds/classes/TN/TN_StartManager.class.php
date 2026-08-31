<?php

class TN_StartManager
{
	/**
	 * Enter description here...
	 *
	 * @var GB_Date
	 */
	static private $date;
	
	static public function Start()
	{
		self::$date = GB_TypeFactory::CreateBase('GB_Date', time());
		
		TN_TurnirController::GetCommon()->startTrans();
		
		self::_endVocationMode();
		
		self::_updateLastRefresh();
		
		self::_closeRegistration();
		
		self::_updateTurnirRecord();
		
		TN_TurnirController::GetCommon()->closeTrans('COMMIT');
		
		self::_collectWorld();
	}
	
	static private function _endVocationMode()
	{
		TN_Search::SetVocationEndTime(self::$date);
	}
	
	static private function _updateLastRefresh()
	{
		TN_Search::SetLastRefresh(self::$date);
	}
	
	static private function _closeRegistration()
	{
		$_SESSION['world'] = TN_TurnirController::GetWorldID();
		$_SESSION['dbId'] = TN_TurnirController::GetDBID();
		
		$allowEnterAfterStart = (int)DE_WorldSettings::CreateForCurentWorld()->get('TURNIR_ALLOW_ENTER_AFTER_START');
		
		if($allowEnterAfterStart == 0)
		{
			TN_Search::SetModifier('MAX_COUNT_WORLD_USER_INSTANCE', 0);
			$status = 'REGISTRATION_CLOSE';
		} else {
			$status = 'TURNIR_STARTED';
		}
		
		TN_Search::SetWorldStatus($status);
	}
	
	static private function _updateTurnirRecord()
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
		
		$turnirID = (int)$notStartedTurnirs[0]['id_turnir'];
		
		TN_Search::SetTurnirStartDate($turnirID, self::$date);
	}
	
	static private function _collectWorld()
	{
		$collectWorlds = new SU_CollectWorlds();
		
		ob_start();
		$collectWorlds->allowBases = array(TN_TurnirController::GetDBID());
		$collectWorlds->collect();
		ob_end_clean();
	}
}


?>