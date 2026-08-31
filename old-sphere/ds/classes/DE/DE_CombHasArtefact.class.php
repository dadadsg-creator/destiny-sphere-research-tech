<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Объект - "артефакт в хранилище"
 * используется при добавление артефакта на соту, при нахождении археологами
 *
 */
class DE_CombHasArtefact extends DE_Base
{
	private $artefactInstanceID;
	
	private $comb;
	
	private $artefact;

	function __construct($id, $data = null)
	{
		assert(!empty($id));
		assert(strpos($id, '_') !== false);
		
		assert(is_array($data) || is_null($data));
		
		parent::__construct($id);
		
		$arr = explode('_', $id);
		assert(count($arr) == 2);
		
		$this->artefactInstanceID = (int)$arr[0];
		assert($this->artefactInstanceID > 0);
		
		$this->artefact = DE_Artefact::CreateFromArtefactInstanceID($this->artefactInstanceID);
		
		$id_comb = (int)$arr[1];
		assert($id_comb > 0);
		
		$this->comb = DE_Comb::CreateFromID($id_comb);
		
		if(is_array($data))
		{
			if(key_exists('artefact_creation', $data) && $data['artefact_creation'] == 1)
			{
				$this->needInsert = true;
			}
		} else {
			//$data = DS_Artefact::GetArtefactsFromCombStore($this->comb, $this->artefactInstanceID);
		}
		
		$this->_invariant('construct');
	}
	
	static public function CreateFromID($id, $data = '')
	{
		assert(!empty($id));
		assert(strpos($id, '_') !== false);
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data);
	}
	
	static public function CreateInDB($id)
	{
		assert(!empty($id));
		assert(strpos($id, '_') !== false);
		$data['artefact_creation'] = 1;
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id, $data);
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		
		parent::_invariant();
		
		assert(is_int($this->artefactInstanceID) && $this->artefactInstanceID > 0);
		assert(is_a($htis->artefact, 'DE_Artefact'));
		assert(is_a($htis->comb, 'DE_Comb'));
	}
	
	public function commit()
	{
		$this->_invariant();
		
		if($this->needInsert)
		{
			$this->artefact->commit();
			DS_Artefact::AddArtefactToCombStore($this->comb, $this->artefactInstanceID);
			$this->needInsert = false;
		}
		
		parent::commit();
	}
}