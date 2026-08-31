<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_MarkStartUpkeepForRefresh
{
	static public function Count(DE_UserInstance $userInstance)
	{		
		$combsCollection = $userInstance->getCombsCollection();
		$lastRefreshDate = $userInstance->getLastRefresh();
		
		$upkeepPoints = DE_LiveUpkeepPoints::GetInstance();		
	
		$combsCollection->resetNext();
		while($comb = $combsCollection->next())
		{
			$upkeepPoints->addPoint($comb, $lastRefreshDate);
		}
	}
}


?>