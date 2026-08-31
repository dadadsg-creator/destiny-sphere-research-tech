<?php $CVS_Version = '$Revision$ ; $Date$';

/** 
 * Класс сужности ивента
 */

class DE_EventQueueNull extends DE_Base  implements DE_EventQueueInterface
{
	
	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($id) && $id>0);
		
		parent::__construct($id);	

		$this->_invariant('construct');	
	}
	
	protected function _invariant($mode = 'public')
	{
		if(BS_Base::_exitInvariant($mode)) return;
		
		parent::_invariant($mode);
			
	}
	
	static public function CreateFromID($id)
	{
		assert(is_int($id) && $id>0);		
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);									
	}

	public function isNull()
	{
		return true;
	}
	
	public function commit()
	{
		$this->_invariant();
		parent::commit();
		
		if($this->isModifyed == true)
		{
			if(DS_Event::UpdateEvent($this))
			{
				$this->isModifyed = false;
			}			
		}
	}
	
	function __destruct()
	{
		$this->_invariant();
		parent::__destruct();
		//$this->commit();
	}

}//END OF CLASS


?>