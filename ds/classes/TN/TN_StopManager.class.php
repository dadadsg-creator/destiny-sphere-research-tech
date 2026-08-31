<?php

class TN_StopManager
{
	static private $turnirID;
	
	static public function Stop()
	{
		TN_TurnirController::UpdateRanking();
		
		TN_TurnirController::GetCommon()->startTrans();
		
		self::_setVocationMode();
		
		self::_updateWorldStatus();
		
		self::_updateTurnirRecord();
		
		self::_saveRankingHistory();
		
		TN_RewardSender::SendPrizeForTurnir(self::$turnirID);
		
		TN_TurnirController::GetCommon()->closeTrans('COMMIT');
	}
	
	static private function _setVocationMode()
	{
		TN_Search::SetVocationEndTime(null);
	}
	
	static private function _updateWorldStatus()
	{
		TN_Search::SetWorldStatus('REGISTRATION_CLOSE');
	}
	
	static private function _updateTurnirRecord()
	{
		$notEndedTurnirs = TN_Search::GetNotFinishedTurnirs();
		
		if(count($notEndedTurnirs) == 0)
		{
			echo 'NO ACTIVE TURNIRS FOUND';
			exit;
		}
		
		if(count($notEndedTurnirs) > 1)
		{
			echo 'MORE THEN 1 ACTIVE TURNIR FOUND';
		}
		
		self::$turnirID = (int)$notEndedTurnirs[0]['id_turnir'];
		
		$date = GB_TypeFactory::CreateBase('GB_Date', time());
		
		TN_Search::SetTurnirEndDate(self::$turnirID, $date);
	}
	
	static private function _saveRankingHistory()
	{
		TN_Search::SaveTurnirRanking(self::$turnirID);
	}
}

?>