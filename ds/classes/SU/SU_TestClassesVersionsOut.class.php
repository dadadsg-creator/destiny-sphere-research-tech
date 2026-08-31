<?php $CVS_Version = '$Revision$ ; $Date$';
class SU_TestClassesVersionsOut extends SU_TestClassesVersions 
{
	static function Out()
	{
		//выводим папки, которых нет в CVS

		echo '<table border=1 width=100%>';

		echo "<tr bgcolor='#828282'>";
		echo "<th colspan='3'>";
		echo "<b>Can't find on CVS</b>";
		echo '</th>';
		echo '</tr>';

		self::_testFileVersions(self::$CVSData, self::$dirData);
		
		echo "</table>";
		
		//выводим папки, которых нет в VERSION
		echo '<table border=1 width=100%>';

		echo "<tr bgcolor='#828282'>";
		echo "<th colspan='3'>";
		echo "<b>Can't find on VERSION</b>";
		echo '</th>';
		echo '</tr>';

		self::_testFileVersions(self::$dirData, self::$CVSData);
		
		echo "</table>";

		//выводим новые папки в CVS
		echo '<table border=1 width=100%>';

		echo "<tr bgcolor='#828282'>";
		echo "<th colspan='4'>";
		echo "<b>New file on CVS</b>";
		echo '</th>';
		echo '</tr>';

		echo "<tr bgcolor='#9C9C9C'>";
		echo '<th>';
		echo 'File';
		echo '</th>';

		echo '<th width=15%>';
		echo 'CVS Data';
		echo '</th>';

		echo '<th width=15%>';
		echo 'VERSION Data';
		echo '</th>';

		echo '<th width=10%>';
		echo 'Ciphered';
		echo '</th>';
		echo "</tr>";
		
		self::_compareVersionsFolders(self::$CVSData, self::$dirData);
		
		echo "</table>";
		
		//выводим новые папки в VERSION
		echo '<table border=1 width=100%>';

		echo "<tr bgcolor='#828282'>";
		echo "<th colspan='4'>";
		echo "<b>New file on VERSION</b>";
		echo '</th>';
		echo '</tr>';

		echo "<tr bgcolor='#9C9C9C'>";
		
		echo "<th align='rigth'>";
		echo 'File';
		echo '</th>';
		
		echo '<th width=15%>';
		echo 'VERSION Last Update';
		echo '</th>';

		echo '<th width=15%>';
		echo 'CVS Last Update';
		echo '</th>';

		echo '<th width=10%>';
		echo 'Coded';
		echo '</th>';
		echo "</tr>";

		self::_compareVersionsFolders(self::$dirData, self::$CVSData);

		echo "</table>";
	}
	
	static private function _testFileVersions($master, $slave)
	{
		echo "<tr bgcolor='#9C9C9C'>";
		
		echo '<th>';
		echo 'File';
		echo '</th>';
		
		echo '<th width=15%>';
		echo 'Last Update';
		echo '</th>';

		echo '<th width=10%>';
		echo 'Coded';
		echo '</th>';
		echo "</tr>";

		foreach ($slave as $folder => $files)
		{
			//Если папка есть
			if(key_exists($folder, $master))
			{
				$str = self::_compareLists($files, $master[$folder]);
			}
			else {//Если папки нет
				unset($str);
				$k = 0;

				foreach ($files as $fileName => $changeTime)
				{
					$str[$k]['data'] = date ("Y-m-d H:i:s", $changeTime);
					$str[$k]['fileName'] = $fileName;
					$k ++;
				}
				}
			//Если есть что выводить
			if ($str)
			{
				echo "<tr bgcolor='#CFCFCF'>";
				echo "<td colspan='3'>";
				echo "<b>$folder</b>";
				echo '</td>';
				echo '</tr>';

				foreach ($str as $key => $data)
				{
					echo '<tr>';
					
					
					echo "<td>";
					echo "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp";					
					echo $data['fileName'];
					echo '</td>';
					
					echo "<td align='center'>";
					echo $data['data'];
					echo '</td>';

					echo "<td align='center'>";
					echo "Unknown";
					echo '</td>';

					echo '</tr>';
				}
			}
		}
	}
	
	
	static private function _compareLists($master, $slave)
	{
		assert(is_array($master));
		assert(is_array($slave));

		$k = 0;
		foreach ($master as $fileName => $changeTime)
		{
			if(!key_exists($fileName, $slave))
			{
				$str[$k]['data'] = date ("Y-m-d H:i:s", $changeTime);
				$str[$k]['fileName'] = $fileName;
				$k++;
			}
		}
		return $str;
	}

	static private function _compareVersions($master, $slave)
	{
		assert(is_array($master));
		assert(is_array($slave));

		$k = 0;
			
		foreach ($master as $fileName => $masterChangeTime)
		{	
			if(!key_exists($fileName, $slave))
			{
				continue;
			}

			$slaveChangeTime = $slave[$fileName];
			
			if($masterChangeTime > $slaveChangeTime)
			{				
				$str[$k]['CVSData'] = date ("Y-m-d H:i:s", $masterChangeTime);
				$str[$k]['VersionData'] = date ("Y-m-d H:i:s", $slaveChangeTime);
				$str[$k]['fileName'] = $fileName;
				$k++;
			}
		}
	
		return $str;
	}
	
	static private function _compareVersionsFolders($master, $slave)
	{
		
		assert(is_array($master));
		assert(is_array($slave));
		
		foreach ($master as $folder => $files)
		{		
			//Если папка есть
			if(!key_exists($folder, $slave))
			{
				continue;
			}
			$newFiles = self::_compareVersions($files, $slave[$folder]);

			//Если есть, что выводить
			if ($newFiles)
			{
				echo "<tr bgcolor='#CFCFCF'>";
				echo "<td colspan='4'>";
				echo "<b>$folder</b>";
				echo '</td>';
				echo '</tr>';

				foreach ($newFiles as $key => $data)
				{
					echo '<tr>';
					
					echo "<td>";
					echo "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp";
					echo $data['fileName'];
					echo '</td>';
					
					echo "<td>";
					echo $data['CVSData'];
					echo '</td>';
					
					echo "<td>";
					echo $data['VersionData'];
					echo '</td>';

					echo "<td align='center'>";
					echo "Unknown";
					echo '</td>';

					echo '</tr>';
				}
			}
		}
	}
}
?>
