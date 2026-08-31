<?php $CVS_Version = '$Revision$ ; $Date$';

class SU_TestTableData
{	
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static private $common;
	
	static private $storedData = array();
	
	static private $changes = array();
	
	static private $lastDate;
	
	static private $changeExist = false;
	
	/**
	 * Enter description here...
	 *
	 * @var integer
	 */
	static private $DBID = 0;
	
	static public function Exec()
	{
		self::_getStoredData();	
		
		self::$common = DS_Common::CreateForCurentDB(0);

		foreach($GLOBALS['dbWorldAccess'] as $DBID=>$serverData)
		{
			self::$DBID = $DBID;
			self::$common->setDbId($DBID);
			
			self::_processDB();	
		}
		
		if (self::$changeExist === true) {
			self::_sendLatter();			
		}
		self::_setStoreData();	
	}
	
	static private function _processDB()
	{
		$sql = "SELECT * FROM ".self::_getTableName()." ORDER BY id_world, modifiername";
		$res = self::$common->getResultArray('', $sql);
		assert(empty(self::$common->errormsg));
		
		foreach($res as $k=>$data)
		{
			$worldID = (int)$data['id_world'];
			if($worldID <= 0)
			{
				continue;
			}
			
			$name = $data['modifiername'];
			assert(!empty($name));
			
			$val = $data['value'];

			if(@key_exists($name, @self::$storedData[self::$DBID][$worldID]))
			{
				if(@self::$storedData[self::$DBID][$worldID][$name]['VALUE'] != $val)
				{
					self::$changes[self::$DBID.".".$worldID][] = "[".$name."] FROM ".@number_format(self::$storedData[self::$DBID][$worldID][$name]['VALUE'], 6, ".", " ")." TO ".number_format($val, 6, ".", " ")."\n";
					self::$lastDate = @self::$storedData[self::$DBID][$worldID][$name]['DATE'];
					
					self::$changeExist = true;
				}	
			}
						
			self::$storedData[self::$DBID][$worldID][$name] = array
			(
				'VALUE' => $val,
				'DATE' => SU_DebugTools::GetInternalDate()	
			);
		}		
	}	
	
	static private function _sendLatter()
	{
		$title = "[DS] Change game modifaers [".str_replace('_', '', constant('CACHING_PREFIX'))."]";
			
		$latter = "";
		
		foreach (self::$changes as $world => $changeModifiers)
		{
			$tmp = explode('.', $world);
			$DBID = $tmp[0];
			$worldID = $tmp[1];
			
			self::$common->setDbId($DBID);
			$sql = "SELECT * FROM ds_world WHERE id_world = $worldID";

			$res = self::$common->getResultArray('', $sql);
			assert(empty(self::$common->errormsg));
			$worldName = $res[0]['worldname'];

			$latter .= "Changing GameModifaers in world '$worldName'\n";
			$latter .= "Server: ".$GLOBALS['dbWorldAccess'][$DBID]['host']."\n";

			foreach ($changeModifiers as $key => $change)
			{
				$latter .= $change;
			}
			$latter .= "\n";
		}

		$latter .= "\nLast test date: ".self::$lastDate."\n\n";

		$eol = "\r\n";
		$headers = 'From: noreply <noreply@destinysphere.com>'.$eol;
		$headers .= "Message-ID: <".time()." TheSystem@".$_SERVER['SERVER_NAME'].">".$eol;
		$headers .= "X-Mailer: PHP v".phpversion().$eol;
		$headers .= "Content-Type: text/plain; charset=windows-1251".$eol;
		
		mail('annita@destinysphere.com', $title, $latter, $headers);
	}
	
	
	static private function _getStoredData()
	{
		self::$storedData = unserialize(file_get_contents(self::_getStoreFileName()));
	}
	
	static public function _setStoreData()
	{
		assert(file_put_contents(self::_getStoreFileName(), serialize(self::$storedData)));
	}
	
	static private function _getStoreFileName()
	{
		return $GLOBALS['LOGING_DEBUG_LOGS_PATH']['MAIN']."set_data_storing.inc";
	}
	
	static public function _getTableName()
	{
		return 'ds_gamemodifier';
	}
}

?>