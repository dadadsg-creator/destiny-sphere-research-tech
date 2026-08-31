<?php $CVS_Version = '$Revision: 1.3 $ ; $Date: 2009/07/06 12:25:26 $';

/**
 * Меню отправки зондов
 *
 */
class PN_SpyAction extends UA_GetBuildMenu
{
	/**
	 * Enter description here...
	 *
	 * @var TI_SpyAction
	 */
	private $transportInput;

	/**
	 * Enter description here...
	 *
	 * @var DE_Comb
	 */
	private $combObject;
		
	/**
	 * Enter description here...
	 *
	 * @var DE_Unit
	 */
	private $unit;
	
	function __construct()
	{
		parent::UA_GetBuildMenu();
	}

	function getPanel()
	{
		$this->transportInput = new TI_SpyAction((int)$this->bid, $this->tab);
		$this->combObject = DE_Comb::CreateFromID($this->comb);
		$this->worldSettings = DE_WorldSettings::CreateForCurentWorld();
		
		// значения модифаеров
		$this->_getValuesFromModifiers();
		
		// список действий
		$this->_getActions();
		
		// список шпионских юнитов
		$spyUnits = DS_SpyMenu::GetSpyUnits();
		
		foreach ($spyUnits as $k => $u)
		{		
			$this->unit = DE_Unit::CreateFromID((int)$u['id_unit']);
			
			// данные о юните
			$this->transportInput->setZondUnit($this->unit);
			
			// текущие кол-ва зондов
			$this->_getZondsCount();
			
			// данные о группах
			$this->_getZondGroups();
		}

		// Текущее кольцо
		$this->transportInput->setCurrentRing((int)$this->combObject->getCombRing()->getNumber());
		
		SU_OutputHandler::Singleton()->addString(TL_SpyAction::FormMenu($this->transportInput), 'xmldata');
		
		return true;
	}
	
	private function _getValuesFromModifiers()
	{
		$this->transportInput->setMaxDefenceGroups((int)$this->worldSettings->get('SPY_MAX_DEFENCE_GROUPS'));
		$this->transportInput->setMaxDefenceGroupSize((int)$this->worldSettings->get('SPY_MAX_ZONDS_IN_DEFENCE_GROUP'));
		$this->transportInput->setMaxDefenceTime((int)$this->worldSettings->get('SPY_MAX_DEFENCE_TIME'));
		$this->transportInput->setMaxResearchGroups((int)$this->worldSettings->get('SPY_MAX_RESEARCH_GROUPS'));
		$this->transportInput->setMaxResearchGroupSize((int)$this->worldSettings->get('SPY_MAX_ZONDS_IN_RESEARCH_GROUPS'));
		$this->transportInput->setMaxSpeed((int)$this->worldSettings->get('SPY_RESEARCH_GROUP_SPEED_ACLERATION_TO'));
		$this->transportInput->setTurnTime((float)$this->worldSettings->get('TURN_TIME') * 60);
	}
	
	private function _getActions()
	{
		$this->transportInput->addAction(DE_SpyAction::CreateFromName('RESEARCH'));
		$this->transportInput->addAction(DE_SpyAction::CreateFromName('DEFEND'));
	}
	
	private function _getZondsCount()
	{
		$freeAmount = (int)$this->combObject->getAttributes()->getAttribute('CURRENT_AMOUNT', $this->unit->getName());
		$awayAmount = (int)$this->combObject->getAttributes()->getAttribute('AWAY_AMOUNT', $this->unit->getName());
		$inResearch =  DS_SpyMenu::GetCountZondsInAction($this->combObject, $this->unit, DE_SpyAction::CreateFromName('RESEARCH'));
		$inResearch += DS_SpyMenu::GetCountZondsInAction($this->combObject, $this->unit, DE_SpyAction::CreateFromName('MOVE_BACK'));
		$inDefence  =  DS_SpyMenu::GetCountZondsInAction($this->combObject, $this->unit, DE_SpyAction::CreateFromName('DEFEND'));
		$repairAmount= DS_SpyMenu::GetCountZondsInAction($this->combObject, $this->unit, DE_SpyAction::CreateFromName('REPAIR'));
				
		$this->transportInput->setFreeZondsCount($freeAmount);
		$this->transportInput->setInDefenceZondsCount($inDefence);
		$this->transportInput->setInResearchZondsCount($inResearch);
		$this->transportInput->setRepairZondsCount($repairAmount);
	}
	
	private function _getZondGroups()
	{
		$groups = DS_SpyAction::GetAllSpyGroups($this->combObject, $this->unit);
		
		foreach ($groups as $k => $groupData)
		{
			$this->transportInput->addGroup(DE_SpyZondGroup::CreateFromID((int)$groupData['id_spy_group_action']));
		}
	}
}

?>