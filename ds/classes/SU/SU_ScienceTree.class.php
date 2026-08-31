<?php $CVS_Version = '$Revision$ ; $Date$';

/**
 * Дерево наук
 *
 */
class SU_ScienceTree
{
	/**
	 * Enter description here...
	 *
	 * @var DS_Common
	 */
	static private $common;
	
	static private $cacheTime = 172800;
	
	static private $result;
	
	static private $language = 3;
	
	static private $race = 1;
	
	static private $lang;
	
	static public function Get(array $data = array())
	{
		self::_initParams($data);
		
		self::$result = DS_Cache::Singleton()->get(self::GetCacheMark());
		
		if(self::$result)
		{
			echo self::$result;
			return;
		}

		if(GameDown(0))
		{
			echo '<?xml version="1.0" encoding="utf-8"?>'."\n".'<game_down/>';
			return;
		}
			
		self::$common = DS_Common::CreateForCurentDB(0);
			
		self::_getHeader();
			
		self::_getLocalization();
			
		self::_getSciences();
			
		self::_getBuildings();
			
		self::_getFooter();
			
		self::$result = SU_OutputHandler::encodeData(self::$result);
			
		DS_Cache::Singleton()->set(self::GetCacheMark(), self::$cacheTime, self::$result);

		echo self::$result;
	}
	
	static private function _initParams(array $data)
	{
		if(isset($data['lang']))
		{
			self::$language = (int)$data['lang'];
			assert(self::$language > 0 && self::$language < 5);
			
		} elseif(isset($_SESSION['lang'])) {
			self::$language = $_SESSION['lang'];
		} else {
			self::$language = constant('DEFAULT_LANGUAGE');
		}
		
		$_SESSION['lang'] = self::$language;
		
		if(isset($data['race']))
		{
			self::$race = (int)$data['race'];
			assert(self::$race > 0 && self::$race < 4);
		}
	}
	
	static public function GetCacheMark()
	{
		return 'SCIENCE_TREE_BUILDINGS_'.self::$language.'_'.self::$race;
	}
	
	static private function _getHeader()
	{
		self::$result = 'xmldata=<?xml version="1.0" encoding="utf-8"?>'."\n".'<sciencetree lang="'.self::$language.'">';
	}
	
	static private function _getFooter()
	{
		self::$result .= '</sciencetree>';
	}
	
	static private function _getLocalization()
	{
		$langs = array(1 => 'G', 2 => 'E', 3 => 'R', 4 => 'I', 5 => 'L'); // ?? заменить на выборку из базы
		
		require_once($_SERVER['DOCUMENT_ROOT'].'/ds/language/'.$langs[$_SESSION['lang']].'/science_tree.lang');
		assert(is_array($lang));
		
		self::$result .= '<texts>';
		
		self::$lang = $lang;
		
		foreach ($lang as $key => $val)
		{
			self::$result .= '<text id="'.$key.'" ln="'.$val.'"/>';
		}
		
		self::$result .= '</texts>';
	}
	
	static private function _getSciences()
	{
		$curLevel = self::_getCurrentLevels();
		$sciences = self::_getAllSciences();
		
		self::$result .= '<currentlevel>';
		
		foreach ($sciences as $k => $science)
		{
			if(key_exists($science['name'], self::$lang))
			{
				$localName = self::$lang[$science['name']];
			} else {
				$localName = $science['local_name'];
			}
			
			self::$result .= '<science level="'.$curLevel[$science['name']].'" name="'.$localName.'" id="'.$science['name'].'"/>';
		}
		
		self::$result .= '</currentlevel>';
	}
	
	static private function _getCurrentLevels()
	{
		if(!isset($_SESSION['uid']) || !isset($_SESSION['uiid']) || !isset($_SESSION['world']) || !isset($_SESSION['dbId']))
		{
			// из базы выбрать текущие уровни науки игрока
			$curLevel = array('KADERIUM' => 0, 'NANO_CRYSTAL' => 0, 'STASIS_ENERGY' => 0, 'FOOD' => 0, 'PRODIUM' => 0, 'TRADE' => 0, 'SPYING' => 0);
		} else {
			$curLevel = array('KADERIUM' => 0, 'NANO_CRYSTAL' => 0, 'STASIS_ENERGY' => 0, 'FOOD' => 0, 'PRODIUM' => 0, 'TRADE' => 0, 'SPYING' => 0);
		}
		
		return $curLevel;
	}
	
	static private function _getAllSciences()
	{
		$sql = "
			SELECT
				sb.branchname AS name,
				COALESCE(ltl.text, sb.branchname) AS local_name
			FROM
				ds_sciencebranch AS sb
				LEFT JOIN ds_localizedtextlanguage AS ltl ON (ltl.id_localizedtext = sb.id_localizedname AND ltl.id_language = ".self::$language.")
			ORDER BY outorder
			";
		$res = self::$common->getResultArray("SCIENCE_TREE_ALL_SCIENCES_".self::$language, $sql, "", "", "GLOBAL_CACHE");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
	
	static private function _getBuildings()
	{
		//for($i=1; $i<4; $i++)
		//{
		self::$result .= self::_getBuildingsXMLForRace(self::$race);
		//}
	}
	
	static private function _getBuildingsXMLForRace($race)
	{
		assert(is_int($race) && $race > 0 && $race < 4);
		
		$data = self::_getBuildingsDataForRace($race);
		
		$race = DE_Race::CreateFromID($race);
		
		$xml = '<builds race="'.$race->getID().'" name="'.$race->getLocalizedName().'">';
		
		foreach ($data as $k => $b)
		{
			$xml .= '<build id="'.$b['id_building'].'" status="2" name="'.$b['localizedname'].'" libname="'.$b['internalname'].'" science="'.$b['science'].'" need="'.$b['need'].'"/>';
		}
		
		$xml .= '</builds>';
		
		return $xml;
	}
	
	static private function _getBuildingsDataForRace($race)	
	{
		assert(is_int($race) && $race > 0 && $race < 4);
		
		$sql = "
				SELECT
					b.buildingname AS internalname,
					b.id_building,
					(
						SELECT text
						FROM ds_localizedtextlanguage
						WHERE
							id_language = ".self::$language."
							AND
							id_localizedtext = bcbr.id_localizedname
					) AS localizedname,
										
                    COALESCE((
                            SELECT max(s.level)
							FROM
	   							ds_buildingneedsscience bns
	   							INNER JOIN ds_science s ON bns.id_science = s.id_science
	   							INNER JOIN ds_sciencebranch sb ON s.id_branch = sb.id_branch
							WHERE
								b.id_building = bns.id_building
								AND
								sb.branchname = 'KADERIUM'
								AND
								s.level > 1
						), 0
                    )||','||
                    COALESCE((
                            SELECT max(s.level)
							FROM
	   							ds_buildingneedsscience bns
	   							INNER JOIN ds_science s ON bns.id_science = s.id_science
	   							INNER JOIN ds_sciencebranch sb ON s.id_branch = sb.id_branch
							WHERE
								b.id_building = bns.id_building
								AND
								sb.branchname = 'NANO_CRYSTAL'
								AND
								s.level > 1
						), 0
                    )||','||
                    COALESCE((
                            SELECT max(s.level)
							FROM
	   							ds_buildingneedsscience bns
	   							INNER JOIN ds_science s ON bns.id_science = s.id_science
	   							INNER JOIN ds_sciencebranch sb ON s.id_branch = sb.id_branch
							WHERE
								b.id_building = bns.id_building
								AND
								sb.branchname = 'STASIS_ENERGY'
								AND
								s.level > 1
						), 0
                    )||','||
                    COALESCE((
                            SELECT max(s.level)
							FROM
	   							ds_buildingneedsscience bns
	   							INNER JOIN ds_science s ON bns.id_science = s.id_science
	   							INNER JOIN ds_sciencebranch sb ON s.id_branch = sb.id_branch
							WHERE
								b.id_building = bns.id_building
								AND
								sb.branchname = 'PRODIUM'
								AND
								s.level > 1
						), 0
                    )||','||
                    COALESCE((
                            SELECT max(s.level)
							FROM
	   							ds_buildingneedsscience bns
	   							INNER JOIN ds_science s ON bns.id_science = s.id_science
	   							INNER JOIN ds_sciencebranch sb ON s.id_branch = sb.id_branch
							WHERE
								b.id_building = bns.id_building
								AND
								sb.branchname = 'FOOD'
								AND
								s.level > 1
						), 0
                    )||','||
                    COALESCE((
                            SELECT max(s.level)
							FROM
	   							ds_buildingneedsscience bns
	   							INNER JOIN ds_science s ON bns.id_science = s.id_science
	   							INNER JOIN ds_sciencebranch sb ON s.id_branch = sb.id_branch
							WHERE
								b.id_building = bns.id_building
								AND
								sb.branchname = 'TRADE'
								AND
								s.level > 1
						), 0
                    )||','||
                    COALESCE((
                            SELECT max(s.level)
							FROM
	   							ds_buildingneedsscience bns
	   							INNER JOIN ds_science s ON bns.id_science = s.id_science
	   							INNER JOIN ds_sciencebranch sb ON s.id_branch = sb.id_branch
							WHERE
								b.id_building = bns.id_building
								AND
								sb.branchname = 'SPYING'
								AND
								s.level > 1
						), 0
                    ) as science,
                    
                    
					(
						SELECT ARRAY_TO_STRING
						(
							ARRAY(
								SELECT
									bnb.id_building_2
								FROM
	    							ds_buildingneedsbuilding AS bnb
								WHERE
									b.id_building = bnb.id_building_1
           					),
           					','
           				)
					) AS need
				FROM
					ds_building AS b
					INNER JOIN ds_buildingcanbuildbyrace AS bcbr USING (id_building)
				WHERE
				    bcbr.id_race = $race
				ORDER BY b.id_building";
		
		$res = self::$common->getResultArray("SCIENCE_TREE_BUILDING_RACE_".$race."_".self::$language, $sql, "", "", "GLOBAL_CACHE");
		assert(empty(self::$common->errormsg));
		
		return $res;
	}
}

?>