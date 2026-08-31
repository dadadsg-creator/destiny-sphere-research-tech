<?php $CVS_Version = '$Revision: 1.2 $ ; $Date: 2010/04/19 09:26:03 $';

// Меню реинкарнации юнитов(то биш их покупки)

class PN_WarCalculator extends UA_GetBuildMenu {

	private $docXML;

	private $rootXML;
	
	function __construct() {
		UA_GetBuildMenu::UA_GetBuildMenu ();
	}

	function getPanel ($newUnit = "") {

		$xml = '<?xml version="1.0" encoding="utf-8"'."?"."><armycalculator />";
		$this->docXML = domxml_open_mem ($xml);
		$this->rootXML = $this->docXML->document_element();
		
		$koeff = "";
		$colon = "";
		
		foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
		{
			$koeff .= $colon.(DE_Resource::CreateFromName('PRODIUM')->getExchangeRate()/DE_Resource::CreateFromName($resourceName)->getExchangeRate());
			$colon = ':';
		}
		
		$this->rootXML->set_attribute ( "koeff", $koeff );

		// Выбираем всех юнитов
		$units = DS_Unit::GetAllUnitsID();	

		// Проходимся по каждому юниту
		foreach ($units as $untKey => $unitData)
		{
			$unit = DE_Unit::CreateFromID((int)$unitData['id_unit']);
			
			// Выбираем вес юнита
			$unitWeight = $unit->getUnitPower()/10;
			
			// Выбираем стоимость постойки юнита
			$unitAttributes = $unit->getAttributes();
			
			$price = "";
			
			foreach(GB_ResourcesList::$allResources as $k=>$resourceName)
			{
				if(!$unitAttributes->haveAttribute('PRODUCTION_COSTS', $resourceName))
				{
					$prodValue = 0;
				} else {
					$prodValue = $unitAttributes->getAttribute('PRODUCTION_COSTS', $resourceName);
				}
				
				$price .= substr($resourceName, 0, 2).':'.$prodValue.';';
			}
			
			$ch = $this->rootXML->new_child ( "unit" );
			$ch->set_attribute ( "id", $unit->getID() );
			$ch->set_attribute ( "unitname", $unit->getName() );
			$ch->set_attribute ( "price", $price );
			$ch->set_attribute ( "price", $price );
			$ch->set_attribute ( "PW", $unitWeight );
		}

		// закладки
		$this->getBuildingTabs ( $this->rootXML, $this->bid, $this->params["lang"], $this->tab );

		SU_OutputHandler::Singleton()->addString( $this->docXML->dump_mem() , 'xmldata');
		return true;
	}
}
?>