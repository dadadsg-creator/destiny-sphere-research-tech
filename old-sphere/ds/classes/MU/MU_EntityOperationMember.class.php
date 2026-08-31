<?php $CVS_Version = '$Revision$ ; $Date$';

class MU_EntityOperationMember
{
	/**	 
	 *
	 * @var DE_User
	 */
	private $user;
	
	/**		 
	 * @var DE_WorldFromCollection
	 */
	private $collectionWorld;
	
	public function __construct(DE_User $user, DE_WorldFromCollection $collectionWorld)
	{		
		$this->user = $user;
		$this->collectionWorld = $collectionWorld;		
	}
	
	static public function CreateFromUserInstance(DE_UserInstance $userInstance)
	{
		$user = $userInstance->getUser();
		$worldShortName = $userInstance->getWorld()->getShortName();
		
		return self::CreateFromUserAnWorldShortName($user, $worldShortName);
	}
	
	static public function CreateFromUserAnWorldShortName(DE_User $user, $worldShortName)
	{
		assert(is_string($worldShortName) && !empty($worldShortName));
		
		return new MU_EntityOperationMember($user, DE_WorldFromCollection::CreateFromID($worldShortName));
	}	
	
	static public function CreateSystemAccount($worldName)
	{
		assert(is_string($worldName) && !empty($worldName));
		
		return self::CreateFromUserAnWorldShortName(DE_User::CreateFromID(0), $worldName);
	}
	
	public function getUserID()
	{
		return $this->user->getID();
	}
	
	/**
	 * Enter description here...
	 *
	 * @return DE_User
	 */
	public function getUser()
	{
		return DE_User::CreateFromID($this->getUserID());
	}
	
	
	public function getWorldShortName()
	{
		return $this->collectionWorld->getShortName();	
	}
	
	public function getCollectionWorld()
	{
		return $this->collectionWorld;
	}
	
	public function getDBID()
	{		
		return DS_Common::CreateForCurentDB()->getDBIDByName($this->collectionWorld->getDBName());
	}
	
	public function getLockID()
	{
		return $this->getWorldShortName()."_".$this->getUserID();
	}
}

?>