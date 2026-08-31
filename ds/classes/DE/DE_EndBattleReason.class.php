<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_EndBattleReason extends BS_Base
{
	private $winner;
	
	private $reasonCode;
	
	private $allowedReasonCode = array('EMPTY_ARMY', 'EMPTY_DEMAGE', 'BIG_LOSES', 'DEFENDER_TOO_WEEK_FOR_BATTLE');	
	
	public function __construct(GB_BattleParticipantTypeCode $winner, $reasonCode)
	{		
		assert(in_array($reasonCode, $this->allowedReasonCode));	
		
		parent::__construct();;
		
		$this->winner = $winner;
		$this->reasonCode = $reasonCode;
	}
	
	public function getWinner()
	{
		return $this->winner;
	}
	
	public function getReason()
	{
		return $this->reasonCode;
	}
	
	public function __destruct()
	{
		parent::__destruct();
	}
	
}

?>