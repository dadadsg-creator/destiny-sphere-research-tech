<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * ¬ывод меню строительства зондов
 * <allowzond 
 * 		type="1" 
 * 		inaction="3" 
 * 		count="12213" 
 * 		already="15" 
 * 		price="K:1,N:12,S:34,F:4,P:6,W:54,C:60" 
 * 		delta="K:1.04,N:1.5,S:1.04,F:1.04,P:1.06,W:1,C:1"
 * />
 *
 */
class TL_SpyTrainUnit extends TL_BaseMenu 
{
	static private $zondClientType = array('SPY_ZOND_SCOUT' => 1, 'SPY_ZOND_EXPLORER' => 2);
	
	static public function FormMenu(TI_SpyTrainUnit $incoming)
	{
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><zondcreate/>\n";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();
		
		$root->set_attribute('battle_xp', $incoming->battleXP);
		
		foreach ($incoming->units as $unitID => $data)
		{
			$ch = $root->new_child('allowzond');
			$ch->set_attribute("type", self::$zondClientType[$unitID]);
			$ch->set_attribute("id", $unitID);
			$ch->set_attribute("count", $data['MAX_AMOUNT']);
			$ch->set_attribute("inaction", $data['AMOUNT_TRAINING']);
			$ch->set_attribute("already", $data['CURRENT_AMOUNT']);
			$ch->set_attribute("price", $data['PRODUCTION_COST']);
			$delta = $data['PRICE_DELTA'];
			$ch->set_attribute("delta", "K:$delta,N:$delta,S:$delta,F:$delta,P:$delta,W:1,C:1,WR:1");
			$ch->set_attribute("awail",  $data['AWAIL']);
		}
		
		// закладки
		self::GetBuildingTabs($root, $incoming->getBuildingID(), DE_Language::CreateForCurentLanguage()->getID(), $incoming->getTabName());	
		
		return $doc->dump_mem();
	}
}

?>