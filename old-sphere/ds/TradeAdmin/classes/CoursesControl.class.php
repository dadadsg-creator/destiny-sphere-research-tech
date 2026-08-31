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
			default:
				ds_error('Unknown action name = '.self::$action);
		}
	}
	
	static private function _execUpdate()	
	{
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
			
			
						
			$tradeCourse = abs($tradeCourse);
			
			settype($tradeCourse, 'float');
	
			if($tradeCourse == self::_getOldCourse($resourceID, $id_article, $id_tradecategory))
			{
				continue;
			}
		CoursesSearch::SetCourse(self::$DBID, self::$worldID, $resourceID, $id_article, $id_tradecategory, $tradeCourse);	
		}
	}
	
	static private function _getOldCourse($resourceID, $id_article, $id_tradecategory)
	{
		assert(is_int($resourceID) && $resourceID > 0);
		assert(is_int($id_article) && $id_article > 0);
		assert(is_int($id_tradecategory) && $id_tradecategory > 0);
		
		//if(count(self::$oldData) == 0)
		//	{
			self::$oldData = CoursesSearch::GetCourses(self::$DBID, self::$worldID);
		//}

		
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
/*echo "<xmp>";
print_r($data);
echo "</xmp>";*/
		$title = CoursesSearch::GetTitle(self::$DBID, self::$worldID);
			
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
}


?>