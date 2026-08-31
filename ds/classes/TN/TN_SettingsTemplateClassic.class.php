<?php

/**
 * Набор настроек для турнира типа "КЛАССИЧЕСКИЙ"
 *
 */
class TN_SettingsTemplateClassic extends TN_SettingsTemplateBase
{
	public function __construct()
	{
		parent::__construct('CLASSIC_TURNIR');
	}
	
	public function init()
	{
		// МОДИФАЕРЫ
		$this->addModifier('DISABLE_ALLIANCE_TRADING', 	0);
		$this->addModifier('DISABLE_COMPLEX_TRADING', 	0);
		$this->addModifier('DISABLE_INTERRING_TRADING', 0);
		$this->addModifier('DISABLE_RING_TRADING', 		0);
		$this->addModifier('TURN_TIME', 				1); // 0.5 !!!
		$this->addModifier('BATTLE_XP_TYPE',			1);
		
		// ЗАПРЕЩЕННЫЕ ЗДАНИЯ
		$this->addDisabledBuilding(45); // храм созерцателей
		$this->addDisabledBuilding(46); // храм штурмовиков
		$this->addDisabledBuilding(23); // локальный конектор
		$this->addDisabledBuilding(31); // кольцевой конектор
		$this->addDisabledBuilding(20); // межкольцевой конектор
		$this->addDisabledBuilding(14); // посольство
		
		// ШАБЛОН СОТЫ
		$this->setCombTemplate('ui_tpl_1');
	}
}

?>