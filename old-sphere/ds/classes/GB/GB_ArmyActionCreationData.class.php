<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Набор данных для создания экшена армии
 *
 */
class GB_ArmyActionCreationData extends BS_Base
{
	static public $allowActionsEvents = array(
		'MOVE_TO_ATTACK' => 'ARMY_MOVEMENT_TO',
		'IN_BATTLE' => 'BATTLE_ROUND',
		'MOVE_FROM' => 'ARMY_MOVEMENT_FROM',
		'MOVE_TO_DEFEND' => 'MOVE_TO_DEFEND',
		'IN_DEFEND'	=> '',
		'MOVE_TO_COMB' => 'MOVE_TO_COMB', 
		'HIDE_ARMY' => 'HIDE_ARMY'
	);
	
	private $armyActionName;
	
	private $army;
	
	private $datePeriod;
	
	private $participants;
	
	private $path;
	
	private $battle;
	
	private $round;
	
	public function __construct($armyActionName)
	{
		parent::__construct();	
		
		assert(key_exists($armyActionName, self::$allowActionsEvents));	
		
		$this->armyActionName = $armyActionName;
	}
	
	public function setArmy(DE_Army $army)
	{
		$this->army = $army;
	}
	
	public function setDatePeriod(GB_DatePeriod $datePeriod)
	{
		$this->datePeriod = $datePeriod;
	}
	
	public function setParticipants(GB_EventParticipants $participants)
	{
		$this->participants = $participants;
	}
	
	public function setPath(GB_TravelPath $path)
	{
		$this->path = $path;
	}
	
	public function setBattle(DE_Battle $battle)
	{ 		
		$this->battle = $battle;
	}
	
	public function setRound(DE_BattleRound $battleRound)
	{
		$this->round = $battleRound;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return string
	 */
	public function getActionName()
	{
		$this->_invariant();
		
		return $this->armyActionName;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return string
	 */
	public function getEventName()
	{
		$this->_invariant();
		return self::$allowActionsEvents[$this->getActionName()];
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Army
	 */
	public function getArmy()
	{
		$this->_invariant();
		return $this->army;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return GB_DatePeriod
	 */
	public function getDatePeriod()
	{
		$this->_invariant();
		return $this->datePeriod;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return GB_EventParticipants
	 */
	public function getParticipants()
	{
		$this->_invariant();
		return $this->participants;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return GB_TravelPath
	 */
	public function getPath()
	{
		$this->_invariant();
		return $this->path;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Battle
	 */
	public function getBattle()
	{
		$this->_invariant();
		return $this->battle;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_BattleRound
	 */
	public function getRound()
	{
		$this->_invariant();
		return $this->round;
	}
	
	private function  _invariant()
	{		
		assert($this->army instanceof DE_Army);
		assert($this->datePeriod instanceof GB_DatePeriod);
		assert($this->participants instanceof GB_EventParticipants);
		assert($this->path instanceof GB_TravelPath);
		assert(is_null($this->battle) || $this->battle instanceof DE_Battle);
		
		if($this->battle instanceof DE_Battle)
		{
			assert($this->round instanceof DE_BattleRound);
		}else{
			assert(is_null($this->round));
		}		
	}
	
	public function __destruct()
	{
		parent::__destruct();
	}
}

?>