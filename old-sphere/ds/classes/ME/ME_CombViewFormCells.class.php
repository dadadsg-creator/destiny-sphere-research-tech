<?php

/**
 * ћенеджер формирует список €чеек соты с почвой.
 *
 */
class ME_CombViewFormCells 
{
	/**
	 * —ота дл€ которой формируем список €чеек
	 *
	 * @var DE_Comb
	 */
	static private $comb;
	
	/**
	 * ID части соты на которой находимс€.
	 *
	 * @var integer
	 */
	static private $cellTypeID;
	
	/**
	 * ќбъект с €чейками соты.
	 *
	 * @var DE_CombCells
	 */
	static private $combCells;
	
	/**
	 * Ёто условыне обозначени€ типо почв которые используютьс€ в протооколе
	 *
	 * @var array
	 */
	static private $groudTypeShortNames = array(
		1 => 'S',
		2 => 'G',
		3 => 'R', 
		4 => 'D',
		5 => 'W'
	);
	
	/**
	 * ѕеременна€ со строкой дл€ вывода
	 *
	 * @var string
	 */
	static private $out = '';
	
	static public function Exec(DE_Comb $comb, $cellTypeID)
	{
		assert(is_int($cellTypeID) && $cellTypeID > 0);
		
		self::$comb = $comb;
		self::$cellTypeID = $cellTypeID;
		
		self::$combCells = DE_CombCells::CreateFromCombIDAndCellTypeID($comb->getID(), $cellTypeID);
		
		self::$out = "";
		
		if($cellTypeID == 2)
		{
			self::_formCentralPart();
		}else{
			self::_formHomeAndNeitralPart();
		}

		if(strlen(self::$out) < 217)
		{
			self::$out .= str_repeat('F', 217 - strlen($out));
		}
		
		return self::$out;
	}
	
	static private function _formCentralPart()
	{
		$cellsMask = GB_CombCentralViewMask::GetClientBaseMask();

		foreach ($cellsMask AS $clientCellNumber=>$templateCellData)
		{
			$cellGround = '';

			if($templateCellData['GROUND_TYPE'] == 'Z')
			{
				$cellGround = self::$groudTypeShortNames[self::$combCells->cells[(int)$templateCellData['SERVER_NUMBER']]['GROUND_TYPE_ID']];
			}else{
				$cellGround = $templateCellData['GROUND_TYPE'];
			}
			
			assert(!empty($cellGround));
		    self::$out .= $cellGround;
		}
	}
	
	static private function _formHomeAndNeitralPart()
	{
		foreach (self::$combCells->cells AS $cellNumber=>$cellData)
		{
			self::$out .= self::$groudTypeShortNames[$cellData['GROUND_TYPE_ID']];
		}
	}
	
}

?>