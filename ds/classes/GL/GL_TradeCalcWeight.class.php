<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * Расчет веса товара в стазисе.
 * На данный момент класс не используется
 *
 */
class GL_TradeCalcWeight
{
	static public function GetWeightInNano(DE_TradeType $TradeType)
	{
		//$categoryName = DS_Trade::GetTradeCategoryNameByID($fromArticle->getTradeCategoryID());
		//assert(!empty($categoryName));
		
		// Полечаем курс в стазисе
		switch($TradeType->getTradeFromCategoryID())
		{
			// RESOURCE
			case 1:
				$resource = DE_Resource::CreateFromID($TradeType->getFromArticle()->getArticleID());
				$weight = $resource->getStazis();
				break;
			// UNIT
			case 2:
				$unit = DE_Unit::CreateFromID($TradeType->getFromArticle()->getArticleID());
				$weight = $unit->getStazis();
				break;
			// ARTEFACT
			case 3:
				$artefact = DE_Artefact::CreateFromArtefactID($TradeType->getFromArticle()->getArticleID());
				$weight = $artefact->getStazis();
				break;
			default:
				ds_error('Uncorrect categoryName = '.$categoryName);
		}

		
		// Пересчитываем курс в нано
		$nano = DE_Resource::CreateFromName('NANO_CRYSTAL');
		$weightNano = $nano->getStazis();
		$weight = $weight/$weightNano;
		
		return $weight;
	}
}

?>