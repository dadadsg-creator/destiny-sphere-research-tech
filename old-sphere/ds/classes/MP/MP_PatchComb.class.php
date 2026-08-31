<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс патчит соту игрока
 *
 */
class MP_PatchComb extends MP_Patch 
{
	private $combData;
	
	static public $combPatches = array(
		'MP_CombParalelBuilds', //Проверяем колличество паралельных строек
		'MP_CombHumans', //Проверяем рабочих, нацников, архов
		'MP_CombUpkeep', //Проверяем рабочих, нацников, архов
		'MP_CombArtefactSlots', //Проверяем максимальное колличество слотов на соте для артов
		'MP_CombTurnProduction', //Проверка производства зап турн
		'MP_CombBonus', //Артефакты юонусы,
		'MP_CombBindEvents', //Убиваем ивенты пикрепленые к соте, оторых на саомо деле нет
		'MP_CombSpyZonds', //Проверяем кол-во шпионских зондов
		//'MP_CheckPeace',//Удаление тех перемирий, которые расторгли и наступило время окончания
		//'MP_RamblerStat'//Удаление тех перемирий, которые расторгли и наступило время окончания

		);

	public function __construct($combData)
	{
		assert(is_array($combData) && count($combData) > 0);
		$this->combData = $combData;
	}
	
	public function patch()
	{
		self::_out(" 	+ Start patch comb ".$this->combData['combname']);
		
		foreach(self::$combPatches as $k=>$patchName)
		{
			$patch = new $patchName($this->combData);
			$patch->patch();
		}		
	}
}
?>