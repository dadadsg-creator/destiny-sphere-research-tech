<?php $CVS_Version = '$Revision: 1.3 $ ; $Date: 2008/12/09 11:39:23 $';

class TL_TransferResource extends TL_BaseMenu
{
	static public function FormMenu(TI_TransferResource $incoming)
	{
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><transferresource/>\n";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();
		
		/***********************  RESOURCES  ***************************************/
		
		$tradeCategoryID = DS_Trade::GetTradeCategoryIDByName('RESOURCE');
		foreach ($incoming->resources as $k => $resource)
		{
			$ch = $root->new_child("article");
			$ch->set_attribute("id", $resource['ID']);
			$ch->set_attribute("id_category", $tradeCategoryID);
			$ch->set_attribute("libname", $resource['NAME']);
			$ch->set_attribute("amount", GB_Integer::Format($resource['AMOUNT']));
		}
		
		/**********************  ARTEFACTS  ****************************************/
		
		$tradeCategoryID = DS_Trade::GetTradeCategoryIDByName('ARTEFACT');
		if(count($incoming->artefacts) == 0)
		{
			$ch = $root->new_child("article");
			$ch->set_attribute("id_category", $tradeCategoryID);
			$elem = $doc->create_text_node(urlencode(trim(DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NO_ARTEFACT_FOR_TRANSFER', array(), null, null, false))));
			$ch->append_child($elem);
		}
		foreach ($incoming->artefacts as $k => $art)
		{
			$ch = $root->new_child("article");
			$ch->set_attribute("id", $art['ID']);
			$ch->set_attribute("id_category", $tradeCategoryID);
			$ch->set_attribute("libname", $art['NAME']);
			$ch->set_attribute("amount", $art['AMOUNT']);
			$ch->set_attribute("bonusname", $art['BONUS_NAME']);
			$ch->set_attribute("bonusvalue", ' '.$art['BONUS_VALUE']);
			if($art['DEATH_MESSAGE'] != "") $ch->set_attribute("death_message", ' '.$art['DEATH_MESSAGE']);
			if($art['LIFETIME'] != "") $ch->set_attribute("lifetime", $art['LIFETIME']);
		}
		
		/*********************   UNITS   *******************************************/
		
		$tradeCategoryID = DS_Trade::GetTradeCategoryIDByName('UNIT');
		if(count($incoming->units) == 0)
		{
			$ch = $root->new_child("article");
			$ch->set_attribute("id_category", $tradeCategoryID);
			$elem = $doc->create_text_node(urlencode(trim(DE_SystemMessages::CreateForCurrentUserInstance()->getFormatedMessage('NO_UNIT_FOR_TRANSFER', array(), null, null, false))));
			$ch->append_child($elem);
		}
		foreach ($incoming->units as $k => $unit)
		{
			$ch = $root->new_child("article");
			$ch->set_attribute("id", $unit['ID']);
			$ch->set_attribute("id_category", $tradeCategoryID);
			$ch->set_attribute("libname", $unit['NAME']);
			$ch->set_attribute("amount", GB_Integer::Format($unit['AMOUNT']));
		}
		
		/********************   COMBS   ********************************************/
		
		foreach ($incoming->combsToTransfer as $k => $comb)
		{
			$ch = $root->new_child("to");
			$ch->set_attribute("combname", urlencode($comb['NAME']));
			$ch->set_attribute("coords", urlencode($comb['COORDS']));
			$ch->set_attribute("id_comb", $comb['ID']);
		}
		
		// закладки
		self::GetBuildingTabs($root, $incoming->getBuildingID(), DE_Language::CreateForCurentLanguage()->getID(), $incoming->getTabName());	
		
		return $doc->dump_mem();
	}
}

?>