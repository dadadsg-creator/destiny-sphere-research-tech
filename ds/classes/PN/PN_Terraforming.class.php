<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2009/05/27 12:50:36 $';

class PN_Terraforming extends UA_GetBuildMenu
{

	function __construct()
	{
		UA_GetBuildMenu::UA_GetBuildMenu ();
	}

	function getPanel ()
	{
		global $params;
		global $common;
		
		//Извлекаем список того во что можно реформирвоать данную ячейку даному юзверю
		$allowFormingTypes = DS_Terraforming::GetAllowFormingsByCellIDAndUIID($this->id_cell, $_SESSION["uiid"]);
		
		$bindEventID = DS_Terraforming::HaveTerraformingOnCell($this->id_cell);
		
		if($bindEventID == 0)
		{						
			$xml = $this->_formXMLForFirstMenu($allowFormingTypes);
		}else{
			$xml = $this->_formXMLForSecondMenu($bindEventID);
		}
		
		SU_OutputHandler::Singleton()->addString($xml, 'xmldata');
		
		return true;
	}

	private function _formXMLForSecondMenu($bindEventID)
	{		
		
		global $params;
						
		$eventQueue = DE_EventQueue::CreateFromID($bindEventID);
		$eventAttributes = $eventQueue->getAttributes();
				
		
		$xml = '<?xml version="1.0" encoding="utf-8"'."?".">\n<terraforming />";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();


		$v = DS_Terraforming::GetTerraformingInfoByID((int)$eventAttributes->getAttribute('ID_Terraforming'));	
		
		$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);
		$terraformingAttributes = DE_TerraformingAttributes::CreateFromTerraformingID((int)$eventAttributes->getAttribute('ID_Terraforming'));
			
		$ch = $root->new_child("terra");
									
		$groundTypeInfo = DS_Terraforming::GetGroudTypeByID($v['id_groundtypeto']);			
		$ch->set_attribute("type", $groundTypeInfo['groundname']);
							
		$constructionTime = $terraformingAttributes->getAttribute('PRODUCTION_COSTS', 'CONSTRUCTION_TIME') * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;	
		$ch->set_attribute("time", GB_TypeFactory::CreateBase('GB_TimePeriod', $constructionTime)->getDSFormat());			
						
		// Разрешение отмены терраформинга
		$ch->set_attribute ( "s", "2" );					

		$attributeNames = $terraformingAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');						
		foreach ($attributeNames as $k => $resourceName)
		{				
			if (!$combAttributes->haveAttribute('CURRENT_AMOUNT', $resourceName))
			{
				continue;
			}
			
			$needResourcesList[] = substr($resourceName, 0, 1).$terraformingAttributes->getAttribute('PRODUCTION_COSTS', $resourceName);
		}
		
		$ch->set_attribute("price", implode(":",$needResourcesList));						
			
		$delta = $eventQueue->getEndDate()->value - time();
		
		if($delta < 0)
		{
			$delta = 0;
		}

		$timePeriod = GB_TypeFactory::CreateBase('GB_TimePeriod', $delta);
				
		$ch->set_attribute("action", $timePeriod->getDSFormat());				
								
		$this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab);
		
		return $doc->dump_mem();
	}
	
	private function _formXMLForFirstMenu($res)
	{		
		
		global $params;						
		
		$xml = '<?xml version="1.0" encoding="utf-8"'."?".">\n<terraforming />";
		$doc = domxml_open_mem ( $xml );
		$root = $doc->document_element ();

		$combAttributes = DE_CombAttributes::CreateFromCombID((int)$this->comb);	

		foreach($res as $k=>$v)
		{
			$disabledBuilding = false;
			$needResourcesList = array();
			
			$terraformingAttributes = DE_TerraformingAttributes::CreateFromTerraformingID((int)$v['id_terraforming']);
			
			$ch = $root->new_child("terra");
									
			$groundTypeInfo = DS_Terraforming::GetGroudTypeByID($v['id_groundtypeto']);			
			$ch->set_attribute("type", $groundTypeInfo['groundname']);
							
			$constructionTime = $terraformingAttributes->getAttribute('PRODUCTION_COSTS', 'CONSTRUCTION_TIME') * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;	
			$ch->set_attribute("time", GB_TypeFactory::CreateBase('GB_TimePeriod', $constructionTime)->getDSFormat());	
			
			// Разрешение терраформинга
			$ch->set_attribute ( "s", "1" );		
						
			$attributeNames = $terraformingAttributes->getAttributeNamesByTypeName('PRODUCTION_COSTS');						
			foreach ($attributeNames as $k => $resourceName)
			{				
				if (!$combAttributes->haveAttribute('CURRENT_AMOUNT', $resourceName))
				{
					continue;
				}
				
				$needResourcesList[] = substr($resourceName, 0, 1).$terraformingAttributes->getAttribute('PRODUCTION_COSTS', $resourceName);
							
				if($terraformingAttributes->getAttribute('PRODUCTION_COSTS', $resourceName) > $combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName))
				{					
					$ch->set_attribute ( "s", "0" );					
				}
			}			
			
			$ch->set_attribute("price", implode(":",$needResourcesList));				
		}				
		
		$this->getBuildingTabs ( $root, $this->bid, $params["lang"], $this->tab);
		
		return $doc->dump_mem();
	}
}
?>