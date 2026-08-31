<?php

class GL_GetCombForNPC
{
	static private $iterations;
	
	static private $worldID;
	
	static private $existCombs = array();
	
	static public function Get($worldID, $ringnumber, $count, $useSameComplexForBots = false)
	{
		$countOnRing = DS_Ring::GetCountUsersOnRing($worldID, $ringnumber);
		
		self::$worldID = $worldID;
		
		$countPerSector = (int)floor($countOnRing / $count);
	
		$offset = 0;
		
		$sectorBorderComplex = array();
		
		$ring = DE_Ring::CreateFromNumber($ringnumber);
		
		for($i=0; $i<$count; $i++)
		{
			$offset = DS_Ring::GetComplexCountWithUsers($worldID, $ringnumber, $countPerSector, $offset);
			
			// дошли до края жилой зоны или до конца кольца
			if($offset == 0)
			{
				$offset = $sectorBorderComplex[count($sectorBorderComplex) - 1] + (int)round(($ring->getComplexCount() - $sectorBorderComplex[count($sectorBorderComplex) - 1]) / ($count - count($sectorBorderComplex)));
/*echo $ring->getComplexCount();
echo "\n";
echo $ring->getComplexCount() - $sectorBorderComplex[count($sectorBorderComplex) - 1];
echo "\n";
echo $count - count($sectorBorderComplex);
echo "\n";
echo $sectorBorderComplex[count($sectorBorderComplex) - 1];
echo "\n";*/
			}
			$sectorBorderComplex[] = $offset;
		}
		
//print_r($sectorBorderComplex);
		
		$result = array();
		
		for($i=0; $i<$count; $i++)
		{
			if($i == 0)
			{
				$minComplex = 1;
			} else {
				$minComplex = $sectorBorderComplex[$i-1];
			}
			
			if($i == $count - 1)
			{
				$maxComplex = $ring->getComplexCount();
			} else {
				$maxComplex = $sectorBorderComplex[$i];
			}
			
			$id_comb = (int)self::_getCombInRange($ringnumber, $minComplex, $maxComplex);
			
			if($id_comb > 0)
			{	
				$result[] = $id_comb;
			}
		}
		
//print_r($result);
		
		return $result;
	}
	
	static private function _getCombInRange($ringnumber, $minComplex, $maxComplex)
	{
		$limitNPCInComplex = 1;
			
		while(true)
		{		
			for($i=$minComplex;$i<=$maxComplex;$i++)
			{		
				$id_comb = DS_Comb::GetFreeCombInComplex(self::$worldID, $ringnumber, $i, self::$existCombs);
			
				if(!$id_comb)
				{
					continue;
				}
				
				$comb = DE_Comb::CreateFromID($id_comb);
		
				if($comb instanceof DE_CombNull)
				{
					continue;
				}
				
				if(DS_Comb::HasNPCInSameComplex($comb->getComplexID()) < $limitNPCInComplex)
				{	
					self::$existCombs[] = $id_comb;
/*echo "
id_comb = $id_comb
comb = ".$comb->getFormatedAddress()."
";*/
					return $id_comb;
				}
			}
			
			$limitNPCInComplex++;
			
			// в комплексе только 6 мест, мест нету в данном диапазоне
			if($limitNPCInComplex > 5)
			{
				return 0;
			}
		}
	}
}


?>