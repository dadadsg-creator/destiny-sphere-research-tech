<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Артефакт
 */
class DE_Artefact extends DE_Base implements DE_TradeGoodInterface
{
	private $artefactID;
	private $artefactscopeID;
	private $artefacttypeID;
	private $artefactprobabilityID;
	private $artefactName;
	private $attributes;
	private $localizedNameID;
	private $artifactpower;
	private $destroyable;
	private $quest;
	private $artifactscore;
	
	function __construct($id, $data = null)
	{
		assert(is_int($id) && $id>0);
		
		assert(is_array($data) || is_null($data));
		
		parent::__construct($id);
		
		$needSelectInstance = false;
		$needSelectArtefact = false;
	
		if(!is_array($data))
		{
			$needSelectInstance = true;
		}else{
			if(key_exists('empty_data', $data) && $data['empty_data'] == 1)
			{
				if(key_exists('artefact_creation', $data) && $data['artefact_creation'] == 1)
				{
					$needSelectArtefact = true;
				}else{
					$needSelectInstance = true;
				}	
			}
		}
		
		if($needSelectInstance)
		{
			$data = DS_Artefact::GetByArtefactInstanceID($id);
		}elseif ($needSelectArtefact){
			$data = DS_Artefact::GetByArtefactID($id);
		}
		
		assert(is_array($data) && count($data)>0);		
		
		assert(key_exists('id_artefact', $data));
		$this->artefactID = (int)$data['id_artefact'];
		assert($this->artefactID > 0);
		
		assert(key_exists('id_artefactscope', $data));
		$this->artefactscopeID = (int)$data['id_artefactscope'];
		assert($this->artefactscopeID > 0);
	
		assert(key_exists('id_artefacttype', $data));
		$this->artefacttypeID = (int)$data['id_artefacttype'];
		assert($this->artefacttypeID > 0);

		assert(key_exists('id_artefactprobability', $data));
		$this->artefactprobabilityID = (int)$data['id_artefactprobability'];
		assert($this->artefactprobabilityID > 0);
		
		assert(key_exists( 'artefactname', $data));		
		$this->artefactName = $data['artefactname'];
		
		assert(key_exists( 'id_localizedname', $data));
		$this->localizedNameID = (int)$data['id_localizedname'];
		assert($this->localizedNameID > 0);
		
		assert(key_exists( 'artefactpower', $data));
		$this->artifactpower = (int)$data['artefactpower'];
		assert($this->artifactpower > 0);
		
		assert(key_exists( 'artefact_score', $data));
		$this->artifactscore = (int)$data['artefact_score'];
		assert($this->artifactscore > 0);
		
		assert(key_exists( 'destroyable', $data));
		if($data['destroyable'] == 't')
		{
			$this->destroyable = true;
		} else {
			$this->destroyable = false;
		}
		
		assert(key_exists( 'quest', $data));
		if($data['quest'] == 't')
		{
			$this->quest = true;
		} else {
			$this->quest = false;
		}
			
		if(key_exists('NEED_INSERT', $data))
		{
			$this->needInsert = $data['NEED_INSERT'];
		}
		
		$this->_invariant('construct');
	}
	
	/**
	 * Enter description here...
	 *
	 * @param int $id
	 * @return DE_Artefact
	 */
	static public function CreateFromArtefactInstanceID($id)
	{
		assert(is_int($id) && $id>0);
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);						
	}
	
	/**
	 * Enter description here...
	 *
	 * @param int $id
	 * @return DE_Artefact
	 */
	static public function CreateFromArtefactID($id)
	{
		assert(is_int($id) && $id>0);
		
		$data['empty_data'] = 1;
		$data['artefact_creation'] = 1;
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data);						
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_ArtefactAttributes
	 */
	public function getAttributes()
	{
		return DE_ArtefactAttributes::CreateFromArtefactID($this->getArtefactID());
	}
	
	/**
	 * Enter description here...
	 *
	 * @param int $artefactID
	 * @return DE_Artefact
	 */
	static public function CreateInDB($artefactID)
	{		
		assert(is_int($artefactID) && $artefactID > 0);
		
		$artefactInstanceID = DS_Artefact::GetNewArtefatcInstanceID();
		assert($artefactInstanceID > 0);
		
		$data = DS_Artefact::GetArtefactData($artefactID);
		assert(is_array($data));					
		$data['NEED_INSERT'] = true;
		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $artefactInstanceID, $data);
	}
	
	public function getArtefactID()
	{
		return $this->artefactID;
	}
	
	public function getStazis()
	{
		return $this->artifactpower;
	}
	
	public function getScore()
	{
		return $this->artifactscore;
	}
	
	public function getProbabilityID()
	{
		return $this->artefactprobabilityID;
	}
	
	public function getLocalizedName()
	{
		return DE_Localization::CreateForCurentLang()->get($this->localizedNameID);
	}
	
	public function getIsDestroyable()
	{
		return $this->destroyable;
	}
	
	public function getIsQuest()
	{
		return $this->quest;
	}
	
	public function commit()
	{
		$this->_invariant();
		
		if($this->needInsert)
		{
			DS_Artefact::InsertArtefactInstance($this);
			$this->needInsert = false;
		}
		
		parent::commit();
	}
	
	protected function _invariant($mode = 'public')
	{
 		parent::_invariant();
		
		assert(!empty($this->artefactName));
		assert(is_int($this->artefactID) && $this->artefactID>0);

		assert(is_int($this->artefactscopeID) && $this->artefactscopeID>0);
		assert(is_int($this->artefacttypeID) && $this->artefacttypeID>0);
		assert(is_int($this->artefactprobabilityID) && $this->artefactprobabilityID>0);		
	}
	
	function __destruct()
	{
		//$this->commit();
	}
	
}//END OF CLASS

?>