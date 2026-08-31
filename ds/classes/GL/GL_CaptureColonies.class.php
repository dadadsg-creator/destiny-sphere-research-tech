<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Класс игровой логики захватываемых колоний 
 * 
 *
 */

class GL_CaptureColonies extends GameBase
{
	/**
	 * ID ошибки
	 *
	 * @var string
	 */
	private $errorID;

	/**
	 * Сота откуда идет нападение
	 *
	 * @var DE_Comb
	 */
	private $combFrom;

	function __construct()
	{
		$this->settings = DE_WorldSettings::CreateForCurentWorld();
	}

	public function canCaptureColony(DE_Comb $combFrom)
	{
		$this->combFrom = $combFrom;

		try {
			// Достижение максимума захваченных колоний
			$this->_checkMaxColonies();

			// Наличие колонизационного ценра на соте
			$this->_checkColonizationCenter();

			// Достаточно ли наук
			$this->_checkEhoughSciens();

		}catch(Exception $e){
			$this->errorID = $e->getMessage();
			return false;
		}

		return true;
	}

	private function _checkMaxColonies()
	{
		$maxColonies = (int)$this->settings->get('MAX_CAPTURE_COLONIES');
		assert($maxColonies >= 0);

		// Если нельзя иметь колонии, сразу выходим
		if ($maxColonies == 0) {
			throw new Exception("MAX_CAPTURE_COLONIES_DESIGNED_MESS");
		}

		// Если ограничение есть
		$usersColonies = DS_Comb::GetCombsByUserInstance($this->combFrom->getUserInstance()->getID());

		$usersColoniesCount = 0;
		foreach ($usersColonies as $key => $data)
		{
			if ($data['is_capture'] == 't') {
				$usersColoniesCount ++;
			}
		}

		if($usersColoniesCount >= $maxColonies)
		{
			throw new Exception("MAX_CAPTURE_COLONIES_DESIGNED_MESS");
		}

	}

	private function _checkColonizationCenter()
	{
		if(count(DS_Building::GetAttachedBuildingByCombIDAndBuildingID($this->combFrom->getCombID(), 61)) == 0)
		{
			throw new Exception("COLONIZATION_CENTER_NOT_EXIST_MESS");
		}
	}

	private function _checkEhoughSciens()
	{
		if (
		DS_Science::GetScienceBranchLevel('KADERIUM', $this->combFrom->getUserInstance()->getID()) < $this->settings->get('MIN_KADERIUM_SCIENS_TO_CAPTURE_COLONIES')
		||
		DS_Science::GetScienceBranchLevel('NANO_CRYSTAL', $this->combFrom->getUserInstance()->getID()) < $this->settings->get('MIN_NANO_CRYSTAL_SCIENS_TO_CAPTURE_COLONIES')
		||
		DS_Science::GetScienceBranchLevel('STASIS_ENERGY', $this->combFrom->getUserInstance()->getID()) < $this->settings->get('MIN_STASIS_ENERGY_SCIENS_TO_CAPTURE_COLONIES')
		||
		DS_Science::GetScienceBranchLevel('FOOD', $this->combFrom->getUserInstance()->getID()) < $this->settings->get('MIN_FOOD_SCIENS_TO_CAPTURE_COLONIES')
		||
		DS_Science::GetScienceBranchLevel('PRODIUM', $this->combFrom->getUserInstance()->getID()) < $this->settings->get('MIN_PRODIUM_SCIENS_TO_CAPTURE_COLONIES')
		) {
			throw new Exception("NOT_ENOUGH_SCIENS_TO_CAPTURE_MESS");
		}
	}

	public function getErrorID()
	{
		return $this->errorID;
	}
}

?>