<?php

class ArtefactPriceControl
{

	static private $out = '';

	static private $action = 'index';

	static private $worldID;

	static private $dbID;

	static private $scope = -1;

	static private $size = -1;

	static private $artefactID;

	static private $arts;

	static private $csvFile = '';

	static private $oldData = array();
	
	static private $title = array();

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

		if(key_exists('scope', $data))
		{
			self::$scope = $data['scope'];
			assert(self::$scope > 0 || self::$scope == 'all');
		}

		if(key_exists('size', $data))
		{
			self::$size = $data['size'];
			assert(self::$size > 0 || self::$size == 'all');
		}

		if(key_exists('artefact', $data))
		{
			self::$artefactID = (int) $data['artefact'];
			assert(self::$artefactID > 0);
		}

		if(key_exists('art', $data))
		{
			self::$arts = $data['art'];
			assert(is_array(self::$arts));
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

			case 'art_search':
				self::_showArtSearchMenu();
				break;

			case 'edit':
				self::_editArtPrices();
				//				break;
			case 'art_list':
				self::_showArtList();
				break;

			case 'history':
				self::_showArtHistory();
				break;

			case 'csv':
				self::_importCSV();
				break;

			default:
				break;
		}
	}

	static private function _showIndex()
	{
		$worlds = TypeSearch::GetAllWorldsList();

		self::$out = ArtefactPriceOut::outIndex($worlds);
	}

	static private function _showArtSearchMenu()
	{
		$scopes = ArtefactPriceSearch::getArtefactScopes(self::$dbID);
		
		$sizes = ArtefactPriceSearch::getArtefactSizes(self::$dbID);

		$filters['scope'] = $scopes;
		$filters['size'] = $sizes;

		$selected = array('scope' => self::$scope, 'size' => self::$size);

		self::$out = ArtefactPriceOut::outArtSearch($filters, $selected, self::$dbID.'_'.self::$worldID);
	}
	
	static private function _showArtList()
	{
		$artefacts = ArtefactPriceSearch::getArtefactsByScopeAndSize(self::$scope, self::$size, self::$dbID, self::$worldID);

		$title = ArtefactPriceSearch::getTitle(self::$dbID, self::$worldID);

		$rithTitle = ArtefactPriceSearch::getRithTitle(self::$dbID, self::$worldID, self::$scope, self::$size);
		
		self::_showArtSearchMenu();

		self::$out .= ArtefactPriceOut::outArtefactList($title, $rithTitle, $artefacts, self::$dbID.'_'.self::$worldID, self::$scope, self::$size);
	}

	static private function _showArtHistory()
	{
		$history = ArtefactPriceSearch::getArtefactCourseHistoryByID(self::$artefactID, self::$dbID, self::$worldID);
		
		$title = ArtefactPriceSearch::getArtefactCourseHistoryTitleByID(self::$artefactID, self::$dbID, self::$worldID);

		self::$out = ArtefactPriceOut::outHistory($title, $history);
	}

	static private function _editArtPrices()
	{
		foreach(self::$arts as $id => $art)
		{
			if (key_exists('course', $art))
			{
				$tempArr = explode("/", $id);
				$id_artefact = $tempArr[0];
				$id_tradecategory = $tempArr[1];
				$id_article = $tempArr[2];

				settype($id_artefact, 'integer');
				settype($id_article, 'integer');
				settype($id_tradecategory, 'integer');
				assert($id_artefact >= 0);
				assert($id_article > 0);
				assert($id_tradecategory > 0);

				if($art['course'] == self::_getOldCourse($id_artefact, $id_tradecategory, $id_article))
				{
					continue;
				}

				if ($art['course'] == NULL)
				{
					continue;
				}

				ArtefactPriceSearch::updateArtefactCourse($id_artefact, $id_tradecategory, $id_article, (float)$art['course'], self::$dbID, self::$worldID);
			}
			if (key_exists('power', $art))
			{
				$tempArr = explode("/", $id);
				$id_artefact = $tempArr[0];

				settype($id_artefact, 'integer');
				assert($id_artefact >= 0);

				//ArtefactPriceSearch::updateArtefactPower($id_artefact, (float)$art['power'], self::$dbID, self::$worldID);
			}
		}
	}

	static private function _getOldCourse($id_artefact, $id_tradecategory, $id_article)
	{
		assert(is_int($id_artefact) && $id_artefact > 0);
		assert(is_int($id_article) && $id_article > 0);
		assert(is_int($id_tradecategory) && $id_tradecategory > 0);

		if(count(self::$oldData) == 0)
		{
			self::$oldData = ArtefactPriceSearch::getArtefactsByScopeAndSize(self::$scope, self::$size, self::$dbID, self::$worldID);
		}

		foreach(self::$oldData as $k => $course)
		{
			if($course['id_artefact'] == $id_artefact AND $course['id_article'] == $id_article AND $course['id_tradecategory'] == $id_tradecategory)
			{
				return $course['course'];
			}
		}

		return 0;
	}

	static private function _importCSV()
	{
		$csvContent = file(self::$csvFile);
		foreach($csvContent as $k => $str)
		{
			$data = explode(';', $str);
			assert(count($data) >= 2);

			$replace = array('"', "'");
			$name = strtoupper(trim(str_replace($replace, "", $data[0])));
			$course = (float)trim(str_replace($replace, "", $data[1]));

			if(key_exists(3, $data))
			{
				$power = (float)trim(str_replace($replace, "", $data[3]));
			} else {
				$power = 0;
			}

			$id = ArtefactPriceSearch::getArtefactIDByName($name, self::$dbID);
			assert($id > 0);

			if($course > 0)
			{
				ArtefactPriceSearch::updateArtefactCourse($id, $course, self::$dbID, self::$worldID);
			}

			if($power > 0)
			{
				ArtefactPriceSearch::updateArtefactPower($id, $power, self::$dbID, self::$worldID);
			}
		}

		self::_showArtSearchMenu();
	}
}

?>