<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_GetCombForPlacement
{
	static public function GetComb($ringNumber, $worldID, $existsComb)
	{
		settype($ringNumber, 'integer');
		assert($ringNumber > 0);
		
		settype($worldID, 'integer');
		assert($worldID > 0);
		
		assert(is_array($existsComb));
		
		$combID = (int)DS_Comb::GetMinCombIDForPlacementOnRing($ringNumber, $worldID);
		
		if($combID == 0)
		{
			ds_error('No free space on ring = '.$ringNumber);
		}
		
		return (int)DS_Comb::GetMinCombIDForPlacementOnRing($ringNumber, $worldID, $existsComb);
	}		

	static public function Get($ringNumber, $worldID, $existsComb = array(), $useRandomNow = 0)
	{
		settype($ringNumber, 'integer');
		assert($ringNumber > 0);
		
		settype($worldID, 'integer');
		assert($worldID > 0);
		
		assert(is_array($existsComb));
		
		if ($useRandomNow == 0) {
			$useRandom = DE_WorldSettings::CreateForCurentWorld()->get('RANDOM_COMB_FOR_NEW_INSTANCE');
		} else {
			$useRandom = 1;
		}
		
		$maxComplex = (int)DE_WorldSettings::CreateForCurentWorld()->get('RANDOM_COMB_MAX_COMPLEX');
		$minComplex = (int)DE_WorldSettings::CreateForCurentWorld()->get('RANDOM_COMB_MIN_COMPLEX');
		if($useRandom == 1 && $maxComplex > 0 && $minComplex < $maxComplex)
		{
			$randomCombCount = (int)DS_Comb::GetCountFreeCombsInComplexRange($worldID, $ringNumber, $minComplex, $maxComplex);	
		} else {
			$randomCombCount = 0;		
		}	
	
		// если включен рандом и есть свободные соты в нужном диапазоне
		if($useRandom && $randomCombCount > 0)
		{
			$randComb = rand(0, $randomCombCount);
			$combID = (int)DS_Comb::GetFreeCombIdInComplexsRangeByRaceAndRandomOffset($worldID, $ringNumber, $minComplex, $maxComplex, $randComb);
		// иначе используем свободную соту с мин. ID
		} else { 
			$combID = (int)DS_Comb::GetMinCombIDForPlacementOnRing($ringNumber, $worldID, $existsComb);
		}
		
		return $combID;
	}
}

?>