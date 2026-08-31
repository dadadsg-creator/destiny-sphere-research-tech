<?php $CVS_Version = '$Revision$ ; $Date$';

class GL_SpyMaxCountZondsOnComb
{
	static private $disableParallelTraing = true;
	
	static public function Get(DE_Comb $comb, DE_Unit $unit)
	{
		$max = (int)DE_WorldSettings::CreateForCurentWorld()->get('SPY_MAX_ZONDS_COUNT_PER_SCIENCE_LEVEL');
		$scienceLevel = DS_Science::GetSpyScienceBranchLevel($comb->getUserInstanceID());
		
		return $max * $scienceLevel;
	}
}

?>