<?php $CVS_Version = '$Revision$ ; $Date$';

interface DE_SpyZondGroupInterface
{
	public function getCombFrom();
	public function getUnit();
	public function getAmount();
	public function getAttack();
	public function getDemage();
	public function getDefence();
	public function getWarPower();
	public function getDefenceField();
	public function getOneHitPoints();
	public function getStartAmount();
	public function getRealAmount();
}

?>