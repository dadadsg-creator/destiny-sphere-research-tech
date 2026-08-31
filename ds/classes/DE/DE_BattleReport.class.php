<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * ־עקוע מ במו
 */

class DE_BattleReport extends DE_Base 
{
	private $battleID;
	
	//private $userInstanceID;
	private $participantFlag;
	
	private $report = '';
	
	private $latter = '';
	
	public function __construct($id, $data = null)
	{
		assert(strpos($id, '_') !== false);	
		parent::__construct($id);
		
		list($battleID, $participant) = explode('_', $id);
		$this->battleID = (int)$battleID;
		assert($this->battleID > 0);
		
		assert(empty($participant) || $participant == 'A' || $participant == 'D');
		if(empty($participant))
		{
			$this->participantFlag = null;
		} else {
			$this->participantFlag = $participant;
		}
		
		if(is_array($data) && key_exists('CREATION', $data))
		{
			$this->needInsert = true;
			$this->report = '';
		} else {
			$data = DS_Battle::GetReportByBattleIDAndParticipantFlag($this->battleID, $this->participantFlag);		
			assert(is_array($data) && count($data)>0);
			
			assert(key_exists('report', $data));		
			$this->report = (string)$data['report'];
		}
		
		$this->_invariant('construct');		
	}
	/**
	 * Enter description here...
	 *
	 * @param int $battleID
	 * @return DE_BattleReport
	 */
	static public function CreateFromBattleIDAndParticipantFlag($battleID, $participantFlag)
	{
		assert(is_int($battleID) && $battleID>0);					
		assert($participantFlag == 'A' || $participantFlag == 'D');
		//$reportID = DS_Battle::GetReportIDByBattleIDAndParticipantFlag($battleID, $participantFlag);
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $battleID.'_'.$participantFlag, $data, 'WORLD_CACHE');
	}
	

	/**
	 * Enter description here...
	 *
	 * @param DE_Battle $battle
	 * @return DE_BattleReport
	 * @deprecated
	 */
	static public function CreateInDB(DE_Battle $battle, $participantFlag)
	{
		assert($participantFlag == 'A' || $participantFlag == 'D');
		$reportID = DS_Battle::InsertEmptyBattleReport($battle, $participantFlag);		
		return self::CreateFromID($reportID);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param string $id
	 * @return DE_BattleReport
	 */
	static public function CreateFromID($id)
	{
		//assert(is_int($id) && $id>0);	
		assert(strpos($id, '_') !== false);
		$data['CREATION'] = true;
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_Battle
	 */
	public function getBattle()
	{
		$this->_invariant();
		return DE_Battle::CreateFromID($this->battleID);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return string
	 */
	public function getReport()
	{
		$this->_invariant();
		return str_replace("'", "", $this->report);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return string
	 */
	public function getLatter()
	{
		$this->_invariant();
		return $this->latter;
	}
	
	public function setParticipantFlag($participantFlag)
	{
		assert($participantFlag == 'A' || $participantFlag == 'D');
		$this->participantFlag = $participantFlag;
	}
	
	public function getParticipantFlag()
	{
		return $this->participantFlag;
	}
	
	public function addString($str = '', $type = 'REPORT')	
	{		
		$this->_invariant();
		//assert(is_string($str));
		assert($type == 'REPORT' || $type == 'LATTER');
		
		$multyLang = DE_WorldSettings::CreateForCurentWorld()->get('BATTLE_LOG_MULTY_LANG');
		if ($multyLang == 1) {
			$this->report[] = $str;
		} else {
			$this->report .= $str."\n";	
		}
		
		if($type == 'LATTER')
		{
			$this->latter .= $str."\n";		
		}
		
		$this->isModifyed = true;
	}
	
	public function resetReport()
	{		
		$this->_invariant();
		$this->report = '';
		$this->isModifyed = true;
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		parent::_invariant($mode);
		
		assert(is_int($this->battleID) && $this->battleID > 0);
		assert(is_string($this->report));		
		assert(is_string($this->latter));		
	}
	
	public function commit()
	{
		parent::commit();
		
		if($this->needInsert)
		{
			DS_Battle::InsertBattleReport($this);
			$this->needInsert = false;	
		} elseif($this->isModifyed == true) {
			if(DS_Battle::UpdateBattleReport($this))
			{
				$this->isModifyed = false;
			}			
		}
	}
	
	public function __destruct()
	{
		parent::__destruct();
	}
}

?>