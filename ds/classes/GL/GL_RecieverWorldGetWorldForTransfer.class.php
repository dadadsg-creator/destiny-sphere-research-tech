<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_RecieverWorldGetWorldForTransfer
{
	static private $user;
	
	static public function Get($uid, $dbId, $worldId)
	{
		assert(is_int($uid) && $uid > 0);
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		
		$worlds = DS_RecieverWorld::GetWorldsForTransferFromRecieverWorld($dbId, $worldId);
				
		if(count($worlds) == 0)
		{
			return false;
		}
		
		self::$user = DE_User::CreateFromId($uid);
				
		// loop : check transfer posibility
		foreach ($worlds as $k => $world)
		{
			$dbId = 0;
			
			foreach ($GLOBALS['dbWorldAccess'] as $k => $v)
			{
				if($v['server'] == $world['dbname'])
				{
					$dbId = (int)$k;
					break;
				}
			}
			
			$world['dbId'] = $dbId;
			
			if(self::_checkWorldForTransfer($dbId, (int)$world['id_world']))
			{
				return $world;
			}
		}

		return false;
	}
	
	static private function _checkWorldForTransfer($DBID, $worldID)
	{		
		assert(is_int($DBID) && $DBID >= 0);		
		assert(is_int($worldID) && $worldID > 0);		
		
		GE_SearchStart::SetCommon(DS_Common::CreateForCurentDB($DBID));
		$worldsToReg = GE_LogicWorldsList::GetWorldsToReg(self::$user, DS_User::IsPremium(self::$user->getID()), false);
		
		$res = false;
		
		foreach($worldsToReg as $k => $world)
		{
			if($world['dbId'] == $DBID && $world['id_world'] == $worldID)
			{
				$res = true;
				break;
			}
		}
		
		if(!$res)
		{
			return false;
		}
		
		// не заказал ли уже переезд в этот мир
		$res = DS_WorldTransfer::getUserTransferToWorld($DBID, $worldID, self::$user->getID());
		if(count($res) > 0)
		{
			return false;
		}
		
		return true;
	}
}

?>