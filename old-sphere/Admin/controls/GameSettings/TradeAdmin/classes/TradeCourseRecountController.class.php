<?php

class TradeCourseRecountController
{

	static private $out = '';

	static private $action = 'index';

	static private $worldData = array();

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

		if(key_exists('WORLD_S_NAME', $data))
		{
			foreach ($data['WORLD_S_NAME'] as $key => $woldData)
			{
				self::$worldData[] = $woldData;
				assert(!empty(self::$worldData));

			}
		}
	}

	static private function _execAction()
	{
		switch (self::$action) {
			case 'index':
				self::_showIndex();
				break;

			case 'recount':
				self::_recountCourse();
				break;
			default:
				break;
		}
	}

	static private function _showIndex()
	{
		$worlds = TradeCourseRecountSearch::GetAllWorldsList();
		TradeCourseRecountOut::OutWorldList($worlds);
	}

	static private function _recountCourse()
	{
		foreach (self::$worldData as $key => $data)
		{
			$arr = explode('_', $data);
			$dbID = (int)$arr[0];
			assert($dbID >= 0);

			$worldID = (int)$arr[1];
			assert($worldID > 0);

			$startDate = TradeCourseRecountSearch::GetLastRecountDate($dbID, $worldID);

			$courseGC = constant('GC_COURSE');

			self::$out = "START RECOUNT TRADE COURSES DBID = $dbID WORLD_ID = $worldID";

			/*TradeCourseRecountSearch::$common->startTrans();
			$tradesData = TradeCourseRecountSearch::GetResourceTrades($dbID, $worldID, $startDate);

			self::_recountCourse($tradesData, 'InsertResourceCourse', 'RESOURCES');

			$tradesData = TradeCourseRecountSearch::GetUnitTrades(self::$worldID, self::$startDate);
			self::_recountCourse($tradesData, 'InsertUnitsCourse', 'UNITS');

			$tradesData = TradeCourseRecountSearch::GetArtefactTrades(self::$worldID, self::$startDate);
			self::_recountCourse($tradesData, 'InsertArtefactCourse', 'ARTEFACTS');

			TradeCourseRecountSearch::InsertRecountDate(self::$worldID);

			TradeCourseRecountSearch::$common->closeTrans('COMMIT');*/

			self::$out .= "RECOUNT COMPLIT";
		}
	}
}

?>