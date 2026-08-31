<?php

class TMP_ControllClearComplex
{
	static private $dbID;
	
	static private $worldID;
	
	static private $ringNumber;
	
	static private $offset;
	
	static private $action = 'index';
	
	static private $worldList;
	
	static public function Exec(array $data)
	{
		self::_getParams($data);
		
		switch (self::$action)
		{
			case 'index':
				self::_showIndex();
				break;
				
			case 'clear':
				self::_clear();
				break;
		
			default:
				break;
		}
	}
	
	static private function _getParams(array $data)
	{
		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}
		
		if(key_exists('world', $data))
		{
			$arr = explode('_', $data['world']);
			assert(count($arr) == 2);
			self::$dbID = (int)$arr[0];
			self::$worldID = (int)$arr[1];
			assert(self::$dbID >= 0);
			assert(self::$worldID > 0);
		}
		
		if(key_exists('ring', $data))
		{
			self::$ringNumber = (int)$data['ring'];
			assert(self::$ringNumber > 0 && self::$ringNumber < 5);
		}
		
		if(key_exists('offset', $data))
		{
			self::$offset = (int)$data['offset'];
			assert(self::$offset >= 0);
		}
	}
	
	static private function _showIndex()
	{
		$worlds = self::_getWorldList();
		$rings = self::_getRingList();
		
		$str = "<form action='' method='POST' name='CLEAR_FORM' id='CLEAR_FORM'>\n";
		$str .= "<input type='hidden' name='action' value='clear'>\n";
		
		$str .= "<b>World</b>:&nbsp;&nbsp;&nbsp;<select name='world'>\n";
		foreach ($worlds as $id => $name)
		{
			if($id == self::$dbID.'_'.self::$worldID)
			{
				$s = 'selected';
			} else {
				$s = '';
			}
			
			$str .= "<option value='$id' $s>".$name."\n";
		}
		$str .= "</select><br><br>\n";
		
		$str .= "<b>Ring</b>:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<select name='ring'>\n";
		foreach ($rings as $id => $name)
		{
			if($id == self::$ringNumber)
			{
				$s = 'selected';
			} else {
				$s = '';
			}
			
			$str .= "<option value='$id' $s>".$name."\n";
		}
		$str .= "</select><br><br>\n";
		
		$str .= "<b>Offset</b>:&nbsp;&nbsp;&nbsp;<input type='text' name='offset' size='5' maxsize='4' value='".self::$offset."'><br><br>\n";
		
		$str .= "<input type='button'onclick='confirmDelete();' value='CLEAR'>\n";
		$str .= "</form>\n";
		
		$str .= '
				<SCRIPT language="javascript">
					function confirmDelete(){
						if(confirm("Удалить пустые комплексы?")){
							document.getElementById("CLEAR_FORM").submit();
						}
					}
				</SCRIPT>';
		
		echo $str;
	}
	
	static private function _getRingList()
	{
		$rings = array(1 => 'VORANIA', 2 => 'LIENSU', 3 => 'PSOLAO', 4 => 'MELSION');
		
		return $rings;
	}
	
	static private function _getWorldList()
	{
		self::$worldList = array();
		$common = DS_Common::CreateForCurentDB(0);
		$sql = "SELECT id_world, worldname FROM ds_world";
			
		foreach ($GLOBALS['dbWorldAccess'] as $dbId => $db)
		{
			$common->setDbId($dbId);
			$res = $common->getResultArray("", $sql);
			assert(empty($common->errormsg));
			
			foreach ($res as $k => $w)
			{
				self::$worldList[$dbId.'_'.$w['id_world']] = $w['worldname'];
			}
		}
		
		return self::$worldList;
	}
	
	static private function _clear()
	{
		$countCleared = TMP_ClearEmptyComplex::Clear(self::$dbID, self::$worldID, self::$ringNumber, self::$offset);
		
		echo "<br><b>$countCleared</b> complexes cleared<br><br>\n";	
		
		self::_showIndex();
		
		actionLog($countCleared.' complexes cleared in world '.self::$worldList[self::$dbID."_".self::$worldID], 'On ring '.self::$ringNumber.', starting from complex '.self::$offset);
	}
}

?>