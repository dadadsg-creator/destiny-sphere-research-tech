<?php $CVS_Version = '$Revision$ ; $Date$';

class UA_CalcTravel extends UA_Action {

	/** ID армии которую обсчитавыем */
	private $armyId = 0;

	private $army = null;

	/** Номер кольца на который двигаемся */
	private $targetRing = 0;

	/** Номер комплекса на который двигаемся */
	private $targetComplex = 0;

	/** Номер комплекса на который двигаемся */
	private $targetComb = 0;

	private $combTo = null;

	private $combFrom = null;


	private $mode;

	private $speed;

	/** Домустимые режимы обсчета */
	private $allowModes = array (
	'DEFEND', //Постановка на защиту
	'ATTACK', //Атака
	'TRANSFER', //Передача
	'TRANSFERRES', //Передача ресурсов
	'MOVE_TO_COMB' // трансфер армии
	);

	/** ID колонии */
	private $colonyID = 0;

	/** ID юнита */
	private $unitID = 0;

	private $unit = 0;

	/** Колличество */
	private $amount = 0;

	private $startDelayHour = 0;

	private $startDelayMin = 0;

	function UA_CalcTravel()
	{
		$this->UA_Action();

		assert ( key_exists ( 'params', $GLOBALS ) );
		assert ( is_array ( $GLOBALS['params'] ) );
		$this->params = &$GLOBALS['params'];

	}//END OF CONSTRUCTOR

	function checkLocalParams ()
	{

		$this->armyId = (int)$this->root->get_attribute("id_army");
		$this->targetRing = (int)$this->root->get_attribute("targetRing");
		$this->targetComplex = (int)$this->root->get_attribute("targetComplex");
		$this->targetComb = (int)$this->root->get_attribute("targetComb");
		$this->mode = (string)$this->root->get_attribute("mode");
		$this->colonyID = (int)$this->root->get_attribute("id_colony");
		$this->unitID = (int)$this->root->get_attribute("id_unit");
		$this->amount = (int)$this->root->get_attribute("amount");

		$this->startDelayHour = (int)$this->root->get_attribute("timeh");
		if(empty($this->startDelayHour) || $this->startDelayHour < 0)
		{
			$this->startDelayHour = 0;
		}

		$this->startDelayMin = (int)$this->root->get_attribute("timem");
		if(empty($this->startDelayMin) || $this->startDelayMin < 0)
		{
			$this->startDelayMin = 0;
		}

		$this->speed = (float)$this->root->get_attribute("speed");
		if(empty($this->speed))
		{
			$this->speed = (float)100;
		}

		assert(in_array($this->mode, $this->allowModes));

		assert($this->comb > 0);
		$this->combFrom = DE_Comb::CreateFromID($this->comb);

		if ($this->mode == 'TRANSFER' || $this->mode == 'TRANSFERRES')
		{
			assert($this->colonyID > 0);
			$this->combTo = DE_Comb::CreateFromID($this->colonyID);
		}elseif($this->mode == 'ATTACK' || $this->mode == 'DEFEND' || $this->mode == 'MOVE_TO_COMB'){
			assert($this->world > 0);
			assert($this->targetRing > 0);
			assert($this->targetComplex > 0);
			assert($this->targetComb > 0);
			$this->combTo = DE_Comb::CreateFromCoordinates($this->world, $this->targetRing, $this->targetComplex, $this->targetComb);
		}

		return true;
	}//END OF FUNCTION

	function checkAction ()
	{
		// армия принадлежит игроку и она не в походе
		//Фактически проверка отпадает, поскольку теперь заведомо в список для
		//трансфера не попадает что-то иное, кроме как то что может быть странсферино
		switch ($this->mode)
		{
			case 'TRANSFER':
				break;
		}
		return true;
	}//END OF FUNCTION

	function performAction ()
	{
		//if($_SESSION['profi_game'])
		//{
		$noCellData = true;
		//} else {
		//	$noCellData = false;
		//}

		$msg = false;

		//Проверяем заселена ли сота.
		if ($this->combTo instanceof DE_CombNull)
		{
			$msg = 'CALC_TRAVEL_NOT_EXIST';
		} elseif (($this->combTo instanceof DE_Comb) && ($this->combTo->getUserInstanceID() <= 0) && !$this->combTo->getIsCapture()) {
			$msg = 'CALC_TRAVEL_FREE';
		} elseif ($this->combTo->getCombID() == $this->combFrom->getCombID()) {
			$msg = 'CALC_TRAVEL_ZERO_PATH';
		}

		//Если нет, то актуализируем ресурсы, и выходим без ошибок
		if($msg)
		{
			$messparams = array();
			DE_SystemMessages::AddMessageFromAction(__CLASS__, $msg, $messparams);

			if(!$this->refresh ( 2, "", "", "", $noCellData))
			{
				return false;
			}
			return true;
		}

		$startDate = GB_TypeFactory::CreateConstant('GB_Date', time() + $this->startDelayHour * 3600 + $this->startDelayMin * 60);

		switch ($this->mode) {
			case 'TRANSFERRES':



				$moveSpeed = GB_TypeFactory::CreateMaster('GB_MoveSpeed', (float)(1));

				// Если мы трансферим на захватываемую колонию или с нее, время трансфера свое
				if ($this->combFrom->getIsCapture() || $this->combTo->getIsCapture()) {
					$transferTime =  (float)$this->worldSettings->get('TRANSFER_CAPTURE_COLONIES_TIME');
					assert($transferTime > 0);
					$TotalTravelTime = $transferTime*60*DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME');
					$travelTime = GB_TypeFactory::CreateConstant('GB_TimePeriod', $TotalTravelTime);
				} else {
					$travelTime = GL_TravelTime::Count($this->combFrom, $this->combTo, $moveSpeed);

				}
				$messparams[0] = "{b}'".$this->combTo->getName()."' {/b}";
				$messparams[1] = "{b} ".$travelTime->getDSFormat()."{/b}";

				DE_SystemMessages::AddMessageFromAction(__CLASS__, 'CALC_TRAVEL_RES', $messparams);

				break;

			case 'TRANSFER':

				assert($this->unitID > 0);

				$unit = DE_Unit::CreateFromID($this->unitID);

				$moveSpeed = GB_TypeFactory::CreateMaster('GB_MoveSpeed', (float)(1));
				
				// Если мы трансферим на захватываемую колонию или с нее, время трансфера свое
				if ($this->combFrom->getIsCapture() || $this->combTo->getIsCapture()) {
					$transferTime =  (float)$this->worldSettings->get('TRANSFER_CAPTURE_COLONIES_TIME');
					assert($transferTime > 0);
					$TotalTravelTime = $transferTime*60*DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME');
					$travelTime = GB_TypeFactory::CreateConstant('GB_TimePeriod', $TotalTravelTime);
				} else {
					$travelTime = GL_TravelTime::Count($this->combFrom, $this->combTo, $moveSpeed);
				}

				$upkeepValue = GL_FullUnitsMovementCost::CountTransfer($unit, $this->amount, $this->combFrom, $this->combTo, $moveSpeed);

				$messparams[0] = "{b}'".$unit->getUnitNameLocolized()."' (".$this->amount."){/b}";
				$messparams[1] = "{b}'".$this->combTo->getName()."' {/b}";
				$messparams[2] = "{b} ".$travelTime->getDSFormat()."{/b}";
				$messparams[3] = "{b} ".$upkeepValue->getShortPayList()."{/b}";

				DE_SystemMessages::AddMessageFromAction(__CLASS__, 'CALC_TRAVEL', $messparams);

				break;

			case 'ATTACK':

				assert($this->armyId>0);

				$army = DE_Army::CreateFromID($this->armyId);

				$moveSpeed = GB_TypeFactory::CreateMaster('GB_MoveSpeed', (float)($this->speed / 100));

				$travelTime = GL_TravelTime::Count($this->combFrom, $this->combTo, $moveSpeed, $startDate);
				$upkeepValue = GL_FullUnitsMovementCost::CountAttack($army, $this->combFrom, $this->combTo, $moveSpeed, $startDate);

				$messparams[0] = "{b}'".$army->getName()."'{/b}";
				$messparams[1] = "{b}'".$this->combTo->getName()."' {/b}";
				$messparams[2] = "{b} ".$travelTime->getDSFormat()."{/b}";
				$messparams[3] = "{b} ".$upkeepValue->getShortPayList()."{/b}";

				DE_SystemMessages::AddMessageFromAction(__CLASS__, 'CALC_TRAVEL', $messparams);

				break;

			case 'DEFEND':

				assert($this->armyId>0);

				$army = DE_Army::CreateFromID($this->armyId);

				$moveSpeed = GB_TypeFactory::CreateMaster('GB_MoveSpeed', (float)($this->speed / 100));

				$travelTime = GL_TravelTime::Count($this->combFrom, $this->combTo, $moveSpeed,$startDate);
				$upkeepValue = GL_FullUnitsMovementCost::CountDefend($army, $this->combFrom, $this->combTo, $moveSpeed, $startDate);

				$messparams[0] = "{b}'".$army->getName()."'{/b}";
				$messparams[1] = "{b}'".$this->combTo->getName()."' {/b}";
				$messparams[2] = "{b} ".$travelTime->getDSFormat()."{/b}";
				$messparams[3] = "{b} ".$upkeepValue->getShortPayList()."{/b}";

				DE_SystemMessages::AddMessageFromAction(__CLASS__, 'CALC_TRAVEL', $messparams);

				break;

			case 'MOVE_TO_COMB':

				assert($this->armyId>0);

				$army = DE_Army::CreateFromID($this->armyId);

				$moveSpeed = GB_TypeFactory::CreateMaster('GB_MoveSpeed', (float)($this->speed / 100));
				$startDate = GB_TypeFactory::CreateConstant('GB_Date', time() + $this->startDelayHour*3600 + $this->startDelayMin*60);

				// Если мы трансферим на захватываемую колонию или с нее, время трансфера свое
				if ($this->combFrom->getIsCapture() || $this->combTo->getIsCapture()) {
					$transferTime =  (float)$this->worldSettings->get('TRANSFER_CAPTURE_COLONIES_TIME');
					assert($transferTime > 0);
					$TotalTravelTime = $transferTime*60*DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME');
					$travelTime = GB_TypeFactory::CreateConstant('GB_TimePeriod', $TotalTravelTime);
				} else {
					$travelTime = GL_TravelTime::Count($this->combFrom, $this->combTo, $moveSpeed, $startDate);
				}
				
				$upkeepValue = GL_FullUnitsMovementCost::CountPath($army, $this->combFrom, $this->combTo, $moveSpeed, $startDate, 'ATTACK');

				$messparams[0] = "{b}'".$army->getName()."'{/b}";
				$messparams[1] = "{b}'".$this->combTo->getName()."' {/b}";
				$messparams[2] = "{b} ".$travelTime->getDSFormat()."{/b}";
				$messparams[3] = "{b} ".$upkeepValue->getShortPayList()."{/b}";

				DE_SystemMessages::AddMessageFromAction(__CLASS__, 'CALC_TRAVEL', $messparams);

				break;
		}//END OF SWITCH


		if ( !$this->refresh ( 2, "", "", "", $noCellData ) )
		{
			return false;
		}

		return true;
	}//END OF FUNCTION

	private function _formResourceList()
	{
	}

}//END OF CLASS


?>