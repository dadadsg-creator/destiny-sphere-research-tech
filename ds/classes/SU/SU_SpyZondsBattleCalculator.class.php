<?php

class SU_SpyZondsBattleCalculator
{
	static private $action = 'index';
	
	/**
	 * Enter description here...
	 *
	 * @var DE_SpyZondFakeGroup
	 */
	static private $attackGroup;
	
	/**
	 * Enter description here...
	 *
	 * @var DE_SpyZondFakeGroup
	 */
	static private $defenceGroup;
	
	static public function Calc(array $data)
	{
		self::_getParams($data);
		
		switch (self::$action)
		{
			case 'index':
				self::_outIndex();
				break;
				
			case 'calc':
				self::_calcBattle();
				break;
		
			default:
				break;
		}
	}
	
	static private function _getParams(array $data)
	{
		$zdata = array();
		$zdata['id_unit'] = 83;

		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}
		
		if(key_exists('attack', $data))
		{
			$zdata['zonds_amount'] = (int)$data['attack'];
			self::$attackGroup = DE_SpyZondFakeGroup::CreateFromID(-1, $zdata);
		}
		
		if(key_exists('defence', $data))
		{
			$zdata['zonds_amount'] = (int)$data['defence'];
			self::$defenceGroup = DE_SpyZondFakeGroup::CreateFromID(-2, $zdata);
		}
	}
	
	static private function _outIndex()
	{
		$str  = '<form>';
		$str .= '<input type="hidden" name="action" value="calc">';
		$str .= 'Zonds in attack: <input type="text" name="attack" size=3 value="1"><br>';
		$str .= 'Zonds in defence: <input type="text" name="defence" size=3 value="1"><br>';
		$str .= '<input type="submit" value="CALC">';
		$str .= '</form>';
		
		echo $str;
	}
	
	static private function _calcBattle()
	{
		ME_SpyZondsBattle::CalcFight(self::$attackGroup, self::$defenceGroup);
		$result = ME_SpyZondsBattle::GetResult();
		
		self::_outResult($result);
	}
	
	static private function _outResult(array $result)
	{
		$str = '<a href="?"><< BACK</a><br><br>';
		
		$str .= 'Winner: <b>'.$result['WINNER'].'</b><br>';
		$str .= 'Round: <b>'.$result['ROUND'].'</b><br>';
		$str .= 'Reason: <b>'.$result['REASON'].'</b><br>';
		
		$str .= '<br>';
		
		$str .= 'Attack: <br>';
		$str .= '&nbsp;&nbsp;&nbsp;start amount = '.self::$attackGroup->getStartAmount().'<br>';
		$str .= '&nbsp;&nbsp;&nbsp;lost = '.self::$attackGroup->getLoses().'<br>';
		
		$str .= '<br>';
		
		$str .= 'Defence: <br>';
		$str .= '&nbsp;&nbsp;&nbsp;start amount = '.self::$defenceGroup->getStartAmount().'<br>';
		$str .= '&nbsp;&nbsp;&nbsp;lost = '.self::$defenceGroup->getLoses().'<br>';
		
		echo $str;
	}
}



?>