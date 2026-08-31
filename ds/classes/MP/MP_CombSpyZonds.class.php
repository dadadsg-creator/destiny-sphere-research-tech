<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Проверка количества шпионских зондов
 *
 */
class MP_CombSpyZonds extends MP_CombBase
{
	static private $zonds = array(
								'SPY_ZOND_SCOUT' => 83,
								'SPY_ZOND_EXPLORER' => 88);
	
	public function __construct($combData)
	{
		parent::__construct($combData);
	}	
	
	public function patch()
	{
		foreach (self::$zonds as $zondName => $zondID)
		{
			assert(is_int($zondID) && $zondID > 0);
			assert(!empty($zondName));
			
			$this->_patchAwayAmount($zondID, $zondName);
		
			$this->_patchCurentAmount($zondID, $zondName);
		}
	}
	
	private function _patchAwayAmount($zondID, $zondName)
	{
		$awayValueData = self::GetCombAttributeByTypeAndName($this->combID, 'AWAY_AMOUNT', $zondName);
		$awayValueID = (int)$awayValueData['id'];
		$awayValue = (int)$awayValueData['value'];
		
		$realAway = $this->_getAwayZondsAmount($zondID);
		
		if($awayValue != $realAway)
		{
			$str =  "		".__CLASS__.": ERROR! Mismatch $zondName AWAY_ATTRIBUTE = $awayValue and REAL_AWAY = ".$realAway;
			self::_out($str);
			if(self::$onlyValidate)
			{
				$str =  "		ONLY VALIDATE!";
				self::_out($str);
			} else {				
				self::SetCombAttributeByID($awayValueID, $realAway);	
			}
		}
	}
	
	private function _patchCurentAmount($zondID, $zondName)
	{
		$maxValue = $this->_getScienceLevel() * self::GetWorldSettingByWorldIDSettingName(self::GetWorldIDByCombID($this->combID), 'SPY_MAX_ZONDS_COUNT_PER_SCIENCE_LEVEL');

		$curValueData = self::GetCombAttributeByTypeAndName($this->combID, 'CURRENT_AMOUNT', $zondName);
		$curValueID = (int)$curValueData['id'];

		$awayValueData = self::GetCombAttributeByTypeAndName($this->combID, 'AWAY_AMOUNT', $zondName);
		$awayValueID = (int)$awayValueData['id'];
		
		$curValue = (int)$curValueData['value'];
		$curValue += (int)$awayValueData['value'];
		$curValue += (int)$this->_getCountInTrain($zondID);
	
		if($curValue > $maxValue)
		{
			$str =  "		".__CLASS__.": ERROR! Mismatch $zondName MAX_ZONDS_AMOUNT = $maxValue and REAL_ZONDS_AMOUNT = ".$curValue;
			self::_out($str);
		if(self::$onlyValidate)
			{
				$str =  "		ONLY VALIDATE!";
				self::_out($str);
			} else {
				$newValue = $maxValue;
				if($newValue < 0)
				{
					$newValue = 0;
				}
				
				self::SetCombAttributeByID($curValueID, $newValue);	
			}
		}
	}
	
	private function _getAwayZondsAmount($zondID)
	{
		$sql = "
			SELECT
				sum(zonds_amount) as sum
			FROM
				ds_spy_group_action AS sga
			WHERE
				id_comb_from = ".$this->combID."
				AND 
				id_unit = ".$zondID."
			";
		$res = self::$common->getResultArray("", $sql, "", "", "NO_CACHE");
		assert(empty(self::$common->errormsg));
		
		if(count($res) == 0)
		{
			return 0;
		}
		
		return (int)$res[0]['sum'];
	}
	
	private function _getCountInTrain($zondID)
	{
		$sql = "
			SELECT 
				eho1.value
			FROM 
				ds_combbindeventqueue cbeq1,
				ds_eventqueue eq1,
				ds_event e1,
				ds_eventhasoption eho1
			WHERE 
				cbeq1.id_eventqueue = eq1.id_eventqueue
				and eho1.id_eventqueue = eq1.id_eventqueue
				and eq1.id_event = e1.id_event
				and eq1.istrash = false
				and eho1.id_eventoption = (
					SELECT 
						id_eventoption
					FROM 
						ds_eventoption
					WHERE 
						id_event = e1.id_event
						and optionname = 'CountToTrain'
				)
				and eq1.id_eventqueue = (
					SELECT 
						eq.id_eventqueue 
					FROM 
						ds_eventqueue eq,
						ds_combbindeventqueue cbeq2,
						ds_eventhasoption eho
					WHERE 
						eq.id_eventqueue = eho.id_eventqueue
						and eq.istrash = false
						and cbeq2.id_eventqueue = eq.id_eventqueue
						and cbeq2.id_comb = ".$this->combID."
						and eho.id_eventoption = 
						(
							SELECT 
								id_eventoption
							FROM 
								ds_eventoption eo,
								ds_event e
							WHERE 
								eo.id_event = e.id_event
								and e.eventname = 'TRAIN_UNIT'
								and eo.optionname = 'ID_Unit'
						)
						and eho.value = ".$zondID."
					)
				and id_comb = ".$this->combID."";
		$res = self::$common->getResultArray("", $sql, "", "", "NO_CACHE");
		assert(empty(self::$common->errormsg));
		
		if(count($res) == 0)
		{
			return 0;
		}
		
		return (int)$res[0]['value'];
	}
	
	private function _getScienceLevel()
	{
		$sql = "
			SELECT 
				max(s.level) as level
			FROM 
				ds_userhasscience AS uhs
               	INNER JOIN ds_science AS s ON (uhs.id_science = s.id_science)
               	INNER JOIN ds_sciencebranch AS sb USING (id_branch)
               	INNER JOIN ds_comb AS c USING (id_userinstance)
			WHERE 
				c.id_comb = $this->combID
				AND
				sb.branchname = 'SPYING'
			";
		$res = self::$common->getResultArray("", $sql, "", "", "NO_CACHE");
		assert(empty(self::$common->errormsg));
		
		if(count($res) == 0)
		{
			return 0;
		}
		
		return (int)$res[0]['level'];
	}
}

?>