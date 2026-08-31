<?php $CVS_Version = '$Revision$ ; $Date$';

class GP_PatchComb extends GP_Patch 
{
	private $combData;
	
	static public $combPatches = array(
		'GP_CombParalelBuilds', //Проверяем колличество паралельных строек
		'GP_CombHumans', //Проверяем рабочих, нацников, архов
		'GP_CombArtefactSlots', //Проверяем максимальное колличество слотов на соте для артов
		'GP_CombTurnProduction', //Проверка производства зап турн
		'GP_CombBonus', //Артефакты юонусы
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