<?php

/**
 * Базовый класс для классов-шаблонов настроек турнира
 * 
 * задаются:
 * 		- модифаеры
 * 		- запрещенные к строительству здания
 * 		- файл с шаблоном соты
 *
 */
class TN_SettingsTemplateBase
{
	private $modifierList;
	
	private $disabledBuildings;
	
	private $templateName;
	
	private $combTemplate;
	
	private $templateID;
	
	public function __construct($templateName)
	{
		$this->templateName = $templateName;
		$this->init();
	}
	
	/**
	 * Метод должен быть определен во всех шаблонах
	 *
	 */
	public function init()
	{
		$this->templateID = TN_Search::GetTemplateIDByName($this->templateName);
		
		$modifiers = TN_Search::GetTemplateModifiers($this->templateID);
		foreach ($modifiers as $k => $m)
		{
			$this->addModifier($m['modifiername'], $m['value']);
		}
		
		$buildings = TN_Search::GetTemplateDisabledBuildings($this->templateID);
		foreach ($buildings as $k => $b)
		{
			$this->addDisabledBuilding((int)$b['id_building']);
		}
	}
	
	public function getName()
	{
		return $this->templateName;
	}
	
	public function getModifierList()
	{
		assert(is_array($this->modifierList));
		return $this->modifierList;
	}
	
	public function addModifier($modifierName, $value)
	{
		assert(is_numeric($value));
		assert(!empty($modifierName));
		
		DE_WorldSettings::CreateForCurentWorld()->get($modifierName);
		
		$this->modifierList[$modifierName] = $value;
	}
	
	public function addDisabledBuilding($buildingID)
	{
		assert(is_int($buildingID) && $buildingID > 0);
		
		$this->disabledBuildings[] = $buildingID;
	}
	
	public function getDisabledBuildings()
	{
		return $this->disabledBuildings;
	}
	
	public function setCombTemplate($templateName)
	{
		$this->combTemplate = $templateName;
	}
	
	public function getCombTemplate()
	{
		return $this->combTemplate;
	}
}

?>