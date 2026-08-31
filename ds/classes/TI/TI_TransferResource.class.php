<?php $CVS_Version = '$Revision: 1.3 $ ; $Date: 2008/12/09 11:39:24 $';

class TI_TransferResource extends TI_BaseMenu 
{
	public $resources = array();
	
	public $units = array();
	
	public $artefacts = array();
	
	public $combsToTransfer = array();
	
	function __construct($buildingID, $tabName)
	{
		parent::__construct($buildingID, $tabName);
	}
	
	public function setResource($id, $name, $amount)
	{
		assert(is_int($id) && $id > 0);
		assert(!empty($name) && in_array($name, GB_ResourcesList::$allCombResources));
		assert(is_float($amount) && $amount >= 0);
		
		$this->resources[] = array('ID' => $id, 'NAME' => $name, 'AMOUNT' => $amount);
	}
	
	public function setUnit(DE_UnitsGroup $unitGroup)
	{
		$amount = $unitGroup->getAmount();
		$unit = $unitGroup->getUnit();
			
		$this->units[] = array('ID' => $unit->getID(), 'NAME' => $unit->getName(), 'AMOUNT' => $amount);
	}
	
	public function setArtefact($id, $name, $amount, array $bonus, $death_message = "", $lifetime = "")
	{
		assert(is_int($id) && $id > 0);
		//assert(preg_match('/^[1-9a-zA-Z_\:]+$/', $name));
		assert(is_int($amount) && $amount >= 0);
		assert(key_exists('NAME', $bonus));
		assert(key_exists('VALUE', $bonus));
		assert(key_exists('TYPE', $bonus));
		assert(empty($bonus['NAME']) || preg_match('/^[\w]+$/', $bonus['NAME']));
		assert(empty($bonus['VALUE']) || is_numeric($bonus['VALUE']));
		assert(empty($bonus['TYPE']) || ($bonus['TYPE'] == '' || $bonus['TYPE'] == '$'));
			
		$bonusValue = '';
		if(!empty($bonus['VALUE']))
		{
			$bonusValue = '#'.$bonus['VALUE'].$bonus['TYPE'];
		}
		
		$this->artefacts[] = array('ID' => $id, 'NAME' => $name, 'AMOUNT' => $amount, 'BONUS_NAME' => $bonus['NAME'], 'BONUS_VALUE' => $bonusValue, 'DEATH_MESSAGE' => $death_message, 'LIFETIME' => $lifetime);
	}
	
	public function setCombToTransfer(DE_Comb $comb)
	{
		$this->combsToTransfer[] = array('ID' => $comb->getCombID(), 'NAME' => $comb->getName(), 'COORDS' => $comb->getFormatedAddress());
	}
}

?>