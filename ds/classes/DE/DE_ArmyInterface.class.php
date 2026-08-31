<?php $CVS_Version = '$Revision$ ; $Date$';
/**
 * $Author$
 * $Date$
 * $Revision$
 * 
 *
 */
interface DE_ArmyInterface 
{    	
    public function getUnitsGroupCollection();    
    public function getName();    
    public function repair();
    public function getArmyStars();
    public function getArmyPower($calcUnitsWithNullDamage = true);
    public function reciveHit(DE_UnitsGroupWarPower $warPower);		
	public function fetchHits();	
	public function haveHits();	
	public function getUserInstance();
	public function getInAction();
	public function getBaseComb();
	public function getBonus();
}

?>