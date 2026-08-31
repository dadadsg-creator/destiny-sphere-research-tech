<?php $CVS_Version = '$Revision: 1.5 $ ; $Date: 2008/07/01 08:47:35 $';

class GE_LogicCanRegInWorld
{
	static public function Test(DE_User $user, $isProfy, $DBID, $worldID)
	{
		// есть ли у игрока инстансы в непремиумных мирах
		$instances = GE_SearchStart::GetUserNonProfiInstances($user);
		
		if($instances)
		{
			$userHasInstances = true;
		} else {
			$userHasInstances = false;
		}
		
		$worldsToReg = GE_LogicWorldsList::GetWorldsToReg($user, $isProfy, $userHasInstances);
		
		foreach($worldsToReg as $k => $world)
		{
			if( $world['dbId'] == $DBID && $world['id_world'] == $worldID)
			{
				return true;
			}
		}
		
		return false;
	}
}
?>