<?php

class GL_GenerateNPCCombName
{
	static private $namesVoraner = array(
			'Styheim',
			'Akbeador',
			'Shadeahpur',
			'Numialus',
			'Gonaikia',
			'Mecusun',
			'Akbailus',
			'Sabkia',
			'Ophodor',
			'Erilus',
			'Akbeshan',
			'Akropus',
			'Staikhmet',
			'Khorealia',
			'Thiadia',
			'Aghraigia',
			'Iyza',
			'Kaidrah',
			'Bosaehan',
			'Vilaithu',
			'Shadaska',
		);
		
	static private $namesLiensu = array(
			'Harathu',
			'Kaetea',
			'Taejar',
			'Ophexur',
			'Erenta',
			'Nilam',
			'Cimaer',
			'Kudrah',
			'Libyska',
			'Tihpur',
			'Anaik',
			'Frusun',
			'Hanison',
			'Loran',
		);
		
	static private $namesPsolao = array(
			'Kisol',
			'Morunta',
			'Nordlus',
			'Ereadier',
			'Samymu',
			'Frugia',
			'Hanothu',
			'Morkia',
			'Gheamir',
			'Ieran',
			'Anuverus',
			'Fergo',
			'Mecaim',
			'Meceros',
			'Lusol',
			'Arenafar',
			'Aquza',
			'Gonozar',
			'Knia',
			'Lialus',
			'Khaf',
		);
		
	static private $addonVoraner = array(
			'Q01',
			'W607',
			'A203',
			'F101',
			'G43',
			'V1',
			'A303',
			'F301',
			'N000',
			'Z123',
			'A22',
			'G401',
			'T005',
			'S333',
			'F2',
			'G67',
			'J234',
			'S0',
			'A376',
			'F13',
			'Q666',
		);
		
	static private $addonLiensu = array(
			0 => array(
				'Son',
				'Eat',
				'Don',
				'Fri',
				'Ano',
				'Try',
				'Gro',
				'Von',
				'Tau',
				'Toy',
				'Kio',
				'Nau',
				'Sat',
				'Dok',
			),
			
			1 => array(
				'Eart',
				'Wate',
				'Metr',
				'Dron',
				'Komb',
				'Drag',
				'Yado',
				'Death',
				'Life',
				'Suns',
				'Kredo',
				'Fox',
				'Sky',
				'Tromb',
			),
		);
		
	static private $addonPsolao = array(
			'Roj',
			'Nog',
			'Sun',
			'Dok',
			'Tin',
			'Run',
			'Nub',
			'Tor',
			'Fee',
			'Gin',
			'Orh',
			'Try',
			'Red',
			'Tza',
			'Fio',
			'Rog',
			'Xol',
			'Von',
			'Min',
			'Men',
			'Set',
	);
		
	static private $vowel = array('a', 'e', 'u', 'i', 'o', 'y');
	
	static private $consonant = array('q', 'w', 'r', 't','p', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'z', 'x', 'c', 'v', 'b', 'n', 'm');
	
	static public function generate($race)
	{
		assert(is_int($race));
		
		switch ($race) {
			case 1:
				$combName = self::_generateVoraner();
				break;
				
			case 2:
				$combName = self::_generateLiensu();
				break;
				
			case 3:
				$combName = self::_generatePsolao();
				break;
		
			default:
				$combName = self::_generateQuest();
				break;
		}
		
		return $combName;
	}
	
	static private function _generateVoraner()
	{
		// Имя_Цифры
		$counter = 0;
			
		while(!VD_CombName::Validate($combName, true) && $counter < 100)
		{
        	$combName = self::$namesVoraner[array_rand(self::$namesVoraner)].'_'.self::$addonVoraner[array_rand(self::$addonVoraner)];
        	$counter++;
		}
		
		return $combName;
	}
	
	static private function _generateLiensu()
	{
		// Слог_Слог_Слог
		$counter = 0;
			
		while(!VD_CombName::Validate($combName, true) && $counter < 100)
		{
        	$combName = self::$namesLiensu[array_rand(self::$namesLiensu)].'_'.self::$addonLiensu[0][array_rand(self::$addonLiensu[0])].'_'.self::$addonLiensu[1][array_rand(self::$addonLiensu[1])];
        	$counter++;
		}
		
		return $combName;
	}
	
	static private function _generatePsolao()
	{
		// Имя_Слог
		$counter = 0;
			
		while(!VD_CombName::Validate($combName, true) && $counter < 100)
		{
        	$combName = self::$namesPsolao[array_rand(self::$namesPsolao)].'_'.self::$addonPsolao[array_rand(self::$addonPsolao)];
        	$counter++;
		}
		
		return $combName;
	}
	
	static private function _generateQuest()
	{
		return '';
	}
	
	static public function _getSyllable()
	{
		return self::$consonant[array_rand(self::$consonant)].self::$vowel[array_rand(self::$vowel)];
	}
}

?>