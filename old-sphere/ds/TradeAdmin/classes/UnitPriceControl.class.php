<?php

class UnitPriceControl
{
	static private $out = '';
	
	static private $action = 'index';
	
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
			case 'index':
				self::_showIndex();
				break;
		
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
				
			case 'csv':
				self::_importCSV();
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

		self::$out = UnitPriceOut::outIndex($worlds);
	}
	
	static private function _showUnitSearchMenu()
	{
		$races = UnitPriceSearch::getUnitRaces(self::$dbID);
		$classes = UnitPriceSearch::getUnitClasses(self::$dbID);
		
		$filters['race'] = $races;
		$filters['class'] = $classes;
		
		$selected = array('race' => self::$race, 'class' => self::$class);
		
		self::$out = UnitPriceOut::outUnitSearch($filters, $selected, self::$dbID.'_'.self::$worldID);
	}
	
	static private function _editUnitPrices()
	{
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

				if($unit['course'] == self::_getOldCourse($id_unit, $id_tradecategory, $id_article))
				{
					continue;
				}

				if ($unit['course'] == NULL)
				{
					continue;
				}
				UnitPriceSearch::updateUnitCourse($id_unit, $id_tradecategory, $id_article, (float)$unit['course'], self::$dbID, self::$worldID);
		}
	}

	static private function _getOldCourse($id_unit, $id_tradecategory, $id_article)
	{
		assert(is_int($id_unit) && $id_unit > 0);
		assert(is_int($id_article) && $id_article > 0);
		assert(is_int($id_tradecategory) && $id_tradecategory > 0);

		if(count(self::$oldData) == 0)
		{
			self::$oldData = UnitPriceSearch::getUnitsByRaceAndClass(self::$race, self::$class, self::$dbID, self::$worldID);
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
		$units = UnitPriceSearch::getUnitsByRaceAndClass(self::$race, self::$class, self::$dbID, self::$worldID);
		
		$title = UnitPriceSearch::getTitle(self::$dbID, self::$worldID);		
		
		self::_showUnitSearchMenu();	
		
		self::$out .= UnitPriceOut::outUnitList($units, $title, self::$dbID.'_'.self::$worldID, self::$race, self::$class);
	}
	
	static private function _showUnitHistory()
	{
		$history = UnitPriceSearch::getUnitCourseHistoryByID(self::$unitID, self::$dbID, self::$worldID);
		
		$title = UnitPriceSearch::getUnitCourseHistoryTitleByID(self::$unitID, self::$dbID, self::$worldID);
		
		self::$out = UnitPriceOut::outHistory($title, $history);
	}
	
	static private function _importCSV()
	{
		
	}
	
	static private function _updateUnitPower()
	{
		UnitPriceSearch::initUnitTradeCourse(self::$dbID, self::$worldID);
		
		self::_showUnitSearchMenu();
	}
}

?>