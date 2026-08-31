<?php $CVS_Version = '$Revision: 1.13 $ ; $Date: 2009/03/17 11:06:10 $';

// закладка трансфера ресурсов, артефактов и юнитов между своими сотами

class PN_TransferResource extends UA_GetBuildMenu
{

	/**
	 * Enter description here...
	 *
	 * @var TI_TransferResource
	 */
	private $transportInput;

	/**
	 * Enter description here...
	 *
	 * @var DE_Comb
	 */
	private $combObject;

	function __construct()
	{
		UA_GetBuildMenu::UA_GetBuildMenu ();
	}

	function getPanel()
	{
		$this->transportInput = new TI_TransferResource((int)$this->bid, $this->tab);

		$this->combObject = DE_Comb::CreateFromID((int)$this->comb);

		// РЕСУРСЫ
		$this->_getResources();

		// ЮНИТЫ
		$this->_getUnits();
			
		// АРТЕФАКТЫ
		$this->_getArtefacts();

		// СОТЫ ИГРОКА
		$this->_getCombs();

		SU_OutputHandler::Singleton()->addString(TL_TransferResource::FormMenu($this->transportInput), 'xmldata');

		return true;
	}

	private function _getResources()
	{
		$combAttributes = DE_CombAttributes::CreateFromCombID($this->combObject->getID());
		$st = ST_Attributes::Create();

		foreach (GB_ResourcesList::$allCombResources as $k => $resourceName)
		{						
			$id = (int)$st->getAttributeIDByAttributeName($resourceName);
			$amount = (float)$combAttributes->getAttribute('CURRENT_AMOUNT', $resourceName);
			if ($resourceName == 'SCIENTIST') {
				$addScientists = $this->worldSettings->get('COUNT_ADD_CSIENTISTS_CONSTRUCT_CENTER');
				assert($addScientists >= 0);
				if ($addScientists != 0) {
					$amount = (float)max(0, ($amount - $addScientists));
				}
			}
			
			if ($resourceName == 'ARCHEOLOGIST') {
				$addArcheologists = $this->worldSettings->get('COUNT_ADD_ARCHEOLOGISTS_CONSTRUCT_INSTITUT');
				assert($addArcheologists >= 0);
				if ($addArcheologists != 0) {
					$amount = (float)max(0, ($amount - $addArcheologists));
				}
			}
			
			$this->transportInput->setResource($id, $resourceName, $amount);
		}
	}

	private function _getUnits()
	{
		if(!GL_CanMoveUnitsFromComb::IsCan($this->combObject))
		{
			return;
		}

		$homeArmy = DE_Army::CreateHomeArmyByCombID($this->combObject->getID());
		$unitsCollection = $homeArmy->getUnitsGroupCollection(true);
		$unitsCollection->resetNext();

		while($unitGroup = $unitsCollection->next())
		{
			if ($unitGroup->getUnitID() == 99 || $unitGroup->getUnitID() == 98 || $unitGroup->getUnitID() == 93) {
				continue;
			}
			$pos = $unitsCollection->getCurrentPosition();
			$this->transportInput->setUnit($unitGroup);
			$unitsCollection->setPointerToPosition($pos);
		}
	}

	private function _getArtefacts()
	{
		if(isset($_SESSION['profi_game']) && $_SESSION['profi_game'] == true)
		{
			$artefacts = DS_Artefact::GetArtefactsOnCombStoreWithGrouping($this->combObject);
		} else {
			$artefacts = DS_Artefact::GetArtefactsOnCombStoreWithoutGrouping($this->combObject);
		}

		
		$sql = sprintf ( "select ltl.text
						from ds_localizedtextlanguage ltl,
						ds_systemmessage sm
						where ltl.id_localizedtext = sm.id_localizedtext
						and ltl.id_language = %d
						and sm.messagename = 'ARTEFACT_DEATH_TIME'", $this->langId );

		$loc_death_text_res = $this->common->getResultArray ( "system_message_art_finished_id_language_".$this->langId, $sql, "", "SHORT_PREFIX", "GLOBAL_CACHE" );
		$loc_death_text = $loc_death_text_res[0]['text'];

		foreach ($artefacts as $k => $art)
		{
			$b = DS_Artefact::GetArtefactAtrributes((int)$art['id_artefact']);
			if(is_array($b) && count($b) > 0)
			{
				$b = $b[0];

				if($b['typename'] == 'BONUS_RELATIVE')
				{
					$type = '$';
					$b['value'] = $b['value'] * 100;
				} else {
					$type = '';
				}

				$bonus = array('NAME' => $b['attributename'], 'VALUE' => $b['value'], 'TYPE' => $type);
			} else {
				$bonus = array('NAME' => '', 'VALUE' => '', 'TYPE' => '');
			}
			list($_prefix, $_scopename, $_type) = explode(':', $art['name']);
			$death_message = "";
			if(preg_match("/^IGROMIR_/", $_type)) {
				$_type = preg_replace(array('/COMB_/', '/COMBCOMPLEX_/', '/ALLIANCE_/', '/WAR_/'), array('', '', '', ''), $_type);
			}
			$lt = "";
			if($art['immortal'] == 'f') {
				list($d,$m,$y) = explode('.', $art['birth_time']);
				$lt = mktime(0, 0, 0, $m, $d, $y);
				list($d,$m,$y) = explode('.', $art['death_time']);
				$lt .= '|'.mktime(0, 0, 0, $m, $d, $y);
				$_type .= "_".$lt;
				$death_message = sprintf(str_replace('%', '%s', $loc_death_text), $art['death_time']);
			}

			$art['name'] = $_prefix.":".$_scopename.":".$_type;
				
			$this->transportInput->setArtefact((int)$art['id_artefact'], $art['name'], (int)$art['amount'], $bonus, $death_message, $lt);
		}
	}

	private function _getCombs()
	{
		$userCombsCollection = DC_UserInstanceCombs::CreateFromUserInstanceID($this->uiid);
		$userCombsCollection->resetNext();

		while($userComb = $userCombsCollection->next())
		{
			if($userComb->getID() == $this->combObject->getID())
			{
				continue;
			}

			$this->transportInput->setCombToTransfer($userComb);
		}
	}
}

?>