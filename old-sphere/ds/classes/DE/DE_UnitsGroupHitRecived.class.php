<?php $CVS_Version = '$Revision$ ; $Date$';
 
/**
 * класс заменил класс ущности. тперь пришлось все сведен€и храниь в массивы и использовать 
 * тако вот статический класс дл€ работы с ним. —взанно с перерасходом пам€тию.
 * 
 */
class DE_UnitsGroupHitRecived
{
	public function __construct()
	{
		ds_error('ITS STATIC CLASS! U cant create his objects!!!!');
	}
	
	static public function Create($hit, $seporator)
	{
		DE_UnitsGroupHit::Validate($hit);
		assert(is_float($seporator) && $seporator > 0 && $seporator<=1);
		
		$hitRecived['WAR_POWERS'] = $hit;
		$hitRecived['SEPORATOR'] = $seporator;
		$hitRecived['HIT_RECIVER_ID'] = 0;		
		
		self::Validate($hitRecived);
		
		return $hitRecived;
	}
	
	static public function SetHitReciver(&$hitRecived, DE_UnitsGroup $unitsGroup)
	{
		self::Validate($hitRecived);
		$hitRecived['HIT_RECIVER_ID'] = (int)$unitsGroup->getID();
	}	
	
	static public function GetHitUnitsGroupReciver($hitRecived)
	{
		self::Validate($hitRecived);
		return GB_SingletonObjectStorage::GetSingleton('DE_UnitsGroup', $hitRecived['HIT_RECIVER_ID']);
	}
	
	static public function GetSeporator($hitRecived)
	{
		self::Validate($hitRecived);
		return $hitRecived['SEPORATOR'];
	}
	
	static public function AddSeporator ($hitRecived, $seporator)
	{
		self::Validate($hitRecived);
		assert(is_float($seporator) && $seporator >= 0 && $seporator<=1);
		
		$hitRecived['SEPORATOR'] = $hitRecived['SEPORATOR'] * $seporator;
	}
	
	static public function Validate($hitRecived)
	{
		assert(is_array($hitRecived) && count($hitRecived) == 3);
		
		assert(key_exists('WAR_POWERS', $hitRecived));
		DE_UnitsGroupHit::Validate($hitRecived['WAR_POWERS']);
		
		assert(key_exists('SEPORATOR', $hitRecived));
		assert(is_float($hitRecived['SEPORATOR']) && $hitRecived['SEPORATOR'] >= 0 &&  $hitRecived['SEPORATOR'] <= 1);
		
		assert(key_exists('HIT_RECIVER_ID', $hitRecived));
		assert(is_int($hitRecived['HIT_RECIVER_ID']) && $hitRecived['HIT_RECIVER_ID']>=0);
	}
}

?>

