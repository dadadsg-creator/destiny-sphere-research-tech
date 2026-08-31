<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Торговые фильтры
 *
 */
class ME_TradeUnserializeFilter
{
	/**
	 * Возвращает из сериализоваанной строки только значения, не сохраняя полную структуру массива
	 *
	 * @param string $str
	 * @return array
	 */
	static public function UnserializeFlat($str)
	{
		$res = array();
		$IDs = array();
		$pattern = '/\[([\w,]+)\]/';
	
		if(preg_match_all($pattern, $str, $res))
		{
			foreach ($res[1] as $k => $v) 
			{
				$IDs = array_merge($IDs, explode(',', $v));
			}
		} else {
			$IDs = explode(',', $str);
		}
		
		return $IDs;
	}
	
	/**
	 * Полноценная рассериализация
	 * Преобразование строки вида 2[SIZE[2,11]],7[BONUS[1]]
	 * в массив вида array('2' => array('SIZE' => array(2, 11)), '7' => array('BONUS' => array(1)))
	 *
	 * @param string $str
	 * @return array
	 */
	static public function UnserializeFull($str)
	{
		$braceOpen = '[';
		$braceClose = ']';
		$elementDelimiter = ',';
		
		$c = 0;
		$buf = '';
		$braceLevel = 0;
		$result = array();
		$parent = array();
		$curent = &$result;
		
		while($c < strlen($str))
		{
			$char = $str[$c];
			$c++;
			
			switch ($char) 
			{
				case $braceOpen:
					$curent[$buf] = array();
					$parent[$braceLevel] = &$curent;
					$curent = &$curent[$buf];
					$braceLevel++;
					$buf = '';
					continue 2;
			
				case $braceClose:
					if(!empty($buf) && $buf !== 'undefined')
					{
						$curent[] = $buf;
					}
					$braceLevel--;
					$curent = &$parent[$braceLevel];
					$buf = '';
					continue 2;
					
				case $elementDelimiter:
					if(!empty($buf) && $buf !== 'undefined')
					{
						$curent[] = $buf;
					}
					$buf = '';
					continue 2;
			}
			
			$buf .= $char;
		}
		
		return $result;
	}
}

?>