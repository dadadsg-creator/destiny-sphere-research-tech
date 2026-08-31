<?php $CVS_Version = '$Revision$ ; $Date$';

class SI_ExecStoreUserCombs extends SI_Base 
{
	static private $id_storeuserdata;
	
	static public function Exec($combs, $id_storeuserdata, $username, $dbId, $reserve = false)
	{
		assert($combs);
		assert(is_array($combs));
		assert($id_storeuserdata);
		
		
		self::$id_storeuserdata = $id_storeuserdata;
		
		self::$common = DS_Common::CreateForCurentDB($dbId);
		SI_SearchComb::SetCommon(self::$common);
		
		foreach($combs as $k => $comb)
		{
			$comb_id = $comb->getCombID();
			
			assert($comb_id);
			
			$comb_name = $comb->getCombName();
			
			self::$common->setDbId($dbId);
		
			$comb_data = SI_SearchComb::GetCombDataByID($comb_id);
			
			assert(key_exists('ringnumber', $comb_data));
			$ring = (int)$comb_data['ringnumber'];
			assert($ring > 0);
			
			$comb_coords = $ring.".".$comb_data['complexnumber'].".".$comb_data['combnumber'];
						
			if($reserve)
			{
				self::_reserveComb($comb_id, $username, $dbId);				
			}
			
			self::_storeUserCombData($id_storeuserdata, $comb_id, $comb_name, $comb_coords, $reserve);		
		}
	}
	
	static private function _storeUserCombData($id_storeuserdata, $comb_id, $comb_name, $comb_coords, $reserve)
	{		
		assert($id_storeuserdata);
		assert($comb_id);
		assert(!empty($comb_name));
		
		self::$common->setDbId(0);
		
		$comb_name_new = '';
		$comb_coords_new = '';
		
		if($reserve)
		{
			$comb_name_new = $comb_name;
			$comb_coords_new = $comb_coords;
		}
		
		$sql = "
			INSERT INTO
				ds_storeusercombs
				(
					id_storeuserdata,
					id_comb_old,
					id_comb_new,
					comb_name_old,
					coordinates_old,
					comb_name_new,
					coordinates_new
				)
			VALUES
				(
					$id_storeuserdata,
					$comb_id,
					$comb_id,
					'$comb_name',
					'$comb_coords',
					'$comb_name_new',
					'$comb_coords_new'
				)
		";
		
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
	
	
	static private function _reserveComb($comb_id, $username, $dbId)
	{
		assert($comb_id);
		assert(is_int($dbId));
		assert($username);
		assert(is_int($dbId));
		
		self::$common->setDbId($dbId);
		
		assert($username);
		
		settype($comb_id, 'int');
		assert($comb_id);
		
		$sql = "
			INSERT INTO
				ds_reservedcomb
				(
					id_comb,
					username
				)
			VALUES
				(
					$comb_id, 
					'$username'
				)
			";					
				
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));

		self::$common->setDbId(0);
		$sql = "
			UPDATE
				ds_storeuserdata
			SET 
				is_reserved_combs = 't'
			WHERE
				id_storeuserdata = ".self::$id_storeuserdata."
			";
		self::$common->executeQuery($sql);
		assert(empty(self::$common->errormsg));
	}
}
?>