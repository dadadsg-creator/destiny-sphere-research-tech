<?php $CVS_Version = '$Revision$ ; $Date$';
class SU_LogDeleteCombObjects
{
	static public function LogForCurrentUserAndComb($objectType, $objectID, $cellID, $artDeathTime = '')
	{
		assert(!empty($objectType));
		assert(is_int($objectID) && $objectID > 0);
		assert(is_int($cellID) && $cellID >= 0);
		
		assert(key_exists('uiid', $_SESSION));
		$comb = DE_Comb::CreateFromID((int)$_SESSION['comb']);	
		
		$world = DE_World::CreateForCurrentWorld();
	
		DS_LogDeletingGameObjects::InsertDeleteGameObjectLog($world, $comb, $objectType, $objectID, $cellID, $artDeathTime);
	}
}

?>