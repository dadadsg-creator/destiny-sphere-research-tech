<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * класс заменил класс ущности. тперь пришлось все сведен€и храниь в массивы и использовать 
 * тако вот статический класс дл€ работы с ним. —взанно сперерасходом пам€тию.
 * 
 */
class DE_UnitsGroupHit
{
	public function __construct()
	{
		ds_error('ITS STATIC CLASS! U cant create his objects!!!!');
	}
	
	static public function Create(DE_UnitGroupWarPower $warPower)
	{
		$unitsGroupHit[] = $warPower;
		self::Validate($unitsGroupHit);
		return $unitsGroupHit;
	}
	
	static public function AddWarPower(&$unitsGroupHit, DE_UnitGroupWarPower $warPower)
	{
		self::Validate($unitsGroupHit);		
		$unitsGroupHit[] = $warPower;
		return $unitsGroupHit;
	}
	
	static public function GetUnit($unitsGroupHit)
	{
		self::Validate($unitsGroupHit);		
		return $unitsGroupHit[0]->getUnitsGroup()->getUnit();
	}
	
	static public function Validate($unitsGroupHit)
	{
		assert(is_array($unitsGroupHit) && count($unitsGroupHit > 0));
		
		foreach($unitsGroupHit as $k=>$v)
		{
			assert($v instanceof DE_UnitGroupWarPower);			
		}
	}
}

?>