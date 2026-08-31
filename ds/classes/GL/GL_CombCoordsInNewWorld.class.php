<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_CombCoordsInNewWorld
{
	static private $common = null;
	
	static private $dbId;
	
	static private $worldId;
	
	static private $userName;
	
	static private $selectedNewCombID = array();
	
	static private $combNewCoordinates = array();
	
	static private $usedReservedCombs = array();
	
	static public function SetCommon(DS_Common $common)
	{
		self::$common = $common;
		
		SI_SearchInterface::SetCommon(self::$common);
	}
	
	static private function _getCommon()
	{
		assert(!is_null(self::$common));
		return self::$common;
	}
	
	static public function Get($oldCombCoords, $userName, $dbId, $worldId)
	{
		self::$combNewCoordinates = array();
				
		assert(is_array($oldCombCoords));
		assert(!empty($userName));
		assert(is_int($dbId) && $dbId >= 0);
		assert(is_int($worldId) && $worldId > 0);
		
		self::$userName = $userName;
		self::$dbId 	= $dbId;
		self::$worldId 	= $worldId;
		
		//$oldCombCoords = self::_sortCombCoords($oldCombCoords);
	
		foreach($oldCombCoords as $k => $comb)
		{
			$reservedCombs = self::_getReservedCombsOnRing((int)$comb['ringnumber']);
			
			if(count($reservedCombs) == 0)
			{
				self::_getFreeCoords($comb);
			} else {
				// поверяем есть ли зарезервированная сота именно под эту соту
				
				// Есть ли соответствие соте в спец табличке
				self::$common->setDbId(0);
				$sql = "
						SELECT
							id_cobm_to
						FROM
							ds_worldtransfercoordrelation
						WHERE
							id_comb_from = ".$comb['id_comb']."
						";

				$res = self::$common->getResultArray("", $sql, "", "", "");
				assert(empty(self::$common->errormsg));
				
				self::$common->setDbId($dbId);

				// Если есть
				if (count($res) > 0) {
					foreach($reservedCombs as $k => $reservedComb)
					{
	
						if($res[0]['id_cobm_to'] == $reservedComb['id_comb'])
						{
							$comb['complexnumber'] = $reservedComb['complexnumber'];
							$comb['combnumber'] = $reservedComb['combnumber'];
							
							self::_setNewCoords($comb);
							
							self::$usedReservedCombs[] = $reservedComb['id_reservedcomb'];
						
							continue 2;
						}
					}
				}
				
				foreach($reservedCombs as $k => $reservedComb)
				{
					if($comb['priority'] == $reservedComb['priority'])
					{
						$comb['complexnumber'] = $reservedComb['complexnumber'];
						$comb['combnumber'] = $reservedComb['combnumber'];
						
						self::_setNewCoords($comb);
						
						self::$usedReservedCombs[] = $reservedComb['id_reservedcomb'];
						
						continue 2;
					}
				}
				
				// если нет резерва под текущую соту, то берем любую зарезервированную
				foreach($reservedCombs as $k => $reservedComb)
				{
					if($reservedComb['priority'] == 0)
					{
						$comb['complexnumber'] = $reservedComb['complexnumber'];
						$comb['combnumber'] = $reservedComb['combnumber'];
						
						self::$usedReservedCombs[] = $reservedComb['id_reservedcomb'];
						
						self::_setNewCoords($comb);
						
						continue 2;
					}
				}
				
				// если вообще ничего нет для этой соты в резерве, то выбираем любую соту
				self::_getFreeCoords($comb);
			}
		}
						
		return self::$combNewCoordinates;
	}
	
	static private function _sortCombCoords($combCoords)
	{
		/*assert(is_array($combCoords));
		
		usort($combCoords, 'GL_CombCoordsInNewWorld::_sortCombsFunction');
	
		$num = 0;
		$curRing = 0;
		
		foreach($combCoords as $k => $comb)
		{
			if($curRing != $comb['ringnumber'])
			{
				$curRing = $comb['ringnumber'];
				$num = 0;
			}
			
			$num++;
			
			$combCoords[$k]['priority'] = $num;
		}
		
SU_DebugTools::LogAll($combCoords, __CLASS__);
*/
		return $combCoords;
	}
	
	static private function _sortCombsFunction($comb1, $comb2)
	{
		/*if($comb1['ringnumber'] == $comb2['ringnumber'])
		{
			if($comb1['complexnumber'] == $comb2['complexnumber'])
			{
				if($comb1['combnumber'] == $comb2['combnumber'])
				{
					return 0;		
				} else {
					$res = $comb1['combnumber'] > $comb2['combnumber'] ? -1 : 1;
					return $res;
				}
			} else {
				$res = $comb1['complexnumber'] > $comb2['complexnumber'] ? -1 : 1;
				return $res;
			}
		} else {
			$res = $comb1['ringnumber'] > $comb2['ringnumber'] ? -1 : 1;
			return $res;
		}*/
	}
	
	static private function _getReservedCombsOnRing($ring)
	{
		assert(is_int($ring) && $ring > 0);
		
		self::$common->setDbId(self::$dbId);
		
		if(count(self::$usedReservedCombs) > 0)
		{
			$resComb = "
				AND
				rc.id_reservedcomb NOT IN (".implode(', ', self::$usedReservedCombs).")
				";
		} else {
			$resComb = "";
		}
		
		$sql = "
			SELECT
				rc.id_reservedcomb,
				ri.ringnumber,
				cc.complexnumber,
				co.combnumber,
				rc.id_comb,
				rc.priority
			FROM
				ds_reservedcomb AS rc 
				INNER JOIN ds_comb AS co ON (co.id_comb = rc.id_comb)
				INNER JOIN ds_combcomplex AS cc ON (cc.id_combcomplex = co.id_combcomplex)
				INNER JOIN ds_ring AS ri ON (ri.id_ring = cc.id_ring)
			WHERE
				ri.id_world = ".self::$worldId."
				AND
				username = '".self::$userName."'
				AND
				ri.ringnumber = $ring
				".$resComb."
			ORDER BY rc.priority, ri.ringnumber, cc.complexnumber, co.combnumber
			";
		$common = self::_getCommon();
		
		$res = $common->getResultArray("", $sql, "", "", "");
		assert(empty($common->errormsg));
		
		return $res;
	}
	
	static private function _setNewCoords($comb)
	{
		assert(key_exists('combname', $comb));
		assert(key_exists('ringnumber', $comb));
		assert(key_exists('complexnumber', $comb));
		assert(key_exists('combnumber', $comb));
		
		self::$combNewCoordinates[$comb['combname']] = array(
			'RING' => $comb['ringnumber'],
			'COMPLEX' => $comb['complexnumber'],
			'COMB' => $comb['combnumber'] 
		);
	}
	
	static private function _getFreeCoords($comb)
	{
		$newCombID = (int)SI_SearchInterface::GetRandomCombIDForPlacementOnRing($comb['ringnumber'], self::$worldId, self::$selectedNewCombID);

		self::$selectedNewCombID[] = $newCombID;
			
		if($newCombID == 0)
		{
			throw new Exception("No free combs on ring ".$comb['ringnumber']);				
		}

		$newComb = SI_SearchComb::GetCombDataByID($newCombID);
	
		$newComb['combname'] = $comb['combname'];
		
		self::_setNewCoords($newComb);		
	}
}

?>