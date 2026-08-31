<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_TestRestoreUserInWorld extends SI_Base
{
	static public function Test($worldID, $combCoords, $userName, $email) 
	{
		settype($worldID, 'integer');
		assert($worldID > 0);		
		assert(is_array($combCoords));		
		assert(!empty($userName));
		assert(!empty($email));
		
		self::_validateCombCoords($combCoords, $worldID);
		
		self::_validateUser($worldID, $userName, $email);
	}
	
	static private function _validateUser($worldID, $userName, $email)
	{
		$userData = SI_SearchUserInstance::GetUserDataByName($userName);
		
		if(count($userData) == 0)
		{
			$userData = SI_SearchUserInstance::GetUserDataByEmail($email);
		}
		
		//Оключили вреенно, так как перешли на политку когда создаем юзверя, если нет такого юзвера в БД
		/**if(count($userData) == 0)
		{
			ds_error('Cant find user for name = '.$userName);
		}*/

		if(key_exists('id_user',$userData))	
		{
			$userID = (int)$userData['id_user'];
		}else{
			return;
		}
		
		$instanceData = SI_SearchUserInstance::GetUserInstanceDataByUserID($userID, $worldID);

		if(count($instanceData) > 0)
		{
			ds_error("User $userName alrady have instance in this world");
		}	
	}
	
	static private function _validateCombCoords($combCoords, $worldID)
	{					
		foreach($combCoords as $combName => $combCoord)
		{			
			self::_validateCombCoord($combCoord, $worldID);		
		}
	}
	
	static private function _validateCombCoord($combCoords, $worldID)
	{		
		assert(key_exists('RING',$combCoords));
		assert(key_exists('COMPLEX',$combCoords));
		assert(key_exists('COMB',$combCoords));
		
		//Проверям что такое комбы есть и сводобны
		$combID = SI_SearchComb::GetCombIDByCoordinates($worldID, $combCoords['RING'], $combCoords['COMPLEX'], $combCoords['COMB']);
				
		$combCoordStr = $combCoords['RING'].".".$combCoords['COMPLEX'].".".$combCoords['COMB']." in world ".$worldID;
		
		if($combID == 0)
		{
			ds_error('Dont exists comb with coords: '.$combCoordStr);
		}
		
		$combData = SI_SearchComb::GetCombDataByID($combID);
		
		if(!empty($combData['id_userinstance']))
		{
			ds_error('This comb alrady have owner '.$combCoordStr);
		}				
	}	
}




?>
