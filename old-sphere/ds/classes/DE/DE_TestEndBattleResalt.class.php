<?php $CVS_Version = '$Revision$ ; $Date$';

class DE_TestEndBattleResalt
{
	private $winner;
	
	private $reasonCode;
	
	private $allowedReasonCode = array('EMPTY_ARMY', 'EMPTY_DEMAGE', 'BIG_LOSES', 'DEFENDER_RETREAT'); 
	
	public function __construct(DE_ArmyInterface $winner, $reasonCode)
	{
		assert(key_exists($reasonCode, $this->allowedReasonCode));
		assert(is_bool($isEnded));
		
		$this->winner = $winner;
		$this->reasonCode = $reasonCode;
	}
}

?>