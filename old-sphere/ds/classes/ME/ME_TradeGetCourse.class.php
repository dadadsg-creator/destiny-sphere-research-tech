<?php $CVS_Version = '$Revision$ ; $Date$';

class ME_TradeGetCourse
{
	static public function Get(DE_TradeArticle $fromArticle, DE_TradeArticle $toArticle)
	{
		$world = DE_World::CreateForCurrentWorld();
		
		$categoryName = DS_Trade::GetTradeCategoryNameByID($fromArticle->getTradeCategoryID());
		assert(!empty($categoryName));
		
		switch($categoryName)
		{
			case 'RESOURCE':
				$data = DS_Trade::GetTradeCourseForResources($fromArticle, $toArticle, $world);
				break;
			case 'UNIT':
				$data = DS_Trade::GetTradeCourseForUnits($fromArticle, $toArticle, $world);
				break;
			case 'ARTEFACT':
				$data = DS_Trade::GetTradeCourseForArtefacts($fromArticle, $toArticle, $world);
				break;
			default:
				ds_error('Uncorrect categoryName = '.$categoryName);
		}
		
		assert(is_array($data) && count($data) > 0);
		
		return (float)$data['value'];
	}
}

?>