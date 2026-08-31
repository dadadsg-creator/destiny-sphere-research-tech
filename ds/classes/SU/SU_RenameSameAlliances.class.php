<?php $CVS_Version = '$Revision$ ; $Date$';
class SU_RenameSameAlliances
{
	static private $fileName;
	
	static private $file;
	
	static private $common;
	
	static private $dbId = array(6, 7);
	
	static private $nameAddon = array(' (Андромеда)', ' (Кассиопея)');
	
	static private $tagAddon = array('_A', '_K');

	static public function rename($file)
	{
		self::$common = DS_Common::CreateForCurentDB();
		
		$allianceList = file($file);

		foreach($allianceList as $k => $tag)
		{
			if(trim($tag) == 'alliancetag')
			{
				continue;
			}
			
			$alliance[0] = self::getAllianceByTag(trim($tag), self::$dbId[0]);
			$alliance[1] = self::getAllianceByTag(trim($tag), self::$dbId[1]);

print_r($alliance);			
			
			if((int)$alliance[0]['members_amount'] > (int)$alliance[1]['members_amount'])
			{
echo self::$nameAddon[1];
				self::changeTag($alliance[1], self::$dbId[1], self::$tagAddon[1]);
			} else {
echo self::$nameAddon[0];
				self::changeTag($alliance[0], self::$dbId[0], self::$tagAddon[0]);
			}
		}	
	}
	
	static public function getAllianceByTag($tag, $dbId)
	{
		assert(is_int($dbId) && $dbId >= 0);
		self::$common->setDbId($dbId);
		
		$sql = "
			SELECT
				*
			FROM
				ds_alliance
			WHERE
				alliancetag = '$tag'
			";
		$res = self::$common->getResultArray("", $sql, "", "", "");
		assert(empty(self::$common->errormsg));
		assert(count($res) == 1);
		
		return $res[0];
	}
	
	static public function changeTag($alliance, $dbId, $tagAddon)
	{
		assert(is_array($alliance));
		assert(is_int($dbId) && $dbId >= 0);
		self::$common->setDbId($dbId);
		
		$newTag = substr($alliance['alliancetag'], 0, strlen($alliance['alliancetag']) - 1).$tagAddon."]";
		
		$sql = "
			UPDATE ds_alliance
			SET 
				alliancetag = '".$newTag."'
			WHERE
				id_alliance = ".$alliance['id_alliance']."
			";
		echo $sql;
	}
	
	static public function changeName($alliance, $dbId, $nameAddon)
	{
		assert(is_array($alliance));
		assert(is_int($dbId) && $dbId >= 0);
		self::$common->setDbId($dbId);
		
		$sql = "
			UPDATE ds_alliance
			SET 
				alliancename = '".$alliance['alliancename'].$nameAddon."'
			WHERE
				id_alliance = ".$alliance['id_alliance']."
			";
		echo $sql;
	}
	
	static public function getSameNames()
	{
		self::$common = DS_Common::CreateForCurentDB(self::$dbId[0]);
		
		$sql = "
			SELECT
				*
			FROM
				ds_alliance
			";
		
		self::$common->setDbId(5);
		$alliances = self::$common->getResultArray("", $sql, "", "", "");
		
		self::$common->setDbId(self::$dbId[1]);
		
		$total = 0;
		
		foreach($alliances as $k => $alliance)
		{
			self::$common->setDbId(3);
			$sql = "
				SELECT
					*
				FROM
					ds_alliance
				WHERE
					alliancename = '".$alliance['alliancename']."'
				";
			$same = self::$common->getResultArray("", $sql, "", "", "");
echo "
1 ".$alliance['alliancename']." <<<< ".$alliance['alliancetag']." <<<< \n";			
			if(count($same) > 0)
			{
echo "
2 ".$same[0]['alliancename']." <<< ".$alliance['alliancetag']." <<<< \n";
				if($alliance['members_amount'] > $same[0]['members_amount'])
				{
					self::changeName($same[0], self::$dbId[1], self::$nameAddon[1]);
				} else {
					self::changeName($alliance, self::$dbId[0], self::$nameAddon[0]);
				}
				$total++;
			}
		}
		
echo "
TOTAL : $total
";
	
	}
	
}

?>