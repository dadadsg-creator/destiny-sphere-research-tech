<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Результаты рануда (кого и сколько убили)
 */

class DE_BattleRoundResult extends DE_Base 
{
	private $roundID;
	
	private $participantID;
		
	private $unitContainerID;
	
	private $unitID;
	
	private $amount;
	
	private $loses;
		
	private $receiveDamage;
	
	private $outputDamage;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_Unit
	 */
	private $unit;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_BattleParticipant
	 */
	private $battleParticipant;
	
	public function __construct($id, $data = '')
	{
		assert(is_integer($id) && $id>0);		
		parent::__construct($id);
		
		if(!is_array($data))
		{
			$data = DS_Battle::GetBattleRoundResultByID($id);		
		}
		
		assert(is_array($data) && count($data)>0);

		assert(key_exists('id_battleround', $data));		
		$this->roundID = (int)$data['id_battleround'];
		assert(is_int($this->roundID) &&  $this->roundID > 0);
		
		assert(key_exists('id_battleparticipant', $data));		
		$this->participantID = (int)$data['id_battleparticipant'];
		assert(is_int($this->participantID) &&  $this->participantID > 0);
		
		assert(key_exists('id_unitcontainer', $data));		
		$this->unitContainerID = (int)$data['id_unitcontainer'];
		assert(is_int($this->unitContainerID) &&  $this->unitContainerID > 0);
		
		assert(key_exists('id_unit', $data));		
		$this->unitID = (int)$data['id_unit'];
		assert(is_int($this->unitID) &&  $this->unitID > 0);
		
		assert(key_exists('amount', $data));		
		$this->amount = (float)$data['amount'];
		assert(is_float($this->amount) &&  $this->amount >= 0);
		
		assert(key_exists('loses', $data));		
		$this->loses = (float)$data['loses'];	
		assert(is_float($this->loses) &&  $this->loses >= 0);
		
		assert(key_exists('receivedamage', $data));		
		$this->receiveDamage = (float)$data['receivedamage'];	
		assert(is_float($this->receiveDamage) &&  $this->receiveDamage >= 0);
		
		assert(key_exists('outputdamage', $data));		
		$this->outputDamage = (float)$data['outputdamage'];	
		assert(is_float($this->outputDamage) &&  $this->outputDamage >= 0);
		
		if(key_exists('sintetic', $data) && $data['sintetic'] == 1)
		{
			$this->needInsert = true;
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @param int $id
	 * @param array $data
	 * @return DE_BattleRoundResult
	 */
	static public function CreateFromID($id, $data = '')
	{
		assert(is_int($id) && $id>0);					
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param DE_BattleRound $round
	 * @param DE_BattleParticipant $participant
	 * @param DE_UnitsGroup $unitsGroup
	 * @return DE_BattleRoundResult
	 */
	static public function CreateInDB(DE_BattleRound $round, DE_BattleParticipant $participant, DE_UnitsGroup $unitsGroup)
	{
		$id = DS_Battle::GetNextIDForBattleResult();

		$amount = $unitsGroup->getAmount() - $unitsGroup->getLoses();	
		assert($amount >= 0);
		
		$data = array
		(
			'id_battleround' => $round->getID(),
			'id_battleparticipant' => $participant->getID(),
			'id_unitcontainer' => $unitsGroup->getArmy()->getID(),
			'id_unit' => $unitsGroup->getUnit()->getID(),
			'amount' => $amount,
			'loses' => $unitsGroup->getLoses(),
			'receivedamage' => $unitsGroup->getRecivedDamage(),
			'outputdamage' => $unitsGroup->getOutputedDamage(),
			'sintetic' => 1
		);		
		
		return self::CreateFromID($id, $data);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return int
	 */
	public function getArmyID()
	{
		return $this->unitContainerID;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Unit
	 */
	public function getUnit()
	{
		if(!($this->unit instanceof DE_Unit))
		{
			$this->unit = DE_Unit::CreateFromID($this->unitID);
		}
		
		return $this->unit;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return float
	 */
	public function getAmount()
	{
		return $this->amount;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return float
	 */
	public function getLoses()
	{
		return $this->loses;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return int
	 */
	public function getBattleParticipantID()
	{
		return $this->participantID;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_BattleParticipant
	 */
	public function getPattleParticipant()
	{
		if(!($this->battleParticipant instanceof DE_BattleParticipant))
		{
			$this->battleParticipant = DE_BattleParticipant::CreateFromID($this->participantID);
		}
		
		return $this->battleParticipant;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return float
	 */
	public function getReceiveDamage()
	{
		return $this->receiveDamage;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return float
	 */
	public function getOutputDamage()
	{
		return $this->outputDamage;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return int
	 */
	public function getRoundID()
	{
		return $this->roundID;
	}
	
	public function commit()
	{
		parent::commit();
		if($this->needInsert)
		{
			DS_Battle::InsertBattleRoundResult($this);
			$this->needInsert = false;
			$this->isModifyed = false;	
		}else{
			if($this->isModifyed == true)
			{
				if(DS_Battle::UpdateBattleRound($this))
				{
					$this->isModifyed = false;
				}			
			}	
		}
	}
}

?>