<?php $CVS_Version = '$Revision: 1.6 $ ; $Date: 2009/10/08 10:12:31 $';

/**
 * Меню тренировки шпионских юнитов
 *
 */
class PN_SpyTrainUnit extends UA_GetBuildMenu
{
	/**
	 * Enter description here...
	 *
	 * @var TI_SpyTrainUnit
	 */
	private $transportInput;
	
	function __construct()
	{
		parent::UA_GetBuildMenu();
	}

	function getPanel()
	{
		$this->transportInput = new TI_SpyTrainUnit((int)$this->bid, $this->tab);
		
		// список шпионских юнитов
		$spyUnits = DS_SpyMenu::GetSpyUnits();
		
		$comb = DE_Comb::CreateFromID($this->comb);
		$combAttributes = $comb->getAttributes();
		
		
		// максимум юнитов - общий, поэтому смотрим сколько всего есть "шпионов"
		$curAmountTotal = 0;
		foreach ($spyUnits as $k => $unit) 
		{
			// данные о юните
			$unitID = (int)$unit['id_unit'];
			$unit = DE_Unit::CreateFromID($unitID);
			$trainingAmount = DS_UnitTrain::GetCountUnitInProduction($unit, $comb);
			$curAmountTotal += $combAttributes->getAttribute('CURRENT_AMOUNT', $unit->getName()) + $combAttributes->getAttribute('AWAY_AMOUNT', $unit->getName());
			$curAmountTotal += $trainingAmount;
		}
		
		foreach ($spyUnits as $k => $unit) 
		{
			// данные о юните
			$unitID = (int)$unit['id_unit'];
			$unit = DE_Unit::CreateFromID($unitID);
			$this->transportInput->addUnit($unit->getName());
			
			// кол-во уже тренирующихся
			$trainingAmount = DS_UnitTrain::GetCountUnitInProduction($unit, $comb);
			$this->transportInput->addParam($unit->getName(), 'AMOUNT_TRAINING', $trainingAmount);
			
			// максимальное кол-во юнитов на соте
			$curAmount = 0;
			$maxAmount = GL_SpyMaxCountZondsOnComb::Get($comb, $unit);
			$curAmount += $combAttributes->getAttribute('CURRENT_AMOUNT', $unit->getName()) + $combAttributes->getAttribute('AWAY_AMOUNT', $unit->getName());
	
			// стоимость производства
			$productionCost = $unit->getAttributes()->getProductionCost();
			$productionCostArr = array();
			$maxAmountTemp = array(0 => $maxAmount - $curAmountTotal);
			// Если текущее значене уже больше допкустимого
			if ($curAmountTotal > $maxAmount) {
				$maxAmountTemp[0] = 0;
			}
			
			foreach ($productionCost as $attr => $val)
			{
				if($attr == 'CONSTRUCTION_TIME')
				{
					// время в секундах
					$val = $val * DE_WorldSettings::CreateForCurentWorld()->get('TURN_TIME') * 60;
				}
								
				$productionCostArr[] = substr($attr, 0, 1).":".$val;
			}
			
			// сколько военного опыта стоит
			if($unit->getName() == 'SPY_ZOND_EXPLORER')
			{
				$productionCostArr[] = 'WR:'.(float)DE_WorldSettings::CreateForCurentWorld()->get('SPY_ZOND_EXPLORER_BATTLE_XP_COST');
			} else {
				$productionCostArr[] = 'WR:0';
			}
			
			$this->transportInput->addParam($unit->getName(), 'PRODUCTION_COST', implode(',', $productionCostArr));
			
			$maxAmountTemp[] = (int)GL_SpyCountZondPrice::GetMaxByResource($comb, $unit);
			$maxAmount = min($maxAmountTemp);

			$awail = 1;
			
			if ($maxAmount == 0) {
				// Доступность тренировки хотябы одного занда
				$awail = 0;
				// Тут ставим 1, чтобы можно было посмотреть, сколько ресов не хватает для тренировки одного зонда
				$maxAmount = 1;
			}

			$this->transportInput->addParam($unit->getName(), 'MAX_AMOUNT', $maxAmount);
			$this->transportInput->addParam($unit->getName(), 'CURRENT_AMOUNT', $curAmount);			
			$this->transportInput->addParam($unit->getName(), 'PRICE_DELTA', 1.0 + (float)DE_WorldSettings::CreateForCurentWorld()->get($unit->getName().'_PRODUCTION_COST_INCREASE'));
			$this->transportInput->addParam($unit->getName(), 'AWAIL', $awail);			
		}
		
		$this->transportInput->setBattleXP(DE_BattleXP::CreateFromID($this->uiid)->getXP());
		
		SU_OutputHandler::Singleton()->addString(TL_SpyTrainUnit::FormMenu($this->transportInput), 'xmldata');
		
		return true;
	}
	
	
}

?>