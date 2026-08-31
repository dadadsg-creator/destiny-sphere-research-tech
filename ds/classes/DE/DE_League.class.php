<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2008/07/01 08:46:26 $';
/**
 * Объект, который представляет лигу игрока
 *
 */

class DE_League extends DE_Base 
{	
	// Название лиги
	private $leagueName;
	
	function __construct($id)
	{
		GB_SingletonObjectStorage::TestSingletonCreation();
		assert(is_int($id) && $id>0);

		parent::__construct($id);
		
		$data = DS_League::getByID($id);
		
		assert(is_array($data) && count($data)>0);		
		
		assert(key_exists('league_name', $data));
		$this->leagueName = $data['league_name'];
		
		$this->_invariant('construct');
	}	
	
	static public function CreateFromID($id)
	{
		assert(is_int($id) && $id>0);					
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, $id);

	}		

	public function getLeagueName()
 	{
 		return $this->leagueName;
 	}
}//END OF CLASS


?>