<?php

/**
 * Вывод кол-ва ресурсов (или производства) на соте, в зависимости от точности лога
 *
 */
class GL_SpyLogResourceCountData
{
	/**
	 * Градации кол-ва ресурсов
	 *
	 * @var unknown_type
	 */
	static private $resourceGrades = array(
			'NO' => 0.0,
			'S' => 0.01,
			'M' => 0.4,
			'L' => 0.8,
			'XL' => 1.2,
			'XXL' => 2
		);
	
	/**
	 * Граница по которой считаем есть ресурсы на соте или нет
	 *
	 * @var unknown_type
	 */
	static private $noResLimit = 0.01;
	
	static private $localization = array(
			'NO' => 31172,
			'YES' => 31173,
			'S' => 31174,
			'M' => 31175,
			'L' => 31176,
			'XL' => 31177,
			'XXL' => 31178,
		);
	
	/**
	 * Enter description here...
	 *
	 * @param float $value - сколько ресурсов на соте
	 * @param int $accuracy - точность (кол-во раундов 1-3), 3 - самые точные данные
	 */
	static public function Get(DE_Resource $resource, $type, $value, $accuracy)
	{
		assert(is_numeric($value) && $value >= 0);
		assert(is_int($accuracy) && $accuracy >= 0 && $accuracy <= 3);
		
		$avgValue = DS_SpyAction::GetAverageResourceValue($resource, $type);
		
		switch ($accuracy)
		{
			// есть/нет
			case 0:
			case 1: 
				if((float)($avgValue * 0.01) < (float)$value)
				{
					return DE_Localization::CreateForCurentLang()->get(self::$localization['YES']);
				} else {
					return DE_Localization::CreateForCurentLang()->get(self::$localization['NO']);
				}
				break;
				
			// сколько есть (мало/много/...)
			case 2:
				$relation = $value / $avgValue;
				$grade = 'NO';				
				foreach (self::$resourceGrades as $name => $limit)
				{
					if($relation >= $limit)
					{
						$grade = $name;
					}
				}
				
				return DE_Localization::CreateForCurentLang()->get(self::$localization[$grade]);
				break;
			
			// значение
			case 3:
				return GB_Integer::Format($value, ' ');
				break;
		}
	}
}

?>