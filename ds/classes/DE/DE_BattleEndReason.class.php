<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2008/07/01 08:46:25 $';

/**
 * $Author: annita $
 * $Date: 2008/07/01 08:46:25 $
 * $Revision: 1.5 $
 * 
 *
 */

class DE_BattleEndReason extends DE_Base
{
	private $reasonName;
	private $armyType;
	private $localizedNameID;
	
	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($id));
		parent::__construct($id);				
		
		$data = DS_Battle::GetBattleEndReasonByID($id);
		
		assert(is_array($data) && count($data)>0);		

		assert(key_exists( 'reasonname', $data));		
		$this->reasonName = (string)$data['reasonname'];
		
		assert(key_exists('armytype', $data));		
		$this->armyType = (string)$data['armytype'];
		
		assert(key_exists('id_localizedname', $data));		
		$this->localizedNameID = (int)$data['id_localizedname'];
		
		$this->_invariant('construct');
	}//END OF METHOD
	
	/**
	 * Enter description here...
	 *
	 * @param int $id
	 * @return DE_BattleEndReason
	 */
	static public function CreateFromID($id)
	{
		assert(is_int($id));
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);						
	}		
		
	/**
	 * Enter description here...
	 *
	 * @param GB_BattleParticipantTypeCode $winnerCode
	 * @param string $reasonName
	 * @return DE_BattleEndReason
	 */
	static public function CreateFromWinnerAndReasonName(GB_BattleParticipantTypeCode $winnerCode, $reasonName)
	{		
		$id = (int)DS_Battle::GetBattleEndReasonIDByWinnerAndReasonName($winnerCode, $reasonName);
		assert($id > 0);
		
		return self::CreateFromID($id);
	}
	
	public function getLocolizedReason($useDefaultLang = false)
	{
		$this->_invariant();
		if($useDefaultLang)
		{
			return DE_Localization::CreateFromLangID(constant('DEFAULT_LANGUAGE'))->get($this->localizedNameID);
		} else {
			return DE_Localization::CreateForCurentLang()->get($this->localizedNameID);
		}
	}
	
	protected function _invariant($mode = 'public')
	{		
		if(BS_Base::_exitInvariant($mode)) return;
		assert(is_int($this->localizedNameID) && $this->localizedNameID>0);
		assert(!empty($this->armyType));
		assert(!empty($this->reasonName));		
	}
	
	public function commit()
	{
		$this->_invariant();
		parent::commit();			
	}
	
	function __destruct()
	{
		$this->_invariant();
		parent::__destruct();
		//$this->commit();
	}

}


?>