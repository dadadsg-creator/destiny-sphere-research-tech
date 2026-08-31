<?php $CVS_Version = '$Revision$ ; $Date$';


interface DE_UnitsGroupInterface
{	
	/**
	 * Возвращает объект армии
	 *
	 */
    public function getArmy();       	
    public function getAmount();
    public function getGroupPower($calcUnitsWithNullDamage = true);
    public function getUnit();
    public function getGroupWarPower();
    public function resetGroupWarPower();
    public function setLoses($loses);
    public function getLoses();
}

?>