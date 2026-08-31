<?php

/**
 * Набор настроек для турнира типа "КВЕСТОВЫЙ"
 *
 */
class TN_SettingsTemplateQuest extends TN_SettingsTemplateBase
{
	public function __construct()
	{
		parent::__construct('QUEST_TURNIR');
	}
	
	public function init()
	{
		// МОДИФАЕРЫ
		$this->addModifier('DISABLE_ALLIANCE_TRADING', 	0  );
		$this->addModifier('DISABLE_COMPLEX_TRADING', 	0  );
		$this->addModifier('DISABLE_INTERRING_TRADING', 0  );
		$this->addModifier('DISABLE_RING_TRADING', 		0  );
		$this->addModifier('TURN_TIME', 				12 );
		$this->addModifier('MAX_COLONIES', 				0  );
		$this->addModifier('SIMILTANEOUS_BUILDS_ADDON',-2  );
		$this->addModifier('MIN_TRAVEL_TIME',			0.83 ); // 10 минут
		$this->addModifier('MAX_TRAVEL_TIME',			0.83 ); // 10 минут
		$this->addModifier('BATTLE_XP_TYPE', 			2);
		
		// ЗАПРЕЩЕННЫЕ ЗДАНИЯ
		$this->addDisabledBuilding(45); // храм созерцателей
		$this->addDisabledBuilding(46); // храм штурмовиков
		$this->addDisabledBuilding(23); // локальный конектор
		$this->addDisabledBuilding(31); // кольцевой конектор
		$this->addDisabledBuilding(20); // межкольцевой конектор
		$this->addDisabledBuilding(14); // посольство
		
		// ШАБЛОН СОТЫ
		$this->setCombTemplate('ui_tpl_2');
	}
}

?>