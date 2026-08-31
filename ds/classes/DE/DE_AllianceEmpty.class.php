<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 * Сущность псевдо-альянса(пустого) игрока
 */

class DE_AllianceEmpty extends DE_Base 
{
	/**
	 * Полное Название Альянса
	 *
	 * @var string
	 */
	private $name = 'EMPTY ALLIANCE';
	
	/**
	 * Короткое название альянса
	 *
	 * @var string
	 */
	private $shortname = 'EMPTY ALLIANCE';
	
	function __construct($id)
	{			
		parent::__construct($id);
				
	}//END OF CONTRUCTOR
	
	/**
	 * Enter description here...
	 *
	 * @return DE_AllianceEmpty
	 */
	static public function CreateFromID()
	{		
		// у пустого альянса задан такой айдишник
		return GB_SingletonObjectStorage::GetSingleton(__CLASS__, 211111111);		
	}//END OF METHOD
		
	public function getName()
	{
		 
		return $this->name;
	}//END OF METHOD
	
	public function isEmpty()
	{
		 
		return true;
	}//END OF METHOD
	
	public function getShortName()
	{
		 
		return $this->shortName;
	}//END OF METHOD

} //END OF CLASS


?>