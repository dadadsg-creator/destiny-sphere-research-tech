<?php

/**
 * Получение имени менюшки по-умолчанию
 *
 */
class GL_DefaultBuildingTab
{
	static public function Get($buildingID)
	{
		assert(is_int($buildingID) && $buildingID > 0);
		
		$tabs = DS_Interface::GetBuildingTabs((int)$_SESSION['lang'], $buildingID);
		
		$validTab = '';
		
		foreach ($tabs as $k => $tab)
		{
			if($tab['isdefault'] == 't' && (empty($tab['affected_by_modifier']) || DE_WorldSettings::CreateForCurentWorld()->get($tab['affected_by_modifier'])))
			{
				return $tab['specialfunctionname'];
			}
			
			// первая попавшаяся менюшка
			if(empty($tab['affected_by_modifier']) || DE_WorldSettings::CreateForCurentWorld()->get($tab['affected_by_modifier']))
			{
				$validTab = $tab['specialfunctionname'];
			}
		}
		
		return $validTab;
	}
}


?>