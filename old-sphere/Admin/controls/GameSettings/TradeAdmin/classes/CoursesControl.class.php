<?php

class CoursesControl
{
	static private $action;

	static private $DBID;

	static private $worldID;

	static private $ID;

	static private $out;

	static private $data;

	static private $oldData = array();

	static public function Exec($data)
	{
		assert(is_array($data));

		self::_getParams($data);

		self::_execAction();

		return self::$out;
	}

	static private function _execAction()
	{
		assert(!empty(self::$action));

		switch(self::$action)
		{
			case 'show_empty_list':
				self::_showEmptyList();
				break;
			case 'show_full_list':
				self::_showFullList();
				break;
			case 'update_exec':
				self::_execUpdate();
				self::_showFullList();
				break;
				
			case 'export_csv':
				self::_exportCSV();
				break;
				
			case 'import_csv':
				self::_importCSV();
				break;
			default:
				ds_error('Unknown action name = '.self::$action);
		}
	}

	static private function _execUpdate()
	{
		$log = '';

		foreach(self::$data as $tradeCourseID=>$tradeCourse)
		{
			$tempArr = explode("/", $tradeCourseID);
			$resourceID = $tempArr[0];
			$id_article = $tempArr[1];
			$id_tradecategory = $tempArr[2];

			settype($resourceID, 'integer');
			settype($id_article, 'integer');
			settype($id_tradecategory, 'integer');
			assert($resourceID > 0);
			assert($id_article > 0);
			assert($id_tradecategory > 0);

			settype($tradeCourse, 'float');

			$oldCourse = self::_getOldCourse($resourceID, $id_article, $id_tradecategory);

			if($tradeCourse == $oldCourse)
			{
				continue;
			}
			$resourceOn = CoursesSearch::GetNameByID(self::$DBID, self::$worldID, $resourceID);
			$resourceBy = CoursesSearch::GetNameByArticleAndTradecategory(self::$DBID, self::$worldID, $id_article, $id_tradecategory);

			$data = CoursesSearch::GetNameByID(self::$DBID, self::$worldID, $id_article, $id_tradecategory);

			if ($oldCourse = '') {
				$oldCourse = '0';
			}
			
			$log .= "Set cource $resourceOn/$resourceBy = $tradeCourse (Old course = $oldCourse).<br>";
			CoursesSearch::SetCourse(self::$DBID, self::$worldID, $resourceID, $id_article, $id_tradecategory, $tradeCourse);
		}
		$worldName = CoursesSearch::GetWorldNameByID(self::$DBID, self::$worldID);
		//Логируем действие
		actionLog( "Edit resource cources in world '$worldName'", "$log");
	}

	static private function _getOldCourse($resourceID, $id_article, $id_tradecategory)
	{
		assert(is_int($resourceID) && $resourceID > 0);
		assert(is_int($id_article) && $id_article > 0);
		assert(is_int($id_tradecategory) && $id_tradecategory > 0);

		if(count(self::$oldData) == 0)
		{
			self::$oldData = CoursesSearch::GetCourses(self::$DBID, self::$worldID);
		}

		foreach(self::$oldData as $k => $course)
		{
			if($course['id_attributename'] == $resourceID AND $course['id_article'] == $id_article AND $course['id_tradecategory'] == $id_tradecategory)
			{
				return $course['value'];
			}
		}

		return 0;
	}

	static private function _showEmptyList()
	{
		$worldsData = TypeSearch::GetAllWorldsList();

		$typeData = array();

		$title = array();

		self::$out = CoursesOut::OutMainScena($worldsData, $title, $typeData, 0, 0);
	}

	static private function _showFullList()
	{
		assert(is_int(self::$worldID) && self::$worldID > 0);
		assert(is_int(self::$DBID) && self::$DBID >= 0);

		$worldsData = TypeSearch::GetAllWorldsList();

		$data = CoursesSearch::GetCourses(self::$DBID, self::$worldID);

		$title = CoursesSearch::GetTitle(self::$DBID, self::$worldID);

		$_SESSION['dbId'] = self::$DBID;
		$_SESSION['world'] = self::$worldID;
		self::$out = CoursesOut::OutMainScena($worldsData, $data, $title, self::$worldID, self::$DBID);
	}

	static private function _getParams($data)
	{
		assert(is_array($data));

		if(key_exists('action', $data))
		{
			self::$action = $data['action'];
		}else{
			self::$action = 'show_empty_list';
		}
		assert(!empty(self::$action));

		if(key_exists('world_id', $data))
		{
			$tmpArray = explode('_', $data['world_id']);
			assert(count($tmpArray) == 2);

			self::$worldID = (int)$tmpArray[1];
			self::$DBID = (int)$tmpArray[0];

			assert(self::$worldID > 0);
			assert(self::$DBID >= 0);
		}

		if(key_exists('id', $data))
		{
			self::$ID = (int)$data['id'];
			assert(self::$ID > 0);
		}

		if(key_exists('min_price_delta', $data))
		{
			self::$minPriceDelta = abs((float)$data['min_price_delta']);
		}

		if(key_exists('max_price_delta', $data))
		{
			self::$maxPriceDelta = abs((float)$data['max_price_delta']);
		}

		if(key_exists('tax', $data))
		{
			self::$tax = abs((float)$data['tax']);
		}

		if(key_exists('is_allowed', $data))
		{
			self::$isAllowed = $data['is_allowed'];
		}

		if(key_exists('data', $data))
		{
			self::$data = $data['data'];
			assert(is_array(self::$data));
		}
	}
	
	static private function _importCSV()
	{
		assert(is_int(self::$worldID) && self::$worldID > 0);
		assert(is_int(self::$DBID) && self::$DBID >= 0);

		$worldsData = TypeSearch::GetAllWorldsList();
		$data = CoursesSearch::GetCourses(self::$DBID, self::$worldID);
		$title = CoursesSearch::GetTitle(self::$DBID, self::$worldID);

		$file = $_FILES['csv_file']['tmp_name'];

		if (!$fp = fopen($file, 'r'))
		{
			echo "<h2><font color=red>Не могу открыть файл ($file)</font></h2>";
			self::$out = CoursesOut::OutMainScena($worldsData, $data, $title, self::$worldID, self::$DBID);
			return;
		}
		
		while (($row = fgetcsv($fp, 1000, ";")) !== FALSE) 
		{		
			$newResourcePrice[] = $row;
		}

		fclose($fp);

		$keyValue = 1;
		$key = 1;
		$i = 0;
	
		foreach($data as $keyArr=>$valueArr)
		{
			$i ++;
			
			$data[$keyArr]['value'] = (float)str_replace(',', '.', $newResourcePrice[$key][$keyValue]);
			$keyValue ++;
			
			if ($i % 7 == 0 && $i != 1)
			{
				$key ++;
				$keyValue = 1;
			}
		}
	
		
		self::$out = CoursesOut::OutMainScena($worldsData, $data, $title, self::$worldID, self::$DBID);
	}
	
	static private function _exportCSV()
	{
		assert(is_int(self::$worldID) && self::$worldID > 0);
		assert(is_int(self::$DBID) && self::$DBID >= 0);

		$worldsData = TypeSearch::GetAllWorldsList();
		$data = CoursesSearch::GetCourses(self::$DBID, self::$worldID);
		$title = CoursesSearch::GetTitle(self::$DBID, self::$worldID);

		$file = $_SERVER['DOCUMENT_ROOT']."/ds/utils/files/ResourcePrace.csv";

		if (!$fp = fopen($file, 'w'))
		{
			echo "<h2><font color=red>Не могу открыть файл ($file)</font></h2>";
			self::$out = CoursesOut::OutMainScena($worldsData, $data, $title, self::$worldID, self::$DBID);
			return;

		}
	
		//Записываем в файл имена полей
		$fildName = array('Resource Name', 'PRODIUM', 'NANO', 'KADERIUM', 'FOOD ', 'STASIS', 'SILVER', 'GOLD');
		fputcsv($fp, $fildName, ";");
		
		$line = '';
		$i = 0;
		
		foreach($data as $key=>$valueArr)
		{
			if ($i % 7 == 0)
			{
				if ($line != '') {
					//Записываем строчку в файл
					fputcsv($fp, $line, ";");
				}
				//Записываем имя арта
				$line = '';
				$line[] = $valueArr['attributename'];	
				$line[] = number_format($valueArr['value'], 5, ',', " ");
			} else {
				$line[] = number_format($valueArr['value'], 5, ',', " ");
			}
			$i ++;
		}

		//Записываем последнюю строчку в файл
		fputcsv($fp, $line, ";");
					
		fclose($fp);
		
		self::$out = CoursesOut::OutMainScena($worldsData, $data, $title, self::$worldID, self::$DBID);
	}
}


?>