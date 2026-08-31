<?php $CVS_Version = '$Revision$ ; $Date$';

//DE_EndBattleReason
class DE_EndBattleTestResult extends BS_Base
{
	private $winner;
	
	private $reasonCode;
	
	private $allowedReasonCode = array('EMPTY_ARMY', 'EMPTY_DEMAGE', 'BIG_LOSES', 'DEFENDER_TOO_WEEK_FOR_BATTLE', 'DEFENDER_RETREAT', 'ATTACKER_RETREAT');	
	
	public function __construct(GB_BattleParticipantTypeCode $winner, $reasonCode)
	{		
		//assert($winner instanceof GB_BattleParticipantTypeCode);
		assert(in_array($reasonCode, $this->allowedReasonCode));	
		
		parent::__construct();;
		
		$this->winner = $winner;
		$this->reasonCode = $reasonCode;
	}
	
	public function getWinnerCode()
	{
		return $this->winner;
	}
	
	public function getReasonCode()
	{
		return $this->reasonCode;
	}
	
	public function __destruct()
	{
		parent::__destruct();
	}
	
}

?>