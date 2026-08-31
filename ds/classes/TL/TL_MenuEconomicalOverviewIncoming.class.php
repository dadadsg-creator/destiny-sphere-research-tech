<?php $CVS_Version = '$Revision$ ; $Date$';

class TL_MenuEconomicalOverviewIncoming extends TL_BaseMenu
{
	static private $resourceColours = array
	(
		'FOOD' =>  '#63B263',
		'KADERIUM' => '#FFC77B',
		'PRODIUM' => '#9C9E4A',
		'NANO_CRYSTAL' => '#6396E7',
		'STASIS_ENERGY' => 	'#B5B28C'		
	);
	
	static public function FormMenu(TI_EconomicalOverviewIncoming $incoming)
	{
		
		$statistic = $incoming->getStatistic();
		
		//$localization = DE_InterfaceLocalization::CreateForCurentLang();
		
		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><table/>\n";
		$doc = domxml_open_mem($xml);
		$root = $doc->document_element();	
				
		$out = "<table>";		
		$out .= "<tr><td width=\"38\" rcol=\"0xBBBBBB\" fcol=\"0x222222\" alpha=\"80\"> - </td>";
				
		foreach($statistic as $resourceName=>$paramsList)
		{
			$resourceList[] = $resourceName;
			$out .= "<img width=\"12\" rcol=\"0xBBBBBB\" fcol=\"0x222222\" alpha=\"80\">".$resourceName."</img>";
		}
		$out .= "</tr>";
		
		foreach($paramsList as $paramName => $v)
		{
			$out .= "<tr>";
			//$localization->get($paramName)
			$out .= "<td width=\"38\" rcol=\"0xBBBBBB\" fcol=\"0x222222\" alpha=\"80\"><FONT COLOR=\"#FFFFFF\" FACE=\"Arial\"><b>{:".$paramName.":}</b></FONT></td>";
			foreach($resourceList as $k => $resourceName)
			{
//DebugTools::logAll($resourceName." ".$paramName." ".$statistic[$resourceName][$paramName].' - '.number_format(round($statistic[$resourceName][$paramName]), 0, '.', ' ')."\n", '!zya_TL_MenuEconomicalOverviewIncoming.log');
				$out .= "<td width=\"12\" rcol=\"0xBBBBBB\" fcol=\"0x222222\" style=\"center\" alpha=\"80\"><b><FONT COLOR=\"".self::$resourceColours[$resourceName]."\" FACE=\"Arial\">".number_format(round($statistic[$resourceName][$paramName]), 0, '.', ' ')."</FONT></b></td>";
			}
			$out .= "</tr>";
		}				
		
		// закладки
		self::GetBuildingTabs($root, $incoming->getBuildingID(), DE_Language::CreateForCurentLanguage()->getID(), $incoming->getTabName());
						
		$forOut = str_replace('<table>', $out, $doc->dump_mem());
		
		return $forOut;						
	}		
}

?>