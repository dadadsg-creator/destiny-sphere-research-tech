<?php

/**
 * Применение к миру заданного шаблона настроек
 *
 */
class TN_SettingsManager
{
	/**
	 * Шаблон настроек
	 *
	 * @var TN_SettingsTemplateBase
	 */
	static private $template;
	
	static public function ApplyTemplate(TN_SettingsTemplateBase $template)
	{
		self::$template = $template;
		
		self::_resetModifiers();
		
		self::_clearDisabledBuildings();
		
		self::_applyModifiers();
		
		self::_applyDisabledBuildings();
		
		self::_setCombTemplate();
	}
	
	static private function _resetModifiers()
	{
		TN_Search::ResetGameModifiersToDefault();
	}
	
	static private function _applyModifiers()
	{
		$modifiers = self::$template->getModifierList();
		assert(is_array($modifiers));
		
		foreach ($modifiers as $modifier => $value)
		{
			TN_Search::SetModifier($modifier, $value);
		}
	}
	
	static private function _clearDisabledBuildings()
	{
		TN_Search::DeleteDisabledBuildings();
	}
	
	static private function _applyDisabledBuildings()
	{
		$buildings = self::$template->getDisabledBuildings();
		
		foreach ($buildings as $k => $buildingID)
		{
			TN_Search::AddDisabledBuilding($buildingID);
		}
	}
	
	static private function _setCombTemplate()
	{
		TN_Search::UpdateCombTemplate(self::$template->getCombTemplate());
	}
}

?>