<?php

class UnitReincarnatePriceControl
{
	static private $out = '';

	static private $action = 'unit_search';

	static private $worldID;

	static private $dbID;

	static private $unitID;

	static private $units;

	static private $race;

	static private $class;

	static private $csvFile = '';

	static private $oldData = array();

	static public function Exec($data)
	{		
		assert(is_array($data));

		self::_getParams($data);

		self::_execAction();

		return self::$out;
	}

	static private function _getParams($data)
	{
		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}

		if(key_exists('world', $data))
		{
			$arr = explode('_', $data['world']);
			self::$dbID = (int)$arr[0];
			assert(self::$dbID >= 0);

			self::$worldID = (int)$arr[1];
			assert(self::$worldID > 0);
		}

		if(key_exists('race', $data))
		{

			self::$race = $data['race'];
			assert(self::$race > 0 || self::$race == 'all');
		}

		if(key_exists('class', $data))
		{
			self::$class = $data['class'];
			assert(self::$class > 0 || self::$class == 'all');
		}

		if(key_exists('unit', $data))
		{
			self::$unitID = (int) $data['unit'];
			assert(self::$unitID > 0);
		}

		if(key_exists('units', $data))
		{
			self::$units = $data['units'];
			assert(is_array(self::$units));
		}

		if(key_exists('csv', $_FILES) && key_exists('tmp_name', $_FILES['csv']))
		{
			self::$csvFile = $_FILES['csv']['tmp_name'];
			assert(file_exists(self::$csvFile));
		}
	}

	static private function _execAction()
	{
		switch (self::$action) {
			case 'unit_search':
				self::_showUnitSearchMenu();
				break;

			case 'edit':
				self::_editUnitPrices();
				//				break;
			case 'unit_list':
				self::_showUnitList();
				break;

			case 'history':
				self::_showUnitHistory();
				break;

			case 'export_csv':
				self::_showUnitSearchMenu();
				self::_exportCSV();
				self::_showUnitList();
				break;
				
			case 'import_csv':
				self::_showUnitSearchMenu();
				self::_importCSV();
				//self::_showUnitList();
				break;

			case 'init_course':
				self::_updateUnitPower();
				break;

			default:
				break;
		}
	}

	static private function _showIndex()
	{
		$worlds = TypeSearch::GetAllWorldsList();

		self::$out = UnitReincarnatePriceOut::outIndex($worlds);
	}

	static private function _showUnitSearchMenu()
	{
		$worlds = TypeSearch::GetAllWorldsList();

		$races = UnitPriceSearch::getUnitRaces(0);
		$classes = UnitPriceSearch::getUnitClasses(0);

		if (empty(self::$dbID) AND empty(self::$worldID))
		{
			$selectedWorld = '';
		}else
		{
			$selectedWorld = self::$dbID.'_'.self::$worldID;
		}

		$filters['race'] = $races;
		$filters['class'] = $classes;

		$selected = array('race' => self::$race, 'class' => self::$class, 'world' => $selectedWorld);

		self::$out = UnitReincarnatePriceOut::outUnitSearch($worlds, $filters, $selected, $selectedWorld);
	}

	static private function _editUnitPrices()
	{
		$log = '';
		foreach(self::$units as $id => $unit)
		{
			assert(key_exists('course', $unit));

			$tempArr = explode("/", $id);
			$id_unit = $tempArr[0];
			$id_tradecategory = $tempArr[1];
			$id_article = $tempArr[2];

			settype($id_unit, 'integer');
			settype($id_article, 'integer');
			settype($id_tradecategory, 'integer');
			assert($id_unit >= 0);
			assert($id_article > 0);
			assert($id_tradecategory > 0);

			// Старый курс
			$oldCource = self::_getOldCourse($id_unit, $id_tradecategory, $id_article);
			
			// Если курс не поменялся
			if($unit['course'] == $oldCource)
			{
				continue;
			}

			// Если новый курс не установлен
			if ($unit['course'] == NULL)
			{
				continue;
			}
			
			
			$unitName = UnitPriceSearch::GetUnitNameByID(self::$dbID, $id_unit);
			$categoryBy = UnitPriceSearch::GetNameByArticleAndTradecategory(self::$dbID, self::$worldID, $id_tradecategory, $id_article);
			$log .= "Set $unitName/$categoryBy course=".$unit['course']."<br>";			
				
			
			// Если старого курса нет
			if ($oldCource == 0)
			{
				// Вставляем новый курс
				UnitReincarnatePriceSearch::insertUnitReincarnatePrice($id_unit, $id_tradecategory, $id_article, (float)$unit['course'], self::$dbID, self::$worldID);
			} else {
				// Обновляем курс
				UnitReincarnatePriceSearch::updateUnitReincarnatePrice($id_unit, $id_tradecategory, $id_article, (float)$unit['course'], self::$dbID, self::$worldID);
			}
		}
		
		//Логируем действие
		if ($log != '')
		{
		$worldName = UnitPriceSearch::GetWorldNameByID(self::$dbID, self::$worldID);			
		actionLog( "Edit unit price in world '$worldName'", $log);
		}
	}

	static private function _getOldCourse($id_unit, $id_tradecategory, $id_article)
	{
		assert(is_int($id_unit) && $id_unit > 0);
		assert(is_int($id_article) && $id_article > 0);
		assert(is_int($id_tradecategory) && $id_tradecategory > 0);

		if(count(self::$oldData) == 0)
		{
			self::$oldData = UnitReincarnatePriceSearch::getUnitsByRaceAndClass(self::$race, self::$class, self::$dbID, self::$worldID);
		}
		foreach(self::$oldData as $k => $course)
		{
			if($course['id'] == $id_unit AND $course['id_article'] == $id_article AND $course['id_tradecategory'] == $id_tradecategory)
			{
				return $course['value'];
			}
		}

		return 0;
	}

	static private function _showUnitList()
	{
		$_SESSION['dbId'] = self::$dbID;
		$_SESSION['world'] = self::$worldID;
		
		$coursesInNano = array();

		// Смотрим, как у нас считаются курсы
		$recountInNano = DE_WorldSettings::CreateForCurentWorld()->get('RECOUNT_COURSES_IN_NANO');
		// Если все курсы выражаются через нану
		if ($recountInNano == 1) {
			$onlyForNano = true;
			$coursesRes = TypeSearch::GetCoursesForNano(self::$dbID, self::$worldID);
			foreach ($coursesRes as $kkk => $ddd)
			{
				$coursesInNano[$ddd['id_resource']] = $ddd['value'];
			}
		} else {
			$onlyForNano = false;
		}
		
		$units = UnitReincarnatePriceSearch::getUnitsByRaceAndClass(self::$race, self::$class, self::$dbID, self::$worldID, $onlyForNano);

		$title = UnitReincarnatePriceSearch::getTitle(self::$dbID, self::$worldID);
		$rithTitle = UnitPriceSearch::getRithTitle(self::$dbID, self::$race, self::$class);

		self::_showUnitSearchMenu();

		self::$out .= UnitReincarnatePriceOut::outUnitList($units, $title, $rithTitle, self::$dbID.'_'.self::$worldID, self::$race, self::$class, $coursesInNano);
	}

	static private function _showUnitHistory()
	{
		$history = UnitPriceSearch::getUnitCourseHistoryByID(self::$unitID, self::$dbID, self::$worldID);

		$title = UnitPriceSearch::getUnitCourseHistoryTitleByID(self::$unitID, self::$dbID, self::$worldID);

		self::$out = UnitReincarnatePriceOut::outHistory($title, $history);
	}

	static private function _importCSV()
	{
		$units = UnitReincarnatePriceSearch::getUnitsByRaceAndClass(self::$race, self::$class, self::$dbID, self::$worldID);
		$title = UnitReincarnatePriceSearch::getTitle(self::$dbID, self::$worldID);		
		$rithTitle = UnitPriceSearch::getRithTitle(self::$dbID, self::$race, self::$class);

		$file = $_FILES['csv_file']['tmp_name'];

		if (!$fp = fopen($file, 'r'))
		{
			echo "<h2><font color=red>Не могу открыть файл ($file)</font></h2>";
			self::$out .= UnitReincarnatePriceOut::outUnitList($units, $title, $rithTitle, self::$dbID.'_'.self::$worldID, self::$race, self::$class);
			return;
		}
		
		while (($data = fgetcsv($fp, 1000, ";")) !== FALSE) 
		{		
			$newUnitPrice[] = $data;
		}

		fclose($fp);
		
		$keyValue = 1;
		$key = 0;
		
		foreach ($rithTitle as $keyUnit => $unitList)
		{
			$resKey = 1;
			
			foreach($title as $k1=>$v1)
			{
				
				foreach($v1['TYPES'] as $k2=>$v2)
				{					
					$value= (float)str_replace(',', '.', $newUnitPrice[$keyValue][$resKey]);

					if ($unitList['name'] ==  $units[$key]['name'] AND $units[$key]['trade_artyicle_name'] == $v2['TYPE_NAME'])
					{

						//$line[] = number_format($units[$key]['value']);
						$units[$key]['value'] = $value;
						$key ++;
						$resKey ++;
					} else {
						$units[$key]['id'] = $unitList['id_unit'];
						$units[$key]['name'] = $newUnitPrice[$keyValue][0];
						$units[$key]['value'] = $value;
						$units[$key]['id_tradecategory'] = $v1['CATEGORY_ID'];
						$units[$key]['id_article'] = $v1['TYPE_ID'];
						$units[$key]['trade_artyicle_name'] = $v2['TYPE_NAME'];
						$units[$key]['add_date'] = date('Y-m-d H:i:s');
						$resKey ++;
					}
				}
			}			
			$keyValue ++;
		}
		
		self::$out .= UnitReincarnatePriceOut::outUnitList($units, $title, $rithTitle, self::$dbID.'_'.self::$worldID, self::$race, self::$class);
	}
	
	static private function _exportCSV()
	{
		$units = UnitReincarnatePriceSearch::getUnitsByRaceAndClass(self::$race, self::$class, self::$dbID, self::$worldID);

		$file = $_SERVER['DOCUMENT_ROOT']."/ds/utils/files/UnitPrace.csv";
		$title = UnitReincarnatePriceSearch::getTitle(self::$dbID, self::$worldID);		
		$rithTitle = UnitPriceSearch::getRithTitle(self::$dbID, self::$race, self::$class);

		if (!$fp = fopen($file, 'w'))
		{
			echo "<h2><font color=red>Не могу открыть файл ($file)</font></h2>";
			self::$out .= UnitReincarnatePriceOut::outUnitList($units, $title, $rithTitle, self::$dbID.'_'.self::$worldID, self::$race, self::$class);
			return;
		}
		
		//Записываем в файл имена полей
		$fildName = array('Unit Name', 'PRODIUM', 'NANO', 'KADERIUM', 'FOOD ', 'STASIS', 'SILVER', 'GOLD');
		fputcsv($fp, $fildName, ";");
		
		//$keyValue = 0;
		$key = 0;
		$currace = '';
		
		foreach ($rithTitle as $keyUnit => $unitList)
		{
			//Записываем имя юнита
			$line = '';
			$line[] = $unitList['name'];

			foreach($title as $k1=>$v1)
			{
				foreach($v1['TYPES'] as $k2=>$v2)
				{
					if ($unitList['name'] ==  $units[$key]['name'] AND $units[$key]['trade_artyicle_name'] == $v2['TYPE_NAME'])
					{
						$line[] =  number_format($units[$key]['value'], 5, ',', " ");
						$key ++;
					}
					else
					{
						$line[] = '';						
					}
				}
			
			}

			//Записываем строчку в файл
			fputcsv($fp, $line, ";");
		}

		fclose($fp);
		
		self::_showUnitSearchMenu();

		self::$out .= UnitReincarnatePriceOut::outUnitList($units, $title, $rithTitle, self::$dbID.'_'.self::$worldID, self::$race, self::$class);
	
	}

	static private function _updateUnitPower()
	{
		UnitPriceSearch::initUnitTradeCourse(self::$dbID, self::$worldID);

		self::_showUnitSearchMenu();
	}
}

?>