<?php
class SU_TestSorceVersions
{
	/**
	 * Enter description here...
	 *
	 * @var 
	 */

	static private $changeExist = false;

	static private $changes = array();

	static private $storedData = array();

	static private $rootFolder;

	static private $subFolder;

	static private $newFiles;

	static private $deleteFiles;

	static private $lastTestDate;

	static private $sendLetter = true;

	static public function Exec()
	{
		self::$rootFolder = $_SERVER['DOCUMENT_ROOT'];
		self::$subFolder = "";

		self::_getStoredData();

		//Засекаем время проверки
		$startProcessDate = date('Y-m-d H:i:s');

		self::_processData();

		//Если есть изменения и файл с данными существовал до проверки
		if (self::$changeExist === true && self::$sendLetter === true) {
			self::_sendLatter($startProcessDate);
		}

		self::_setStoreData();
		self::_log();
	}

	static private function _processData()
	{
		self::$lastTestDate = @self::$storedData['LastTestDate'];

		$expansionArr = array(
		0 => 'php',
		1 => 'html'
		);

		$tree = SU_GetTree::Exec(self::$rootFolder.self::$subFolder, $expansionArr);

		foreach (self::$storedData as $filename => $data)
		{
			$tmp = explode("/", $filename);

			if ($filename == 'LastTestDate' || strpos(end($tmp), '%%') === 0)
			{
				continue;
			}

			if (!key_exists($filename, $tree)) {
				self::$deleteFiles[] = self::$subFolder.$filename;
				unset(self::$storedData[$filename]);
				self::$changeExist = true;
			}
		}

		foreach($tree as $filename => $data)
		{
			assert(!empty($filename));

			//Если файл начинается с %%
			$tmp = @explode("/", $filename);
			if (@strpos(end($tmp), '%%') === 0) {
				continue;
			}

			if(@key_exists($filename, @self::$storedData))
			{
				if(@self::$storedData[$filename]['time'] != $data['time'] || @self::$storedData[$filename]['size'] != $data['size'])
				{
					self::$changes[] = self::$subFolder.$filename."(".date('Y-m-d H:i:s', $data['time']).")";
					self::$changeExist = true;
				}
			} else {
				self::$newFiles[] = self::$subFolder.$filename."(".date('Y-m-d H:i:s', $data['time']).")";
				self::$changeExist = true;
			}

			self::$storedData[$filename]['time'] = $data['time'];
			self::$storedData[$filename]['size'] = $data['size'];
		}

		self::$storedData['LastTestDate'] = SU_DebugTools::GetInternalDate();
	}

	static private function _getStoredData()
	{
		if (file_exists(self::_getStoreFileName()))
		{
			self::$storedData = unserialize(file_get_contents(self::_getStoreFileName()));
		} else {
			self::$sendLetter = false;
		}

	}

	static private function _getStoreFileName()
	{
		return $GLOBALS['LOGING_DEBUG_LOGS_PATH']['MAIN']."set_version_storing.inc";
	}

	static public function _setStoreData()
	{
		assert(file_put_contents(self::_getStoreFileName(), serialize(self::$storedData)));
	}

	static public function _sendLatter($startProcessDate)
	{
		$title = "[DS] Changed files [".str_replace('_', '', constant('CACHING_PREFIX'))."]";
		$latter = "GAME VERSION: ".constant('GAME_HOST')."\nSERVER TIME: ".$startProcessDate."\n\n";

		if (!empty(self::$changes))
		{
			$latter .= "CHANGED FILES:\n";

			foreach (self::$changes as $key => $filename)
			{
				$latter .= "$filename\n";
			}

			$latter .= "\n";
		}

		if (!empty(self::$newFiles))
		{
			$latter .= "NEW FILES:\n";

			foreach (self::$newFiles as $key => $filename)
			{
				$latter .= "$filename\n";
			}
			$latter .= "\n";
		}

		if (!empty(self::$deleteFiles))
		{
			$latter .= "DELETED FILES:\n";

			foreach (self::$deleteFiles as $key => $filename)
			{
				$latter .= "$filename\n";
			}
			$latter .= "\n";
		}

		$latter .= "\nLast test date: ".self::$lastTestDate."\n\n";

		$eol = "\r\n";
		$headers = 'From: noreply <noreply@destinysphere.com>'.$eol;
		$headers .= "Message-ID: <".time()." TheSystem@".$_SERVER['SERVER_NAME'].">".$eol;
		$headers .= "X-Mailer: PHP v".phpversion().$eol;
		$headers .= "Content-Type: text/plain; charset=windows-1251".$eol;

		mail('annita@destinysphere.com', $title, $latter, $headers);
	}

	static public function _log()
	{
		$logFile = "TestSorce.log";

		$log = "";
		$log .= "CHANGED FILES: ".count(self::$changes)."\n";
		$log .= "NEW FILES: ".count(self::$newFiles)."\n";
		$log .= "DELETED FILES: ".count(self::$deleteFiles)."\n";
		$log .= "TOTAL FILES: ".@(count(self::$storedData)-1)."\n";


		SU_DebugTools::LogAll($log, $logFile, 0);
	}
}

?>