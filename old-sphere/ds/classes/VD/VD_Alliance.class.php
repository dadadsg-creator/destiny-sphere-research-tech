<?php  $CVS_Version = '$Revision$ ; $Date$';

class VD_Alliance extends VD_Base
{
	static private function _validLocal($allianceName, $tag, $password, $worldId,  DS_Common $common)
	{
		if(!VD_AllianceName::Validate($allianceName, $worldId, $common))
		{
			self::$errorCode = VD_AllianceName::FetchErrorCode();
			return false;
		}
		
		if(!VD_AllianceTag::Validate($tag, $worldId, $common))
		{
			self::$errorCode = VD_AllianceTag::FetchErrorCode();
			return false;
		}
		
		if(!VD_AlliancePassword::Validate($password))
		{
			self::$errorCode = VD_AlliancePassword::FetchErrorCode();
			return false;
		}
		
		return true;
	}
	
	static private function _validGlobal($allianceName, $tag)
	{
		if(count(DS_GlobalAlliance::GetByName($allianceName)) > 0)
		{
			self::$errorCode = 'GLOBAL_ALLIANCE_NAME_NOT_UNIQUE';
			return false;
		}
		
		if(count(DS_GlobalAlliance::GetByTag($tag)) > 0)
		{
			self::$errorCode = 'GLOBAL_ALLIANCE_TAG_NOT_UNIQUE';
			return false;
		}
		
		return true;
	}
	
	static public function Validate($allianceName, $tag, $password, $worldId, DS_Common $common)
	{
		assert(is_int($worldId) && $worldId >= 0);
		
		if($worldId == 0)
		{
			$oldDbId = $common->getCurrentDBID();
			
			foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db)
			{
				$common->setDbId($dbId);

				$res = self::_validLocal($allianceName, $tag, $password, $worldId, $common);
				
				if(!$res)
				{
					break;
				}
			}
			
			$common->setDbId($oldDbId);
			
			if(!$res)
			{
				return false;
			}
			
			return self::_validGlobal($allianceName, $tag);
			
		} else {
			return self::_validLocal($allianceName, $tag, $password, $worldId, $common);
		}
	}
}

?>